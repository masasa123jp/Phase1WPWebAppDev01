<?php
/**
 * Handle media uploads and associate meta.
 *
 * Route: /roro/v1/photo
 *
 * @package RoroCore\API
 */

declare( strict_types = 1 );

namespace RoroCore\API;

use WP_REST_Server;
use WP_REST_Response;
use WP_REST_Request;

class Endpoint_Photo {

	public function __construct() {
		add_action( 'rest_api_init', [ $this, 'register_route' ] );
	}

	public function register_route(): void {
		register_rest_route(
			'roro/v1',
			'/photo',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'upload' ],
				'permission_callback' => function () {
					return current_user_can( 'upload_files' ) &&
						wp_verify_nonce( $_REQUEST['nonce'] ?? '', 'wp_rest' ); // :contentReference[oaicite:6]{index=6}
				},
			]
		);
	}

	public function upload( WP_REST_Request $req ): WP_REST_Response {
		if ( empty( $_FILES['file'] ) ) {
			return new WP_REST_Response( [ 'error' => 'no_file' ], 400 );
		}

		$file  = $_FILES['file'];
		$types = [ 'image/jpeg', 'image/png', 'image/gif' ];
		if ( ! in_array( $file['type'], $types, true ) ) {
			return new WP_REST_Response( [ 'error' => 'invalid_type' ], 415 );
		}

		$uploaded = wp_handle_upload( $file, [ 'test_form' => false ] ); // :contentReference[oaicite:7]{index=7}

		if ( isset( $uploaded['error'] ) ) {
			return new WP_REST_Response( [ 'error' => $uploaded['error'] ], 500 );
		}

		$attachment_id = wp_insert_attachment(
			[
				'post_mime_type' => $uploaded['type'],
				'post_title'     => sanitize_file_name( $file['name'] ),
				'post_content'   => '',
				'post_status'    => 'inherit',
			],
			$uploaded['file']
		);

		require_once ABSPATH . 'wp-admin/includes/image.php';
		wp_update_attachment_metadata( $attachment_id, wp_generate_attachment_metadata( $attachment_id, $uploaded['file'] ) );

		return rest_ensure_response( [ 'attachment_id' => $attachment_id, 'url' => wp_get_attachment_url( $attachment_id ) ] );
	}
}
