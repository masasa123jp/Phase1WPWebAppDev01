<?php
/**
 * 写真アップロード用エンドポイント。
 * ファイルを受け取り、WordPress の添付ファイルを作成します。
 * ファイルサイズと MIME タイプを検証します。成功時には添付 ID を返します。
 * 認証はデフォルトの permission callback により要求されます。
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
     * ファイルアップロードを処理します。デフォルトでは 2MB までのファイルのみ受け付け、
     * MIME タイプは画像形式に制限します。`wp_handle_upload` を使用してアップロードを処理し、
     * `wp_insert_attachment` によって添付ファイルレコードを作成します。
     *
     * @param WP_REST_Request $request 受信したリクエスト。
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
        // 添付ファイルポストを作成
        $attachment_id = wp_insert_attachment( [
            'post_mime_type' => $uploaded['type'],
            'post_title'     => sanitize_file_name( $uploaded['file'] ),
            'post_content'   => '',
            'post_status'    => 'inherit',
        ], $uploaded['file'] );
        // メタデータを生成して添付を更新
        $attach_data = wp_generate_attachment_metadata( $attachment_id, $uploaded['file'] );
        wp_update_attachment_metadata( $attachment_id, $attach_data );
        return new WP_REST_Response( [ 'attachment_id' => (int) $attachment_id ], 201 );
    }
}
