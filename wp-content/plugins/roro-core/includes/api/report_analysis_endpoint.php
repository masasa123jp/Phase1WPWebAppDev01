<?php
/**
 * Report analysis endpoint.
 *
 * Accepts user input (breed, age, region and selected issues) and returns
 * an analysis of the pet’s situation.  In a future iteration this
 * endpoint would call internal services to generate trend graphs,
 * suggested facilities and AI commentary.  At present it returns a
 * placeholder structure so that the front‑end can be built without
 * breaking.
 *
 * @package RoroCore\Api
 */

namespace RoroCore\Api;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

class Report_Analysis_Endpoint extends Abstract_Endpoint {
    public const ROUTE = '/report/analysis';

    public function __construct() {
        add_action( 'rest_api_init', [ $this, 'register' ] );
    }

    public static function register() : void {
        register_rest_route( 'roro/v1', self::ROUTE, [
            [
                'methods'             => 'POST',
                'callback'            => [ self::class, 'handle' ],
                'permission_callback' => [ self::class, 'permission_callback' ],
                'args'                => [
                    'breed_id'  => [ 'type' => 'integer', 'required' => true ],
                    'age'       => [ 'type' => 'number',  'required' => true ],
                    'region'    => [ 'type' => 'string',  'required' => true ],
                    'issues'    => [ 'type' => 'array',   'required' => true, 'items' => [ 'type' => 'integer' ] ],
                ],
            ],
        ] );
    }

    public static function handle( WP_REST_Request $request ) : WP_REST_Response|WP_Error {
        // Extract parameters.  In a real implementation these would be
        // validated and passed to analysis services.
        $breed_id = (int) $request->get_param( 'breed_id' );
        $age      = (float) $request->get_param( 'age' );
        $region   = sanitize_text_field( $request->get_param( 'region' ) );
        $issues   = (array) $request->get_param( 'issues' );

        // TODO: implement actual analysis logic.  For now return a stub.
        $response = [
            'summary' => sprintf( __( 'Breed %1$d (age %2$s) in %3$s', 'roro-core' ), $breed_id, $age, $region ),
            'message' => __( 'Analysis results will be implemented in a future release.', 'roro-core' ),
            'graph'   => [],
            'facilities' => [],
        ];
        return rest_ensure_response( $response );
    }
}
