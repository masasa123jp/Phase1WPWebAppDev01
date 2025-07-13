<?php
/**
 * 「RoRo 設定」タブを WP‑Admin › 設定に追加し、
 * - Google Maps API Key
 * - AdSense Publisher ID
 * - ガチャ確率テーブル (CSV)
 * を保存する。
 */
namespace RoroCore\Admin;

class Settings {

	public function __construct() {
		add_action( 'admin_init', [ $this, 'register_settings' ] );
		add_action( 'admin_menu', [ $this, 'menu' ] );
	}

	public function menu() {
		add_options_page( 'RoRo 設定', 'RoRo 設定', 'manage_options', 'roro-settings', [ $this, 'render' ] );
	}

	public function register_settings() {
		register_setting( 'roro_settings', 'roro_maps_key', [ 'type'=>'string', 'sanitize_callback'=>'sanitize_text_field' ] );
		register_setting( 'roro_settings', 'roro_adsense_id', [ 'type'=>'string', 'sanitize_callback'=>'sanitize_text_field' ] );
		register_setting( 'roro_settings', 'roro_gacha_table', [ 'type'=>'string', 'sanitize_callback'=>'wp_kses_post' ] );
	}

	public function render() {
		?>
		<div class="wrap">
			<h1>RoRo 設定</h1>
			<form method="post" action="options.php">
				<?php settings_fields( 'roro_settings' ); ?>
				<table class="form-table" role="presentation">
					<tr><th>Google Maps API Key</th>
						<td><input type="text" name="roro_maps_key" value="<?= esc_attr( get_option('roro_maps_key') ) ?>" class="regular-text" /></td></tr>
					<tr><th>AdSense Publisher ID</th>
						<td><input type="text" name="roro_adsense_id" value="<?= esc_attr( get_option('roro_adsense_id') ) ?>" class="regular-text" /></td></tr>
					<tr><th>ガチャ確率テーブル (CSV)</th>
						<td><textarea name="roro_gacha_table" rows="6" class="large-text code"><?= esc_textarea( get_option('roro_gacha_table') ) ?></textarea></td></tr>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}
