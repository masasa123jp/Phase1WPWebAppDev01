<?php
/**
 * 分析用エンドポイント。
 * 過去 30 日間のガチャログテーブルから集計した主要な指標を返します。
 * 本日のスピン数、アクティブな日数、ユニークな IP 数をカウントします。
 * 公開アクセス可能です。
 *
 * @package RoroCore\Api
 */

namespace RoroCore\Api;

use WP_REST_Request;
use WP_REST_Response;

class Analytics_Endpoint {

    public function __construct() {
        add_action( 'rest_api_init', [ $this, 'register' ] );
    }

    public function register() : void {
        register_rest_route( 'roro/v1', '/analytics', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'handle' ],
            'permission_callback' => '__return_true',
        ] );
    }

    /**
     * 分析を計算します。ガチャログテーブルから集計した統計情報を取得します。
     *
     * @return WP_REST_Response
     */
    public function handle( WP_REST_Request $req ) : WP_REST_Response {
        global $wpdb;
        $table = $wpdb->prefix . 'roro_gacha_log';
        $sql   = "
            SELECT
                SUM( created_at >= DATE( NOW() ) ) AS today_spins,
                COUNT( DISTINCT DATE( created_at ) ) AS active_days,
                COUNT( DISTINCT ip ) AS unique_ips_30d
            FROM {$table}
            WHERE created_at >= DATE_SUB( NOW(), INTERVAL 30 DAY )
        ";
        $row = $wpdb->get_row( $sql, ARRAY_A );
        return rest_ensure_response( [
            'today_spins'    => (int) ( $row['today_spins'] ?? 0 ),
            'active_days'    => (int) ( $row['active_days'] ?? 0 ),
            'unique_ips_30d' => (int) ( $row['unique_ips_30d'] ?? 0 ),
        ] );
    }
}
