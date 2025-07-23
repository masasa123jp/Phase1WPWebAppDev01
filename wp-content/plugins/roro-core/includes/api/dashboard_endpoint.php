<?php
/**
 * Module path: wp-content/plugins/roro-core/includes/api/dashboard_endpoint.php
 *
 * 管理者用ダッシュボードKPIエンドポイント。30日間のアクティブユーザー数、当日広告CTR、
 * 当月売上をまとめて返します。アクセスは manage_options 権限を持つユーザーに限定されます。
 *
 * @package RoroCore\Api
 */

namespace RoroCore\Api;

use wpdb;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;

class Dashboard_Endpoint extends WP_REST_Controller {
    /** @var wpdb */
    private wpdb $db;

    public function __construct( wpdb $wpdb ) {
        $this->db        = $wpdb;
        $this->namespace = 'roro/v1';
        $this->rest_base = 'dashboard';
        add_action( 'rest_api_init', [ $this, 'register_routes' ] );
    }

    /**
     * ルート登録。
     */
    public function register_routes(): void {
        register_rest_route( $this->namespace, "/{$this->rest_base}", [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_kpi' ],
            'permission_callback' => function() {
                return current_user_can( 'manage_options' );
            },
        ] );
    }

    /**
     * KPIを計算して返却。
     *
     * @param WP_REST_Request $req
     * @return WP_REST_Response
     */
    public function get_kpi( WP_REST_Request $req ) : WP_REST_Response {
        $p = $this->db->prefix;

        // 30日間に登録されたカスタマー数
        $active30 = (int) $this->db->get_var(
            "SELECT COUNT(*) FROM {$p}roro_customer WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
        );

        // 当日の広告CTR (施設賞取得数 / gachaログ総数) を小数3桁で返す。ゼロ割回避。
        $ctr = (float) $this->db->get_var(
            "SELECT ROUND(
                (SELECT COUNT(*) FROM {$p}roro_gacha_log WHERE prize_type='facility' AND created_at>=CURDATE())
                /
                NULLIF((SELECT COUNT(*) FROM {$p}roro_gacha_log WHERE created_at>=CURDATE()),0)
            , 3)"
        );

        // 当月売上
        $revenueMo = (float) $this->db->get_var(
            "SELECT COALESCE(SUM(amount),0) FROM {$p}roro_revenue
             WHERE DATE_FORMAT(created_at,'%Y-%m') = DATE_FORMAT(NOW(),'%Y-%m')"
        );

        return rest_ensure_response( [
            'active_30d'      => $active30,
            'ad_click_rate'   => $ctr,
            'revenue_current' => $revenueMo,
        ] );
    }
}
