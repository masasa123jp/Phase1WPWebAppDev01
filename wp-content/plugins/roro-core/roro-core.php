<?php
/**
 * Plugin Name:  RoRo Core
 * Description : Core functionality for RoRo pet platform (Phase 1.5 / 1.6).
 * Version     : 0.5.0
 * Author      : XServer App Dev Team
 * License     : GPL-2.0+
 *
 * @package RoroCore
 */

defined( 'ABSPATH' ) || exit; // 直アクセス防止  :contentReference[oaicite:6]{index=6}

/* -------------------------------------------------------------------------
   1. PSR-4 Autoloader
   ------------------------------------------------------------------------- */
require_once __DIR__ . '/includes/class-loader.php';

$loader = new RoroCore\Loader();                     // 自前オートローダ  :contentReference[oaicite:7]{index=7}
$loader->addNamespace( 'RoroCore',       __DIR__ . '/includes' );
$loader->addNamespace( 'RoroCore\\API',  __DIR__ . '/api' );
$loader->addNamespace( 'RoroCore\\Cron', __DIR__ . '/includes/cron' ); // 追加で Cron 系も
$loader->addNamespace( 'RoroCore\\Admin',__DIR__ . '/includes/admin' );
$loader->register();

/* -------------------------------------------------------------------------
   2. Activation / Deactivation
   ------------------------------------------------------------------------- */
register_activation_hook( __FILE__,  __NAMESPACE__ . '\\activate' );   // :contentReference[oaicite:8]{index=8}
register_deactivation_hook( __FILE__, __NAMESPACE__ . '\\deactivate' );

function activate(): void {
	global $wpdb;

	// ── 2-1) 文字コードを安全に取得しテーブルを生成
	$charset = $wpdb->get_charset_collate();           // :contentReference[oaicite:9]{index=9}
	$table   = $wpdb->prefix . 'roro_photo_meta';

	$wpdb->query(
		"CREATE TABLE IF NOT EXISTS $table (
			id        BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			post_id   BIGINT UNSIGNED NOT NULL,
			meta_key  VARCHAR(255)  NOT NULL,
			meta_value LONGTEXT NULL
		) $charset"
	);

	// ── 2-2) Cron 登録（重複防止）
	if ( ! wp_next_scheduled( RoroCore\Cron\Scheduler::HOOK_WEEKLY_PUSH ) ) { // :contentReference[oaicite:10]{index=10}
		RoroCore\Cron\Scheduler::schedule_events();                          // :contentReference[oaicite:11]{index=11}
	}
}

function deactivate(): void {
	wp_clear_scheduled_hook( RoroCore\Cron\Scheduler::HOOK_WEEKLY_PUSH );
}

/* -------------------------------------------------------------------------
   3. ロードするサブシステム
   ------------------------------------------------------------------------- */
add_action( 'plugins_loaded', static function () {

	// REST API はエンドポイント各クラスが register_rest_route() を自己登録
	do_action( 'roro_core/register_endpoints' ); // 各 Endpoint_* クラスがここにフック

	// Cron 初期化
	RoroCore\Cron\Scheduler::init();

	// 管理画面メニューなど
	if ( is_admin() ) {
		if ( class_exists( 'RoroCore\\Admin\\Menu' ) ) {
			new RoroCore\Admin\Menu();
		}
	}
} );
