<?php
declare( strict_types=1 );

namespace RoroCore;

class Assets {

	public static function init(): void {
		add_action( 'enqueue_block_editor_assets', [ self::class, 'editor' ] );
		add_action( 'wp_enqueue_scripts', [ self::class, 'frontend' ] );
	}

	public static function editor(): void {
		$dir = plugin_dir_url( __DIR__ ) . 'blocks/';

		wp_register_script(
			'roro-advice-card-editor',
			$dir . 'advice-card/index.js',
			[ 'wp-blocks', 'wp-element', 'wp-i18n', 'wp-data' ],
			'1.0.0',
			false
		);
		wp_register_script(
			'roro-gacha-wheel-editor',
			$dir . 'gacha-wheel/index.js',
			[ 'wp-blocks', 'wp-element', 'wp-i18n' ],
			'1.0.0',
			false
		);
	}

	public static function frontend(): void {
		$dir = plugin_dir_url( __DIR__ ) . 'blocks/';
		wp_register_script(
			'roro-gacha-wheel',
			$dir . 'gacha-wheel/frontend.js',
			[ 'wp-element' ],
			'1.0.0',
			true
		);
	}
}
Assets::init();
