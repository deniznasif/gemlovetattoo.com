<?php
/**
 * Builder Elements Class.
 *
 * @package Fusion-Library
 * @since 1.1.0
 */

// Do not allow directly accessing this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit( 'Direct script access denied.' );
}

/**
 * Builder Elements Class.
 *
 * @since 1.1.0
 */
class Fusion_Builder_Gutenberg {

	/**
	 * Class constructor.
	 *
	 * @since 2.0
	 * @access public
	 */
	public function __construct() {
		if ( function_exists( 'gutenberg_can_edit_post' ) ) {
			add_action( 'admin_init', array( $this, 'init' ), 10 );
		}
	}

	/**
	 * Admin init.
	 *
	 * @since 2.0
	 * @access public
	 * @return void
	 */
	public function init() {
		global $typenow, $pagenow;

		$post_type = $typenow;
		if ( 'edit.php' === $pagenow && '' === $typenow ) {
			$post_type = 'post';
		}

		if ( is_admin() && $this->is_fb_enabled( $post_type ) ) {

			// Alter the add new dropdown.
			add_action( 'admin_print_footer_scripts-edit.php', array( $this, 'edit_dropdown' ), 10 );

			// Remove the edit links.
			remove_filter( 'page_row_actions', 'gutenberg_add_edit_link', 10, 2 );
			remove_filter( 'post_row_actions', 'gutenberg_add_edit_link', 10, 2 );

			// Add gutenberg edit link.
			add_filter( 'page_row_actions', array( $this, 'add_edit_link' ), 10, 2 );
			add_filter( 'post_row_actions', array( $this, 'add_edit_link' ), 10, 2 );
		}

		// Make sure G only loads with get variable if FB is new default.
		remove_filter( 'replace_editor', 'gutenberg_init', 10, 2 );
		add_filter( 'replace_editor', array( $this, 'replace_gutenberg' ), 99, 2 );

		add_action( 'admin_print_footer_scripts-post-new.php', array( $this, 'fb_editor_button' ), 10 );
		add_action( 'admin_print_footer_scripts-post.php', array( $this, 'fb_editor_button' ), 10 );
	}

	/**
	 * Adds fb editor button to gutenberg page.
	 *
	 * @since 2.0
	 * @access public
	 * @return void
	 */
	public function fb_editor_button() {
		global $post_type, $post;
		if ( isset( $_GET['gutenberg-editor'] ) && $this->is_fb_enabled( $post_type ) && is_object( $post ) ) {
			$editor_label = esc_attr__( 'Edit With Fusion Builder', 'fusion-builder' );
			$button       = '<a href="' . get_edit_post_link( $post->ID, 'raw' ) . '" id="fusion_builder_switch" class="button button-primary button-large">' . $editor_label . '</a>'; // WPCS: XSS ok.
			?>
			<script type="text/javascript">
			jQuery( window ).load( function() {
				var $toolbar = jQuery( '.edit-post-header-toolbar' );

				if ( $toolbar.length ) {
					$toolbar.append( '<?php echo $button; // WPCS: XSS ok. ?>' );
				}
			} );
			</script>
			<?php
		}
	}

	/**
	 * Checks if Gutenberg should be disabled.
	 *
	 * @since 2.0
	 * @access public
	 * @param  bool   $return Whether to replace the editor. Used in the `replace_editor` filter.
	 * @param  object $post   The post to edit or an auto-draft.
	 * @return bool   Whether Gutenberg was initialized.
	 */
	public function replace_gutenberg( $return, $post ) {
		global $post_type;

		if ( ( isset( $_GET['gutenberg-editor'] ) || ! $this->is_fb_enabled( $post_type ) ) && function_exists( 'gutenberg_init' ) ) { // WPCS: CSRF ok.
			return gutenberg_init( $return, $post );
		}
		return false;
	}

	/**
	 * Adds edit link for Gutenberg.
	 *
	 * @since 2.0
	 * @access public
	 * @param  array   $actions Post actions.
	 * @param  WP_Post $post    Edited post.
	 *
	 * @return array          Updated post actions.
	 */
	public function add_edit_link( $actions, $post ) {
		if ( ! gutenberg_can_edit_post( $post ) ) {
			return $actions;
		}

		$edit_url = get_edit_post_link( $post->ID, 'raw' );
		$edit_url = add_query_arg( 'gutenberg-editor', '', $edit_url );

		// Build the classic edit action. See also: WP_Posts_List_Table::handle_row_actions().
		$title       = _draft_or_post_title( $post->ID );
		$edit_action = array(
			'classic' => sprintf(
				'<a href="%s" aria-label="%s">%s</a>',
				esc_url( $edit_url ),
				esc_attr(
					sprintf(
						/* translators: %s: post title */
						__( 'Edit &#8220;%s&#8221; in the Gutenberg editor', 'fusion-builder' ),
						$title
					)
				),
				__( 'Gutenberg Editor', 'fusion-builder' )
			),
		);

		// Insert the Gutenberg Edit action after the Edit action.
		$edit_offset = array_search( 'edit', array_keys( $actions ), true );
		$actions     = array_merge(
			array_slice( $actions, 0, $edit_offset + 1 ),
			$edit_action,
			array_slice( $actions, $edit_offset + 1 )
		);

		return $actions;
	}

	/**
	 * Check if FB is activated for the post type.
	 *
	 * @since 2.0
	 * @access public
	 * @param string $post_type Post type to check.
	 * @return bool
	 */
	public function is_fb_enabled( $post_type ) {

		$options = get_option( 'fusion_builder_settings', array() );

		if ( ! empty( $options ) && isset( $options['post_types'] ) ) {
			// If there are options saved, used them.
			$post_types = ( ' ' === $options['post_types'] ) ? array() : $options['post_types'];
			// Add fusion_element to allowed post types ( bc ).
			$post_types[] = 'fusion_element';
			$activated    = apply_filters( 'fusion_builder_allowed_post_types', $post_types );
		} else {
			// Otherwise use defaults.
			$activated = FusionBuilder::default_post_types();
		}

		if ( $post_type ) {
			return in_array( $post_type, $activated, true );
		}
		return false;
	}

	/**
	 * Add edit link for fusion builder.
	 *
	 * @since 2.0
	 * @access public
	 * @return void
	 */
	public function edit_dropdown() {
		global $typenow;

		if ( ! gutenberg_can_edit_post_type( $typenow ) ) {
			return;
		}

		$edit          = 'post' !== $typenow ? 'post-new.php?post_type=' . $typenow : 'post-new.php';
		$gutenberg_url = add_query_arg( 'gutenberg-editor', '', $edit );
		$builder       = sprintf( '<a href="%s">%s</a>', esc_url_raw( admin_url( $edit ) ), esc_attr__( 'Fusion Builder', 'fusion-builder' ) );
		?>
		<script type="text/javascript">
		jQuery( document ).ready( function() {
			var $menu = jQuery( '#split-page-title-action .dropdown' ),
				$gutenberg;

			if ( $menu.length ) {
				$menu.prepend( '<?php echo $builder; // WPCS: XSS ok. ?>' );
			}

			$gutenberg = $menu.find( 'a' ).filter( function( index ) { return jQuery( this ).text() === 'Gutenberg'; } );
			$gutenberg.attr( 'href', '<?php echo esc_url_raw( $gutenberg_url ); ?>' );

		} );
		</script>
		<?php
	}


}
