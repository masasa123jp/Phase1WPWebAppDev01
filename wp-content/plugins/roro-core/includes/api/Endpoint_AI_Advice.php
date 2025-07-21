<?php
/**
 * AI アドバイス API
 *
 * @package RoroCore
 */

namespace RoroCore\Api;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use OpenAI;

class Endpoint_AI_Advice extends Abstract_Endpoint {

	const ROUTE = '/ai/advice';

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
						'question' => [ 'type' => 'string', 'required' => true ],
						'breed'    => [ 'type' => 'string', 'required' => false ],
					],
				],
			]
		);
	}

	public static function handle( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$options   = get_option( \RoroCore\Settings\General_Settings::OPTION_KEY );
		$api_key   = $options['openai_key'] ?? '';

		if ( empty( $api_key ) ) {
			return new WP_Error( 'no_api_key', __( 'OpenAI key not set.', 'roro-core' ), [ 'status' => 500 ] );
		}

		$client = OpenAI::client( $api_key ); // :contentReference[oaicite:10]{index=10}
		$result = $client->chat()->create(
			[
				'model'    => 'gpt-4o',
				'messages' => [
					[
						'role'    => 'system',
						'content' => 'You are a pet-care expert.',
					],
					[
						'role'    => 'user',
						'content' => $request['question'],
					],
				],
			]
		);

		return rest_ensure_response( [ 'answer' => $result->choices[0]->message->content ] );
	}
}

add_action( 'rest_api_init', [ Endpoint_AI_Advice::class, 'register' ] );
