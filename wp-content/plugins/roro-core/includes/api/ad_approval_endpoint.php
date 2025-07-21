<?php
/**
 * Advertisement approval endpoint.
 *
 * Allows administrators to approve or reject sponsor advertisements.  A
 * POST request with an ad ID and a status (approved|rejected) will
 * update the advertisement record.  Only users with the
 * `manage_options` capability may call this endpoint.  For now the
 * implementation simply returns the submitted values.
 *
 * @package RoroCore\Api
 */

namespace RoroCore\Api;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

class Ad_Approval_Endpoint extends Abstract_Endpoint {
    public const ROUTE = '/ads/approval';

    public function __construct() {
        add_action( 'rest_api_init', [ $this, 'register' ] );
    }

    public static function register() : void {
        register_rest_route( 'roro/v1', self::ROUTE, [
            [
                'methods'             => 'POST',
                'callback'            => [ self::class, 'handle' ],
                'permission_callback' => function() {
                    return current_user_can( 'manage_options' );
                },
                'args'                => [
                    'ad_id' => [ 'type' => 'integer', 'required' => true ],
                    'status' => [ 'type' => 'string',  'required' => true, 'enum' => [ 'approved', 'rejected' ] ],
                ],
            ],
        ] );
    }

    public static function handle( WP_REST_Request $request ) : WP_REST_Response|WP_Error {
        $ad_id = (int) $request->get_param( 'ad_id' );
        $status = $request->get_param( 'status' );
        // TODO: Update the ad status in the database.
        return rest_ensure_response( [ 'ad_id' => $ad_id, 'status' => $status ] );
    }
}
