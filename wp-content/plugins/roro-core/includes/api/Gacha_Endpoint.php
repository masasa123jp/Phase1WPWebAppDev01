<?php
/**
 * Gacha (prize draw) endpoint.  Returns a random prize and records the
 * outcome in a log table.  A simple rate limiter is applied to prevent
 * abuse – by default a client may spin the gacha five times per hour.
 * The list of prizes may be filtered via the `roro_gacha_prizes` filter.
 *
 * @package RoroCore\Api
 */

namespace RoroCore\Api;

use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use RoroCore\Utils\Rate_Limiter;

class Gacha_Endpoint extends Abstract_Endpoint {
    /**
     * REST route.
     */
    public const ROUTE = '/gacha';

    public function __construct() {
        add_action( 'rest_api_init', [ $this, 'register' ] );
    }

    /**
     * Register the gacha endpoint.  Accepts POST requests.  Optionally
     * accepts a `category` parameter which could be used by plugins to
     * categorise prizes (unused in this example but preserved for
     * backwards compatibility).
     */
    public static function register() : void {
        register_rest_route( 'roro/v1', self::ROUTE, [
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [ self::class, 'handle' ],
                'permission_callback' => [ self::class, 'permission_callback' ],
                'args'                => [
                    'category' => [
                        'type'     => 'string',
                        'required' => false,
                    ],
                ],
            ],
        ] );
    }

    /**
     * Handle a gacha spin.  Applies a rate limit and then selects a
     * random prize from the configured list.  The result is logged in
     * the `roro_gacha_log` table along with the client IP and timestamp.
     *
     * @param WP_REST_Request $request Incoming request.
     *
     * @return WP_REST_Response|WP_Error
     */
    public static function handle( WP_REST_Request $request ) {
        // Enforce rate limiting: 5 spins per hour by default.
        $limiter = new Rate_Limiter( 'gacha', 5, HOUR_IN_SECONDS );
        if ( ! $limiter->check() ) {
            return new WP_Error( 'rate_limited', __( 'You have reached the gacha spin limit. Please try again later.', 'roro-core' ), [ 'status' => 429 ] );
        }
        // Define available prizes.  Each prize has an ID, name and optional image URL.
        $prizes = [
            [ 'id' => 1, 'name' => __( 'Snack', 'roro-core' ),    'image' => RORO_CORE_URL . 'assets/prizes/snack.png' ],
            [ 'id' => 2, 'name' => __( 'Coupon', 'roro-core' ),   'image' => RORO_CORE_URL . 'assets/prizes/coupon.png' ],
            [ 'id' => 3, 'name' => __( 'Training Video', 'roro-core' ), 'image' => RORO_CORE_URL . 'assets/prizes/video.png' ],
            [ 'id' => 4, 'name' => __( 'Discount', 'roro-core' ), 'image' => RORO_CORE_URL . 'assets/prizes/discount.png' ],
        ];
        $prizes = apply_filters( 'roro_gacha_prizes', $prizes, $request );
        $prize  = $prizes[ array_rand( $prizes ) ];
        // Log the result.
        global $wpdb;
        $table = $wpdb->prefix . 'roro_gacha_log';
        // Some installations may not have the table yet; silence failures.
        $wpdb->insert( $table, [
            'prize_id'   => $prize['id'],
            'ip'         => $_SERVER['REMOTE_ADDR'] ?? '',
            'created_at' => current_time( 'mysql' ),
        ], [ '%d', '%s', '%s' ] );
        return rest_ensure_response( [
            'result'   => $prize['name'],
            'image'    => $prize['image'],
            'category' => $request->get_param( 'category' ),
        ] );
    }
}
