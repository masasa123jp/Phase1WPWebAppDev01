<?php
/**
 * 写真アップロード API
 *
 * @package RoroCore
 */

namespace RoroCore\Api;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

class Endpoint_Photo_Upload extends Abstract_Endpoint {

	const ROUTE = '/photo';

	public static function register(): void {
		register_rest_route(
			'roro/v1',
			self::ROUTE,
			[
				[
					'methods'             => 'POST',
					'callback'            => [ self::class, 'handle' ],
					'permission_callback' => [ self::class, 'permission_callback' ],
				],
			]
		);
	}

	public static function handle( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		if ( empty( $_FILES['file'] ) ) {
			return new WP_Error( 'no_file', __( 'No file uploaded.', 'roro-core' ), [ 'status' => 400 ] );
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		$file = wp_handle_upload( $_FILES['file'], [ 'test_form' => false ] );

		if ( isset( $file['error'] ) ) {
			return new WP_Error( 'upload_error', $file['error'], [ 'status' => 500 ] );
		}

		$attachment_id = wp_insert_attachment(
			[
				'post_mime_type' => $file['type'],
				'post_title'     => sanitize_file_name( $file['file'] ),
				'post_status'    => 'inherit',
			],
			$file['file']
		);

		return new WP_REST_Response( [ 'attachment_id' => $attachment_id ], 201 );
	}
}

add_action( 'rest_api_init', [ Endpoint_Photo_Upload::class, 'register' ] );
