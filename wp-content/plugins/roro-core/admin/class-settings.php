<?php
declare( strict_types=1 );

namespace RoroCore\Admin;

use function register_setting;
use function add_settings_section;
use function add_settings_field;
use function get_option;

class Settings {

	private const OPT_GROUP = 'roro_core_options';
	private const OPT_NAME  = 'roro_core_general';

	public static function init(): void {
		add_action( 'admin_init', [ self::class, 'register' ] );
	}

	public static function register(): void {
		/* 1. DB option */
		register_setting(
			self::OPT_GROUP,
			self::OPT_NAME,
			[
				'type'              => 'array',
				'sanitize_callback' => [ self::class, 'sanitize' ],
				'default'           => [
					'liff_id'   => '',
					'api_token' => '',
				],
			]
		); // Settings API 登録 :contentReference[oaicite:2]{index=2}

		/* 2. Section */
		add_settings_section(
			'roro_general',
			__( 'General Settings', 'roro-core' ),
			'__return_false',
			'roro-settings'
		);

		/* 3. Fields */
		add_settings_field(
			'liff_id',
			__( 'LINE LIFF ID', 'roro-core' ),
			[ self::class, 'field_liff_id' ],
			'roro-settings',
			'roro_general'
		);

		add_settings_field(
			'api_token',
			__( 'External API Token', 'roro-core' ),
			[ self::class, 'field_api_token' ],
			'roro-settings',
			'roro_general'
		);
	}

	public static function sanitize( array $input ): array {
		return [
			'liff_id'   => sanitize_text_field( $input['liff_id'] ?? '' ),
			'api_token' => sanitize_text_field( $input['api_token'] ?? '' ),
		];
	}

	/* ---------- field callbacks ---------- */
	public static function field_liff_id(): void {
		$opt = get_option( self::OPT_NAME );
		printf(
			'<input type="text" name="%1$s[liff_id]" value="%2$s" class="regular-text" />',
			esc_attr( self::OPT_NAME ),
			esc_attr( $opt['liff_id'] ?? '' )
		);
	}

	public static function field_api_token(): void {
		$opt = get_option( self::OPT_NAME );
		printf(
			'<input type="password" name="%1$s[api_token]" value="%2$s" class="regular-text" />',
			esc_attr( self::OPT_NAME ),
			esc_attr( $opt['api_token'] ?? '' )
		);
	}

	/* ---------- page renderer ---------- */
	public static function render_page(): void {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'RoRo Settings', 'roro-core' ); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( self::OPT_GROUP );
				do_settings_sections( 'roro-settings' );
				submit_button();
				?>
			</form>
		</div>
	<?php
	}
}
Settings::init();
