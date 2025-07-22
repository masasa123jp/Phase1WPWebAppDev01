<?php
/**
 * REST: /wp-json/roro/v1/dashboard
 * Aggregates KPI metrics (active users, ad CTR, revenue)
 */

namespace RoroCore\Api;
use wpdb;
use WP_REST_Controller;
use WP_REST_Request;

class Endpoint_Dashboard extends WP_REST_Controller {

	private wpdb $db;

	public function __construct( wpdb $wpdb ) {
		$this->db        = $wpdb;
		$this->namespace = 'roro/v1';
		$this->rest_base = 'dashboard';
	}

	public function register_routes(): void {
		register_rest_route( $this->namespace, "/{$this->rest_base}", [
			'methods'  => 'GET',
			'callback' => [ $this, 'get_kpi' ],
			'permission_callback' => fn() => current_user_can( 'manage_options' ),
		] );
	}

	public function get_kpi( WP_REST_Request $req ) {
		$p = $this->db->prefix;

		$active30 = (int) $this->db->get_var(
			"SELECT COUNT(*) FROM {$p}roro_customer WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
		);

		$ctr = (float) $this->db->get_var(
			"SELECT ROUND(
			    (SELECT COUNT(*) FROM {$p}roro_gacha_log WHERE prize_type='facility' AND created_at>=CURDATE()) /
			    NULLIF((SELECT COUNT(*) FROM {$p}roro_gacha_log WHERE created_at>=CURDATE()),0)
			  , 3)"
		);

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
