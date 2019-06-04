<?php

namespace WPaaS\Admin;

use \WPaaS\Plugin;

if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

final class Pages {

	/**
	 * Get our hidden tabs
	 */
	const HIDE_OPTION_KEY = 'wpaas_toplevel_page_hidden_tabs';

	/**
	 * Admin page slug.
	 *
	 * @var string
	 */
	private $slug = 'godaddy';

	/**
	 * Admin menu position.
	 *
	 * @var string
	 */
	private $position = '2.000001';

	/**
	 * Array of registered tabs.
	 *
	 * @var array
	 */
	private $tabs = [];

	/**
	 * Current tab slug.
	 *
	 * @var string
	 */
	private $tab;

	/**
	 * Class constructor
	 */
	public function __construct() {

		add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_scripts' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );

		if ( ! Plugin::is_gd() ) {

			return;

		}

		/**
		 * Filter the admin page slug.
		 *
		 * @since 2.0.0
		 *
		 * @var string
		 */
		$this->slug = (string) apply_filters( 'wpaas_admin_page_slug', $this->slug );

		/**
		 * Filter the admin page menu position.
		 *
		 * @since 2.0.0
		 *
		 * @var string
		 */
		$this->position = (string) apply_filters( 'wpaas_admin_page_menu_position', $this->position );

		add_action( 'init', [ $this, 'init' ] );
		add_action( 'init', [ $this, 'register_submenu_page' ] );
		add_filter( 'admin_body_class', [ $this, 'admin_body_class' ] );

	}

	/**
	 * Register tabs
	 *
	 * @action init
	 */
	public function init() {

		$this->tabs = [
			'help' => __( 'FAQ &amp; Support', 'gd-system-plugin' ),
		];

		/**
		 * Filter the admin page tabs.
		 *
		 * @since 2.0.0
		 *
		 * @var array
		 */
		$this->tabs = (array) apply_filters( 'wpaas_admin_page_tabs', $this->tabs );


		// Hide tabs specified by the user
		foreach ( get_option( self::HIDE_OPTION_KEY, [] ) as $key ) {

			if ( 'help' === $key ) {

				continue;

			}

			unset( $this->tabs[ $key ] );

		}

		$tab = filter_input( INPUT_GET, 'tab' );

		$this->tab = ! empty( $tab ) && array_key_exists( $tab, $this->tabs ) ? sanitize_key( $tab ) : $this->tab;

	}

	/**
	 * Enqueue styles needed for the admin bar.
	 *
	 * @action wp_enqueue_scripts
	 */
	public function enqueue_scripts() {

		if ( ! is_user_logged_in() ) {

			return;

		}

		$rtl    = is_rtl() ? '-rtl' : '';
		$suffix = SCRIPT_DEBUG ? '' : '.min';

		wp_enqueue_style( 'wpaas-admin', Plugin::assets_url( "css/admin{$rtl}{$suffix}.css" ), [], Plugin::version() );

	}

	/**
	 * Enqueue admin styles
	 *
	 * @action admin_enqueue_scripts
	 *
	 * @param string $hook
	 */
	public function admin_enqueue_scripts( $hook ) {

		$suffix = SCRIPT_DEBUG ? '' : '.min';

		wp_enqueue_style( 'wpaas-admin', Plugin::assets_url( "css/admin{$suffix}.css" ), [], Plugin::version() );

		if ( sprintf( 'admin_page_%s', $this->slug ) !== $hook ) {

			return;

		}

		if ( 'help' === $this->tab ) {

			wp_enqueue_script(
				'wpaas-iframeresizer',
				Plugin::assets_url( 'js/iframeResizer.min.js' ),
				[],
				'3.5.1',
				false
			);

			wp_enqueue_script(
				'wpaas-iframeresizer-ie8',
				Plugin::assets_url( 'js/iframeResizer.ie8.polyfils.min.js' ),
				[],
				'3.5.1',
				false
			);

			wp_script_add_data( 'wpaas-iframeresizer-ie8', 'conditional', 'lte IE 8' );

		}

	}

