<?php
/**
 * 犬種一覧エンドポイント。
 *
 * 犬種のリストを返すシンプルな読み取り専用 API を提供します。
 * このエンドポイントはレポートフローで犬種選択画面にデータを供給するために使用されます。
 * 実装では犬種のリストはデータベーステーブルや外部サービスから取得しますが、
 * 現時点では一般的な犬種の配列をハードコードで返します。エンドポイントは公開されており、
 * 認証は不要です。
 *
 * @package RoroCore\Api
 */

namespace RoroCore\Api;

use WP_REST_Request;
use WP_REST_Response;

class Breed_List_Endpoint extends Abstract_Endpoint {
    /**
     * REST route.
     */
    public const ROUTE = '/breeds';

    public function __construct() {
        add_action( 'rest_api_init', [ $this, 'register' ] );
    }

    /**
     * ルートを登録します。エンドポイントは GET リクエストを受け付け、
     * 公開アクセス可能です。常に true を返すよう permission callback を上書きします。
     */
    public static function register() : void {
        register_rest_route( 'roro/v1', self::ROUTE, [
            [
                'methods'             => 'GET',
                'callback'            => [ self::class, 'handle' ],
                'permission_callback' => '__return_true',
            ],
        ] );
    }

    /**
     * リクエストを処理します。犬種オブジェクトの静的な配列を返します。
     * 各犬種は ID と名前を持ちます。将来的にはサイズや性格などのメタデータを追加できます。
     *
     * @param WP_REST_Request $request 受信したリクエスト（未使用）。
     *
     * @return WP_REST_Response 犬種リストを含むレスポンス。
     */
    public static function handle( WP_REST_Request $request ) : WP_REST_Response {
        $breeds = [
            [ 'id' => 1, 'name' => __( 'Shiba Inu', 'roro-core' ) ],
            [ 'id' => 2, 'name' => __( 'Toy Poodle', 'roro-core' ) ],
            [ 'id' => 3, 'name' => __( 'Golden Retriever', 'roro-core' ) ],
            [ 'id' => 4, 'name' => __( 'French Bulldog', 'roro-core' ) ],
            [ 'id' => 5, 'name' => __( 'Chihuahua', 'roro-core' ) ],
        ];
        return rest_ensure_response( $breeds );
    }
}
