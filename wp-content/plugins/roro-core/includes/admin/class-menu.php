<?php
namespace RoroCore\Admin;
defined( 'ABSPATH' ) || exit;

/**
 * Adds “RoRo” top-level menu + subpages to WP Admin.
 */
final class Menu {

	public function __construct() {
		add_action( 'admin_menu', [ $this, 'register' ] );
	}

	public function register(): void {
		add_menu_page(
			__( 'RoRo', 'roro-core' ),
			'RoRo',
			'manage_options',
			'roro',
			[ $this, 'render_dashboard' ],
			'dashicons-pets',
			26
		);

		add_submenu_page(
			'roro',
			__( 'KPI Dashboard', 'roro-core' ),
			__( 'Dashboard', 'roro-core' ),
			'manage_options',
			'roro-dashboard',
			[ $this, 'render_dashboard' ]
		);

		add_submenu_page(
			'roro',
			__( 'Settings', 'roro-core' ),
			__( 'Settings', 'default' ),
			'manage_options',
			'roro-settings',
			[ Settings::class, 'render_page' ]
		);
	}

	public function render_dashboard(): void {
		echo '<div class="wrap"><h1>' . esc_html__( 'KPI Dashboard', 'roro-core' ) . '</h1>';
		echo '<div id="roro-admin-root"></div></div>'; // React mounts here
		wp_enqueue_script( 'roro-admin-bundle' );
	}
}
