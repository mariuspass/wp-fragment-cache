<?php
/**
 * Plugin Name WP Fragment Cache
 *
 * @package   WP_Fragment_Cache_Admin
 * @author    Marius Dobre <mariuspass@gmail.com>
 * @license   GPL-2.0+
 * @link      https://github.com/mariuspass/WP-Fragment-Cache
 * @copyright Marius Dobre
 */

/**
 * @package WP_Fragment_Cache_Admin
 * @author  Marius Dobre <mariuspass@gmail.com>
 */
class WP_Fragment_Cache_Admin {

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Slug of the plugin screen.
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $plugin_screen_hook_suffix = null;

	/**
	 * It meets the requirements.
	 *
	 * @since    1.0.4
	 *
	 * @var      bool
	 */
	protected $it_meets_the_requirements;

	/**
	 * Initialize the plugin by adding a settings page and menu.
	 *
	 * @since     1.0.0
	 */
	private function __construct() {
		$this->plugin_slug               = WP_Fragment_Cache::get_instance()->get_plugin_slug();
		$this->it_meets_the_requirements = WP_Fragment_Cache::get_instance()->it_meets_the_requirements;

		// Add the options page and menu item.
		add_action( 'admin_menu', array( $this, 'add_plugin_admin_menu' ) );

		// Add an action link pointing to the options page.
		$plugin_basename = plugin_basename( plugin_dir_path( realpath( dirname( __FILE__ ) ) ) . $this->plugin_slug . '.php' );
		add_filter( 'plugin_action_links_' . $plugin_basename, array( $this, 'add_action_links' ) );

		if ( $this->it_meets_the_requirements ) {
			// Intercept option save
			add_filter( 'pre_update_option_wp_fragment_cache_is_enabled', array( $this, 'on_is_enabled_option_update' ), 10, 2 );
		}

	}

	/**
	 * Fired when is_enabled option is updated and purge the cache if is disabled.
	 *
	 * @since     1.0.0
	 *
	 * @param $new_value
	 * @param $old_value
	 *
	 * @return mixed
	 */
	public function on_is_enabled_option_update( $new_value, $old_value ) {
		if ( $new_value == 0 && $new_value !== $old_value ) {
			WP_Fragment_Cache::purge();
		}

		return $new_value;
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
	 * Register the administration menu for this plugin into the WordPress Dashboard menu.
	 *
	 * @since    1.0.0
	 */
	public function add_plugin_admin_menu() {

		$this->plugin_screen_hook_suffix = add_options_page(
			__( 'WP Fragment Cache Settings', $this->plugin_slug ),
			__( 'WP Fragment Cache', $this->plugin_slug ),
			'manage_options',
			$this->plugin_slug,
			array( $this, 'display_plugin_admin_page' )
		);

		add_action( 'load-'.$this->plugin_screen_hook_suffix, array( $this, 'on_load_plugin_admin_page' ) );

		if ( $this->it_meets_the_requirements ) {
			//call register settings function
			add_action( 'admin_init', array( $this, 'register_my_settings' ) );
		} else {
			add_action( 'admin_notices', array( $this, 'no_min_req_notice' ) );
		}
	}


	/**
	 * Fired when the plugin settings page is loaded and check if it need to purge the cache.
	 *
	 * @since    1.0.0
	 *
	 */
	public function on_load_plugin_admin_page() {
		if ( isset( $_GET['wp_fragment_cache_nonce'] ) && isset ( $_GET['action'] ) ) {
			if ( wp_verify_nonce( $_GET['wp_fragment_cache_nonce'], 'purge' ) ) {
				switch ( $_GET['action'] ) {
					case 'purge':
						WP_Fragment_Cache::purge();
						$redirect_url = remove_query_arg( 'settings-updated' );
						$redirect_url = add_query_arg( array( 'action' => 'done' ), $redirect_url );
						wp_redirect( $redirect_url );
						break;
					case 'done':
						if ( ! isset ( $_GET['settings-updated'] ) ) {
							add_action( 'admin_notices', array( $this, 'purge_done_notice' ) );
						}
						break;
				}
			}
		}
	}


	/**
	 * Show the no persistent cache notice.
	 *
	 * @since    1.0.0
	 */
	public function no_min_req_notice() {
		if ( ! wp_using_ext_object_cache() ) {
			echo '<div class="error">
				    <p>WP Fragment Cache cannot be enabled, because you don\'t have any
				        <a target="_blank" href="https://codex.wordpress.org/Class_Reference/WP_Object_Cache#Persistent_Caching">
						<strong>persistent cache</strong></a>.
				    </p>
				  </div>';
		}

		if ( version_compare( PHP_VERSION, WP_Fragment_Cache::PHP_MIN_VERSION, '<' ) ) {
			echo '<div class="error">
					  <p>WP Fragment Cache requires PHP version ' . WP_Fragment_Cache::PHP_MIN_VERSION . ' or greater.
				    </p>
				  </div>';
		}
	}

	/**
	 * Show the cache purge notice.
	 *
	 * @since    1.0.0
	 */
	public function purge_done_notice() {
		echo '<div class="updated"><p>Purge initiated</p></div>';
	}


	/**
	 * Register the plugin settings.
	 *
	 * @since    1.0.0
	 */
	public function register_my_settings() {
		register_setting( 'wp-fragment-cache-settings-group', 'wp_fragment_cache_is_enabled' );
	}

	/**
	 * Render the settings page for this plugin.
	 *
	 * @since    1.0.0
	 */
	public function display_plugin_admin_page() {

		include_once( 'views/admin.php' );
	}

	/**
	 * Add settings action link to the plugins page.
	 *
	 * @since    1.0.0
	 */
	public function add_action_links( $links ) {

		return array_merge(
			array(
				'settings' => '<a href="' . admin_url( 'options-general.php?page=' . $this->plugin_slug ) . '">' . __( 'Settings', $this->plugin_slug ) . '</a>'
			),
			$links
		);
	}

}
