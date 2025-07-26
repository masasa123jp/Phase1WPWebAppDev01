<?php
/**
 * データダウンロードエンドポイント。
 *
 * 管理者が分析データをCSV形式でダウンロードできるようにします。
 * リクエストでは `dataset` パラメータを指定してエクスポートするレポートを選択できます。
 * 現在の実装では未実装であることを示すプレースホルダー応答を返します。
 *
 * @package RoroCore\Api
 */

namespace RoroCore\Api;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

class Download_Data_Endpoint extends Abstract_Endpoint {
    public const ROUTE = '/analytics/download-data';

    public function __construct() {
        add_action( 'rest_api_init', [ $this, 'register' ] );
    }

    public static function register() : void {
        register_rest_route( 'roro/v1', self::ROUTE, [
            [
                'methods'             => 'GET',
                'callback'            => [ self::class, 'handle' ],
                'permission_callback' => function() {
                    return current_user_can( 'manage_options' );
                },
                'args'                => [
                    'dataset' => [ 'type' => 'string', 'required' => false ],
                ],
            ],
        ] );
    }

    public static function handle( WP_REST_Request $request ) : WP_REST_Response|WP_Error {
        $dataset = $request->get_param( 'dataset' ) ?: 'all';
        return rest_ensure_response( [
            'dataset' => $dataset,
            'message' => __( 'Data export is not implemented yet.', 'roro-core' ),
        ] );
    }
}
