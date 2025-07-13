<?php
namespace RoroCore\Api;

use WP_REST_Controller;
use wpdb;

class Endpoint_Analytics extends WP_REST_Controller {

	private wpdb $db;
	public function __construct( wpdb $wpdb ) {
		$this->db = $wpdb;
		$this->namespace = 'roro/v1';
		$this->rest_base = 'analytics';
	}

	public function register_routes() {
		register_rest_route(
			$this->namespace,
			"/{$this->rest_base}",
			[
				'methods'  => 'GET',
				'callback' => [ $this, 'get_data' ],
				'permission_callback' => fn() => current_user_can( 'manage_options' ),
			]
		);
	}

	public function get_data() {
		$mau = $this->db->get_var(
			"SELECT COUNT(*) FROM {$this->db->prefix}roro_customer
			 WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
		);

		$gacha_today = $this->db->get_var(
			"SELECT COUNT(*) FROM {$this->db->prefix}roro_gacha_log
			 WHERE DATE(created_at)=CURDATE()"
		);

		$revenue_mo = $this->db->get_var(
			"SELECT COALESCE(SUM(amount),0) FROM {$this->db->prefix}roro_revenue
			 WHERE DATE_FORMAT(created_at,'%Y-%m') = DATE_FORMAT(NOW(),'%Y-%m')"
		);

		return rest_ensure_response( [
			'mau'      => (int) $mau,
			'gacha'    => (int) $gacha_today,
			'revenue'  => (float) $revenue_mo,
		] );
	}
}
