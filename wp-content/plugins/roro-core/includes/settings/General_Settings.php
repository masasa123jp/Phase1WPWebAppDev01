<?php
/**
 * Plugin general settings.  Provides a simple settings page under
 * Settings → RoRo Core where API keys and other configuration values
 * can be stored.  Administrators can manage the Google Maps API key,
 * OpenAI key, Firebase/LIFF identifiers and FCM server key.  All values
 * are sanitised before saving.
 *
 * @package RoroCore\Settings
 */

namespace RoroCore\Settings;

class General_Settings {
    /**
     * Name of the option stored in wp_options.  This array contains
     * individual fields keyed by their identifiers.
     */
    public const OPTION_KEY = 'roro_core_options';

    /**
     * Initialise the settings.  Hooks registration functions into
     * WordPress.
     */
    public static function init() : void {
        add_action( 'admin_init', [ self::class, 'register_settings' ] );
        add_action( 'admin_menu', [ self::class, 'add_options_page' ] );
    }

    /**
     * Register the settings, section and fields.  Each field has its own
     * sanitisation callback.
     */
    public static function register_settings() : void {
        register_setting( self::OPTION_KEY, self::OPTION_KEY, [ 'sanitize_callback' => [ self::class, 'sanitize' ] ] );
        add_settings_section( 'api_keys', __( 'API Keys', 'roro-core' ), '__return_false', self::OPTION_KEY );
        add_settings_field( 'gmaps_key', __( 'Google Maps JS API Key', 'roro-core' ), [ self::class, 'text_field_cb' ], self::OPTION_KEY, 'api_keys', [ 'label_for' => 'gmaps_key' ] );
        add_settings_field( 'openai_key', __( 'OpenAI API Key', 'roro-core' ), [ self::class, 'text_field_cb' ], self::OPTION_KEY, 'api_keys', [ 'label_for' => 'openai_key' ] );
        add_settings_field( 'fcm_key', __( 'FCM Server Key', 'roro-core' ), [ self::class, 'text_field_cb' ], self::OPTION_KEY, 'api_keys', [ 'label_for' => 'fcm_key' ] );
        add_settings_field( 'liff_id', __( 'LINE LIFF ID', 'roro-core' ), [ self::class, 'text_field_cb' ], self::OPTION_KEY, 'api_keys', [ 'label_for' => 'liff_id' ] );
    }

    /**
     * Sanitise the settings on save.  Ensures all values are simple strings.
     *
     * @param array $input Raw input.
     *
     * @return array Sanitised output.
     */
    public static function sanitize( array $input ) : array {
        return [
            'gmaps_key'  => sanitize_text_field( $input['gmaps_key']  ?? '' ),
            'openai_key' => sanitize_text_field( $input['openai_key'] ?? '' ),
            'fcm_key'    => sanitize_text_field( $input['fcm_key']    ?? '' ),
            'liff_id'    => sanitize_text_field( $input['liff_id']    ?? '' ),
        ];
    }

    /**
     * Render a text field.  Reads the current option value and prints
     * an HTML input element.
     *
     * @param array $args Arguments passed by WordPress.
     */
    public static function text_field_cb( array $args ) : void {
        $options = get_option( self::OPTION_KEY );
        $key     = $args['label_for'];
        printf(
            '<input type="text" id="%1$s" name="%2$s[%1$s]" value="%3$s" class="regular-text" />',
            esc_attr( $key ),
            esc_attr( self::OPTION_KEY ),
            esc_attr( $options[ $key ] ?? '' )
        );
    }

    /**
     * Add the options page to the Settings menu.  The page itself is
     * rendered in the admin/Menu class for integration into the plugin
     * menu, but we still register it here for direct access via the
     * Settings menu.
     */
    public static function add_options_page() : void {
        add_options_page(
            __( 'RoRo Core Settings', 'roro-core' ),
            __( 'RoRo Core', 'roro-core' ),
            'manage_options',
            self::OPTION_KEY,
            [ self::class, 'render_page' ]
        );
    }

    /**
     * Render the settings page.  Wraps the standard settings API output
     * in a form.  Called from both the options page and the plugin
     * submenu.
     */
    public static function render_page() : void {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__( 'RoRo Core Settings', 'roro-core' ); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields( self::OPTION_KEY );
                do_settings_sections( self::OPTION_KEY );
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
}
