<?php
/**
 * Facility database endpoint.
 *
 * Provides CRUD operations on the facility database for administrators.
 * A GET request returns a list of facilities, while a POST request
 * creates a new facility record.  Additional methods (PUT, DELETE)
 * could be added for editing and deleting facilities.  Only users
 * with the `manage_options` capability may access this endpoint.
 *
 * @package RoroCore\Api
 */

namespace RoroCore\Api;

use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

class Facility_DB_Endpoint extends Abstract_Endpoint {
    public const ROUTE = '/facilities/db';

    public function __construct() {
        add_action( 'rest_api_init', [ $this, 'register' ] );
    }

    public static function register() : void {
        register_rest_route( 'roro/v1', self::ROUTE, [
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [ self::class, 'list_facilities' ],
                'permission_callback' => function() {
                    return current_user_can( 'manage_options' );
                },
            ],
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [ self::class, 'create_facility' ],
                'permission_callback' => function() {
                    return current_user_can( 'manage_options' );
                },
                'args'                => [
                    'name'    => [ 'type' => 'string', 'required' => true ],
                    'address' => [ 'type' => 'string', 'required' => true ],
                    'lat'     => [ 'type' => 'number', 'required' => false ],
                    'lng'     => [ 'type' => 'number', 'required' => false ],
                ],
            ],
        ] );
    }

    public static function list_facilities( WP_REST_Request $request ) : WP_REST_Response {
        $facilities = [
            [ 'id' => 1, 'name' => 'Happy Paws Clinic', 'address' => 'Tokyo', 'lat' => 35.6895, 'lng' => 139.6917 ],
            [ 'id' => 2, 'name' => 'WanWan Groomers',   'address' => 'Osaka', 'lat' => 34.6937, 'lng' => 135.5023 ],
        ];
        return rest_ensure_response( $facilities );
    }

    public static function create_facility( WP_REST_Request $request ) : WP_REST_Response {
        $name    = sanitize_text_field( $request->get_param( 'name' ) );
        $address = sanitize_text_field( $request->get_param( 'address' ) );
        $lat     = $request->get_param( 'lat' ) !== null ? (float) $request->get_param( 'lat' ) : null;
        $lng     = $request->get_param( 'lng' ) !== null ? (float) $request->get_param( 'lng' ) : null;
        // TODO: insert into DB and return the new facility ID.
        return rest_ensure_response( [ 'id' => rand( 100, 999 ), 'name' => $name, 'address' => $address, 'lat' => $lat, 'lng' => $lng ] );
    }
}
