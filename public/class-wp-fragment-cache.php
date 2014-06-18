<?php
/**
 * Plugin Name WP Fragment Cache
 *
 * @package   WP_Fragment_Cache
 * @author    Marius Dobre <mariuspass@gmail.com>
 * @license   GPL-2.0+
 * @link      https://github.com/mariuspass/WP-Fragment-Cache
 * @copyright 2014 Marius Dobre
 */

/**
 * Plugin class. This class should ideally be used to work with the
 * public-facing side of the WordPress site.
 *
 * @package WP_Fragment_Cache
 * @author  Marius Dobre <mariuspass@gmail.com>
 */
class WP_Fragment_Cache {

	/**
	 * Plugin version, used for cache-busting of style and script file references.
	 *
	 * @since   1.0.0
	 *
	 * @var     string
	 */
	const VERSION = '1.0.4';

	/**
	 * Php minimum version requirement.
	 *
	 * @since   1.0.4
	 *
	 * @var     string
	 */
	const PHP_MIN_VERSION = '5.3.6';

	/**
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $plugin_slug = 'wp-fragment-cache';

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Key prefix.
	 *
	 * @since    1.0.0
	 *
	 * @var string
	 */
	private $cache_key_prefix;

	/**
	 * Key prefix.
	 *
	 * @since    1.0.0
	 *
	 * @var string
	 */
	private $theme_directory;

	/**
	 * Current wp_query or block name.
	 *
	 * @since    1.0.0
	 *
	 * @var string
	 */
	private $current_query_value;

	/**
	 * Is plugin enabled.
	 *
	 * @since   1.0.0
	 *
	 * @var bool
	 */
	public $is_enabled = false;

	/**
	 * It meets the requirements.
	 *
	 * @since   1.0.4
	 *
	 * @var bool
	 */
	public $it_meets_the_requirements = false;

	/**
	 * Cache duration.
	 *
	 * @since   1.0.0
	 *
	 * @var int
	 */
	public $duration = DAY_IN_SECONDS;

	/**
	 * Initialize the plugin by setting the options.
	 *
	 * @since     1.0.0
	 */
	private function __construct() {
		// Activate plugin when new blog is added
		add_action( 'wpmu_new_blog', array( $this, 'activate_new_site' ) );

		if ( wp_using_ext_object_cache() && version_compare( PHP_VERSION, self::PHP_MIN_VERSION, '>=' ) ) {
			$this->it_meets_the_requirements = true;

			if ( get_option( 'wp_fragment_cache_is_enabled' ) ) {
				$this->is_enabled      = true;
				$this->theme_directory = get_stylesheet_directory() . DIRECTORY_SEPARATOR;
			}
		}
	}

	/**
	 * Return the plugin slug.
	 *
	 * @since    1.0.0
	 *
	 * @return string  WP_Fragment_Cache slug variable.
	 */
	public function get_plugin_slug() {
		return $this->plugin_slug;
	}

	/**
	 * Return an instance of this class.
	 *
	 * @since     1.0.0
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Fired when the plugin is activated.
	 *
	 * @since    1.0.0
	 *
	 * @param    boolean $network_wide True if WPMU superadmin uses
	 *                                       "Network Activate" action, false if
	 *                                       WPMU is disabled or plugin is
	 *                                       activated on an individual blog.
	 */
	public static function activate( $network_wide ) {

		if ( function_exists( 'is_multisite' ) && is_multisite() ) {

			if ( $network_wide ) {

				// Get all blog ids
				$blog_ids = self::get_blog_ids();

				foreach ( $blog_ids as $blog_id ) {

					switch_to_blog( $blog_id );
					self::single_activate();
				}

				restore_current_blog();

			} else {
				self::single_activate();
			}
		} else {
			self::single_activate();
		}

	}

