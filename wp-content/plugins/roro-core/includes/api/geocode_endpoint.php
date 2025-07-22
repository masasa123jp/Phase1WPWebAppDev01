<?php
/**
 * Convert postal code to lat/lng via ZipCloud (JP).
 *
 * Route: /roro/v1/geocode/<zip>
 *
 * @package RoroCore\API
 */

declare( strict_types = 1 );

namespace RoroCore\API;

use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;

class Endpoint_Geocode {

	public function __construct() {
		add_action( 'rest_api_init', [ $this, 'register_route' ] );
	}

	public function register_route(): void {
		register_rest_route(
			'roro/v1',
			'/geocode/(?P<zip>[0-9]{3}-?[0-9]{4})',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'lookup' ],
				'permission_callback' => '__return_true',
			]
		);
	}

	public function lookup( WP_REST_Request $req ): WP_REST_Response {
		$zip = str_replace( '-', '', $req['zip'] ); // 101-0032 => 1010032
		$key = 'roro_geo_' . $zip;

		if ( $cache = get_transient( $key ) ) {
			return rest_ensure_response( $cache );
		}

		$url      = 'https://zipcloud.ibsnet.co.jp/api/search?zipcode=' . $zip;
		$response = wp_remote_get( $url ); // :contentReference[oaicite:8]{index=8}

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return new WP_REST_Response( [ 'error' => 'service_unreachable' ], 503 );
		}

		$json = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( empty( $json['results'][0] ) ) {
			return new WP_REST_Response( [ 'error' => 'not_found' ], 404 );
		}

		$result = $json['results'][0];
		set_transient( $key, $result, MONTH_IN_SECONDS );

		return rest_ensure_response( $result );
	}
}
