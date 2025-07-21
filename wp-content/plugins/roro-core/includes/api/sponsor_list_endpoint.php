<?php
/**
 * Sponsor list endpoint.
 *
 * Provides a list of active sponsors for the consumer application.  Each
 * sponsor contains an ID, name and URL to their advertising creative.
 * The data returned here could be managed via a custom post type or
 * stored in a dedicated table.  For now, a static array is used.  The
 * endpoint is public and does not require authentication.
 *
 * @package RoroCore\Api
 */

namespace RoroCore\Api;

use WP_REST_Request;
use WP_REST_Response;

class Sponsor_List_Endpoint extends Abstract_Endpoint {
    public const ROUTE = '/sponsors';

    public function __construct() {
        add_action( 'rest_api_init', [ $this, 'register' ] );
    }

    public static function register() : void {
        register_rest_route( 'roro/v1', self::ROUTE, [
            [
                'methods'             => 'GET',
                'callback'            => [ self::class, 'handle' ],
                'permission_callback' => '__return_true',
            ],
        ] );
    }

    public static function handle( WP_REST_Request $request ) : WP_REST_Response {
        $sponsors = [
            [ 'id' => 1, 'name' => 'Pet Food Co.', 'image' => RORO_CORE_URL . 'assets/ads/pet-food.jpg' ],
            [ 'id' => 2, 'name' => 'Vet Clinic',   'image' => RORO_CORE_URL . 'assets/ads/vet-clinic.jpg' ],
        ];
        return rest_ensure_response( $sponsors );
    }
}
