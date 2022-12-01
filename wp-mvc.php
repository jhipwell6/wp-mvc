<?php

/**
 * Plugin Name: WP MVC
 * Plugin URI: https://snowberrymedia.com/
 * Description: WP MVC Framework
 * Version: 0.2.7
 * Author: Snowberry Media
 * Author URI: https://snowberrymedia.com/
 * GitHub Plugin URI: jhipwell6/wp-mvc
 * Primary Branch: main
 *
 * Text Domain: wp-mvc
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WP_MVC' ) ) :

	final class WP_MVC
	{
		/**
		 * @var string
		 */
		public $version = '0.2.7';

		/**
		 * @var string
		 */
		public $text_domain = 'wp-mvc';

		/**
		 * Query instance.
		 *
		 * @var Query
		 */
		public $query = null;

		/**
		 * @var WP_MVC The single instance of the class
		 * @since 0.1
		 */
		protected static $instance = null;

		/**
		 * Main Instance
		 *
		 * Ensures only one instance is loaded or can be loaded.
		 *
		 * @since 0.1
		 * @static
		 * @return WP_MVC - Main instance
		 */
		public static function instance()
		{
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		/**
		 * Constructor
		 */
		public function __construct()
		{
			$this->define_constants();

			/**
			 * Once plugins are loaded, initialize
			 */
			add_action( 'plugins_loaded', array( $this, 'setup' ), -20 );
			add_action( 'plugins_loaded', array( $this, 'late_setup' ), -10 );
		}

		/**
		 * Setup needed includes and actions for plugin
		 * @hooked plugins_loaded -20
		 */
		public function setup()
		{
			$this->includes();
			$this->init_hooks();
		}
		
		/**
		 * Setup optional includes and actions for plugin
		 * @hooked plugins_loaded -10
		 */
		public function late_setup()
		{
			$this->optional_includes();
		}

		/**
		 * Define WC Constants
		 */
		private function define_constants()
		{
			$upload_dir = wp_upload_dir();
			$this->define( 'WPMVC_PLUGIN_FILE', __FILE__ );
			$this->define( 'WPMVC_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
			$this->define( 'WPMVC_TEXT_DOMAIN', $this->text_domain );
			$this->define( 'WPMVC_VERSION', $this->version );
		}

		/**
		 * Define constant if not already set
		 * @param  string $name
		 * @param  string|bool $value
		 */
		private function define( $name, $value )
		{
			if ( ! defined( $name ) ) {
				define( $name, $value );
			}
		}

		/**
		 * What type of request is this?
		 * string $type ajax, frontend or admin
		 * @return bool
		 */
		public function is_request( $type )
		{
			switch ( $type ) {
				case 'admin' :
					return is_admin();
				case 'ajax' :
					return defined( 'DOING_AJAX' );
				case 'cron' :
					return defined( 'DOING_CRON' );
				case 'frontend' :
					return ( ! is_admin() || defined( 'DOING_AJAX' ) ) && ! defined( 'DOING_CRON' );
			}
		}

		/**
		 * Include required core files used in admin and on the frontend.
		 */
		public function includes()
		{
			// Helpers
			include_once $this->plugin_path() . '/includes/helpers/general-functions.php';
			include_once $this->plugin_path() . '/includes/helpers/formatting-functions.php';
			
			// Traits
			include_once $this->plugin_path() . '/includes/traits/import-trait.php';

			// Models
			// // WP_MVC\Models\Abstracts\Abstract_Model
			include_once $this->plugin_path() . '/includes/models/abstracts/Abstract_Model.php';
			// WP_MVC\Models\Abstracts\Post_Model
			include_once $this->plugin_path() . '/includes/models/abstracts/Post_Model.php';
			// WP_MVC\Models\Abstracts\Repeater_Model
			include_once $this->plugin_path() . '/includes/models/abstracts/Repeater_Model.php';
			// WP_MVC\Models\Abstracts\User_Model
			include_once $this->plugin_path() . '/includes/models/abstracts/User_Model.php';

			// Core
			include_once $this->plugin_path() . '/includes/core/action-queue.php';
			include_once $this->plugin_path() . '/includes/core/abstracts/factory.php';
			include_once $this->plugin_path() . '/includes/core/interfaces/service.php';
			include_once $this->plugin_path() . '/includes/core/abstracts/service.php';
			include_once $this->plugin_path() . '/includes/core/abstracts/query.php';
			
			// Controllers
			include_once $this->plugin_path() . '/includes/controllers/abstracts/mvc-controller-registry.php';
			
			// Libraries
			include_once $this->plugin_path() . '/libraries/league-csv/autoload.php';
			spl_autoload_register( require $this->plugin_path() . '/libraries/json-machine/autoloader.php' );

			// Other Libraries
			include_once $this->plugin_path() . '/libraries/autoload.php';
		}
		
		/**
		 * Myabe include optional files.
		 */
		public function optional_includes()
		{
			// Libraries
			if ( defined( 'WPMVC_ACTION_SCHEDULER_IS_ENABLED' ) && WPMVC_ACTION_SCHEDULER_IS_ENABLED ) {
				include_once $this->plugin_path() . '/libraries/action-scheduler/action-scheduler.php';
			}
		}

		/**
		 * Registers hooks to listen for during plugins_loaded
		 */
		public function init_hooks()
		{
			add_filter( 'action_scheduler_queue_runner_concurrent_batches', array( $this, 'limit_action_queue_batches' ), 999, 1 );
		}

		public function limit_action_queue_batches( $batches )
		{
			return 2;
		}
		
		/**
		 * Get queue instance.
		 *
		 * @return Action_Queue
		 */
		public function queue()
		{
			return \WP_MVC\Core\Action_Queue::instance();
		}

		/**
		 * Get the plugin url.
		 * @return string
		 */
		public function plugin_url()
		{
			return untrailingslashit( plugins_url( '/', __FILE__ ) );
		}

		/**
		 * Get the plugin path.
		 * @return string
		 */
		public function plugin_path()
		{
			return untrailingslashit( plugin_dir_path( __FILE__ ) );
		}

		/**
		 * Get Ajax URL.
		 * @return string
		 */
		public function ajax_url()
		{
			return admin_url( 'admin-ajax.php', 'relative' );
		}

		/**
		 * log information to the debug log
		 * @param  string|array $log [description]
		 * @return void
		 */
		public function debug_log()
		{
			$log_location = $this->plugin_path() . '/logs/wpmvc-debug.log';
			$datetime = new DateTime( 'NOW' );
			$timestamp = $datetime->format( 'Y-m-d H:i:s' );
			$args = func_get_args();
			$formatted = array_map( function ( $item ) {
				return print_r( $item, true );
			}, $args );
			array_unshift( $formatted, $timestamp );
			$joined = implode( ' ', $formatted ) . "\n";
			error_log( $joined, 3, $log_location );
		}

	}

	endif;

/**
 * Returns the main instance of WP_MVC to prevent the need to use globals.
 *
 * @since  0.1
 * @return WP_MVC
 */
function WP_MVC()
{
	return WP_MVC::instance();
}

WP_MVC();