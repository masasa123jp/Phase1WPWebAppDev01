<?php
/**
 * Language settings page.
 *
 * Registers a new settings page under the standard WordPress “Settings”
 * menu where administrators can choose the default language for the
 * application and enable/disable supported languages.  The selected
 * default language is stored as an option and used when no user‑specific
 * preference is available.  See `RoroCore\Locale\User_Locale_Manager` for
 * how the locale is determined at runtime.
 *
 * @package RoroCore\Settings
 */

namespace RoroCore\Settings;

class Language_Settings {
    /**
     * Option name used to store language preferences.
     */
    public const OPTION_KEY = 'roro_core_language';

    /**
     * Hook into WordPress to register settings and menu entries.
     */
    public static function init() : void {
        add_action( 'admin_init', [ self::class, 'register_settings' ] );
        add_action( 'admin_menu', [ self::class, 'add_options_page' ] );
    }

    /**
     * Register the setting, sections and fields for language selection.
     */
    public static function register_settings() : void {
        register_setting( self::OPTION_KEY, self::OPTION_KEY, [ 'sanitize_callback' => [ self::class, 'sanitize' ] ] );
        add_settings_section( 'languages', __( 'Language Settings', 'roro-core' ), '__return_false', self::OPTION_KEY );
        add_settings_field( 'default_language', __( 'Default Language', 'roro-core' ), [ self::class, 'select_field_cb' ], self::OPTION_KEY, 'languages', [ 'label_for' => 'default_language' ] );
    }

    /**
     * Sanitise the input before saving.  Ensures that the selected
     * language code is one of our supported values.
     *
     * @param array $input Raw input from the settings form.
     *
     * @return array Sanitised values.
     */
    public static function sanitize( array $input ) : array {
        $supported = [ 'ja', 'en_US', 'zh_CN', 'ko' ];
        $lang      = $input['default_language'] ?? 'ja';
        if ( ! in_array( $lang, $supported, true ) ) {
            $lang = 'ja';
        }
        return [ 'default_language' => sanitize_text_field( $lang ) ];
    }

    /**
     * Render the select dropdown for choosing the default language.
     *
     * @param array $args Arguments passed by WordPress (unused).
     */
    public static function select_field_cb( array $args ) : void {
        $options = get_option( self::OPTION_KEY );
        $current = $options['default_language'] ?? 'ja';
        $langs   = [
            'ja'    => __( 'Japanese', 'roro-core' ),
            'en_US' => __( 'English',  'roro-core' ),
            'zh_CN' => __( 'Chinese',  'roro-core' ),
            'ko'    => __( 'Korean',   'roro-core' ),
        ];
        echo '<select id="default_language" name="' . esc_attr( self::OPTION_KEY ) . '[default_language]">';
        foreach ( $langs as $code => $label ) {
            printf( '<option value="%1$s" %2$s>%3$s</option>', esc_attr( $code ), selected( $current, $code, false ), esc_html( $label ) );
        }
        echo '</select>';
    }

    /**
     * Add our options page under the Settings menu.
     */
    public static function add_options_page() : void {
        add_options_page(
            __( 'Language Settings', 'roro-core' ),
            __( 'Language', 'roro-core' ),
            'manage_options',
            self::OPTION_KEY,
            [ self::class, 'render_page' ]
        );
    }

    /**
     * Output the settings page markup.
     */
    public static function render_page() : void {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__( 'Language Settings', 'roro-core' ); ?></h1>
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
