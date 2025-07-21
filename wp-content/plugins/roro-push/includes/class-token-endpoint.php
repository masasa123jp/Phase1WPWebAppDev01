<?php
namespace RoroPush;

class Token_Endpoint {

	const ROUTE = '/push/token';

	public static function init() {
		add_action( 'rest_api_init', [ self::class, 'register' ] );
	}

	public static function register() {
		register_rest_route(
			'roro/v1',
			self::ROUTE,
			[
				'methods'             => 'POST',
				'callback'            => [ self::class, 'handle' ],
				'permission_callback' => function () { return is_user_logged_in(); },
				'args'                => [
					'token' => [ 'type' => 'string', 'required' => true ],
				],
			]
		);
	}

	public static function handle( \WP_REST_Request $req ) {
		update_user_meta( get_current_user_id(), 'fcm_token', sanitize_text_field( $req['token'] ) );
		return rest_ensure_response( [ 'status' => 'saved' ] );
	}
}
Token_Endpoint::init();
