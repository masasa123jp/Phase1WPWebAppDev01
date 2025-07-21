<?php
/**
 * Breed list endpoint.
 *
 * Provides a simple read‑only API that returns a list of dog breeds.  This
 * endpoint is used by the report flow to populate the breed selection
 * screen.  In a real implementation the list of breeds would be
 * retrieved from a database table or an external service.  For now we
 * return a hard‑coded array of popular breeds.  The endpoint is public
 * and does not require authentication.
 *
 * @package RoroCore\Api
 */

namespace RoroCore\Api;

use WP_REST_Request;
use WP_REST_Response;

class Breed_List_Endpoint extends Abstract_Endpoint {
    /**
     * REST route.
     */
    public const ROUTE = '/breeds';

    public function __construct() {
        add_action( 'rest_api_init', [ $this, 'register' ] );
    }

    /**
     * Register the route.  The endpoint accepts GET requests and is
     * publicly accessible.  We override the permission callback to
     * always return true.
     */
    public static function register() : void {
        register_rest_route( 'roro/v1', self::ROUTE, [
            [
                'methods'             => 'GET',
                'callback'            => [ self::class, 'handle' ],
                'permission_callback' => '__return_true',
            ],
        ] );
    }

    /**
     * Handle the request.  Returns a static array of breed objects.  Each
     * breed has an ID and a name.  Additional metadata such as size or
     * temperament could be added in future.
     *
     * @param WP_REST_Request $request Incoming request (unused).
     *
     * @return WP_REST_Response Response containing the breed list.
     */
    public static function handle( WP_REST_Request $request ) : WP_REST_Response {
        $breeds = [
            [ 'id' => 1, 'name' => __( 'Shiba Inu', 'roro-core' ) ],
            [ 'id' => 2, 'name' => __( 'Toy Poodle', 'roro-core' ) ],
            [ 'id' => 3, 'name' => __( 'Golden Retriever', 'roro-core' ) ],
            [ 'id' => 4, 'name' => __( 'French Bulldog', 'roro-core' ) ],
            [ 'id' => 5, 'name' => __( 'Chihuahua', 'roro-core' ) ],
        ];
        return rest_ensure_response( $breeds );
    }
}
