<?php
/**
 * 自分のプロフィール取得 API
 *
 * @package RoroCore
 */

namespace RoroCore\Api;

use WP_REST_Request;
use WP_REST_Response;

class Endpoint_User_Profile extends Abstract_Endpoint {

	const ROUTE = '/me';

	public static function register(): void {
		register_rest_route(
			'roro/v1',
			self::ROUTE,
			[
				[
					'methods'             => 'GET',
					'callback'            => [ self::class, 'handle' ],
					'permission_callback' => [ self::class, 'permission_callback' ],
				],
			]
		);
	}

	public static function handle( WP_REST_Request $request ): WP_REST_Response {
		$user = wp_get_current_user();

		return rest_ensure_response(
			[
				'id'    => $user->ID,
				'name'  => $user->display_name,
				'email' => $user->user_email,
				'roles' => $user->roles,
			]
		);
	}
}

add_action( 'rest_api_init', [ Endpoint_User_Profile::class, 'register' ] );
