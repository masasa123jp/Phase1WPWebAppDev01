<?php
/**
 * ガチャ API
 *
 * @package RoroCore
 */

namespace RoroCore\Api;

use WP_REST_Request;
use WP_REST_Response;

class Endpoint_Gacha extends Abstract_Endpoint {

	const ROUTE = '/gacha';

	public static function register(): void {
		register_rest_route(
			'roro/v1',
			self::ROUTE,
			[
				[
					'methods'             => 'POST',
					'callback'            => [ self::class, 'handle' ],
					'permission_callback' => [ self::class, 'permission_callback' ],
					'args'                => [
						'category' => [
							'type'     => 'string',
							'required' => false,
						],
					],
				],
			]
		); // :contentReference[oaicite:4]{index=4}
	}

	public static function handle( WP_REST_Request $request ): WP_REST_Response {
		$prizes = [ 'おやつ', 'クーポン', 'トレーニング動画', '診療割引' ];
		$item   = $prizes[ array_rand( $prizes ) ];

		return new WP_REST_Response(
			[
				'result'   => $item,
				'category' => $request->get_param( 'category' ),
			],
			200
		);
	}
}

add_action( 'rest_api_init', [ Endpoint_Gacha::class, 'register' ] );
