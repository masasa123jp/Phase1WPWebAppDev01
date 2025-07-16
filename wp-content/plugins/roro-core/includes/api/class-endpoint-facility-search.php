<?php
/**
 * Search nearby pet‑friendly facilities.
 *
 * Route: /roro/v1/facility-search
 *
 * @package RoroCore\API
 */

declare( strict_types = 1 );

namespace RoroCore\API;

use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use RoroCore\Rate_Limiter;

class Endpoint_Facility_Search {

	private string $table;

	public function __construct( \wpdb $wpdb ) {
		$this->table = $wpdb->prefix . 'roro_facility';
		add_action( 'rest_api_init', [ $this, 'register_route' ] );
	}

	public function register_route(): void {
		register_rest_route(
			'roro/v1',
			'/facility-search',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'search' ],
				'permission_callback' => '__return_true',
				'args'                => [
					'lat' => [ 'type' => 'number', 'required' => true ],
					'lng' => [ 'type' => 'number', 'required' => true ],
					'rad' => [ 'type' => 'integer', 'default' => 3000 ],
				],
			]
		);
	}

	public function search( WP_REST_Request $req ): WP_REST_Response {
		global $wpdb;

		// ── レート制限 ──
		$limiter = new Rate_Limiter( 'facility_search', 30, HOUR_IN_SECONDS );
		if ( ! $limiter->check() ) {
			return new WP_REST_Response( [ 'error' => 'rate_limited' ], 429 );
		}

		$lat = (float) $req['lat'];
		$lng = (float) $req['lng'];
		$rad = (int)   $req['rad'];

		// ── Transient キャッシュ ──
		$cache_key = 'roro_fac_' . md5( implode( ':', [ $lat, $lng, $rad ] ) );
		if ( $cache = get_transient( $cache_key ) ) {
			return rest_ensure_response( $cache );
		}

		// MariaDB 10.5+ かつ GIS 関数が存在するか判定
		$has_gis = $wpdb->get_var( "SELECT LOCATE('MariaDB', VERSION())" ) && version_compare( $wpdb->get_var( 'SELECT VERSION()' ), '10.5', '>=' );

		if ( $has_gis ) {
			$sql = "
				SELECT id, name, genre,
				       ST_Distance_Sphere(point(lng,lat), point(%f,%f)) AS dist
				FROM {$this->table}
				WHERE ST_Distance_Sphere(point(lng,lat), point(%f,%f)) < %d
				ORDER BY dist
				LIMIT 50
			";
			$query = $wpdb->prepare( $sql, $lng, $lat, $lng, $lat, $rad );
		} else {
			$sql = "
				SELECT id, name, genre,
				       ( 6378137 * ACOS(
				           COS(RADIANS(%f))*COS(RADIANS(lat))*COS(RADIANS(lng)-RADIANS(%f)) +
				           SIN(RADIANS(%f))*SIN(RADIANS(lat))
				       ) ) AS dist
				FROM {$this->table}
				HAVING dist < %d
				ORDER BY dist
				LIMIT 50
			";
			$query = $wpdb->prepare( $sql, $lat, $lng, $lat, $rad );
		}

		$results = $wpdb->get_results( $query, ARRAY_A );
		set_transient( $cache_key, $results, 10 * MINUTE_IN_SECONDS );

		return rest_ensure_response( $results );
	}
}
