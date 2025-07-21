<?php
/**
 * 一般設定ページ
 *
 * @package RoroCore
 */

namespace RoroCore\Settings;

class General_Settings {

	const OPTION_KEY = 'roro_core_options';

	public static function init(): void {
		add_action( 'admin_init', [ self::class, 'register_settings' ] );
		add_action( 'admin_menu', [ self::class, 'add_options_page' ] );
	}

	public static function register_settings(): void {
		register_setting(
			self::OPTION_KEY,
			self::OPTION_KEY,
			[ 'sanitize_callback' => [ self::class, 'sanitize' ] ]
		); // :contentReference[oaicite:8]{index=8}

		add_settings_section(
			'api_keys',
			__( 'API Keys', 'roro-core' ),
			'__return_false',
			self::OPTION_KEY
		);

		add_settings_field(
			'gmaps_key',
			__( 'Google Maps JS API Key', 'roro-core' ),
			[ self::class, 'text_field_cb' ],
			self::OPTION_KEY,
			'api_keys',
			[ 'label_for' => 'gmaps_key' ]
		);

		add_settings_field(
			'openai_key',
			__( 'OpenAI API Key', 'roro-core' ),
			[ self::class, 'text_field_cb' ],
			self::OPTION_KEY,
			'api_keys',
			[ 'label_for' => 'openai_key' ]
		);
	}

	public static function sanitize( array $input ): array {
		return [
			'gmaps_key'  => sanitize_text_field( $input['gmaps_key'] ?? '' ),
			'openai_key' => sanitize_text_field( $input['openai_key'] ?? '' ),
		];
	}

	public static function text_field_cb( array $args ): void {
		$options = get_option( self::OPTION_KEY );
		$key = $args['label_for'];
		printf(
			'<input type="text" id="%1$s" name="%2$s[%1$s]" value="%3$s" class="regular-text" />',
			esc_attr( $key ),
			esc_attr( self::OPTION_KEY ),
			esc_attr( $options[ $key ] ?? '' )
		);
	}

	public static function add_options_page(): void {
		add_options_page(
			'RoRo Core Settings',
			'RoRo Core',
			'manage_options',
			self::OPTION_KEY,
			[ self::class, 'render_page' ]
		);
	}

	public static function render_page(): void {
		?>
		<div class="wrap">
			<h1>RoRo Core Settings</h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( self::OPTION_KEY );
				do_settings_sections( self::OPTION_KEY );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}
}

General_Settings::init();
