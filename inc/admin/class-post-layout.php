<?php
/**
 * Adds a layout selector to the create and edit post admin screen.
 *
 * @package    Evoke
 * @license    http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 */

/**
 * Singleton class for handling the post layout feature.
 *
 * @since  1.0.0
 * @access public
 */
final class Evoke_Admin_Post_Layout {

	/**
	 * Returns the instance.
	 *
	 * @since  1.0.0
	 * @access public
	 * @return object
	 */
	public static function get_instance() {

		static $instance = null;

		if ( is_null( $instance ) ) {
			$instance = new self;
			$instance->setup_actions();
		}

		return $instance;
	}

	/**
	 * Constructor method.
	 *
	 * @since  1.0.0
	 * @access private
	 * @return void
	 */
	private function __construct() {}

	/**
	 * Sets up initial actions.
	 *
	 * @since  1.0.0
	 * @access private
	 * @return void
	 */
	private function setup_actions() {

		if ( ! current_theme_supports( 'theme-layouts', 'post_meta' ) )
			return;

		// Load on the edit tags screen.
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ), 20 );

		// Update post meta.
		add_action( 'save_post',       array( $this, 'save' ), 10, 2 );
		add_action( 'add_attachment',  array( $this, 'save' )        );
		add_action( 'edit_attachment', array( $this, 'save' )        );
	}

	/**
	 * Runs on the load hook and sets up what we need.
	 *
	 * @since  1.0.0
	 * @access public
	 * @param  string  $post_type
	 * @return void
	 */
	public function add_meta_boxes( $post_type ) {

		if ( post_type_supports( $post_type, 'theme-layouts' ) && current_user_can( 'edit_theme_options' ) ) {
			global $wp_meta_boxes;
			$wp_meta_boxes[$post_type]['side']['default']['hybrid-post-layout']['title'] = 'Content Layout';

			// Add meta box.
			add_meta_box( 'evoke-container-post-layout', esc_html__( 'Body Container Layout', 'evoke' ), array( $this, 'meta_box' ), $post_type, 'side', 'default' );

			// Enqueue scripts/styles.
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
		}
	}

	/**
	 * Enqueues scripts/styles.
	 *
	 * @since  1.0.0
	 * @access public
	 * @return void
	 */
	public function enqueue() {

		//wp_enqueue_style( 'hybrid-admin' );

		//add_action( 'admin_footer', 'hybrid_layout_field_inline_script' );
	}

	/**
	 * Callback function for displaying the layout meta box.
	 *
	 * @since  1.0.0
	 * @access public
	 * @param  object  $object
	 * @param  array   $box
	 * @return void
	 */
	public function meta_box( $post, $box ) {

		$layouts = array(
			'default' => 'Default',
			'boxed' => 'Boxed',
			'contained' => 'Contained',
			'full-width' => 'Full Width'
		);

		// Get the current post's layout.
		$post_layout = get_post_meta( $post->ID, 'evoke-container-post-layout', true );

		if ( empty( $post_layout ) ) {

			$obj = get_current_screen();
			$post_layout = 'default';

			if ( 'page' == $obj->post_type ) {
				$inherited_layout = evoke_get_option( 'layout_container_page' );
			}

			elseif ( 'post' == $obj->post_type ) {
				$inherited_layout = evoke_get_option( 'layout_container_post' );
			}

			else {
				$inherited_layout = evoke_get_option( 'layout_container' );
			}

			$layouts['default'] = 'Default (' . $layouts[$inherited_layout] . ')';

		}

		// Output the nonce field.
		wp_nonce_field( basename( __FILE__ ), 'evoke_container_post_layout_nonce' );

		// Output the layout field. ?>
		<label for="evoke-container-post-layout"></label>
		<select name="evoke-container-post-layout" id="evoke-container-post-layout" class="postbox">
			<?php foreach ( $layouts as $key => $layout ) : ?>
				<option value="<?php echo $key; ?>" <?php echo $post_layout == $key ? 'selected' : ''; ?>><?php echo $layout; ?></option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	/**
	 * Saves the post layout when submitted via the layout meta box.
	 *
	 * @since  1.0.0
	 * @access public
	 * @param  int      $post_id The ID of the current post being saved.
	 * @param  object   $post    The post object currently being saved.
	 * @return void
	 */
	public function save( $post_id, $post = '' ) {

		// Fix for attachment save issue in WordPress 3.5. @link http://core.trac.wordpress.org/ticket/21963
		if ( ! is_object( $post ) )
			$post = get_post();

		// Verify the nonce for the post formats meta box.
		if ( ! hybrid_verify_nonce_post( basename( __FILE__ ), 'evoke_container_post_layout_nonce' ) )
			return;

		// Get the previous post layout.
		$meta_value = get_post_meta( $post_id, 'evoke-container-post-layout', true );

		// Get the submitted post layout.
		$new_meta_value = isset( $_POST['evoke-container-post-layout'] ) ? sanitize_key( $_POST['evoke-container-post-layout'] ) : '';

		// If there is no new meta value but an old value exists, delete it.
		if ( '' == $new_meta_value && $meta_value )
			delete_post_meta( $post_id, 'evoke-container-post-layout' );

		// If a new meta value was added and there was no previous value, add it.
		elseif ( $meta_value !== $new_meta_value )
			'default' !== $new_meta_value ? update_post_meta( $post_id, 'evoke-container-post-layout', $new_meta_value ) : delete_post_meta( $post_id, 'evoke-container-post-layout' );
	}
}

Evoke_Admin_Post_Layout::get_instance();