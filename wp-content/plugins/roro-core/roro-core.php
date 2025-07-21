<?php
/**
 * Plugin Name: RoRo Core (Refactored)
 * Description: Core functionality for the RoRo pet platform. Provides REST API endpoints, authentication, notifications and settings.  
 *              This refactored version consolidates duplicate code, corrects database table usage and adds a foundation for multilingual support via
 *              the WordPress internationalisation API.  
 * Version: 1.0.0
 * Author: Your Name
 * Text Domain: roro-core
 */

// Abort if accessed directly.
defined( 'ABSPATH' ) || exit;

/*
 * Define a few helpful constants.  These constants are used throughout the
 * plugin to build file system paths and URLs.  If you move the plugin
 * directory, WordPress will resolve these automatically on reload.
 */
define( 'RORO_CORE_VERSION', '1.0.0' );
define( 'RORO_CORE_DIR', plugin_dir_path( __FILE__ ) );
define( 'RORO_CORE_URL', plugin_dir_url( __FILE__ ) );

/**
 * Load the plugin textdomain for translations.  By default WordPress
 * will look in the `languages` directory for `.mo` files that correspond
 * to the current locale.  You can generate translation files using tools
 * like Poedit or Loco Translate.  See the documentation in
 * docs.i18n.wordpress.org for details.
 */
function roro_core_load_textdomain() {
    load_plugin_textdomain( 'roro-core', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'plugins_loaded', 'roro_core_load_textdomain' );

/**
 * Simple PSR‑4 autoloader.  WordPress does not ship with a PSR‑4 compliant
 * autoloader so we provide our own.  Any class under the `RoroCore` namespace
 * will be loaded from the `includes` directory automatically.  To add a new
 * class simply create a file that matches the namespace and class name.  For
 * example, `RoroCore\Api\Gacha_Endpoint` maps to
 * `includes/Api/Gacha_Endpoint.php`.
 */
/*
 * Register an autoloader for classes under the RoroCore namespace.  Many of the
 * original files in this project used mixed‑case filenames.  To support a
 * consistent lowercase naming convention on case‑sensitive file systems such
 * as Linux, this autoloader first attempts to load the class using the
 * canonical PSR‑4 mapping (matching the class name exactly).  If that file
 * does not exist it will fall back to a lowercase version of the path.  This
 * allows new modules to be saved with all‑lowercase filenames (e.g.
 * `includes/api/breed_list_endpoint.php`) while still supporting older
 * CamelCase files until they are migrated.
 */
spl_autoload_register( function ( $class ) {
    $prefix   = 'RoroCore\\';
    $base_dir = RORO_CORE_DIR . 'includes/';

    // Ensure the class uses our namespace prefix.
    $len = strlen( $prefix );
    if ( strncmp( $prefix, $class, $len ) !== 0 ) {
        return;
    }

    // Trim the namespace prefix and build a relative path.
    $relative_class = substr( $class, $len );
    $path           = str_replace( '\\', DIRECTORY_SEPARATOR, $relative_class );

    // Attempt to load a file matching the class name exactly.
    $file = $base_dir . $path . '.php';
    if ( file_exists( $file ) ) {
        require_once $file;
        return;
    }

    // Fall back to a lowercase path to support lowercase filenames.
    $lower_file = $base_dir . strtolower( $path ) . '.php';
    if ( file_exists( $lower_file ) ) {
        require_once $lower_file;
    }
} );

/**
 * Initialise the plugin.  Here we instantiate our various classes.  The
 * REST endpoint classes register themselves on construction.  Admin
 * components are only loaded in the WP admin area.  Notification services
 * are also initialised here.
 */
function roro_core_init() {
    // Authentication service registers its own routes.
    new RoroCore\Auth\Auth_Service();

    // REST API endpoints.  Each endpoint registers itself with the REST API on
    // construction.  New endpoints have been added to cover the full
    // functionality described in the screen transition diagram and to
    // consolidate duplicate implementations.
    new RoroCore\Api\Gacha_Endpoint();
    new RoroCore\Api\Facility_Search_Endpoint();
    new RoroCore\Api\Review_Endpoint();
    new RoroCore\Api\Photo_Upload_Endpoint();
    new RoroCore\Api\Ai_Advice_Endpoint();
    new RoroCore\Api\User_Profile_Endpoint();
    new RoroCore\Api\Analytics_Endpoint();
    // Newly added endpoints for report flow and management features.
    new RoroCore\Api\Breed_List_Endpoint();
    new RoroCore\Api\Issues_Endpoint();
    new RoroCore\Api\Report_Analysis_Endpoint();
    new RoroCore\Api\Report_Email_Endpoint();
    new RoroCore\Api\Sponsor_List_Endpoint();
    new RoroCore\Api\Sponsor_Detail_Endpoint();
    new RoroCore\Api\Ad_Approval_Endpoint();
    new RoroCore\Api\Payment_Endpoint();
    new RoroCore\Api\Facility_DB_Endpoint();
    new RoroCore\Api\Contact_Endpoint();
    new RoroCore\Api\Most_Used_Place_Endpoint();
    new RoroCore\Api\Repeat_Usage_Endpoint();
    new RoroCore\Api\Flow_Analysis_Endpoint();
    new RoroCore\Api\Ad_Access_Analysis_Endpoint();
    new RoroCore\Api\Download_Data_Endpoint();

    // Initialise language settings and locale management.  These classes
    // register WordPress hooks to provide a settings page and to honour
    // per‑user language preferences via the determine_locale filter.
    \RoroCore\Settings\Language_Settings::init();
    \RoroCore\Locale\User_Locale_Manager::init();

    // Admin UI.
    if ( is_admin() ) {
        new RoroCore\Admin\Menu();
        new RoroCore\Admin\Settings();
    }

    // Notification service (e.g. weekly advice).  In this refactored version
    // the Notification_Service registers its own scheduled events.
    new RoroCore\Notifications\Notification_Service();
}
add_action( 'init', 'roro_core_init' );
