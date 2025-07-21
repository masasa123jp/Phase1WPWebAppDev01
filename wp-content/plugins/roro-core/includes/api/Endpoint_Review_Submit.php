<?php
/**
 * レビュー投稿 API
 *
 * @package RoroCore
 */

namespace RoroCore\Api;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

class Endpoint_Review_Submit extends Abstract_Endpoint {

	const ROUTE = '/reviews';

	public static function register(): void {
		register_rest_route(
			'roro/v1',
			self::ROUTE,
			[
				[
					'methods'             => 'POST',
					'callback'            => [ self::class, 'handle' ],
					'permission_callback' => [ self::class, 'permission_callback' ],
					'args'                => [
						'facility_id' => [ 'type' => 'integer', 'required' => true ],
						'rating'      => [ 'type' => 'number',  'required' => true ],
						'comment'     => [ 'type' => 'string',  'required' => false ],
					],
				],
			]
		);
	}

	public static function handle( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		global $wpdb;
		$table = $wpdb->prefix . 'roro_reviews';

		$wpdb->insert(
			$table,
			[
				'user_id'     => get_current_user_id(),
				'facility_id' => $request['facility_id'],
				'rating'      => $request['rating'],
				'comment'     => wp_kses_post( $request['comment'] ),
				'created_at'  => current_time( 'mysql' ),
			],
			[ '%d', '%d', '%f', '%s', '%s' ]
		);

		return rest_ensure_response( [ 'id' => $wpdb->insert_id ] );
	}
}

add_action( 'rest_api_init', [ Endpoint_Review_Submit::class, 'register' ] );
