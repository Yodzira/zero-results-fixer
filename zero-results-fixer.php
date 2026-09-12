<?php
/**
 * Plugin Name:       Zero Results Fixer
 * Plugin URI:        https://github.com/Yodzira/zero-results-fixer
 * Description:       Fix empty search results: request→posts mappings, synonyms, keyboard-layout and translit tolerance. Works with native search on top of any theme.
 * Version:           0.1.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Yodzira
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       zero-results-fixer
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ZRF_VERSION', '0.1.0' );
define( 'ZRF_FILE', __FILE__ );
define( 'ZRF_DIR', __DIR__ );

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

add_action( 'plugins_loaded', array( 'ZRF_Plugin', 'boot' ), 20 );

register_activation_hook(
	__FILE__,
	static function () {
		require_once ZRF_DIR . '/includes/class-zrf-store.php';
		ZRF_Store::activate();
	}
);
