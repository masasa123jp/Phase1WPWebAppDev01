<?php
/**
 * Endpoint: /gacha – returns random advice/facility.
 *
 * @package RoroCore\API
 */

declare( strict_types = 1 );

namespace RoroCore\API;

use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;

class Endpoint_Gacha {

	private string $advice_table;
	private string $log_table;

	public function __construct( \wpdb $wpdb ) {
		$this->advice_table = $wpdb->prefix . 'roro_advice';
		$this->log_table    = $wpdb->prefix . 'roro_gacha_log';

		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	/**
	 * REST route registration.
	 */
	public function register_routes(): void {
		register_rest_route(
			'roro/v1',
			'/gacha',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'spin' ],
				'permission_callback' => '__return_true', // 公開ルート :contentReference[oaicite:6]{index=6}
			]
		);
	}

	/**
	 * Gacha spin callback.
	 */
	public function spin( WP_REST_Request $req ): WP_REST_Response {
		global $wpdb;

		$advice = $wpdb->get_row(
			"SELECT id, title, excerpt
			 FROM {$this->advice_table}
			 ORDER BY RAND()
			 LIMIT 1",
			ARRAY_A
		);

		if ( ! $advice ) {
			return new WP_REST_Response( [ 'error' => 'No advice.' ], 404 );
		}

		// log
		$wpdb->insert(
			$this->log_table,
			[
				'advice_id' => (int) $advice['id'],
				'created_at' => current_time( 'mysql' ),
				'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
			],
			[ '%d', '%s', '%s' ]
		); // wpdb→insert は内部でエスケープ処理 :contentReference[oaicite:7]{index=7}

		return rest_ensure_response( $advice );
	}
}
