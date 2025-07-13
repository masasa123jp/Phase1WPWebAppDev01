<?php
namespace RoroCore\Api;

use WP_REST_Controller;
use WP_REST_Request;

class Endpoint_Geocode extends WP_REST_Controller {

	public function __construct() {
		$this->namespace = 'roro/v1';
		$this->rest_base = 'geocode';
	}

	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'lookup' ],
				'permission_callback' => '__return_true',
				'args'                => [
					'zipcode' => [ 'required' => true ],
				],
			]
		);
	}

	public function lookup( WP_REST_Request $req ) {
		$zip = preg_replace( '/[^\d]/', '', $req['zipcode'] );
		$key = "roro_geo_$zip";
		if ( $cached = get_transient( $key ) ) {
			return rest_ensure_response( $cached );
		}

		$res = wp_remote_get( "https://zipcloud.ibsnet.co.jp/api/search?zipcode=$zip" );
		$body = json_decode( wp_remote_retrieve_body( $res ), true );
		if ( empty( $body['results'][0] ) ) {
			return rest_ensure_response( [ 'results' => [] ] );
		}

		$lat = $body['results'][0]['lat']; // サンプル：本番は別 API
		$lng = $body['results'][0]['lng'];
		$data = [ 'results' => [ 'location' => [ 'lat' => $lat, 'lng' => $lng ] ] ];

		set_transient( $key, $data, DAY_IN_SECONDS * 30 );
		return rest_ensure_response( $data );
	}
}
