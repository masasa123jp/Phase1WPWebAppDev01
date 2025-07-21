<?php
/**
 * Enqueue block/editor assets.
 *
 * @package RoroCore
 */

declare( strict_types = 1 );

namespace RoroCore;

class Assets {

    public static function init(): void {
        add_action( 'enqueue_block_editor_assets', [ self::class, 'editor' ] );
        add_action( 'wp_enqueue_scripts', [ self::class, 'frontend' ] );
    }

    public static function editor(): void {
        $base = plugins_url( '../blocks/', __FILE__ );

        wp_enqueue_script(
            'roro-gacha-wheel-editor',
            $base . 'gacha-wheel/index.js',
            [ 'wp-blocks', 'wp-i18n', 'wp-element' ],
            '1.1.0',
            false
        );
        // Load translations for the editor script.  WordPress will look up the
        // appropriate JSON file based on the current locale.  Without this
        // call the __() and _x() functions in JavaScript will return the
        // original strings.
        wp_set_script_translations( 'roro-gacha-wheel-editor', 'roro-core' );
    }

    public static function frontend(): void {
        $base = plugins_url( '../blocks/', __FILE__ );

        wp_enqueue_script(
            'roro-gacha-wheel',
            $base . 'gacha-wheel/frontend.js',
            [],
            '1.1.0',
            true
        );

        wp_localize_script( 'roro-gacha-wheel', 'wpRoro', [
            'rest_url' => esc_url_raw( rest_url( 'roro/v1/' ) ),
            'nonce'    => wp_create_nonce( 'wp_rest' ),
        ] );

        // Load translations for the front‑end script so that useLocale() and
        // wp.i18n functions can resolve strings based on the active language.
        wp_set_script_translations( 'roro-gacha-wheel', 'roro-core' );
    }
}
Assets::init();
