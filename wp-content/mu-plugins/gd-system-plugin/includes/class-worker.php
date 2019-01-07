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

		add_action( 'init', [ $this, 'init' ], PHP_INT_MAX );

		add_filter( 'auto_update_plugin', [ $this, 'auto_update_plugin' ], PHP_INT_MAX, 2 );

	}

	/**
	 * Special behavior to run early on `muplugins_loaded`.
	 *
	 * @action muplugins_loaded - -PHP_INT_MAX
	 */
	public function muplugins_loaded() {

		$plugin_file    = trailingslashit( WP_PLUGIN_DIR ) . self::BASENAME;
		$mu_plugin_file = trailingslashit( WPMU_PLUGIN_DIR ) . '0-worker.php';

		// Reinstall if there is a must-use loader but the plugin is missing.
		if ( is_readable( $mu_plugin_file ) && ! is_readable( $plugin_file ) ) {

			self::install( self::BASENAME );

			return;

		}

		if ( ! is_readable( $mu_plugin_file ) && is_readable( $plugin_file ) && ! is_plugin_active( self::BASENAME ) ) {

			activate_plugin( $plugin_file );

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
	 * Keep the plugin up-to-date.
	 *
	 * @action auto_update_plugin - PHP_INT_MAX
	 *
	 * @param boolean $update Whether to update.
	 * @param object  $item   The plugin info.
	 */
	public function auto_update_plugin( $update, $item ) {

		return ( self::BASENAME === $item->plugin ) ? true : $update;

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
	 * Install the plugin (overwrites existing).
	 *
	 * This is an atomic operation with install failures limited
	 * to one retry per hour.
	 *
	 * @param string $plugin_base Plugin base file (eg: woocommerce/woocommerce.php)
	 * @param bool   $activate    Activate the plugin (defaults to true).
	 * @param string $archive_url The plugin archive URL (defaults to wp.org using slug)
	 */
	public static function install( $plugin_base, $activate = true, $archive_url = '' ) {

		$transient = 'wpaas_system_plugin_install-' . md5( $plugin_base );

		delete_site_transient( $transient );

		if ( ( defined( 'WP_CLI' ) && WP_CLI ) || $plugin_base === get_site_transient( $transient ) ) {

			return;

		}

		set_site_transient( $transient, $plugin_base, HOUR_IN_SECONDS ); // Limit failures to one retry per hour.

		if ( ! function_exists( 'download_url' ) ) {

			require_once ABSPATH . 'wp-includes/pluggable.php'; // download_url() > wp_tempnam() > wp_generate_password()
			require_once ABSPATH . 'wp-admin/includes/file.php'; // download_url()

		}

		$slug        = basename( dirname( $plugin_base ) );
		$archive_url = ( $archive_url ) ? $archive_url : "https://downloads.wordpress.org/plugin/{$slug}.zip";
		$archive     = download_url( $archive_url );

		if ( is_wp_error( $archive ) ) {

			error_log( sprintf( '%s %s', $archive->get_error_code(), $archive->get_error_message() ) );

			@unlink( $archive ); // @codingStandardsIgnoreLine

			return; // Allow retry once the transient expires.

		}

		WP_Filesystem();

		$result = unzip_file( $archive, WP_PLUGIN_DIR );

		@unlink( $archive ); // @codingStandardsIgnoreLine

		if ( is_wp_error( $result ) ) {

			error_log( sprintf( '%s %s', $result->get_error_code(), $result->get_error_message() ) );

			return; // Allow retry once the transient expires.

		}

		if ( $activate ) {

			if ( ! function_exists( 'activate_plugin' ) ) {

				require_once ABSPATH . 'wp-admin/includes/plugin.php';

			}

			activate_plugin( $plugin_base );

		}

		delete_site_transient( $transient );

	}
}
