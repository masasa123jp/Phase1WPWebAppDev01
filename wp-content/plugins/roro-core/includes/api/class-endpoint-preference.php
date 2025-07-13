<?php
namespace RoroCore\Api;

use WP_REST_Controller;
use WP_REST_Request;

class Endpoint_Preference extends WP_REST_Controller {

	public function __construct() {
		$this->namespace = 'roro/v1';
		$this->rest_base = 'preferences';
	}

	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			[
				[
					'methods'             => 'GET',
					'callback'            => [ $this, 'get_item' ],
					'permission_callback' => [ $this, 'check_permissions' ],
				],
				[
					'methods'             => 'POST',
					'callback'            => [ $this, 'update_item' ],
					'permission_callback' => [ $this, 'check_permissions' ],
					'args'                => [
						'line'  => [ 'type' => 'boolean' ],
						'email' => [ 'type' => 'boolean' ],
						'fcm'   => [ 'type' => 'boolean' ],
					],
				],
			]
		);
	}

	public function check_permissions() : bool {
		return is_user_logged_in();
	}

	public function get_item( WP_REST_Request $req ) {
		$u = get_current_user_id();
		return rest_ensure_response( get_user_meta( $u, 'roro_notify_pref', true ) );
	}

	public function update_item( WP_REST_Request $req ) {
		$u = get_current_user_id();
		update_user_meta( $u, 'roro_notify_pref', [
			'line'  => (bool) $req['line'],
			'email' => (bool) $req['email'],
			'fcm'   => (bool) $req['fcm'],
		] );
		return rest_ensure_response( [ 'updated' => true ] );
	}
}
