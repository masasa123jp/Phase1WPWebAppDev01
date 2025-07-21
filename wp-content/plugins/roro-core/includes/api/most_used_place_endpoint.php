<?php
/**
 * Most used place endpoint.
 *
 * Returns analytics data about the most frequently used facilities or
 * locations over the past 30 days.  The endpoint is restricted to
 * administrators.  Data is currently static.
 *
 * @package RoroCore\Api
 */

namespace RoroCore\Api;

use WP_REST_Request;
use WP_REST_Response;

class Most_Used_Place_Endpoint extends Abstract_Endpoint {
    public const ROUTE = '/analytics/most-used-places';

    public function __construct() {
        add_action( 'rest_api_init', [ $this, 'register' ] );
    }

    public static function register() : void {
        register_rest_route( 'roro/v1', self::ROUTE, [
            [
                'methods'             => 'GET',
                'callback'            => [ self::class, 'handle' ],
                'permission_callback' => function() {
                    return current_user_can( 'manage_options' );
                },
            ],
        ] );
    }

    public static function handle( WP_REST_Request $request ) : WP_REST_Response {
        $data = [
            [ 'name' => 'Happy Paws Clinic', 'visits' => 123 ],
            [ 'name' => 'WanWan Groomers',   'visits' => 85 ],
        ];
        return rest_ensure_response( $data );
    }
}
