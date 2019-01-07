<?php

namespace WPaaS\Admin;

if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

final class Dashboard_WP5_Panel {

	/**
	 * Class constructor.
	 */
	public function __construct() {

		add_action( 'admin_init', [ $this, 'admin_init' ] );

		add_action( 'admin_print_scripts', [ $this, 'admin_print_scripts' ] );

		add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_scripts' ] );

		add_action( 'wp_ajax_dismiss_mwp_system_wp5_panel', [ $this, 'wp_ajax_dismiss_mwp_system_wp5_panel' ] );

	}

	/**
	 * Whether the panel should be displayed.
	 *
	 * @return bool
	 */
	private function show_panel() {

		global $current_screen;

		list( $base_wp_version ) = explode( '-', get_bloginfo( 'version' ) );

		if (
			// Must be viewing the WP Admin dashboard.
			is_admin() && isset( $current_screen->id ) && 'dashboard' === $current_screen->id
			&&
			// Must be running between WP 5.0 and 5.1.
			version_compare( $base_wp_version, '5.0', '>=' ) && version_compare( $base_wp_version, '5.1', '<' )
			&&
			// Must be a privileged Admin user.
			current_user_can( 'install_plugins' )
			&&
			// Must not already have the Classic Editor plugin active.
			! is_plugin_active( 'classic-editor/classic-editor.php' )
			&&
			// Must be an upgraded site that predates WP 5.0.
			glob( ABSPATH . '../data/wordpress.4.*' )
			&&
			// Must not have been dismissed.
			'0' !== get_user_meta( get_current_user_id(), 'show_mwp_system_wp5_panel', true )
		) {

			return true;

		}

		return false;

	}

	/**
	 * Fallback to dismiss panel via query string in case JavaScript is disabled.
	 *
	 * (Also a handy way to toggle the panel for testing: `?mwp_system_wp5_panel=1`)
	 *
	 * @action admin_init
	 */
	public function admin_init() {

		if ( isset( $_GET['mwp_system_wp5_panel'] ) && current_user_can( 'install_plugins' ) ) {

			update_user_meta( get_current_user_id(), 'show_mwp_system_wp5_panel', empty( $_GET['mwp_system_wp5_panel'] ) ? 0 : 1 );

		}

	}

