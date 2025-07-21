<?php
/**
 * REST Endpoint Base
 *
 * @package RoroCore
 */

namespace RoroCore\Api;

use WP_Error;
use WP_REST_Request;

abstract class Abstract_Endpoint {

	/**
	 * 各エンドポイントで route を登録する
	 */
	abstract public static function register(): void;

	/**
	 * 権限 + Nonce
	 */
	public static function permission_callback( WP_REST_Request $request ): bool|WP_Error {
		if ( ! is_user_logged_in() ) {
			return new WP_Error( 'rest_forbidden', __( 'Authentication required.', 'roro-core' ), [ 'status' => 401 ] );
		}

		if ( ! wp_verify_nonce( $request->get_header( 'X-WP-Nonce' ), 'wp_rest' ) ) {
			// Nonce 検証 :contentReference[oaicite:3]{index=3}
			return new WP_Error( 'rest_invalid_nonce', __( 'Invalid nonce.', 'roro-core' ), [ 'status' => 403 ] );
		}

		return current_user_can( 'read' );
	}
}
