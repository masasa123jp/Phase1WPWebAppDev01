<?php
/**
 * Endpoint to fetch the current user’s profile.  Returns the user ID,
 * display name, email and roles.  Requires authentication via the
 * default permission callback.
 *
 * @package RoroCore\Api
 */

namespace RoroCore\Api;

use WP_REST_Request;
use WP_REST_Response;

class User_Profile_Endpoint extends Abstract_Endpoint {
    public const ROUTE = '/me';

    public function __construct() {
        add_action( 'rest_api_init', [ $this, 'register' ] );
    }

    public static function register() : void {
        register_rest_route( 'roro/v1', self::ROUTE, [
            [
                'methods'             => 'GET',
                'callback'            => [ self::class, 'handle' ],
                'permission_callback' => [ self::class, 'permission_callback' ],
            ],
        ] );
    }

    public static function handle( WP_REST_Request $request ) {
        $user = wp_get_current_user();
        return rest_ensure_response( [
            'id'    => (int) $user->ID,
            'name'  => $user->display_name,
            'email' => $user->user_email,
            'roles' => $user->roles,
        ] );
    }
}
