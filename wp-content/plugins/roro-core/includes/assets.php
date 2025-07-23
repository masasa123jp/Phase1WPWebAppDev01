<?php
/**
 * Module path: wp-content/plugins/roro-core/includes/assets.php
 *
 * ブロックエディタ用およびフロントエンド用のJavaScriptを登録し、翻訳ファイルを読み込むクラス。
 * wp_set_script_translations() を呼び出すことで wp.i18n.__() が有効になります。
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

    /**
     * ブロックエディタ用スクリプト登録。
     */
    public static function editor(): void {
        $base = plugins_url( '../blocks/', __FILE__ );
        wp_enqueue_script(
            'roro-gacha-wheel-editor',
            $base . 'gacha-wheel/index.js',
            [ 'wp-blocks', 'wp-i18n', 'wp-element' ],
            '1.1.0',
            false
        );
        wp_set_script_translations( 'roro-gacha-wheel-editor', 'roro-core' );
    }

    /**
     * フロントエンド用スクリプト登録。
     */
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
        wp_set_script_translations( 'roro-gacha-wheel', 'roro-core' );
    }
}
Assets::init();
