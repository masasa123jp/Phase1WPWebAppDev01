<?php
/**
 * 施設検索 API
 *
 * @package RoroCore
 */

namespace RoroCore\Api;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

class Endpoint_Facility_Search extends Abstract_Endpoint {

	const ROUTE = '/facilities';

	public static function register(): void {
		register_rest_route(
			'roro/v1',
			self::ROUTE,
			[
				[
					'methods'             => 'GET',
					'callback'            => [ self::class, 'handle' ],
					'permission_callback' => '__return_true', // 公開 API
					'args'                => [
						'lat'    => [ 'type' => 'number',  'required' => true ],
						'lng'    => [ 'type' => 'number',  'required' => true ],
						'radius' => [ 'type' => 'integer', 'default'  => 2000 ],
						'limit'  => [ 'type' => 'integer', 'default'  => 20 ],
					],
				],
			]
		);
	}

	public static function handle( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		global $wpdb;

		$table  = $wpdb->prefix . 'roro_facilities';
		$lat    = (float) $request['lat'];
		$lng    = (float) $request['lng'];
		$radius = (int)   $request['radius'];
		$limit  = (int)   $request['limit'];

		$sql = $wpdb->prepare(
			"SELECT id, name, address,
			        ( 6371000 * acos(
			          cos( radians(%f) ) * cos( radians( lat ) )
			        * cos( radians( lng ) - radians(%f) )
			        + sin( radians(%f) ) * sin( radians( lat ) )
			        ) ) AS distance
			   FROM {$table}
			  HAVING distance < %d
		   ORDER BY distance ASC
			  LIMIT %d",
			$lat,
			$lng,
			$lat,
			$radius,
			$limit
		);

		$results = $wpdb->get_results( $sql, ARRAY_A );

		return rest_ensure_response( $results );
	}
}

add_action( 'rest_api_init', [ Endpoint_Facility_Search::class, 'register' ] );
