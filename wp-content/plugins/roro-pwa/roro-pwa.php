<?php
/**
 * Plugin Name: RoRo PWA & Push
 * Description: PWA (Workbox) と Firebase Push を統合。
 * Version:     1.0.0
 * Author:      RoRo Dev Team
 * Text Domain: roro-pwa
 */

define( 'RORO_PWA_DIR', plugin_dir_path( __FILE__ ) );
define( 'RORO_PWA_URL', plugin_dir_url( __FILE__ ) );

require_once RORO_PWA_DIR . 'includes/class-pwa-loader.php';
