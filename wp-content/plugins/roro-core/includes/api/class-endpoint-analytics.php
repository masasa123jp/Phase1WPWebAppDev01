<?php
/**
 * Endpoint: /analytics – aggregated KPI.
 *
 * @package RoroCore\API
 */

declare( strict_types = 1 );

namespace RoroCore\API;

use WP_REST_Server;
use WP_REST_Response;
use WP_REST_Request;

class Endpoint_Analytics {

	private string $gacha_log;

	public function __construct( \wpdb $wpdb ) {
		$this->gacha_log = $wpdb->prefix . 'roro_gacha_log';
		add_action( 'rest_api_init', [ $this, 'register' ] );
	}

	public function register(): void {
		register_rest_route(
			'roro/v1',
			'/analytics',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'stats' ],
				'permission_callback' => '__return_true',
			]
		);
	}

	public function stats( WP_REST_Request $req ): WP_REST_Response {
		global $wpdb;

		$sql = "
			SELECT
				SUM( created_at >= DATE( NOW() ) )          AS today_spins,
				COUNT( DISTINCT DATE( created_at ) )        AS active_days,
				COUNT( DISTINCT ip )                        AS unique_ips_30d
			FROM {$this->gacha_log}
			WHERE created_at >= DATE_SUB( NOW(), INTERVAL 30 DAY )
		";

		$row = $wpdb->get_row( $sql, ARRAY_A );

		return rest_ensure_response(
			[
				'today_spins'   => (int) $row['today_spins'],
				'active_days'   => (int) $row['active_days'],
				'unique_ips_30d'=> (int) $row['unique_ips_30d'],
			]
		);
	}
}
