<?php
/**
 * Photo upload endpoint.  Accepts a file upload and creates a WordPress
 * attachment.  Validates the file size and MIME type.  Returns the
 * attachment ID on success.  Requires authentication via the default
 * permission callback.
 *
 * @package RoroCore\Api
 */

namespace RoroCore\Api;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

class Photo_Upload_Endpoint extends Abstract_Endpoint {
    public const ROUTE = '/photo';

    public function __construct() {
        add_action( 'rest_api_init', [ $this, 'register' ] );
    }

    public static function register() : void {
        register_rest_route( 'roro/v1', self::ROUTE, [
            [
                'methods'             => 'POST',
                'callback'            => [ self::class, 'handle' ],
                'permission_callback' => [ self::class, 'permission_callback' ],
            ],
        ] );
    }

    /**
     * Handle the file upload.  Only accepts files up to 2MB by default and
     * restricts MIME types to image formats.  Uses `wp_handle_upload` to
     * manage the upload and `wp_insert_attachment` to create the
     * attachment record.
     *
     * @param WP_REST_Request $request Incoming request.
     *
     * @return WP_REST_Response|WP_Error
     */
    public static function handle( WP_REST_Request $request ) {
        if ( empty( $_FILES['file'] ) ) {
            return new WP_Error( 'no_file', __( 'No file uploaded.', 'roro-core' ), [ 'status' => 400 ] );
        }
        $file      = $_FILES['file'];
        $max_size  = (int) apply_filters( 'roro_photo_max_size', 2 * 1024 * 1024 );
        $allowed_mimes = [ 'image/jpeg', 'image/png', 'image/gif', 'image/webp' ];
        if ( $file['size'] > $max_size ) {
            return new WP_Error( 'file_too_large', __( 'The uploaded file exceeds the allowed size.', 'roro-core' ), [ 'status' => 400 ] );
        }
        if ( ! in_array( $file['type'], $allowed_mimes, true ) ) {
            return new WP_Error( 'invalid_type', __( 'Invalid file type.', 'roro-core' ), [ 'status' => 400 ] );
        }
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
        $uploaded = wp_handle_upload( $file, [ 'test_form' => false ] );
        if ( isset( $uploaded['error'] ) ) {
            return new WP_Error( 'upload_error', $uploaded['error'], [ 'status' => 500 ] );
        }
        // Create attachment post.
        $attachment_id = wp_insert_attachment( [
            'post_mime_type' => $uploaded['type'],
            'post_title'     => sanitize_file_name( $uploaded['file'] ),
            'post_content'   => '',
            'post_status'    => 'inherit',
        ], $uploaded['file'] );
        // Generate metadata and update attachment.
        $attach_data = wp_generate_attachment_metadata( $attachment_id, $uploaded['file'] );
        wp_update_attachment_metadata( $attachment_id, $attach_data );
        return new WP_REST_Response( [ 'attachment_id' => (int) $attachment_id ], 201 );
    }
}
