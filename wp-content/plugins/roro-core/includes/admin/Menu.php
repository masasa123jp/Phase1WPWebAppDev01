<?php
/**
 * 管理画面メニュー
 *
 * @package RoroCore
 */

namespace RoroCore\Admin;

defined( 'ABSPATH' ) || exit;

class Menu {

	/**
	 * フック登録
	 */
	public static function init(): void {
		add_action( 'admin_menu', [ self::class, 'register_pages' ] );
	}

	/**
	 * トップレベル「RoRo KPI」を追加
	 */
	public static function register_pages(): void {
		add_menu_page(
			'RoRo KPI',
			'RoRo KPI',
			'manage_options',
			'roro-kpi',
			[ self::class, 'render_kpi_page' ],
			'dashicons-chart-line',
			3
		); // :contentReference[oaicite:2]{index=2}
	}

	/**
	 * KPI ページ描画
	 */
	public static function render_kpi_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.' ) );
		}

		echo '<div class="wrap"><h1>RoRo KPI Dashboard</h1>';
		echo '<p>Total Users: ' . esc_html( self::get_total_users() ) . '</p>';
		echo '<p>Total Facilities: ' . esc_html( self::get_total_facilities() ) . '</p>';
		echo '</div>';
	}

	protected static function get_total_users(): int {
		global $wpdb;
		return (int) $wpdb->get_var( "SELECT COUNT(ID) FROM {$wpdb->users}" );
	}

	protected static function get_total_facilities(): int {
		global $wpdb;
		$table = $wpdb->prefix . 'roro_facilities';
		return (int) $wpdb->get_var( "SELECT COUNT(id) FROM {$table}" );
	}
}

Menu::init();
