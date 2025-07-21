<?php
namespace RoroPWA;

class Loader {

	public static function init() {
		add_action( 'init',                 [ self::class, 'add_rewrites' ] );
		add_filter( 'query_vars',           [ self::class, 'query_vars' ] );
		add_action( 'template_redirect',    [ self::class, 'serve_assets' ] );
		add_action( 'wp_enqueue_scripts',   [ self::class, 'enqueue_sw' ] );
	}

	/** /sw.js と /manifest.json を仮想的に配信 */
	public static function add_rewrites() {
		add_rewrite_rule( '^sw\.js$', 'index.php?roropwa=sw', 'top' );
		add_rewrite_rule( '^manifest\.json$', 'index.php?roropwa=manifest', 'top' );
	}

	public static function query_vars( $vars ) {
		$vars[] = 'roropwa';
		return $vars;
	}

	public static function serve_assets() {
		$type = get_query_var( 'roropwa' );
		if ( ! $type ) {
			return;
		}

		if ( 'sw' === $type ) {
			header( 'Content-Type: application/javascript' );
			readfile( RORO_PWA_DIR . 'public/sw.js' );
		} elseif ( 'manifest' === $type ) {
			header( 'Content-Type: application/manifest+json' );
			readfile( RORO_PWA_DIR . 'public/manifest.json' );
		}
		exit;
	}

	public static function enqueue_sw() {
		// scope='/' で登録
		wp_register_script(
			'roro-register-sw',
			RORO_PWA_URL . 'public/register-sw.js',
			[],
			'1.0',
			true
		);
		wp_enqueue_script( 'roro-register-sw' );
	}
}
Loader::init();
