<?php
/**
 * 広告アクセス分析エンドポイント。
 *
 * 広告のインプレッション数やクリック数に関する簡易統計を返します。
 * スポンサーが広告効果を評価するためのものです。管理者のみがアクセスできます。
 * 現在のデータは静的です。
 *
 * @package RoroCore\Api
 */

namespace RoroCore\Api;

use WP_REST_Request;
use WP_REST_Response;

class Ad_Access_Analysis_Endpoint extends Abstract_Endpoint {
    public const ROUTE = '/analytics/ad-access';

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
        $stats = [
            [ 'sponsor' => 'Pet Food Co.', 'impressions' => 5000, 'clicks' => 250 ],
            [ 'sponsor' => 'Vet Clinic',   'impressions' => 3000, 'clicks' => 120 ],
        ];
        return rest_ensure_response( $stats );
    }
}
