<?php
/**
 * Plugin Name: RoRo Core
 * Description : Phase 1.5/1.6 core functionality (REST API, Gutenberg blocks, cron, social login)
 * Version     : 0.1.0
 * Author      : RoRo Dev Team
 * License     : MIT
 *
 * @package RoroCore
 */

defined( 'ABSPATH' ) || exit;

/* -------------------------------------------------------------------------
   1. Autoloader
   ------------------------------------------------------------------------- */
require_once __DIR__ . '/includes/class-loader.php';
RoroCore\Loader::init();

/* -------------------------------------------------------------------------
   2. Activation / Deactivation hooks
   ------------------------------------------------------------------------- */
register_activation_hook( __FILE__, function () {
	RoroCore\Db\Schema::install();
} );

register_deactivation_hook( __FILE__, function () {
	// nothing to clean yet (keep data) – add wp_clear_scheduled_hook( … ) if needed
} );

/* -------------------------------------------------------------------------
   3. Load front/back components
   ------------------------------------------------------------------------- */
add_action( 'plugins_loaded', function () {
	// Admin pages
	if ( is_admin() ) {
		new RoroCore\Admin\Menu();
		new RoroCore\Admin\Settings();
	}

	// REST ルート
	add_action( 'rest_api_init', function () {
		global $wpdb;
		( new RoroCore\Auth\Auth_Controller( $wpdb ) )->register_routes();
		( new RoroCore\Api\Endpoint_Gacha( $wpdb ) )->register_routes();
		( new RoroCore\Api\Endpoint_Report( $wpdb ) )->register_routes();
		( new RoroCore\Api\Endpoint_Photo( $wpdb ) )->register_routes();
		( new RoroCore\Api\Endpoint_Facility_Search( $wpdb ) )->register_routes();
		( new RoroCore\Api\Endpoint_Breed_Stats( $wpdb ) )->register_routes();
		( new RoroCore\Api\Endpoint_Analytics( $wpdb ) )->register_routes();
		( new RoroCore\Api\Endpoint_Preference( $wpdb ) )->register_routes();
		( new RoroCore\Api\Endpoint_Geocode( $wpdb ) )->register_routes();
		( new RoroCore\Api\Endpoint_Dashboard( $wpdb ) )->register_routes();
	} );

	// Cron tasks
	RoroCore\Cron\Scheduler::init();
	RoroCore\Cron\Cleanup::init();

	// Gutenberg blocks & assets
	RoroCore\Post_Types::register();
	RoroCore\Meta::register();
} );
