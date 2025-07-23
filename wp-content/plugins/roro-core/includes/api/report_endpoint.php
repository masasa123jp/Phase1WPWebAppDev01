<?php
/**
 * Module path: wp-content/plugins/roro-core/includes/api/report_endpoint.php
 *
 * 利用者からのフィードバックレポートを保存するエンドポイント。リクエストボディはJSON形式で、
 * message キーに内容を含めます。JSONが不正な場合や message が空の場合はエラーを返します。
 * 保存時は sanitize_text_field() を通し、作成日時を記録します。
 *
 * @package RoroCore\Api
 */

namespace RoroCore\Api;

use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;

class Report_Endpoint {
    /** @var string 保存先テーブル名 */
    private string $table;

    public function __construct( \wpdb $wpdb ) {
        $this->table = $wpdb->prefix . 'roro_report';
        add_action( 'rest_api_init', [ $this, 'register' ] );
    }

    /**
     * ルート登録。
     */
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

    /**
     * 投稿許可チェック。
     *
     * @return bool
     */
    public function can_submit(): bool {
        return is_user_logged_in() && wp_verify_nonce( $_REQUEST['nonce'] ?? '', 'wp_rest' );
    }

    /**
     * フィードバックを保存。
     *
     * @param WP_REST_Request $req
     * @return WP_REST_Response
     */
    public function save( WP_REST_Request $req ): WP_REST_Response {
        global $wpdb;

        try {
            $data = json_decode( $req->get_body(), true, 512, JSON_THROW_ON_ERROR );
        } catch ( \JsonException $e ) {
            return new WP_REST_Response( [ 'error' => 'invalid_json' ], 400 );
        }

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
