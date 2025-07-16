<?php
/**
 * RoRo Settings page.
 *
 * @package RoroCore\Admin
 */

declare( strict_types = 1 );

namespace RoroCore\Admin;

class Settings {

	private const GROUP = 'roro_options';
	private const NAME  = 'roro_core_general';

	public static function init(): void {
		add_action( 'admin_init', [ self::class, 'register' ] );
	}

	public static function register(): void {
		register_setting(
			self::GROUP,
			self::NAME,
			[
				'type'              => 'array',
				'sanitize_callback' => [ self::class, 'sanitize' ],
				'default'           => [
					'liff_id'   => '',
					'api_token' => '',
				],
			]
		);

		add_settings_section(
			'general',
			__( 'General Settings', 'roro-core' ),
			'__return_false',
			'roro-settings'
		);

		self::field( 'liff_id', __( 'LINE LIFF ID', 'roro-core' ) );
		self::field( 'api_token', __( 'External API Token', 'roro-core' ), true );
	}

	private static function field( string $key, string $label, bool $password = false ): void {
		add_settings_field(
			$key,
			$label,
			function () use ( $key, $password ) {
				$opt  = get_option( self::NAME );
				$type = $password ? 'password' : 'text';
				printf(
					'<input type="%1$s" name="%2$s[%3$s]" value="%4$s" class="regular-text" />',
					esc_attr( $type ),
					esc_attr( self::NAME ),
					esc_attr( $key ),
					esc_attr( $opt[ $key ] ?? '' )
				);
			},
			'roro-settings',
			'general'
		);
	}

	public static function sanitize( array $input ): array {
		return [
			'liff_id'   => sanitize_text_field( $input['liff_id'] ?? '' ),  // :contentReference[oaicite:9]{index=9}
			'api_token' => sanitize_text_field( $input['api_token'] ?? '' ),
		];
	}
}
Settings::init();
