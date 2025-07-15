<?php
declare( strict_types=1 );

namespace RoroCore\Admin;

use function add_menu_page;
use function add_submenu_page;

class Menu {

	/** Registers admin menus. */
	public static function init(): void {
		add_action( 'admin_menu', [ self::class, 'register' ] );
	}

	public static function register(): void {
		/* Top‑level */
		add_menu_page(
			__( 'RoRo Dashboard', 'roro-core' ),
			'RoRo',
			'manage_options',
			'roro-dashboard',
			[ self::class, 'render_dashboard' ],
			'dashicons-pets',
			3
		); // 🐾 Top‑level menu uses add_menu_page :contentReference[oaicite:0]{index=0}

		/* Settings page */
		add_submenu_page(
			'roro-dashboard',
			__( 'RoRo Settings', 'roro-core' ),
			__( 'Settings', 'roro-core' ),
			'manage_options',
			'roro-settings',
			[ Settings::class, 'render_page' ]
		);
	}

	public static function render_dashboard(): void {
		echo '<div class="wrap"><h1>RoRo Analytics</h1><p>概要ダッシュボードは Gutenberg ウィジェットを参照してください。</p></div>';
	}
}
Menu::init();