	/**
	 * Styles and HTML template for the panel.
	 *
	 * @action admin_print_scripts
	 */
	public function admin_print_scripts() {

		if ( ! $this->show_panel() ) {

			return;

		}

		list( $base_wp_version ) = explode( '-', get_bloginfo( 'version' ) );

		$classic_editor_url = wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=classic-editor' ), 'install-plugin_classic-editor' );
		$classic_editor_button_text = __( 'Install the Classic Editor' );

		// If the plugin is already installed, then it must be inactive.
		if ( array_key_exists( 'classic-editor/classic-editor.php', get_plugins() ) ) {

			$classic_editor_url = wp_nonce_url( self_admin_url( 'plugins.php?action=activate&plugin=classic-editor/classic-editor.php&from=mwp-system-wp5-panel' ), 'activate-plugin_classic-editor/classic-editor.php' );
			$classic_editor_button_text = __( 'Activate Plugin' );

		}

		?>
		<style type="text/css">
			.mwp-system-wp5-panel h3 {
				margin: 17px 0 0;
				font-size: 16px;
				line-height: 1.4;
			}
			.mwp-system-wp5-panel-content hr {
				margin: 20px -23px 0 -23px;
				border-top: 1px solid #f3f4f5;
				border-bottom: none;
			}
			.mwp-system-wp5-panel img {
				margin-top: 23px;
				width: calc( 100% - 40px );
				border: 1px solid #f3f4f5;
			}
			.mwp-system-wp5-panel .mwp-system-wp5-panel-action .button.button-hero {
				margin: 0;
			}
			.mwp-system-wp5-panel .install-now.updating-message:before,
			.mwp-system-wp5-panel .install-now.updated-message:before {
				margin-top: 11px;
			}
			.mwp-system-wp5-panel .mwp-system-wp5-panel-column > * {
				padding-right: 40px;
			}
			.mwp-system-wp5-panel .mwp-system-wp5-panel-column-container {
				display: -ms-grid;
				display: grid;
				-ms-grid-columns: 36% 32% 32%;
				grid-template-columns: 36% 32% 32%;
				margin-bottom: 13px;
			}
			.mwp-system-wp5-panel .mwp-system-wp5-panel-column:not(.mwp-system-wp5-panel-image-column) {
				display: -ms-grid;
				display: grid;
				-ms-grid-rows: auto 100px;
				grid-template-rows: auto 100px;
			}
			.mwp-system-wp5-panel-column p {
				margin-top: 7px;
				color: #444;
			}
			@media screen and (max-width: 1024px) {
				.mwp-system-wp5-panel .mwp-system-wp5-panel-column-container {
					-ms-grid-columns: 50% 50%;
					grid-template-columns: 50% 50%;
				}
				.mwp-system-wp5-panel .mwp-system-wp5-panel-image-column {
					display: none;
				}
			}
			@media screen and (max-width: 870px) {
				.mwp-system-wp5-panel .mwp-system-wp5-panel-column-container {
					-ms-grid-columns: 100%;
					grid-template-columns: 100%;
				}
			}
		</style>
		<script id="mwp-system-wp5-panel-template" type="text/x-custom-template">
			<div id="mwp-system-wp5-panel" class="welcome-panel mwp-system-wp5-panel">
				<?php wp_nonce_field( 'mwp-system-wp5-panel-nonce', 'mwpsystemwp5panelnonce', false ); ?>
				<a class="welcome-panel-close mwp-system-wp5-panel-close" href="<?php echo esc_url( admin_url( '?mwp_system_wp5_panel=0' ) ); ?>"><?php _e( 'Dismiss' ); ?></a>
				<div class="welcome-panel-content mwp-system-wp5-panel-content">
					<h2><?php printf( __( 'Welcome to WordPress&nbsp;%s' ), $base_wp_version ); ?></h2>
					<p class="about-description"><?php printf( __( 'Thank you for updating to the latest version! WordPress %s introduces a robust new content creation experience.' ), $base_wp_version ); ?></p>
					<hr>
					<div class="mwp-system-wp5-panel-column-container">
						<div class="mwp-system-wp5-panel-column mwp-system-wp5-panel-image-column">
							<picture>
								<source srcset="about:blank" media="(max-width: 1024px)">
								<img src="https://s.w.org/images/core/gutenberg-screenshot.png">
							</picture>
						</div>
						<div class="mwp-system-wp5-panel-column plugin-card-gutenberg">
							<div>
								<h3><?php _e( 'Say Hello to the New Editor' ); ?></h3>
								<p><?php _e( 'You&#8217;ve successfully upgraded to WordPress 5.0! We’ve made some big changes to the editor. Our new block-based editor is the first step toward an exciting new future with a streamlined editing experience across your site. You’ll have more flexibility with how content is displayed, whether you are building your first site, revamping your blog, or write code for a living.' ); ?></p>
							</div>
							<div class="mwp-system-wp5-panel-action">
								<p><a class="button button-primary button-hero install-now" href="<?php echo esc_url( admin_url( 'post-new.php' ) ); ?>"><?php _e( 'Build your first post' ); ?></a></p>
							</div>
						</div>
						<div class="mwp-system-wp5-panel-column plugin-card-classic-editor">
							<div>
								<h3><?php _e( 'Keep it Classic' ); ?></h3>
								<p><?php _e( 'Prefer to stick with the familiar Classic Editor? No problem! Support for the Classic Editor plugin will remain in WordPress through 2021.' ); ?></p>
								<p><?php _e( 'Note to users of assistive technology: if you experience usability issues with the block editor, we recommend you continue to use the Classic Editor.' ); ?></p>
							</div>
							<div class="mwp-system-wp5-panel-action">
								<p><a class="button button-secondary button-hero" href="<?php echo esc_url( $classic_editor_url ); ?>" data-name="<?php esc_attr_e( 'Classic Editor' ); ?>" data-slug="classic-editor"><?php echo esc_html( $classic_editor_button_text ); ?></a></p>
							</div>
						</div>
					</div>
				</div>
			</div>
		</script>
		<?php

	}

	/**
	 * Enqueue inline jQuery to inject the panel and handle dismiss AJAX.
	 *
	 * @action admin_enqueue_scripts
	 */
	public function admin_enqueue_scripts() {

		if ( ! $this->show_panel() ) {

			return;

		}

		ob_start();
		?>
		jQuery(document).ready( function($) {
			$('#wpbody-content .wrap h1').after( $('#mwp-system-wp5-panel-template').html() );
			$('.mwp-system-wp5-panel-close').click( function(e) {
				e.preventDefault();
				var data = {
					action: 'dismiss_mwp_system_wp5_panel',
					mwpsystemwp5panelnonce: $('#mwpsystemwp5panelnonce').val()
				};
				$.post(ajaxurl, data, function() {
					$('#mwp-system-wp5-panel').remove();
				});
			});
		});
		<?php
		$jquery = ob_get_clean();

		wp_add_inline_script( 'jquery', $jquery );

	}

	/**
	 * Process AJAX request to dismiss the panel.
	 *
	 * @action wp_ajax_dismiss_mwp_system_wp5_panel
	 */
	public function wp_ajax_dismiss_mwp_system_wp5_panel() {

		check_ajax_referer( 'mwp-system-wp5-panel-nonce', 'mwpsystemwp5panelnonce' );

		update_user_meta( get_current_user_id(), 'show_mwp_system_wp5_panel', 0 );

		wp_die( 1 );

	}

}
