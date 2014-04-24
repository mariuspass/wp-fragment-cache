<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package   WP_Fragment_Cache
 * @author    Marius Dobre <mariuspass@gmail.com>
 * @license   GPL-2.0+
 * @link      https://github.com/mariuspass/WP-Fragment-Cache
 * @copyright 2014 Marius Dobre
 */

// If uninstall not called from WordPress, then exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

if ( is_multisite() ) {
	global $wpdb;
	$blogs = $wpdb->get_results( "SELECT blog_id FROM {$wpdb->blogs}", ARRAY_A );
	uninstall_wp_cache_block_delete_options_and_files( false );
	if ( $blogs ) {
		foreach ( $blogs as $blog ) {
			switch_to_blog( $blog['blog_id'] );
			uninstall_wp_cache_block_delete_options_and_files();
			restore_current_blog();
		}
	}
} else {
	uninstall_wp_cache_block_delete_options_and_files();
}

/**
 * @param bool $optimize_tables
 */
function uninstall_wp_cache_block_delete_options_and_files( $optimize_tables = true ) {
	delete_option( 'wp_fragment_cache_is_enabled' );
	//info: remove custom file directory for main site
	$upload_dir = wp_upload_dir();
	$directory  = $upload_dir['basedir'] . DIRECTORY_SEPARATOR . 'CUSTOM_DIRECTORY_NAME' . DIRECTORY_SEPARATOR;
	if ( is_dir( $directory ) ) {
		foreach ( glob( $directory . '*.*' ) as $v ) {
			unlink( $v );
		}
		rmdir( $directory );
	}

	if ( $optimize_tables ) {
		//info: optimize tables
		$GLOBALS['wpdb']->query( 'OPTIMIZE TABLE `' . $GLOBALS['wpdb']->prefix . 'options`' );
	}
}