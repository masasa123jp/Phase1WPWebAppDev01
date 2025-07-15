<?php
namespace RoroCore\API;

use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use RoroCore\DB;

class Endpoint_Breed_Stats {

	const ROUTE = '/breed-stats/(?P<breed>[a-zA-Z0-9_-]+)';

	public static function register(): void {
		register_rest_route(
			'roro/v1',
			self::ROUTE,
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ self::class, 'get_stats' ],
				'permission_callback' => '__return_true',
				'args'                => [
					'breed' => [
						'description' => 'Breed slug (e.g. shiba)',
						'required'    => true,
						'type'        => 'string',
					],
				],
			]
		);
	}

	/**
	 * Return monthly weight/height percentile data for a breed.
	 */
	public static function get_stats( WP_REST_Request $req ): WP_REST_Response {
		global $wpdb;
		$breed = sanitize_text_field( $req['breed'] );

		$sql = "
			SELECT month_age, weight_avg, height_avg
			FROM {$wpdb->prefix}roro_breed_growth
			WHERE breed_slug = %s
			ORDER BY month_age
		";
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $breed ), ARRAY_A );

		if ( empty( $rows ) ) {
			return new WP_REST_Response(
				[ 'message' => 'Breed not found' ],
				404
			);
		}

		return rest_ensure_response( $rows );
	}
}
add_action( 'rest_api_init', [ Endpoint_Breed_Stats::class, 'register' ] );
