<?php
namespace RoroCore\Api;

use WP_REST_Controller;
use wpdb;

class Endpoint_Gacha extends WP_REST_Controller {

	private wpdb $db;
	public function __construct( wpdb $wpdb ) {
		$this->db = $wpdb;
		$this->namespace = 'roro/v1';
		$this->rest_base = 'gacha';
	}

	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			[
				'methods'  => 'POST',
				'callback' => [ $this, 'spin' ],
				'permission_callback' => fn() => is_user_logged_in(),
			]
		);
	}

	public function spin() {
		$customer = get_current_user_id();
		$table    = get_option( 'roro_gacha_table' );
		$lines    = array_filter( array_map( 'str_getcsv', explode( "\n", trim( $table ) ) ) );

		/* CSV = category,probability  (sum to 1.0) */
		$rand = mt_rand() / mt_getrandmax();
		$sum  = 0;
		$cat  = null;
		foreach ( $lines as $l ) {
			$sum += (float) $l[1];
			if ( $rand <= $sum ) { $cat = $l[0]; break; }
		}

		if ( ! $cat ) $cat = 'cafe';

		// 施設かアドバイスをランダム取得
		if ( in_array( $cat, [ 'cafe','hospital','salon','park','hotel','school','store' ], true ) ) {
			$item = $this->db->get_row(
				$this->db->prepare(
					"SELECT facility_id AS id,name FROM {$this->db->prefix}roro_facility WHERE category=%s ORDER BY RAND() LIMIT 1",
					$cat
				),
				ARRAY_A
			);
			$prize_type = 'facility';
			$facility_id = $item['id'];
			$advice_id = null;
		} else {
			$item = $this->db->get_row(
				$this->db->prepare(
					"SELECT advice_id AS id,title AS name FROM {$this->db->prefix}roro_advice WHERE category=%s ORDER BY RAND() LIMIT 1",
					$cat
				),
				ARRAY_A
			);
			$prize_type = 'advice';
			$facility_id = null;
			$advice_id = $item['id'];
		}

		// ログ
		$this->db->insert(
			"{$this->db->prefix}roro_gacha_log",
			[
				'customer_id' => $customer,
				'facility_id' => $facility_id,
				'advice_id'   => $advice_id,
				'prize_type'  => $prize_type,
			],
			[ '%d','%d','%d','%s' ]
		);

		return rest_ensure_response( [
			'prize_type' => $prize_type,
			'item'       => $item,
		] );
	}
}
