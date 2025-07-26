<?php
/**
 * スポンサー詳細エンドポイント。
 *
 * 個別のスポンサー情報の取得と更新をサポートします。`id` パラメータを指定した GET
 * リクエストはスポンサーの詳細を返します。同じルートへの POST リクエストは
 * スポンサー情報を更新します。更新操作は manage_options 権限を持つユーザーのみが
 * 実行できます。ここで返されるデータは現在静的で、デモ目的です。
 *
 * @package RoroCore\Api
 */

namespace RoroCore\Api;

use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

class Sponsor_Detail_Endpoint extends Abstract_Endpoint {
    public const ROUTE = '/sponsors/(?P<id>\d+)';

    public function __construct() {
        add_action( 'rest_api_init', [ $this, 'register' ] );
    }

    public static function register() : void {
        register_rest_route( 'roro/v1', self::ROUTE, [
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [ self::class, 'get_sponsor' ],
                'permission_callback' => '__return_true',
                'args'                => [ 'id' => [ 'type' => 'integer', 'required' => true ] ],
            ],
            [
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => [ self::class, 'update_sponsor' ],
                'permission_callback' => function() {
                    return current_user_can( 'manage_options' );
                },
                'args'                => [
                    'id'   => [ 'type' => 'integer', 'required' => true ],
                    'name' => [ 'type' => 'string',  'required' => false ],
                    'image'=> [ 'type' => 'string',  'required' => false ],
                ],
            ],
        ] );
    }

    public static function get_sponsor( WP_REST_Request $request ) : WP_REST_Response|WP_Error {
        $id = (int) $request->get_param( 'id' );
        // デモ用にハードコードしたスポンサー。実際の利用では DB から取得します。
        $data = [
            1 => [ 'id' => 1, 'name' => 'Pet Food Co.', 'image' => RORO_CORE_URL . 'assets/ads/pet-food.jpg', 'status' => 'active' ],
            2 => [ 'id' => 2, 'name' => 'Vet Clinic',   'image' => RORO_CORE_URL . 'assets/ads/vet-clinic.jpg', 'status' => 'pending' ],
        ];
        if ( ! isset( $data[ $id ] ) ) {
            return new WP_Error( 'not_found', __( 'Sponsor not found.', 'roro-core' ), [ 'status' => 404 ] );
        }
        return rest_ensure_response( $data[ $id ] );
    }

    public static function update_sponsor( WP_REST_Request $request ) : WP_REST_Response|WP_Error {
        // 実装ではスポンサーをデータベースに更新します。
        // ここでは送信されたデータをそのまま返して確認とします。
        $id    = (int) $request->get_param( 'id' );
        $name  = $request->get_param( 'name' );
        $image = $request->get_param( 'image' );
        return rest_ensure_response( [ 'id' => $id, 'name' => $name, 'image' => $image ] );
    }
}
