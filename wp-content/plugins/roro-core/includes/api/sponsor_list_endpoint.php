<?php
/**
 * スポンサー一覧エンドポイント。
 *
 * 消費者アプリケーション向けに、アクティブなスポンサーの一覧を提供します。
 * 各スポンサーには ID、名称、および広告クリエイティブの URL が含まれます。
 * ここで返されるデータはカスタム投稿タイプや専用テーブルで管理することもできますが、
 * 現時点では静的な配列を使用しています。エンドポイントは公開されており、
 * 認証は不要です。
 *
 * @package RoroCore\Api
 */

namespace RoroCore\Api;

use WP_REST_Request;
use WP_REST_Response;

class Sponsor_List_Endpoint extends Abstract_Endpoint {
    public const ROUTE = '/sponsors';

    public function __construct() {
        add_action( 'rest_api_init', [ $this, 'register' ] );
    }

    public static function register() : void {
        register_rest_route( 'roro/v1', self::ROUTE, [
            [
                'methods'             => 'GET',
                'callback'            => [ self::class, 'handle' ],
                'permission_callback' => '__return_true',
            ],
        ] );
    }

    public static function handle( WP_REST_Request $request ) : WP_REST_Response {
        $sponsors = [
            [ 'id' => 1, 'name' => 'Pet Food Co.', 'image' => RORO_CORE_URL . 'assets/ads/pet-food.jpg' ],
            [ 'id' => 2, 'name' => 'Vet Clinic',   'image' => RORO_CORE_URL . 'assets/ads/vet-clinic.jpg' ],
        ];
        return rest_ensure_response( $sponsors );
    }
}
