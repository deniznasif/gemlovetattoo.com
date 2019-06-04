<?php

namespace WPaaS;

if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

final class Worker {

	/**
	 * Plugin basename.
	 *
	 * @var string
	 */
	const BASENAME = 'worker/init.php';

	/**
	 * Class constructor.
	 */
	public function __construct() {

		add_action( 'muplugins_loaded', [ $this, 'muplugins_loaded' ], -PHP_INT_MAX );
		add_action( 'init',             [ $this, 'init' ], PHP_INT_MAX );

		if ( filter_input( INPUT_GET, 'showWorker' ) ) {

			add_filter( 'all_plugins', [ $this, 'show_in_plugins_list' ], PHP_INT_MAX );

		}

	}

	/**
	 * Special behavior to run early on `muplugins_loaded`.
	 *
	 * @action muplugins_loaded - -PHP_INT_MAX
	 */
	public function muplugins_loaded() {

		$mu_plugin_file = trailingslashit( WPMU_PLUGIN_DIR ) . '0-worker.php';

		if ( is_readable( $mu_plugin_file ) ) {

			@unlink( $mu_plugin_file );

		}

		$plugin_file = trailingslashit( WP_PLUGIN_DIR ) . self::BASENAME;

		if ( is_readable( $plugin_file ) ) {

			$this->uninstall( $plugin_file );

		}

	}

	/**
	 * Special behavior to run at the very end of `init`.
	 *
	 * @action init - PHP_INT_MAX
	 */
	public function init() {

		$mmb_core = function_exists( 'mwp_core' ) ? mwp_core() : null;

		if ( is_a( $mmb_core, 'MMB_Core' ) ) {

			$this->remove_hook(
				[ 'admin_notices', [ $mmb_core, 'admin_notice' ] ],
				[ 'network_admin_notices', [ $mmb_core, 'network_admin_notice' ] ] // Multisite.
			);

		}

	}

	/**
	 * Show plugin in the admin list view.
	 *
	 * @filter all_plugins - PHP_INT_MAX
	 *
	 * @param  array $plugins
	 *
	 * @return array
	 */
	public function show_in_plugins_list( $plugins ) {

		$plugins[ self::BASENAME ] = get_plugin_data( Plugin::base_dir() . 'plugins/' . self::BASENAME );

		return $plugins;

	}

	/**
	 * Remove one or more hooked action or filter.
	 *
	 * @param array $... Variable list of param arrays to pass through `remove_filter()`.
	 */
	protected function remove_hook( $array ) {

		foreach ( func_get_args() as $params ) {

			if ( isset( $params[1] ) && is_callable( $params[1] ) ) {

				remove_filter( ...$params );

			}

		}

	}

	/**
	 * Ensure the plugin is deactivated and deleted.
	 *
	 * @param string $plugin_file
	 */
	private function uninstall( $plugin_file ) {

		if ( ! function_exists( 'is_plugin_active' ) ) {

			require_once ABSPATH . 'wp-admin/includes/plugin.php';

		}

		if ( is_plugin_active( plugin_basename( $plugin_file ) ) ) {

			deactivate_plugins( $plugin_file, true ); // Skip deactivation hooks.

		}

		if ( ! class_exists( 'WP_Filesystem' ) ) {

			require_once ABSPATH . 'wp-admin/includes/file.php';

		}

		$plugin_dir = escapeshellarg( dirname( $plugin_file ) );

		exec( "rm -rf {$plugin_dir} > /dev/null 2>/dev/null &" ); // Non-blocking.

	}

}
