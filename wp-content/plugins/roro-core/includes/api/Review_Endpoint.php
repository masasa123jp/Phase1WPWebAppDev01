<?php
/**
 * Facility review submission endpoint.  Accepts POST requests containing
 * a facility ID, rating and optional comment.  The rating is constrained
 * to the range 1–5.  Uses the `roro_facility_review` table which must
 * exist in the database.  Requires authentication via the default
 * permission callback in the base class.
 *
 * @package RoroCore\Api
 */

namespace RoroCore\Api;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

class Review_Endpoint extends Abstract_Endpoint {

    public const ROUTE = '/reviews';

    public function __construct() {
        add_action( 'rest_api_init', [ $this, 'register' ] );
    }

    /**
     * Register the review submission route.
     */
    public static function register() : void {
        register_rest_route( 'roro/v1', self::ROUTE, [
            [
                'methods'             => 'POST',
                'callback'            => [ self::class, 'handle' ],
                'permission_callback' => [ self::class, 'permission_callback' ],
                'args'                => [
                    'facility_id' => [ 'type' => 'integer', 'required' => true ],
                    'rating'      => [ 'type' => 'number',  'required' => true ],
                    'comment'     => [ 'type' => 'string',  'required' => false ],
                ],
            ],
        ] );
    }

    /**
     * Handle review submission.  Validates rating and inserts into DB.
     *
     * @param WP_REST_Request $request Incoming request.
     *
     * @return WP_REST_Response|WP_Error
     */
    public static function handle( WP_REST_Request $request ) {
        global $wpdb;
        $facility_id = (int) $request->get_param( 'facility_id' );
        $rating      = (float) $request->get_param( 'rating' );
        $comment     = $request->get_param( 'comment' );
        if ( $rating < 1 || $rating > 5 ) {
            return new WP_Error( 'invalid_rating', __( 'Rating must be between 1 and 5.', 'roro-core' ), [ 'status' => 400 ] );
        }
        $table = $wpdb->prefix . 'roro_facility_review';
        $wpdb->insert( $table, [
            'user_id'     => get_current_user_id(),
            'facility_id' => $facility_id,
            'rating'      => $rating,
            'comment'     => ( $comment !== null ) ? wp_kses_post( $comment ) : '',
            'created_at'  => current_time( 'mysql' ),
        ], [ '%d', '%d', '%f', '%s', '%s' ] );
        return rest_ensure_response( [ 'id' => (int) $wpdb->insert_id ] );
    }
}
