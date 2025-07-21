<?php
/**
 * Facility search endpoint.  Performs a simple geographic radius search
 * against the `roro_facility` table using the Haversine formula to
 * calculate distance.  Returns a list of facilities sorted by distance
 * ascending.  Only basic validation is performed on input parameters.
 *
 * @package RoroCore\Api
 */

namespace RoroCore\Api;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

class Facility_Search_Endpoint extends Abstract_Endpoint {

    public const ROUTE = '/facilities';

    public function __construct() {
        add_action( 'rest_api_init', [ $this, 'register' ] );
    }

    /**
     * Register the facility search endpoint.  This endpoint is publicly
     * accessible; therefore we override the default permission callback
     * with `__return_true`.
     */
    public static function register() : void {
        register_rest_route( 'roro/v1', self::ROUTE, [
            [
                'methods'             => 'GET',
                'callback'            => [ self::class, 'handle' ],
                'permission_callback' => '__return_true',
                'args'                => [
                    'lat'    => [ 'type' => 'number',  'required' => true ],
                    'lng'    => [ 'type' => 'number',  'required' => true ],
                    'radius' => [ 'type' => 'integer', 'default'  => 2000 ],
                    'limit'  => [ 'type' => 'integer', 'default'  => 20 ],
                ],
            ],
        ] );
    }

    /**
     * Perform the facility search.  Uses a prepared SQL statement to
     * protect against SQL injection.  The radius is interpreted in
     * metres.
     *
     * @param WP_REST_Request $request Incoming request.
     *
     * @return WP_REST_Response|WP_Error
     */
    public static function handle( WP_REST_Request $request ) {
        global $wpdb;
        $table  = $wpdb->prefix . 'roro_facility';
        $lat    = (float) $request->get_param( 'lat' );
        $lng    = (float) $request->get_param( 'lng' );
        $radius = (int)   $request->get_param( 'radius' );
        $limit  = (int)   $request->get_param( 'limit' );
        if ( $radius <= 0 || $limit <= 0 ) {
            return new WP_Error( 'invalid_param', __( 'Radius and limit must be positive integers.', 'roro-core' ), [ 'status' => 400 ] );
        }
        $sql = $wpdb->prepare(
            "SELECT id, name, address, lat, lng,
                ( 6371000 * acos( cos( radians(%f) ) * cos( radians( lat ) ) * cos( radians( lng ) - radians(%f) ) + sin( radians(%f) ) * sin( radians( lat ) ) ) ) AS distance
             FROM {$table}
             HAVING distance < %d
             ORDER BY distance ASC
             LIMIT %d",
            $lat, $lng, $lat, $radius, $limit
        );
        $results = $wpdb->get_results( $sql, ARRAY_A );
        return rest_ensure_response( $results );
    }
}
