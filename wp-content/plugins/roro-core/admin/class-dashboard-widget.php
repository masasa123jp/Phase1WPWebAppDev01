<?php
declare( strict_types=1 );

namespace RoroCore\Admin;

use function wp_remote_get;
use function wp_remote_retrieve_body;

class Dashboard_Widget {

	public static function init(): void {
		add_action( 'wp_dashboard_setup', [ self::class, 'register' ] );
	}

	public static function register(): void {
		wp_add_dashboard_widget(
			'roro_gacha_stats',
			__( 'RoRo 今日のガチャ統計', 'roro-core' ),
			[ self::class, 'render' ]
		); // Dashboard Widgets API :contentReference[oaicite:4]{index=4}
	}

	public static function render(): void {
		$response = wp_remote_get( home_url( '/wp-json/roro/v1/analytics' ) );
		if ( is_wp_error( $response ) ) {
			echo '<p>統計を取得できませんでした。</p>';
			return;
		}
		$data = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		echo '<ul style="list-style:disc;padding-left:1.2em">';
		echo '<li>本日のガチャ回数：' . esc_html( $data['today_spins'] ?? 'N/A' ) . '</li>';
		echo '<li>月間アクティブユーザー：' . esc_html( $data['mau'] ?? 'N/A' ) . '</li>';
		echo '</ul>';
	}
}
Dashboard_Widget::init();
