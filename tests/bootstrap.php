<?php
/**
 * Standalone bootstrap: pure core (translit, resolver).
 */

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', '/wp/' );
}
if ( ! defined( 'ZRF_DIR' ) ) {
	define( 'ZRF_DIR', dirname( __DIR__ ) );
}
if ( ! defined( 'ZRF_VERSION' ) ) {
	define( 'ZRF_VERSION', '0.1.0-test' );
}

spl_autoload_register(
	static function ( $class ) {
		if ( 0 !== strpos( $class, 'ZRF_' ) ) {
			return;
		}
		$snake = strtolower( preg_replace( '/([a-z0-9])([A-Z])/', '$1-$2', substr( $class, 4 ) ) );
		$snake = str_replace( '_', '-', $snake );
		$file  = ZRF_DIR . '/includes/class-zrf-' . $snake . '.php';
		if ( is_readable( $file ) ) {
			require $file;
		}
	}
);
