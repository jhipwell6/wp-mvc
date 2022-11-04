<?php

/**
 * Plugin Name: WP MVC
 * Plugin URI: https://snowberrymedia.com/
 * Description: WP MVC Framework
 * Version: 0.2.5
 * Author: Snowberry Media
 * Author URI: https://snowberrymedia.com/
 * GitHub Plugin URI: jhipwell6/wp-mvc
 * Primary Branch: main
 *
 * Text Domain: wp-mvc
 */

if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Class WP_MVC_Loader
 */
class WP_MVC_Loader {
	/**
	 * Holds plugin file.
	 *
	 * @var $plugin_file
	 */
	private static $plugin_file = 'wp-mvc/wp-mvc.php';

	/**
	 * Let's get going.
	 * Load the plugin and hooks.
	 *
	 * @return void
	 */
	public function run() {
		define( 'WP_MVC_LOADER', true );
		require trailingslashit( WP_PLUGIN_DIR ) . self::$plugin_file;
		$this->load_hooks();
	}

	/**
	 * Load action and filter hooks.
	 *
	 * @return void
	 */
	public function load_hooks() {
		// Deactivate normal plugin as it's loaded as mu-plugin.
		add_action( 'activated_plugin', array( $this, 'deactivate' ), 10, 1 );

		/*
		* Remove links and checkbox from Plugins page so user can't delete main plugin.
		*/
		add_filter( 'network_admin_plugin_action_links_' . static::$plugin_file, array( $this, 'mu_plugin_active' ) );
		add_filter( 'plugin_action_links_' . static::$plugin_file, array( $this, 'mu_plugin_active' ) );
		add_action(
			'after_plugin_row_' . static::$plugin_file,
			function () {
				print '<script>jQuery(".inactive[data-plugin=\'wp-mvc/wp-mvc.php\']").attr("class", "active");</script>';
				print '<script>jQuery(".active[data-plugin=\'wp-mvc/wp-mvc.php\'] .check-column input").remove();</script>';
			}
		);
	}

	/**
	 * Deactivate if plugin in loaded not as mu-plugin.
	 *
	 * @param string $plugin Plugin slug.
	 */
	public function deactivate( $plugin ) {
		if ( static::$plugin_file === $plugin ) {
			deactivate_plugins( static::$plugin_file );
		}
	}

	/**
	 * Label as mu-plugin in plugin view.
	 *
	 * @param array $actions Link actions.
	 *
	 * @return array
	 */
	public function mu_plugin_active( $actions ) {
		if ( isset( $actions['activate'] ) ) {
			unset( $actions['activate'] );
		}
		if ( isset( $actions['delete'] ) ) {
			unset( $actions['delete'] );
		}
		if ( isset( $actions['deactivate'] ) ) {
			unset( $actions['deactivate'] );
		}

		return array_merge( array( 'mu-plugin' => esc_html__( 'Activated as mu-plugin', 'wp-mvc' ) ), $actions );
	}
}

( new WP_MVC_Loader() )->run();