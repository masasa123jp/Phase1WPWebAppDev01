<?php
/**
 * ガチャ API エンドポイント。
 *
 * 犬・猫などの種別やカテゴリに応じて施設・アドバイス・イベント・教材をランダムに返す。
 * 入力された郵便番号を基に候補を絞り込んだ上で乱数で選択し、
 * 抽選結果を gacha_log テーブルに記録する。
 * Phase 1.6 ではスポンサー広告挿入や有料スピンに対応できるよう拡張する。
 */

namespace RoroCore\Api;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

class Gacha_Endpoint extends Abstract_Endpoint {
    public const ROUTE = '/gacha';

    public function __construct() {
        add_action( 'rest_api_init', [ $this, 'register' ] );
    }

    /** エンドポイント登録 */
    public static function register() : void {
        register_rest_route( 'roro/v1', self::ROUTE, [
            'methods'  => WP_REST_Server::CREATABLE,
            'callback' => [ self::class, 'handle' ],
            'permission_callback' => [ self::class, 'permission_callback' ],
            'args' => [
                'species'  => [ 'type' => 'string', 'required' => true ],
                'category' => [ 'type' => 'string', 'required' => true ],
                'zipcode'  => [ 'type' => 'string', 'required' => false ],
            ],
        ] );
    }

    /** 認証済みユーザーのみ実行可能 */
    public static function permission_callback() : bool {
        return is_user_logged_in();
    }

    /**
     * ガチャ処理本体。
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public static function handle( WP_REST_Request $request ) {
        global $wpdb;
        $user_id  = get_current_user_id();
        $species  = sanitize_text_field( $request->get_param( 'species' ) );
        $category = sanitize_text_field( $request->get_param( 'category' ) );
        $zipcode  = sanitize_text_field( $request->get_param( 'zipcode' ) );

        if ( ! in_array( $species, [ 'dog', 'cat' ], true ) ) {
            return new WP_Error( 'invalid_species', __( 'Invalid species.', 'roro-core' ), [ 'status' => 400 ] );
        }
        if ( empty( $category ) ) {
            return new WP_Error( 'invalid_category', __( 'Category is required.', 'roro-core' ), [ 'status' => 400 ] );
        }

        // テーブル名設定
        $mapping_table  = $wpdb->prefix . 'category_zip_mapping';
        $facility_table = $wpdb->prefix . 'facility';
        $advice_table   = $wpdb->prefix . 'onepoint_advice';
        $event_table    = $wpdb->prefix . 'event';
        $material_table = $wpdb->prefix . 'material';

        // 郵便番号から候補となる施設ID・アドバイスコードを取得
        $facilities = [];
        $advices    = [];
        if ( $zipcode ) {
            $like_zip     = $wpdb->esc_like( $zipcode ) . '%';
            $mapping_rows = $wpdb->get_results( $wpdb->prepare(
                "SELECT facility_id, advice_code FROM {$mapping_table} WHERE species = %s AND category = %s AND zipcode LIKE %s",
                $species,
                $category,
                $like_zip
            ) );
            foreach ( $mapping_rows as $row ) {
                if ( $row->facility_id ) {
                    $facilities[] = (int) $row->facility_id;
                }
                if ( $row->advice_code ) {
                    $advices[] = sanitize_text_field( $row->advice_code );
                }
            }
        }

        // 候補リスト生成
        $candidates = [];
        if ( ! empty( $facilities ) ) {
            $placeholders        = implode( ',', array_fill( 0, count( $facilities ), '%d' ) );
            $facility_candidates = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT facility_id AS id, name, 'facility' AS type FROM {$facility_table} WHERE facility_id IN ( {$placeholders} )",
                    $facilities
                ),
                ARRAY_A
            );
            $candidates          = array_merge( $candidates, $facility_candidates );
        }
        if ( ! empty( $advices ) ) {
            $placeholders      = implode( ',', array_fill( 0, count( $advices ), '%s' ) );
            $advice_candidates = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT advice_code AS id, title AS name, 'advice' AS type FROM {$advice_table} WHERE advice_code IN ( {$placeholders} )",
                    $advices
                ),
                ARRAY_A
            );
            $candidates        = array_merge( $candidates, $advice_candidates );
        }
        // イベント・教材候補を追加
        $event_candidates = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT e.event_id AS id, e.title AS name, 'event' AS type
                 FROM {$event_table} e
                 JOIN {$facility_table} f ON e.facility_id = f.facility_id
                 WHERE e.category = %s AND e.start_time >= NOW() AND f.species IN (%s, 'both')",
                $category,
                $species
            ),
            ARRAY_A
        );
        $candidates = array_merge( $candidates, $event_candidates );
        $material_candidates = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT material_id AS id, title AS name, 'material' AS type
                 FROM {$material_table}
                 WHERE category = %s AND (target_species = %s OR target_species = 'both')",
                $category,
                $species
            ),
            ARRAY_A
        );
        $candidates = array_merge( $candidates, $material_candidates );

        if ( empty( $candidates ) ) {
            return new WP_Error( 'no_candidates', __( 'No candidates found.', 'roro-core' ), [ 'status' => 404 ] );
        }

        // 抽選
        $selected = $candidates[ array_rand( $candidates ) ];
        // ログ記録
        $gacha_table = $wpdb->prefix . 'gacha_log';
        $wpdb->insert(
            $gacha_table,
            [
                'customer_id' => $user_id,
                'prize_type'  => $selected['type'],
                'prize_id'    => (int) $selected['id'],
                'created_at'  => current_time( 'mysql' ),
            ],
            [ '%d', '%s', '%d', '%s' ]
        );
        return new WP_REST_Response( [ 'prize' => $selected ] );
    }
}
