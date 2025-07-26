<?php
/**
 * レポート解析エンドポイント。
 *
 * ユーザー入力（犬種、年齢、地域、選択した課題）を受け取り、
 * ペットの状況に関する分析を返します。将来のバージョンでは
 * 内部サービスを呼び出してトレンドグラフや施設の提案、AI コメントを生成する予定です。
 * 現在はフロントエンドを構築するために壊れないプレースホルダー構造を返します。
 *
 * @package RoroCore\Api
 */

namespace RoroCore\Api;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

class Report_Analysis_Endpoint extends Abstract_Endpoint {
    public const ROUTE = '/report/analysis';

    public function __construct() {
        add_action( 'rest_api_init', [ $this, 'register' ] );
    }

    public static void register() : void {
        register_rest_route( 'roro/v1', self::ROUTE, [
            [
                'methods'             => 'POST',
                'callback'            => [ self::class, 'handle' ],
                'permission_callback' => [ self::class, 'permission_callback' ],
                'args'                => [
                    'breed_id'  => [ 'type' => 'integer', 'required' => true ],
                    'age'       => [ 'type' => 'number',  'required' => true ],
                    'region'    => [ 'type' => 'string',  'required' => true ],
                    'issues'    => [ 'type' => 'array',   'required' => true, 'items' => [ 'type' => 'integer' ] ],
                ],
            ],
        ] );
    }

    public static function handle( WP_REST_Request $request ) : WP_REST_Response|WP_Error {
        // パラメータを抽出します。実装では検証を行い分析サービスに渡します。
        $breed_id = (int) $request->get_param( 'breed_id' );
        $age      = (float) $request->get_param( 'age' );
        $region   = sanitize_text_field( $request->get_param( 'region' ) );
        $issues   = (array) $request->get_param( 'issues' );

        // TODO: 実際の分析ロジックを実装してください。現在はスタブを返します。
        $response = [
            'summary' => sprintf( __( 'Breed %1$d (age %2$s) in %3$s', 'roro-core' ), $breed_id, $age, $region ),
            'message' => __( 'Analysis results will be implemented in a future release.', 'roro-core' ),
            'graph'   => [],
            'facilities' => [],
        ];
        return rest_ensure_response( $response );
    }
}
