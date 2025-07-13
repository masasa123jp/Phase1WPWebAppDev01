<?php
namespace RoroCore\Admin;
defined( 'ABSPATH' ) || exit;

/**
 * Enqueues React bundle + passes nonce + locale to JS.
 */
final class Dashboard {

	public function __construct() {
		add_action( 'admin_enqueue_scripts', [ $this, 'assets' ] );
	}

	public function assets(): void {
		$screen = get_current_screen();
		if ( ! $screen || $screen->base !== 'toplevel_page_roro-dashboard' ) {
			return;
		}

		$ver = filemtime( RORO_CORE_DIR . '/assets/build/index.js' );
		wp_register_script(
			'roro-admin-bundle',
			plugins_url( 'assets/build/index.js', RORO_CORE_FILE ),
			[ 'wp-api-fetch', 'wp-element' ],
			$ver,
			true
		);

		wp_localize_script( 'roro-admin-bundle', 'roroDash', [
			'nonce' => wp_create_nonce( 'wp_rest' ),
			'locale' => get_user_locale()
		] );
	}
}
