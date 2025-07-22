<?php
/**
 * Endpoint: /breed-stats/<breed>
 *
 * @package RoroCore\API
 */

declare( strict_types = 1 );

namespace RoroCore\API;

use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;

class Endpoint_Breed_Stats {

	private string $table;

	public function __construct( \wpdb $wpdb ) {
		$this->table = $wpdb->prefix . 'roro_breed_growth';
		add_action( 'rest_api_init', [ $this, 'register_route' ] );
	}

	public function register_route(): void {
		register_rest_route(
			'roro/v1',
			'/breed-stats/(?P<breed>[a-z0-9_-]+)',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'stats' ],
				'permission_callback' => '__return_true',
				'args'                => [
					'breed' => [
						'sanitize_callback' => 'sanitize_title',
					],
				],
			]
		);
	}

	public function stats( WP_REST_Request $req ): WP_REST_Response {
		global $wpdb;
		$breed = $req->get_param( 'breed' );

		// Try transient first
		$key   = "roro_stats_{$breed}";
		$cache = get_transient( $key );
		if ( $cache ) {
			return rest_ensure_response( $cache );
		}

		$sql = "
			SELECT month_age, weight_avg, height_avg
			FROM {$this->table}
			WHERE breed_slug = %s
			ORDER BY month_age
		";
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $breed ), ARRAY_A );

		if ( empty( $rows ) ) {
			return new WP_REST_Response( [ 'error' => 'Not found' ], 404 );
		}

		set_transient( $key, $rows, DAY_IN_SECONDS ); // 24h cache :contentReference[oaicite:8]{index=8}
		return rest_ensure_response( $rows );
	}
}