	/**
	 * Fired when the plugin is deactivated.
	 *
	 * @since    1.0.0
	 *
	 * @param    boolean $network_wide True if WPMU superadmin uses
	 *                                       "Network Deactivate" action, false if
	 *                                       WPMU is disabled or plugin is
	 *                                       deactivated on an individual blog.
	 */
	public static function deactivate( $network_wide ) {

		if ( function_exists( 'is_multisite' ) && is_multisite() ) {

			if ( $network_wide ) {

				// Get all blog ids
				$blog_ids = self::get_blog_ids();

				foreach ( $blog_ids as $blog_id ) {

					switch_to_blog( $blog_id );
					self::single_deactivate();

				}

				restore_current_blog();

			} else {
				self::single_deactivate();
			}
		} else {
			self::single_deactivate();
		}

	}

	/**
	 * Fired when a new site is activated with a WPMU environment.
	 *
	 * @since    1.0.0
	 *
	 * @param    int $blog_id ID of the new blog.
	 */
	public function activate_new_site( $blog_id ) {

		if ( 1 !== did_action( 'wpmu_new_blog' ) ) {
			return;
		}

		switch_to_blog( $blog_id );
		self::single_activate();
		restore_current_blog();

	}

	/**
	 * Get all blog ids of blogs in the current network that are:
	 * - not archived
	 * - not spam
	 * - not deleted
	 *
	 * @since    1.0.0
	 *
	 * @return   array|false    The blog ids, false if no matches.
	 */
	private static function get_blog_ids() {

		global $wpdb;

		// get an array of blog ids
		$sql = "SELECT blog_id FROM $wpdb->blogs WHERE archived = '0' AND spam = '0' AND deleted = '0'";

		return $wpdb->get_col( $sql );

	}

	/**
	 * Fired for each blog when the plugin is activated.
	 *
	 * @since    1.0.0
	 */
	private static function single_activate() {
		if ( wp_using_ext_object_cache() && version_compare( PHP_VERSION, self::PHP_MIN_VERSION, '>=' ) ) {
			$is_enabled = get_option( 'wp_fragment_cache_is_enabled', 'no_value_set' );
			if ( $is_enabled === 'no_value_set' ) {
				update_option( 'wp_fragment_cache_is_enabled', true );
			}
		} else {
			// set is_enabled false regardless of what was before
			update_option( 'wp_fragment_cache_is_enabled', false );
			add_action( 'admin_notices', array( 'WP_Fragment_Cache_Admin', 'no_min_req_notice' ) );
		}
	}

	/**
	 * Fired for each blog when the plugin is deactivated.
	 *
	 * @since    1.0.0
	 */
	private static function single_deactivate() {
		self::purge();
	}


