<?php
namespace RoroCore\API;

use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;

class Endpoint_Facility_Search {

	const ROUTE = '/facility-search';

	public static function register(): void {
		register_rest_route(
			'roro/v1',
			self::ROUTE,
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ self::class, 'search' ],
				'permission_callback' => '__return_true',
				'args'                => [
					'lat'  => [ 'required' => true, 'type' => 'number' ],
					'lng'  => [ 'required' => true, 'type' => 'number' ],
					'rad'  => [ 'required' => false, 'type' => 'integer', 'default' => 3000 ],
				],
			]
		);
	}

	public static function search( WP_REST_Request $req ): WP_REST_Response {
		global $wpdb;
		$lat = (float) $req['lat'];
		$lng = (float) $req['lng'];
		$rad = (int)   $req['rad'];

		$table = $wpdb->prefix . 'roro_facility';

		// MariaDB 10.5 (XServer) なら ST_Distance_Sphere が使える。
		$has_gis = $wpdb->get_var( "SELECT VERSION() LIKE '%MariaDB%'" ) && 
		           version_compare( $wpdb->get_var( "SELECT VERSION()" ), '10.5', '>=' );

		if ( $has_gis ) {
			$sql = "
				SELECT *, ST_Distance_Sphere(
					point(lng, lat),
					point(%f, %f)
				) AS dist
				FROM $table
				WHERE ST_Distance_Sphere(point(lng,lat), point(%f,%f)) < %d
				ORDER BY dist
				LIMIT 50
			";
			$q = $wpdb->prepare( $sql, $lng, $lat, $lng, $lat, $rad );
		} else {
			// MySQL 5.7 系では Haversine を使用
			$sql = "
				SELECT *,
				(6378137 * ACOS(
					COS(RADIANS(%f))*COS(RADIANS(lat))*COS(RADIANS(lng)-RADIANS(%f)) +
					SIN(RADIANS(%f))*SIN(RADIANS(lat))
				)) AS dist
				FROM $table
				HAVING dist < %d
				ORDER BY dist
				LIMIT 50
			";
			$q = $wpdb->prepare( $sql, $lat, $lng, $lat, $rad );
		}
		$rows = $wpdb->get_results( $q );

		return rest_ensure_response( $rows );
	}
}
add_action( 'rest_api_init', [ Endpoint_Facility_Search::class, 'register' ] );
