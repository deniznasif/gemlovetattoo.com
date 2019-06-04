<?php

namespace WPaaS;

if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

final class WooCommerce {

	public function __construct() {

		// @todo: Use real plan name.
		if ( ! defined( 'GD_PLAN_NAME' ) || 'WooCommerce' !== GD_PLAN_NAME ) {

			return;

		}

		add_filter( 'cron_schedules', [ $this, 'wpaas_wc_register_cron_interval' ] );

		add_action( 'activated_plugin', [ $this, 'activated_plugin' ] );

		add_action( 'deactivated_plugin', [ $this, 'deactivated_plugin' ] );

		add_action( 'wpem_done', [ $this, 'register_site' ] );

		add_action( 'wpaas_wc_register', [ $this, 'register_site' ] );

	}

	/**
	 * Custom cron interval for WooCommerce plan register
	 *
	 * @since 3.9.4
	 *
	 * @return array Filtered cron schedules array.
	 */
	public function wpaas_wc_register_cron_interval( $schedules ) {

		if ( get_option( 'wpaas_wc_register' ) ) {

			return $schedules;

		}

		$schedules['wpaas_fifteen_minutes'] = [
			'interval' => 900,
			'display'  => esc_html__( 'Every Fifteen Minutes', 'gd-system-plugin' ),
		];

		return $schedules;

	}

	/**
	 * Given a plugin filename, will attempt to activate a subscription on WooCommerce.com for the
	 * plugin if it is a WooCommerce extension.
	 *
	 * @param string $filename The plugin filename.
	 *
	 * @return void
	 */
	public function activated_plugin( $filename ) {

		if ( ! $this->require_helper_files() ) {

			return;

		}

		\WC_Helper::activated_plugin( $filename );

	}

	/**
	 * Given a plugin filename, will attempt to deactivate a subscription on WooCommerce.com for the
	 * plugin if it is a WooCommerce extension.
	 *
	 * @param string $filename The plugin filename.
	 *
	 * @return void
	 */
	public function deactivated_plugin( $filename ) {

		if ( ! $this->require_helper_files() ) {

			return;

		}

		\WC_Helper::deactivated_plugin( $filename );

	}

	/**
	 * Will require all helper files if WooCommerce is active.
	 *
	 * @return boolean
	 */
	private function require_helper_files() {

		if ( ! class_exists( 'WooCommerce' ) || ! function_exists( 'WC' ) ) {

			return false;

		}

		try {

			foreach ( glob( WC()->plugin_path() . '/includes/admin/helper/*.php' ) as $helper_file ) {

				include_once( $helper_file );

			} // @codingStandardsIgnoreLine

		} catch ( Exception $e ) {

			return false;

		}

		return true;

	}

	/**
	 * Register the customers site with WooCommerce
	 *
	 * @since 3.9.4
	 *
	 * @return null|false Returns null when registration is successful, else returns false
	 */
	public function register_site() {

		$account_uid  = defined( 'GD_ACCOUNT_UID' ) ? GD_ACCOUNT_UID : null;
		$wp_com_token = get_option( 'wpaas_wp_com_token' );

		if ( get_option( 'wpaas_wc_register' ) || ! $wp_com_token || ! $account_uid ) {

			return;

		}

		$env    = Plugin::get_env();
		$prefix = ( 'prod' === $env ) ? '' : "{$env}-";

		$response = wp_remote_post(
			esc_url_raw( "https://mwp.api.phx3.{$prefix}secureserver.net/api/v1/mwp/sites/{$account_uid}/partner/a8c/register/woocommerce" ),
			[
				'timeout' => 30,
				'headers' => [
					'X-SITE-TOKEN' => $wp_com_token,
				],
			]
		);

		if ( is_wp_error( $response ) ) {

			$this->setup_wc_register_cron();

			return;

		}

		$status_code = wp_remote_retrieve_response_code( $response );

		if ( 200 !== $status_code ) {

			$this->setup_wc_register_cron();

			return;

		}

		wp_unschedule_event( wp_next_scheduled( 'wpaas_wc_register' ), 'wpaas_wc_register' );

		update_option( 'wpaas_wc_register', true );

	}

	/**
	 * Setup the WooCommerce site registration cron tasks
	 *
	 * @since 3.9.4
	 *
	 * @return null
	 */
	private function setup_wc_register_cron() {

		if ( wp_next_scheduled( 'wpaas_wc_register' ) ) {

			return;

		}

		wp_schedule_event( time(), 'wpaas_fifteen_minutes', 'wpaas_wc_register' );

	}

}