	/**
	 * Set the key prefix for current fragment.
	 * The key will be the file and the line where the call was initiated.
	 * The file is relative to theme directory.
	 * e.g. widgets/most-commented.php:18 or inc/left-sidebar.php:55
	 *
	 * @since    1.0.0
	 */
	private function _set_cache_key_prefix() {
		// PHP 5.3 compatibility fix, limit parameter was added in php 5.4.0
		if ( version_compare( PHP_VERSION, '5.4.0' ) >= 0 ) {
			$backtraceArray = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 3 );
		} else {
			$backtraceArray = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS );
		}

		// Remove the theme root
		$backtraceArray[2]['file'] = str_replace( $this->theme_directory, '', $backtraceArray[2]['file'] );

		$this->cache_key_prefix = $backtraceArray[2]['file'] . ':' . $backtraceArray[2]['line'];
	}


	/**
	 * Echo the fragment if it's cached and is valid or turn on output buffering.
	 *
	 * @param mixed $wp_query_or_blockname
	 * @param null|int $duration
	 *
	 * @since    1.0.0
	 *
	 * @return bool
	 */
	private function _output( $wp_query_or_blockname, $duration ) {
		$this->_set_cache_key_prefix();

		if ( ! is_string( $wp_query_or_blockname ) ) { //$wp_query_or_blockname is an object or an array
			$this->current_query_value = sha1( serialize( $wp_query_or_blockname ) );
		} else {
			$this->cache_key_prefix   .= '_' . $wp_query_or_blockname;
			$this->current_query_value = '';
		}

		$cachedQueryValue = wp_cache_get( $this->cache_key_prefix . '_key', __CLASS__ );

		if ( $cachedQueryValue !== false ) { //the key exists
			if ( $cachedQueryValue === $this->current_query_value ) { //it's the same
				if ( WP_DEBUG ) {
					echo "<!-- WP_Fragment_Cache from Key '" . $this->cache_key_prefix . "' --> \n";
				}

				echo wp_cache_get( $this->cache_key_prefix . '_content', __CLASS__ );

				if ( WP_DEBUG ) {
					echo "<!-- WP_Fragment_Cache from Key '" . $this->cache_key_prefix . "' --> \n";
				}

				return true;
			} else { //has changed
				// delete the key and the content
				wp_cache_delete( $this->cache_key_prefix . '_key', __CLASS__ );
				wp_cache_delete( $this->cache_key_prefix . '_content', __CLASS__ );
			}
		}

		if ( $duration !== null ) {
			if ( $duration === 'only_today' ) {
				$now            = time();
				$this->duration = strtotime( date( 'Y-m-d' ) . ' 23:59:59', $now ) - $now;
			} else {
				$this->duration = intval( $duration );
			}
		}

		ob_start();

		return false;

	}

	/**
	 * Store the fragment in cache and add the key to All_Fragments_Keys_Array
	 *
	 * @since   1.0.0
	 */
	private function _store() {
		wp_cache_set( $this->cache_key_prefix . '_content', ob_get_contents(), __CLASS__, $this->duration );
		wp_cache_set( $this->cache_key_prefix . '_key', $this->current_query_value, __CLASS__, $this->duration );

		$all_Fragments_Keys_Array = wp_cache_get( 'All_Fragments_Keys_Array', __CLASS__ );
		if ( $all_Fragments_Keys_Array === false ) {
			$all_Fragments_Keys_Array = array();
		}

		$all_Fragments_Keys_Array[] = $this->cache_key_prefix;
		wp_cache_set( 'All_Fragments_Keys_Array', $all_Fragments_Keys_Array, __CLASS__ );

	}

	/**
	 * Purge all the fragments.
	 *
	 * @since   1.0.0
	 *
	 */
	static function purge() {
		$all_Fragments_Keys_Array = wp_cache_get( 'All_Fragments_Keys_Array', __CLASS__ );
		if ( $all_Fragments_Keys_Array !== false ) {
			foreach ( $all_Fragments_Keys_Array as $key ) {
				wp_cache_delete( $key . '_key', __CLASS__ );
				wp_cache_delete( $key . '_content', __CLASS__ );
			}
			wp_cache_delete( 'All_Fragments_Keys_Array', __CLASS__ );
		}
	}

	/**
	 * Static wrapper for _output()
	 * @see _output()
	 *
	 * @since    1.0.0
	 *
	 * @param mixed $wp_query_or_blockname
	 * @param null|int $duration in seconds or null for default duration(1 day)
	 * (you can use WP Time Constants
	 * @link http://codex.wordpress.org/Transients_API#Using_Time_Constants )
	 *
	 * @return bool
	 *
	 */
	static function output( $wp_query_or_blockname = '', $duration = null ) {
		$instance = WP_Fragment_Cache::get_instance();

		if ( $instance->is_enabled ) {
			return $instance->_output( $wp_query_or_blockname, $duration );
		}

		return false;
	}

	/**
	 * Static wrapper for _store()
	 * @see _store()
	 *
	 * @since    1.0.0
	 */
	static function store() {
		$instance = WP_Fragment_Cache::get_instance();

		if ( $instance->is_enabled ) {
			$instance->_store();
		}
	}

}
