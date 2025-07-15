<?php
declare( strict_types=1 );

namespace RoroCore\Auth;

use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;

class Auth_Controller {

	private string $table;

	public function __construct( \wpdb $wpdb ) {
		$this->table = $wpdb->prefix . 'roro_line_users';
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	public function register_routes(): void {
		register_rest_route(
			'roro/v1',
			'/line-login',
			[
				'methods'  => WP_REST_Server::CREATABLE,
				'callback' => [ $this, 'exchange_token' ],
				'permission_callback' => '__return_true',
			]
		);
	}

	/**
	 * LIFF から送られる ID token を検証し WordPress JWT を発行
	 */
	public function exchange_token( WP_REST_Request $req ): WP_REST_Response {
		$id_token = sanitize_text_field( $req->get_param( 'idToken' ) );
		if ( empty( $id_token ) ) {
			return new WP_REST_Response( [ 'error' => 'ID token required' ], 400 );
		}

		// Verify via LINE Verify API
		$response = wp_remote_post(
			'https://api.line.me/oauth2/v2.1/verify',
			[
				'body' => [
					'id_token'   => $id_token,
					'client_id'  => get_option( 'roro_core_general' )['liff_id'] ?? '',
				],
			]
		);

		if ( is_wp_error( $response ) ) {
			return new WP_REST_Response( [ 'error' => 'Verification failed' ], 500 );
		}

		$jwt = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( empty( $jwt['sub'] ) ) {
			return new WP_REST_Response( [ 'error' => 'Invalid token' ], 400 );
		}

		// Map LINE user → WP User ID
		$user = get_user_by( 'login', 'line_' . $jwt['sub'] );
		if ( ! $user ) {
			$user_id = wp_insert_user(
				[
					'user_login' => 'line_' . $jwt['sub'],
					'user_pass'  => wp_generate_password(),
					'display_name' => $jwt['name'] ?? 'LINE User',
				]
			);
			$user = get_user_by( 'id', $user_id );
		}

		// Issue WP nonce as pseudo‑session token
		$token = wp_create_nonce( 'wp_rest' );

		return rest_ensure_response(
			[
				'wp_user' => $user->ID,
				'token'   => $token,
			]
		);
	}
}
