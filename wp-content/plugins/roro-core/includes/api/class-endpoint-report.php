<?php
/**
 * Save customer feedback reports.
 *
 * Route: /roro/v1/report
 *
 * @package RoroCore\API
 */

declare( strict_types = 1 );

namespace RoroCore\API;

use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;

class Endpoint_Report {

	private string $table;

	public function __construct( \wpdb $wpdb ) {
		$this->table = $wpdb->prefix . 'roro_report';
		add_action( 'rest_api_init', [ $this, 'register' ] );
	}

	public function register(): void {
		register_rest_route(
			'roro/v1',
			'/report',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'save' ],
				'permission_callback' => [ $this, 'can_submit' ],
			]
		);
	}

	/** Only logged‑in users with valid wp_rest nonce can submit. */
	public function can_submit(): bool {
		return is_user_logged_in() && wp_verify_nonce( $_REQUEST['nonce'] ?? '', 'wp_rest' ); // :contentReference[oaicite:5]{index=5}
	}

	public function save( WP_REST_Request $req ): WP_REST_Response {
		global $wpdb;

		$data = json_decode( $req->get_body(), true, 512, JSON_THROW_ON_ERROR );
		if ( empty( $data['message'] ) ) {
			return new WP_REST_Response( [ 'error' => 'empty_message' ], 400 );
		}

		$wpdb->insert(
			$this->table,
			[
				'user_id'    => get_current_user_id(),
				'message'    => sanitize_text_field( $data['message'] ),
				'created_at' => current_time( 'mysql' ),
			],
			[ '%d', '%s', '%s' ]
		);

		return rest_ensure_response( [ 'success' => true ] );
	}
}
