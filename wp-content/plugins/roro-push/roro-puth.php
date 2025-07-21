<?php
/**
 * Plugin Name: RoRo Push Manager
 * Description: Firebase トークン保存 & Action Scheduler による Web Push 配信。
 * Version:     1.0.0
 */

define( 'RORO_PUSH_DIR', plugin_dir_path( __FILE__ ) );

require_once RORO_PUSH_DIR . 'includes/class-token-endpoint.php';
require_once RORO_PUSH_DIR . 'includes/class-sender.php';
