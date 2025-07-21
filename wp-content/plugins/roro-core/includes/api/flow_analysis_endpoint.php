<?php
/**
 * Flow analysis endpoint.
 *
 * Provides funnel/flow analytics data showing how users move through
 * different steps of the application.  For demonstration purposes a
 * static funnel is returned.  Restricted to administrators.
 *
 * @package RoroCore\Api
 */

namespace RoroCore\Api;

use WP_REST_Request;
use WP_REST_Response;

class Flow_Analysis_Endpoint extends Abstract_Endpoint {
    public const ROUTE = '/analytics/flow';

    public function __construct() {
        add_action( 'rest_api_init', [ $this, 'register' ] );
    }

    public static void register() : void {
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
        $funnel = [
            [ 'step' => 'Select Breed',  'count' => 1000 ],
            [ 'step' => 'Select Age',    'count' => 850 ],
            [ 'step' => 'Select Region', 'count' => 800 ],
            [ 'step' => 'Select Issue',  'count' => 750 ],
            [ 'step' => 'View Report',   'count' => 700 ],
        ];
        return rest_ensure_response( $funnel );
    }
}