	/**
	 * Register submenu page
	 *
	 * @action init
	 */
	public function register_submenu_page() {

		if ( ! is_user_logged_in() ) {

			return;

		}

		/**
		 * Filter the user cap required to access the admin page.
		 *
		 * @since 2.0.0
		 *
		 * @var string
		 */
		$cap = (string) apply_filters( 'wpaas_admin_page_cap', 'activate_plugins' );

		global $submenu;

		$page_hook = add_submenu_page( 'admin.php',
			__( 'GoDaddy', 'gd-system-plugin' ),
			__( 'GoDaddy', 'gd-system-plugin' ),
			$cap,
			$this->slug,
			[ $this, 'render_menu_page' ]
		);

		// Bail early if we need to hide a page in an option
		add_action(
			'load-' . $page_hook,
			function() use ( $cap ) {

				if ( ! filter_input( INPUT_GET, 'hide' ) || ! current_user_can( $cap ) ) {

					return;

				}

				$option = get_option( self::HIDE_OPTION_KEY, [] );

				if ( ! isset( $option[ $this->tab ] ) ) {

					$option[] = $this->tab;

				}

				update_option( self::HIDE_OPTION_KEY, $option );

				wp_redirect( add_query_arg( 'tab', 'help', remove_query_arg( [ 'hide' ] ) ) ); // @codingStandardsIgnoreLine

				exit;

			}
		);

		foreach ( $this->tabs as $slug => $label ) {

			$parent_slug = $this->slug;

			$permalink = add_query_arg(
				[
					'page' => $this->slug,
					'tab'  => $slug,
				],
				self_admin_url( 'admin.php' )
			);

			$submenu[ $this->slug ][] = [ $label, $cap, $permalink ]; // @codingStandardsIgnoreLine

			$closure = function( $submenu_file, $parent_file ) use ( $parent_slug, $slug, $permalink, &$closure ) {

				if ( $parent_file === $parent_slug ) {

					if ( filter_input( INPUT_GET, 'tab' ) === $slug ) {

						$submenu_file = $permalink;

					}

					// No need to continue applying the filter once we found our parent
					remove_filter( 'submenu_file', $closure );
				}

				return $submenu_file;

			};

			add_filter( 'submenu_file', $closure, 10, 2 );

		}

	}

	/**
	 * Modify admin body classes
	 *
	 * @action admin_body_class
	 *
	 * @param  string $classes
	 *
	 * @return string
	 */
	public function admin_body_class( $classes ) {

		$classes = array_map( 'trim', explode( ' ', $classes ) );

		$classes[] = sprintf( '%s-tab-%s', esc_attr( $this->slug ), esc_attr( $this->tab ) );

		return implode( ' ', $classes );

	}

	/**
	 * Render menu page
	 */
	public function render_menu_page() {

		$suffix = SCRIPT_DEBUG ? '' : '.min';

		wp_enqueue_script( 'wpaas-pages', Plugin::assets_url( "js/wpaas-pages{$suffix}.js" ), [ 'jquery' ], Plugin::version(), false );

		wp_localize_script(
			'wpaas-pages',
			'wpaas_pages',
			[
				'confirm' => __( 'Are you sure? This cannot be undone.', 'gd-system-plugin' ),
			]
		);

		?>
		<div class="wrap">

			<?php
			$tab   = filter_input( INPUT_GET, 'tab', FILTER_SANITIZE_STRING );
			$title = ! $tab ? get_admin_page_title() : $this->tabs[ $tab ];
			?>

			<h1><?php echo esc_html( $title ); ?></h1>

			<?php

			if ( ! empty( $this->tabs ) ) :

				?>

				<h2 class="nav-tab-wrapper" style="display:none;">

					<?php foreach ( $this->tabs as $name => $label ) : ?>

						<a href="<?php echo esc_url( add_query_arg( [ 'tab' => $name ] ) ); ?>" class="nav-tab<?php if ( $this->tab === $name ) : ?> nav-tab-active<?php endif; ?>"><?php echo esc_html( $label ); // @codingStandardsIgnoreLine ?></a>

					<?php endforeach; ?>

				</h2>

				<?php

			endif;

			if ( isset( $this->tabs[ $this->tab ] ) && method_exists( $this, "render_menu_page_{$this->tab}" ) ) {

				$method = "render_menu_page_{$this->tab}";

				if ( is_callable( [ $this, $method ] ) ) {

					$this->$method();

				}

			}

			?>

		</div>

		<?php

	}

	public function render_menu_page_help() {

		$language  = get_option( 'WPLANG', 'www' );
		$parts     = explode( '_', $language );
		$subdomain = ! empty( $parts[1] ) ? strtolower( $parts[1] ) : strtolower( $language );

		// Overrides
		switch ( $subdomain ) {

			case '':
				$subdomain = 'www'; // Default

				break;

			case 'uk':
				$subdomain = 'ua'; // Ukrainian (Українська)

				break;

			case 'el':
				$subdomain = 'gr'; // Greek (Ελληνικά)

				break;
				
			case 'gb':
				$subdomain = 'uk'; // United Kingdom

				break;

		}

		?>
		<iframe src="<?php echo esc_url( "https://{$subdomain}.godaddy.com/help/managed-wordpress-1000021" ); ?>" frameborder="0" scrolling="no"></iframe>

		<script type="text/javascript">
			iFrameResize( {
				bodyBackground: 'transparent',
				checkOrigin: false,
				heightCalculationMethod: 'taggedElement'
			} );
		</script>
		<?php

	}

}
