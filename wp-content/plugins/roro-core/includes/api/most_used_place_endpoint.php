<?php
/**
 * 最も利用された場所エンドポイント。
 *
 * 過去30日間で最も利用された施設や場所に関する分析データを返します。
 * このエンドポイントは管理者のみアクセス可能です。返却データは現在ダミーです。
 *
 * @package RoroCore\Api
 */

namespace RoroCore\Api;

use WP_REST_Request;
use WP_REST_Response;

class Most_Used_Place_Endpoint extends Abstract_Endpoint {
    public const ROUTE = '/analytics/most-used-places';

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
        $data = [
            [ 'name' => 'Happy Paws Clinic', 'visits' => 123 ],
            [ 'name' => 'WanWan Groomers',   'visits' => 85 ],
        ];
        return rest_ensure_response( $data );
    }
}
