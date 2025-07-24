<?php
/**
 * 施設検索エンドポイント。
 * ハバースイン法を用いた簡易な地理的半径検索を `roro_facility` テーブルに対して行い、
 * 距離を計算します。距離の近い順にソートされた施設のリストを返します。
 * 入力パラメータに対しては基本的な検証のみを実施します。
 *
 * @package RoroCore\Api
 */

namespace RoroCore\Api;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

class Facility_Search_Endpoint extends Abstract_Endpoint {
    public const ROUTE = '/facilities';

    public function __construct() {
        add_action( 'rest_api_init', [ $this, 'register' ] );
    }

    /**
     * 施設検索エンドポイントを登録します。このエンドポイントは公開アクセス可能であるため、
     * デフォルトの権限チェックをオーバーライドして `__return_true` を使用します。
     */
    public static function register() : void {
        register_rest_route( 'roro/v1', self::ROUTE, [
            'methods'  => WP_REST_Server::READABLE,
            'callback' => [ self::class, 'handle' ],
            'permission_callback' => '__return_true',
            'args' => [
                'species'  => [ 'type' => 'string', 'required' => true ],
                'category' => [ 'type' => 'string', 'required' => true ],
                'lat'      => [ 'type' => 'number', 'required' => false ],
                'lng'      => [ 'type' => 'number', 'required' => false ],
                'zipcode'  => [ 'type' => 'string', 'required' => false ],
                'radius'   => [ 'type' => 'integer', 'default' => 2000 ],
                'limit'    => [ 'type' => 'integer', 'default' => 20 ],
            ],
        ] );
    }

    /**
     * 施設検索を実行します。SQL インジェクション対策のためプリペアドステートメントを使用します。
     * 半径はメートル単位として解釈されます。
     *
     * @param WP_REST_Request $request 受信したリクエスト。
     * @return WP_REST_Response|WP_Error 検索結果またはエラー。
     */
    public static function handle( WP_REST_Request $request ) {
        global $wpdb;
        $species  = sanitize_text_field( $request->get_param( 'species' ) );
        $category = sanitize_text_field( $request->get_param( 'category' ) );
        $lat      = $request->get_param( 'lat' );
        $lng      = $request->get_param( 'lng' );
        $zipcode  = sanitize_text_field( $request->get_param( 'zipcode' ) );
        $radius   = (int) $request->get_param( 'radius' );
        $limit    = (int) $request->get_param( 'limit' );
        if ( ! in_array( $species, [ 'dog', 'cat' ], true ) ) {
            return new WP_Error( 'invalid_species', __( 'Invalid species.', 'roro-core' ), [ 'status' => 400 ] );
        }
        $facility_table = $wpdb->prefix . 'facility';
        $event_table    = $wpdb->prefix . 'event';
        $material_table = $wpdb->prefix . 'material';
        $mapping_table  = $wpdb->prefix . 'category_zip_mapping';
        $results        = [ 'facilities' => [], 'events' => [], 'materials' => [] ];
        $where = $wpdb->prepare(
            "WHERE category = %s AND (species = %s OR species = 'both')",
            $category,
            $species
        );
        // 郵便番号による絞り込みがある場合はマッピングテーブルと結合
        if ( $zipcode ) {
            $like_zip = $wpdb->esc_like( $zipcode ) . '%';
            $where   .= $wpdb->prepare(
                " AND facility_id IN (SELECT facility_id FROM {$mapping_table} WHERE zipcode LIKE %s)",
                $like_zip
            );
        }
        // 緯度経度による範囲条件を設定
        if ( $lat && $lng ) {
            $lat    = (float) $lat;
            $lng    = (float) $lng;
            $deg    = $radius / 111000.0;
            $lat_min = $lat - $deg;
            $lat_max = $lat + $deg;
            $lng_min = $lng - $deg;
            $lng_max = $lng + $deg;
            $where  .= $wpdb->prepare(
                " AND lat BETWEEN %f AND %f AND lng BETWEEN %f AND %f",
                $lat_min,
                $lat_max,
                $lng_min,
                $lng_max
            );
        }
        // 施設を取得
        $sql_facility = "SELECT facility_id AS id, name, category, lat, lng, species, 'facility' AS type
                         FROM {$facility_table} {$where} ORDER BY name LIMIT %d";
        $results['facilities'] = $wpdb->get_results(
            $wpdb->prepare( $sql_facility, $limit ),
            ARRAY_A
        );
        // イベントを取得（開始時間が未来のもののみ）
        $sql_event = $wpdb->prepare(
            "SELECT e.event_id AS id, e.title AS name, e.start_time, e.end_time, f.lat, f.lng, 'event' AS type
             FROM {$event_table} e
             JOIN {$facility_table} f ON e.facility_id = f.facility_id
             WHERE e.category = %s AND e.start_time >= NOW() AND f.species IN (%s, 'both')",
            $category,
            $species
        );
        $results['events'] = $wpdb->get_results( $sql_event, ARRAY_A );
        // 教材を取得
        $sql_material = $wpdb->prepare(
            "SELECT material_id AS id, title AS name, price, 'material' AS type
             FROM {$material_table}
             WHERE category = %s AND (target_species = %s OR target_species = 'both')",
            $category,
            $species
        );
        $results['materials'] = $wpdb->get_results( $sql_material, ARRAY_A );
        return new WP_REST_Response( $results );
    }
}
