<?php
/**
 * 課題一覧エンドポイント。
 *
 * 吠え癖や食事管理など、飼い主が選択できる健康・行動課題の一覧を返す。
 * Phase 1.6 では roro_issue マスタを新設し、多言語対応や優先度を含めて返す実装を予定。
 */

namespace RoroCore\Api;

use WP_REST_Request;
use WP_REST_Response;

class Issues_Endpoint extends Abstract_Endpoint {
    public const ROUTE = '/issues';

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

    /** 課題一覧返却（仮実装） */
    public static function handle( WP_REST_Request $req ) : WP_REST_Response {
        // 今後 roro_issue テーブルから取得する
        $issues = [
            [ 'id' => 1, 'name' => __( '吠え癖', 'roro-core' ), 'description' => __( '無駄吠えが多い', 'roro-core' ) ],
            [ 'id' => 2, 'name' => __( '食事管理', 'roro-core' ), 'description' => __( '食べ過ぎ・偏食', 'roro-core' ) ],
        ];
        return rest_ensure_response( $issues );
    }
}
