<?php
/**
 *
 * @package   WP_Fragment_Cache
 * @author    Marius Dobre <mariuspass@gmail.com>
 * @license   GPL-2.0+
 * @link      http://wordpress.org/extend/plugins/
 *
 * @wordpress-plugin
 * Plugin Name:       WP Fragment Cache
 * Plugin URI:        https://github.com/mariuspass/WP-Fragment-Cache
 * Description:       Boost your page performance by caching individual page fragments.
 * Version:           1.0.4
 * Author:            Marius Dobre
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * GitHub Plugin URI: https://github.com/mariuspass/WP-Fragment-Cache
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/*----------------------------------------------------------------------------*
 * Public-Facing Functionality
 *----------------------------------------------------------------------------*/

require_once( plugin_dir_path( __FILE__ ) . 'public/class-wp-fragment-cache.php' );

/*
 * Register hooks that are fired when the plugin is activated or deactivated.
 * When the plugin is deleted, the uninstall.php file is loaded.
 */
register_activation_hook( __FILE__, array( 'WP_Fragment_Cache', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'WP_Fragment_Cache', 'deactivate' ) );


add_action( 'plugins_loaded', array( 'WP_Fragment_Cache', 'get_instance' ) );

/*----------------------------------------------------------------------------*
 * Dashboard and Administrative Functionality
 *----------------------------------------------------------------------------*/

if ( is_admin() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) {
	require_once( plugin_dir_path( __FILE__ ) . 'admin/class-wp-fragment-cache-admin.php' );
	add_action( 'plugins_loaded', array( 'WP_Fragment_Cache_Admin', 'get_instance' ) );

}
