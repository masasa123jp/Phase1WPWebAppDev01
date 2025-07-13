<?php
namespace RoroCore\Api;

use WP_REST_Controller;
use WP_REST_Request;
use wpdb;

class Endpoint_Report extends WP_REST_Controller {

	private wpdb $db;
	public function __construct( wpdb $wpdb ) {
		$this->db = $wpdb;
		$this->namespace = 'roro/v1';
		$this->rest_base = 'report';
	}

	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			[
				'methods'  => WP_REST_Server::CREATABLE,
				'callback' => [ $this, 'create_report' ],
				'permission_callback' => [ $this, 'can_create' ],
				'args' => [
					'content' => [ 'required' => true ],
				],
			]
		);
	}

	public function can_create() : bool {
		return is_user_logged_in() && wp_verify_nonce( $_REQUEST['_wpnonce'] ?? '', 'wp_rest' ); // :contentReference[oaicite:9]{index=9}
	}

	public function create_report( WP_REST_Request $req ) {
		$customer_id = get_current_user_id();
		$content     = wp_json_encode( $req->get_json_params() );

		$this->db->insert(
			"{$this->db->prefix}roro_report",
			[
				'customer_id' => $customer_id,
				'content'     => $content,
			],
			[ '%d', '%s' ]
		);

		return rest_ensure_response( [ 'report_id' => $this->db->insert_id ] );
	}
}
