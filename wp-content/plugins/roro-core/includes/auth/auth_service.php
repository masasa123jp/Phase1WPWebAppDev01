<?php
/**
 * Module path: wp-content/plugins/roro-core/includes/auth/auth_service.php
 *
 * Firebase と LINE の認証を統合する REST サービス。IDトークンやアクセストークンを検証し、
 * WordPress ユーザーとカスタマーを作成または取得してログイン処理を行います。
 * Firebase サービスアカウントのパスはフィルタで指定し、機密情報をコードに含めません。
 *
 * @package RoroCore\Auth
 */

namespace RoroCore\Auth;

use WP_REST_Controller;
use WP_REST_Request;
use wpdb;
use WP_Error;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Auth as FirebaseAuth;

class Auth_Service extends WP_REST_Controller {
    /** @var wpdb */
    private wpdb $db;

    /** @var FirebaseAuth|null */
    private ?FirebaseAuth $auth = null;

    public function __construct() {
        global $wpdb;
        $this->db        = $wpdb;
        $this->namespace = 'roro/v1';
        $this->rest_base = 'auth';

        // Firebase初期化（サービスアカウントJSONパスはフィルタで上書き可能）
        $service_account_path = apply_filters( 'roro_core_service_account_path', RORO_CORE_DIR . 'credentials/service-account.json' );
        if ( file_exists( $service_account_path ) ) {
            try {
                $this->auth = ( new Factory )->withServiceAccount( $service_account_path )->createAuth();
            } catch ( \Throwable $e ) {
                error_log( 'RoRo Core: Firebase initialisation failed – ' . $e->getMessage() );
                $this->auth = null;
            }
        }

        add_action( 'rest_api_init', [ $this, 'register_routes' ] );
    }

    /**
     * ルート登録。
     */
    public function register_routes() : void {
        register_rest_route( $this->namespace, "/{$this->rest_base}/firebase", [
            'methods'             => 'POST',
            'callback'            => [ $this, 'firebase_login' ],
            'permission_callback' => '__return_true',
            'args'                => [
                'idToken' => [
                    'type'        => 'string',
                    'required'    => true,
                    'description' => __( 'The Firebase ID token returned from the client SDK.', 'roro-core' ),
                ],
            ],
        ] );
        register_rest_route( $this->namespace, "/{$this->rest_base}/line", [
            'methods'             => 'POST',
            'callback'            => [ $this, 'line_login' ],
            'permission_callback' => '__return_true',
            'args'                => [
                'accessToken' => [
                    'type'        => 'string',
                    'required'    => true,
                    'description' => __( 'The LINE access token obtained from LIFF login.', 'roro-core' ),
                ],
            ],
        ] );
    }

    /**
     * Firebaseログイン処理。
     *
     * @param WP_REST_Request $req
     * @return array|WP_Error
     */
    public function firebase_login( WP_REST_Request $req ) {
        if ( empty( $this->auth ) ) {
            return new WP_Error( 'auth_disabled', __( 'Firebase authentication is not configured.', 'roro-core' ), [ 'status' => 500 ] );
        }
        $token = $req->get_param( 'idToken' );
        try {
            $verified = $this->auth->verifyIdToken( $token );
            $claims   = $verified->claims();
            $uid      = $claims->get( 'sub' );
            $email    = $claims->get( 'email' );
            $provider = $claims->get( 'firebase' )['sign_in_provider'] ?? 'firebase';
            $name     = $claims->get( 'name', '' );
        } catch ( \Throwable $e ) {
            return new WP_Error( 'auth_fail', $e->getMessage(), [ 'status' => 401 ] );
        }
        return $this->finalize( $uid, $provider, $email, $name );
    }

    /**
     * LINEログイン処理。
     *
     * @param WP_REST_Request $req
     * @return array|WP_Error
     */
    public function line_login( WP_REST_Request $req ) {
        $token = $req->get_param( 'accessToken' );
        // 1. トークン検証
        $verify_response = wp_remote_get( 'https://api.line.me/oauth2/v2.1/verify?access_token=' . urlencode( $token ) );
        $verify_body     = json_decode( wp_remote_retrieve_body( $verify_response ), true );
        if ( empty( $verify_body['client_id'] ) ) {
            return new WP_Error( 'line_verify_failed', __( 'LINE token verification failed.', 'roro-core' ), [ 'status' => 401 ] );
        }

        // 2. プロフィール取得
        $profile_response = wp_remote_get( 'https://api.line.me/v2/profile', [
            'headers' => [ 'Authorization' => 'Bearer ' . $token ],
        ] );
        $profile_body = json_decode( wp_remote_retrieve_body( $profile_response ), true );
        if ( empty( $profile_body['userId'] ) ) {
            return new WP_Error( 'line_profile_failed', __( 'Unable to fetch LINE user profile.', 'roro-core' ), [ 'status' => 500 ] );
        }

        $uid   = 'line:' . $profile_body['userId'];
        $name  = $profile_body['displayName'] ?? __( 'LINE User', 'roro-core' );
        $email = $uid . '@line.local';
        return $this->finalize( $uid, 'line', $email, $name );
    }

    /**
     * UID に基づき WordPress ユーザーとカスタマーを取得／作成し、ログイン状態をセットする。
     *
     * @param string $uid
     * @param string $idp
     * @param string $email
     * @param string $name
     * @return array
     */
    private function finalize( string $uid, string $idp, string $email, string $name ) : array {
        $p   = $this->db->prefix;
        $row = $this->db->get_row( $this->db->prepare( "SELECT customer_id, wp_user_id FROM {$p}roro_identity WHERE uid = %s", $uid ), ARRAY_A );

        if ( ! $row ) {
            // WordPressユーザーの取得／作成
            $user = get_user_by( 'email', $email );
            if ( ! $user ) {
                $user_id = wp_create_user( $email, wp_generate_password(), $email );
                wp_update_user( [ 'ID' => $user_id, 'display_name' => $name ] );
            } else {
                $user_id = (int) $user->ID;
            }

            // カスタマー登録
            $this->db->insert( "{$p}roro_customer", [
                'name'     => $name,
                'email'    => $email,
                'breed_id' => 1,
            ], [ '%s', '%s', '%d' ] );
            $customer_id = (int) $this->db->insert_id;

            // identityリンクを保存
            $this->db->insert( "{$p}roro_identity", [
                'uid'         => $uid,
                'customer_id' => $customer_id,
                'wp_user_id'  => $user_id,
                'idp'         => $idp,
            ], [ '%s', '%d', '%d', '%s' ] );
        } else {
            $customer_id = (int) $row['customer_id'];
            $user_id     = (int) $row['wp_user_id'];
        }

        // ログイン処理
        wp_set_current_user( $user_id );
        wp_set_auth_cookie( $user_id, true );

        return [
            'ok'          => true,
            'customer_id' => $customer_id,
            'wp_user_id'  => $user_id,
            'idp'         => $idp,
        ];
    }
}
