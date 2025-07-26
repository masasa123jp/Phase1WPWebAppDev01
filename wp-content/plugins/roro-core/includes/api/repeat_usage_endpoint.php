<?php
/**
 * リピート利用率エンドポイント。
 *
 * 指定期間におけるリピートユーザーの割合を計算します。現在の実装ではダミー値を返します。
 * このエンドポイントは管理者のみアクセス可能です。
 *
 * @package RoroCore\Api
 */

namespace RoroCore\Api;

use WP_REST_Request;
use WP_REST_Response;

class Repeat_Usage_Endpoint extends Abstract_Endpoint {
    public const ROUTE = '/analytics/repeat-usage';

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
            ],
        ] );
    }

    public static function handle( WP_REST_Request $request ) : WP_REST_Response {
        $data = [ 'repeat_percentage' => 42.5 ];
        return rest_ensure_response( $data );
    }
}
