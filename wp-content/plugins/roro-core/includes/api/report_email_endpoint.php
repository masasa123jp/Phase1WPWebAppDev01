<?php
/**
 * Report email endpoint.
 *
 * Sends a report to the specified email address.  The request must
 * include an email and a report payload.  The report is formatted as a
 * simple text message and passed to wp_mail().  Authentication is
 * required to prevent abuse.  In a production system additional
 * validation and rate limiting would be appropriate.
 *
 * @package RoroCore\Api
 */

namespace RoroCore\Api;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

class Report_Email_Endpoint extends Abstract_Endpoint {
    public const ROUTE = '/report/email';

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
                    'email'  => [ 'type' => 'string', 'required' => true ],
                    'report' => [ 'type' => 'object', 'required' => true ],
                ],
            ],
        ] );
    }

    public static function handle( WP_REST_Request $request ) : WP_REST_Response|WP_Error {
        $email  = sanitize_email( $request->get_param( 'email' ) );
        $report = $request->get_param( 'report' );
        if ( ! is_email( $email ) ) {
            return new WP_Error( 'invalid_email', __( 'A valid email address is required.', 'roro-core' ), [ 'status' => 400 ] );
        }
        // Simple formatting of the report.  In future this could be an HTML template.
        $message = print_r( $report, true );
        $sent    = wp_mail( $email, __( 'Your RoRo Report', 'roro-core' ), $message );
        if ( ! $sent ) {
            return new WP_Error( 'email_failed', __( 'Failed to send email.', 'roro-core' ), [ 'status' => 500 ] );
        }
        return rest_ensure_response( [ 'success' => true ] );
    }
}
