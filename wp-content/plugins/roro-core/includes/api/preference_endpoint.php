<?php
/**
 * CRUD for notification preferences.
 *
 * Route: /roro/v1/preference
 *
 * @package RoroCore\API
 */

declare( strict_types = 1 );

namespace RoroCore\API;

use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;

class Endpoint_Preference {

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
				'permission_callback' => function () {
					return is_user_logged_in() && wp_verify_nonce( $_REQUEST['nonce'] ?? '', 'wp_rest' );
				},
			]
		);
	}

	public function handle( WP_REST_Request $req ): WP_REST_Response {
		$user_id = get_current_user_id();

		if ( 'GET' === $req->get_method() ) {
			return rest_ensure_response( get_user_meta( $user_id, self::META_KEY, true ) ?: [] );
		}

		$body = $req->get_json_params();
		update_user_meta( $user_id, self::META_KEY, wp_parse_args( $body, [
			'line'  => false,
			'email' => false,
			'fcm'   => false,
		] ) );

		return rest_ensure_response( [ 'success' => true ] );
	}
}
