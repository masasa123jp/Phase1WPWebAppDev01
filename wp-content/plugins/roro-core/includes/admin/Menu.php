<?php
/**
 * Admin menu registration.  Adds a top‑level menu and subpages for the
 * RoRo Core plugin in the WordPress admin dashboard.  The dashboard
 * page can be extended to show analytics or other summary information.
 *
 * @package RoroCore\Admin
 */

namespace RoroCore\Admin;

class Menu {
    public function __construct() {
        add_action( 'admin_menu', [ $this, 'register_menu' ] );
    }

    /**
     * Register the plugin menu and submenus.
     */
    public function register_menu() : void {
        add_menu_page(
            __( 'RoRo Dashboard', 'roro-core' ),
            __( 'RoRo', 'roro-core' ),
            'manage_options',
            'roro-core',
            [ $this, 'render_dashboard' ],
            'dashicons-pets',
            25
        );
        add_submenu_page(
            'roro-core',
            __( 'Settings', 'roro-core' ),
            __( 'Settings', 'roro-core' ),
            'manage_options',
            'roro-core-settings',
            [ $this, 'render_settings' ]
        );
    }

    /**
     * Output the dashboard page markup.  For now we simply display
     * placeholder text – you can replace this with actual analytics or
     * widgets.
     */
    public function render_dashboard() : void {
        echo '<div class="wrap"><h1>' . esc_html__( 'RoRo Dashboard', 'roro-core' ) . '</h1>';
        echo '<p>' . esc_html__( 'Welcome to the RoRo platform dashboard.', 'roro-core' ) . '</p>';
        echo '</div>';
    }

    /**
     * Output the settings page.  Delegates to the Settings class.
     */
    public function render_settings() : void {
        // The Settings class registers its own page via add_options_page,
        // but we call its render method here to embed within our menu.
        ( new Settings() )->render_page();
    }
}
