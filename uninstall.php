<?php
/**
 * Uninstall cleanup.
 *
 * @package ZeroResultsFixer
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	return;
}

require_once dirname( __FILE__ ) . '/includes/class-zrf-store.php';

zrf_uninstall_cleanup();

/**
 * Remove every trace of the plugin.
 *
 * @global wpdb $wpdb
 * @return void
 */
function zrf_uninstall_cleanup() {
	global $wpdb;

	delete_option( 'zrf_fallback_category' );
	delete_option( 'zrf_fallback_enabled' );
	ZRF_Store::drop_table();
}
