// wp-content/plugins/roro-core/includes/class-loader.php
<?php
/**
 * Simple PSR‑4 loader for RoroCore namespace.
 */
spl_autoload_register( function ( $class ) {
	$prefix = 'RoroCore\\';
	if ( strpos( $class, $prefix ) !== 0 ) return;
	$path = __DIR__ . '/' . str_replace( '\\', '/', substr( $class, strlen( $prefix ) ) ) . '.php';
	if ( file_exists( $path ) ) require $path;
});
