<?php
/**
 * お問い合わせエンドポイント。
 *
 * ユーザーがお問い合わせフォームを送信できるようにします。名前、メールアドレス、メッセージを受け取り、
 * wp_mail() でサイト管理者に内容を送信します。認証は不要ですが、スパム防止のためレート制限を検討してください。
 * 実際の実装では、後追い用に問い合わせ内容をデータベースに保存することが推奨されます。
 *
 * @package RoroCore\Api
 */

namespace RoroCore\Api;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

class Contact_Endpoint extends Abstract_Endpoint {
    public const ROUTE = '/contact';

    public function __construct() {
        add_action( 'rest_api_init', [ $this, 'register' ] );
    }

    public static function register() : void {
        register_rest_route( 'roro/v1', self::ROUTE, [
            [
                'methods'             => 'POST',
                'callback'            => [ self::class, 'handle' ],
                'permission_callback' => '__return_true',
                'args'                => [
                    'name'    => [ 'type' => 'string', 'required' => true ],
                    'email'   => [ 'type' => 'string', 'required' => true ],
                    'message' => [ 'type' => 'string', 'required' => true ],
                ],
            ],
        ] );
    }

    public static function handle( WP_REST_Request $request ) : WP_REST_Response|WP_Error {
        $name    = sanitize_text_field( $request->get_param( 'name' ) );
        $email   = sanitize_email( $request->get_param( 'email' ) );
        $message = wp_kses_post( $request->get_param( 'message' ) );
        if ( ! is_email( $email ) ) {
            return new WP_Error( 'invalid_email', __( 'Invalid email address.', 'roro-core' ), [ 'status' => 400 ] );
        }
        $subject = sprintf( __( 'Contact request from %s', 'roro-core' ), $name );
        $body    = "Name: {$name}\nEmail: {$email}\n\n{$message}";
        $sent    = wp_mail( get_option( 'admin_email' ), $subject, $body );
        if ( ! $sent ) {
            return new WP_Error( 'mail_failed', __( 'Failed to send message.', 'roro-core' ), [ 'status' => 500 ] );
        }
        return rest_ensure_response( [ 'success' => true ] );
    }
}
