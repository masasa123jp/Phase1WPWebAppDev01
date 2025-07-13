<?php
/**
 * Universal social-login controller
 *   • Firebase Auth: Google / Twitter(X) / Facebook / Microsoft / Yahoo
 *   • LINE Login: verify → Firebase customToken
 *
 * @package RoroCore\Auth
 */

namespace RoroCore\Auth;
defined('ABSPATH') || exit;

use WP_REST_Controller;
use WP_REST_Request;
use wpdb;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Auth as FBAuth;

final class Auth_Controller extends WP_REST_Controller {

	private wpdb $db;
	private FBAuth $auth;

	public function __construct( wpdb $wpdb ) {
		$this->db = $wpdb;
		$this->namespace = 'roro/v1';
		$this->rest_base = 'auth';
		$this->auth = ( new Factory )
			->withServiceAccount( __DIR__ . '/service-account.json' ) // ← Firebase 管理画面で取得
			->createAuth();
	}

	/** ルート登録 */
	public function register_routes() : void {
		register_rest_route( $this->namespace, "/{$this->rest_base}/firebase", [
			'methods'  => 'POST',
			'callback' => [ $this, 'firebase_login' ],
			'permission_callback' => '__return_true',
			'args' => [ 'idToken' => [ 'required' => true ] ],
		] );

		register_rest_route( $this->namespace, "/{$this->rest_base}/line", [
			'methods'  => 'POST',
			'callback' => [ $this, 'line_login' ],
			'permission_callback' => '__return_true',
			'args' => [ 'accessToken' => [ 'required' => true ] ],
		] );
	}

	/* ===== Firebase (Google/X/Facebook/MS/Yahoo) ===== */
	public function firebase_login( WP_REST_Request $req ) {
		try {
			$token  = $req['idToken'];
			$claims = $this->auth->verifyIdToken( $token )->claims();
			return $this->finalize(
				$claims->get( 'sub' ),
				$claims->get( 'firebase' )['sign_in_provider'],
				$claims->get( 'email' ),
				$claims->get( 'name', '' )
			);
		} catch ( \Throwable $e ) {
			return new \WP_Error( 'auth_fail', $e->getMessage(), [ 'status' => 401 ] );
		}
	}

	/* ===== LINE (verify → custom token) ===== */
	public function line_login( WP_REST_Request $req ) {
		$token = $req['accessToken'];

		// 1. verify
		$verify = json_decode( wp_remote_retrieve_body(
			wp_remote_get( "https://api.line.me/oauth2/v2.1/verify?access_token={$token}" )
		), true );
		if ( empty( $verify['client_id'] ) ) {
			return new \WP_Error( 'line_verify', 'LINE verify failed', [ 'status' => 401 ] );
		}

		// 2. profile
		$prof = json_decode( wp_remote_retrieve_body(
			wp_remote_get(
				'https://api.line.me/v2/profile',
				[ 'headers' => [ 'Authorization' => "Bearer {$token}" ] ]
			)
		), true );

		$uid   = 'line:' . $prof['userId'];
		$name  = $prof['displayName'];
		$email = $uid . '@line.local'; // LINE は email を返さない

		return $this->finalize( $uid, 'line', $email, $name );
	}

	/* ===== 共通処理 (ID マッピング・WP ログイン) ===== */
	private function finalize( string $uid, string $idp, string $email, string $name ) {
		$p   = $this->db->prefix;
		$row = $this->db->get_row(
			$this->db->prepare( "SELECT customer_id,wp_user_id FROM {$p}roro_identity WHERE uid=%s", $uid ),
			ARRAY_A
		);

		if ( ! $row ) {
			$wpUid = ( $user = get_user_by( 'email', $email ) ) ? $user->ID
				: wp_create_user( $email, wp_generate_password(), $email );

			if ( empty( $user ) ) {
				wp_update_user( [ 'ID' => $wpUid, 'display_name' => $name ] );
			}

			$this->db->insert(
				"{$p}roro_customer",
				[ 'name' => $name, 'email' => $email, 'breed_id' => 1 ],
				[ '%s', '%s', '%d' ]
			);
			$custId = (int) $this->db->insert_id;

			$this->db->insert(
				"{$p}roro_identity",
				[ 'uid' => $uid, 'customer_id' => $custId, 'wp_user_id' => $wpUid, 'idp' => $idp ],
				[ '%s', '%d', '%d', '%s' ]
			);
		} else {
			$custId = (int) $row['customer_id'];
			$wpUid  = (int) $row['wp_user_id'];
		}

		wp_set_current_user( $wpUid );
		wp_set_auth_cookie( $wpUid, true ); // remember me

		return rest_ensure_response( [
			'ok'          => true,
			'customer_id' => $custId,
			'wp_user_id'  => $wpUid,
			'idp'         => $idp,
		] );
	}
}
