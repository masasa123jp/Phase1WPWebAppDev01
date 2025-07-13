<?php
/**
 * POST /wp-json/roro/v1/photo
 * 画像を WP Media Library へ登録し、位置情報・犬種タグを独自テーブルへ書き込む。
 */
namespace RoroCore\Api;

use WP_REST_Controller;
use WP_REST_Request;
use WP_Error;

class Endpoint_Photo extends WP_REST_Controller {

	public function __construct() {
		$this->namespace = 'roro/v1';
		$this->rest_base = 'photo';
	}

	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'create_item' ],
				'permission_callback' => [ $this, 'permissions_check' ],
				'args'                => [
					'file'     => [ 'required' => true ],
					'breed'    => [],
					'zipcode'  => [],
				],
			]
		);
	}

	public function permissions_check() {
		return is_user_logged_in() || current_user_can( 'upload_files' );
	}

	public function create_item( WP_REST_Request $request ) {
		$file = $request->get_file_params()['file'] ?? null;
		if ( ! $file ) {
			return new WP_Error( 'no_file', '画像がありません。', [ 'status' => 400 ] );
		}

		$attach_id = media_handle_upload( 'file', 0 );
		if ( is_wp_error( $attach_id ) ) {
			return $attach_id;
		}

		global $wpdb;
		$wpdb->insert(
			$wpdb->prefix . 'roro_photo',
			[
				'attachment_id' => $attach_id,
				'breed'         => $request['breed'],
				'zipcode'       => $request['zipcode'],
				'created_at'    => current_time( 'mysql' ),
			],
			[ '%d', '%s', '%s', '%s' ]
		);

		return rest_ensure_response( [ 'id' => $attach_id ] );
	}
}
