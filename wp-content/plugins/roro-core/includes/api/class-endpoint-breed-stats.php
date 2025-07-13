<?php
/**
 * GET /wp-json/roro/v1/breed-stats
 * 犬種 × 年齢（月）ごとの罹患リスク指数を返す。
 * 今はダミー計算だが、将来は外部 ML モデルと連携可能。
 */
namespace RoroCore\Api;
use WP_REST_Controller;
use WP_REST_Request;

class Endpoint_Breed_Stats extends WP_REST_Controller {

	public function __construct() {
		$this->namespace = 'roro/v1';
		$this->rest_base = 'breed-stats';
	}

	public function register_routes() {
		register_rest_route( $this->namespace, '/' . $this->rest_base, [
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_stats' ],
			'permission_callback' => '__return_true',
			'args'                => [
				'breed' => [ 'required' => true ],
				'age'   => [ 'required' => true, 'validate_callback' => 'is_numeric' ],
			],
		] );
	}

	public function get_stats( WP_REST_Request $req ) {
		$breed = sanitize_text_field( $req['breed'] );
		$age   = intval( $req['age'] );

		// TODO: 実データに置換
		$dummy = [
			'labels'   => [ '骨格', '心臓', '皮膚', '歯', '肥満' ],
			'datasets' => [[
				'label' => "$breed ($age m)",
				'data'  => array_map( fn() => rand(10, 90), range(1, 5 ) ),
			]],
		];
		return rest_ensure_response( $dummy );
	}
}
