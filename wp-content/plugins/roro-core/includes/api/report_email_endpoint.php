<?php
/**
 * レポートメール送信エンドポイント。
 *
 * 指定されたメールアドレスにレポートを送信します。リクエストには
 * メールアドレスとレポートのペイロードが含まれている必要があります。
 * レポートは単純なテキストメッセージとしてフォーマットされ、 wp_mail() に渡されます。
 * 濫用を防ぐため認証が必要です。実運用では追加の検証やレート制限が適切です。
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
        // レポートを単純なテキスト形式に整形します。将来的には HTML テンプレートになる可能性があります。
        $message = print_r( $report, true );
        $sent    = wp_mail( $email, __( 'Your RoRo Report', 'roro-core' ), $message );
        if ( ! $sent ) {
            return new WP_Error( 'email_failed', __( 'Failed to send email.', 'roro-core' ), [ 'status' => 500 ] );
        }
        return rest_ensure_response( [ 'success' => true ] );
    }
}
