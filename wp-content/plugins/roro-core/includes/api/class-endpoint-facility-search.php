<?php
namespace RoroCore\Api;

use WP_REST_Controller;
use WP_REST_Request;
use wpdb;

class Endpoint_Facility_Search extends WP_REST_Controller {

	private wpdb $db;

	public function __construct( wpdb $wpdb ) {
		$this->db = $wpdb;
		$this->namespace = 'roro/v1';
		$this->rest_base = 'facilities';
	}

	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			[
				'methods'  => 'GET',
				'callback' => [ $this, 'search' ],
				'permission_callback' => '__return_true',
				'args' => [
					'zipcode'  => [ 'required' => true ],
					'category' => [ 'sanitize_callback' => 'sanitize_text_field' ],
					'radius'   => [ 'default' => 5 ],
				],
			]
		);
	}

	public function search( WP_REST_Request $req ) {
		$zip   = preg_replace( '/[^\d]/', '', $req['zipcode'] );
		$cat   = $req['category'];
		$radKm = (int) $req['radius'];

		$geo = roro_geocode_zip( $zip ); // uses transients cache
		if ( ! $geo ) {
			return new \WP_Error( 'bad_zip', 'Invalid zipcode', [ 'status' => 400 ] );
		}

		$sql = "
			SELECT f.*, (
				ST_Distance_Sphere( point(f.lng, f.lat), point(%f,%f) ) / 1000
			) AS dist_km
			FROM {$this->db->prefix}roro_facility f
			WHERE 1 = 1
			" . ( $cat ? $this->db->prepare( ' AND f.category = %s', $cat ) : '' ) . "
			HAVING dist_km <= %d
			ORDER BY dist_km ASC
			LIMIT 100
		";

		$results = $this->db->get_results(
			$this->db->prepare( $sql, $geo['lng'], $geo['lat'], $radKm ),
			ARRAY_A
		); // :contentReference[oaicite:8]{index=8}

		return rest_ensure_response( $results );
	}
}
