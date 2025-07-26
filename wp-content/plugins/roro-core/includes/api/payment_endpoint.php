<?php
/**
 * 決済エンドポイント。
 *
 * スポンサー請求に関する基本的な決済管理機能を提供します。
 * GET リクエストは未払いの決済一覧を返し、POST リクエストは決済を記録します。
 * 管理者のみがこのエンドポイントにアクセスできます。初期実装ではデータは静的で
 * プレースホルダーとして機能します。
 *
 * @package RoroCore\Api
 */

namespace RoroCore\Api;

use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

class Payment_Endpoint extends Abstract_Endpoint {
    public const ROUTE = '/payments';

    public function __construct() {
        add_action( 'rest_api_init', [ $this, 'register' ] );
    }

    public static function register() : void {
        register_rest_route( 'roro/v1', self::ROUTE, [
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [ self::class, 'get_payments' ],
                'permission_callback' => function() {
                    return current_user_can( 'manage_options' );
                },
            ],
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [ self::class, 'record_payment' ],
                'permission_callback' => function() {
                    return current_user_can( 'manage_options' );
                },
                'args'                => [
                    'sponsor_id' => [ 'type' => 'integer', 'required' => true ],
                    'amount'     => [ 'type' => 'number',  'required' => true ],
                ],
            ],
        ] );
    }

    public static function get_payments( WP_REST_Request $request ) : WP_REST_Response {
        $payments = [
            [ 'id' => 1, 'sponsor_id' => 1, 'amount' => 10000, 'status' => 'unpaid' ],
            [ 'id' => 2, 'sponsor_id' => 2, 'amount' => 5000,  'status' => 'paid' ],
        ];
        return rest_ensure_response( $payments );
    }

    public static function record_payment( WP_REST_Request $request ) : WP_REST_Response {
        $sponsor_id = (int) $request->get_param( 'sponsor_id' );
        $amount     = (float) $request->get_param( 'amount' );
        // TODO: 支払いレコードをデータベースに挿入してください。
        return rest_ensure_response( [ 'id' => rand( 100, 999 ), 'sponsor_id' => $sponsor_id, 'amount' => $amount, 'status' => 'paid' ] );
    }
}
