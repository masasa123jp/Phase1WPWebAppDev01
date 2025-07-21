<?php
/**
 * Download data endpoint.
 *
 * Allows administrators to download analytics data in CSV format.  The
 * request may specify a `dataset` parameter to select which report
 * should be exported.  The endpoint currently returns a placeholder
 * response indicating that the feature is not yet implemented.
 *
 * @package RoroCore\Api
 */

namespace RoroCore\Api;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

class Download_Data_Endpoint extends Abstract_Endpoint {
    public const ROUTE = '/analytics/download-data';

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
                'args'                => [
                    'dataset' => [ 'type' => 'string', 'required' => false ],
                ],
            ],
        ] );
    }

    public static function handle( WP_REST_Request $request ) : WP_REST_Response|WP_Error {
        $dataset = $request->get_param( 'dataset' ) ?: 'all';
        return rest_ensure_response( [
            'dataset' => $dataset,
            'message' => __( 'Data export is not implemented yet.', 'roro-core' ),
        ] );
    }
}
