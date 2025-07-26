<?php
/**
 * 広告承認エンドポイント。
 *
 * 管理者がスポンサー広告を承認または拒否できるようにします。
 * 広告 ID とステータス（approved|rejected）を含む POST リクエストが
 * 広告レコードを更新します。manage_options 権限を持つユーザーのみが
 * このエンドポイントを呼び出すことができます。現状の実装では送信された値をそのまま返します。
 *
 * @package RoroCore\Api
 */

namespace RoroCore\Api;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

class Ad_Approval_Endpoint extends Abstract_Endpoint {
    public const ROUTE = '/ads/approval';

    public function __construct() {
        add_action( 'rest_api_init', [ $this, 'register' ] );
    }

    public static function register() : void {
        register_rest_route( 'roro/v1', self::ROUTE, [
            [
                'methods'             => 'POST',
                'callback'            => [ self::class, 'handle' ],
                'permission_callback' => function() {
                    return current_user_can( 'manage_options' );
                },
                'args'                => [
                    'ad_id' => [ 'type' => 'integer', 'required' => true ],
                    'status' => [ 'type' => 'string',  'required' => true, 'enum' => [ 'approved', 'rejected' ] ],
                ],
            ],
        ] );
    }

    public static function handle( WP_REST_Request $request ) : WP_REST_Response|WP_Error {
        $ad_id = (int) $request->get_param( 'ad_id' );
        $status = $request->get_param( 'status' );
        // TODO: データベース内の広告ステータスを更新してください。
        return rest_ensure_response( [ 'ad_id' => $ad_id, 'status' => $status ] );
    }
}
