<?php
/**
 * Module path: wp-content/plugins/roro-core/includes/api/preference_endpoint.php
 *
 * 通知設定を管理するエンドポイント。ログイン中のユーザーのみがアクセス可能で、
 * wp_rest 用の nonce が必要です。GET で現在の設定を返し、PUT/POST で設定を更新します。
 *
 * @package RoroCore\Api
 */

namespace RoroCore\Api;

use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;

class Preference_Endpoint {
    /** @var string ユーザーメタキー */
    private const META_KEY = 'roro_notification_pref';

    public function __construct() {
        add_action( 'rest_api_init', [ $this, 'register_routes' ] );
    }

    public function register_routes(): void {
        register_rest_route(
            'roro/v1',
            '/preference',
            [
                'methods'             => [ WP_REST_Server::READABLE, WP_REST_Server::EDITABLE ],
                'callback'            => [ $this, 'handle' ],
                // nonceとログイン状態をチェック
                'permission_callback' => function () {
                    return is_user_logged_in() && wp_verify_nonce( $_REQUEST['nonce'] ?? '', 'wp_rest' );
                },
            ]
        );
    }

    /**
     * 設定の取得または更新。
     *
     * @param WP_REST_Request $req
     * @return WP_REST_Response
     */
    public function handle( WP_REST_Request $req ): WP_REST_Response {
        $user_id = get_current_user_id();

        if ( 'GET' === $req->get_method() ) {
            return rest_ensure_response( get_user_meta( $user_id, self::META_KEY, true ) ?: [] );
        }

        // 更新処理
        $body = $req->get_json_params();
        update_user_meta( $user_id, self::META_KEY, wp_parse_args( $body, [
            'line'  => false,
            'email' => false,
            'fcm'   => false,
        ] ) );

        return rest_ensure_response( [ 'success' => true ] );
    }
}
