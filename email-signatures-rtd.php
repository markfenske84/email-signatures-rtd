<?php
/**
 * Plugin Name: Email Signatures RTD
 * Description: RTD Logistics email signatures — create and copy a fixed-layout signature per team member.
 * Version: 1.0.2
 * Author: Webfor Agency
 * Author URI: https://webfor.com
 * Text Domain: email-signatures-rtd
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Initialize plugin update checker.
if ( file_exists( __DIR__ . '/plugin-update-checker/plugin-update-checker.php' ) ) {
	require_once __DIR__ . '/plugin-update-checker/plugin-update-checker.php';

	$esp_update_checker = YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
		'https://github.com/markfenske84/email-signatures-rtd/',
		__FILE__,
		'email-signatures-rtd'
	);

	$esp_update_checker->setBranch( 'main' );

	// main/master defaults to latest release/tag first; track main only (ignore legacy v1.2.x tags).
	add_filter(
		'puc_vcs_update_detection_strategies-email-signatures-rtd',
		static function ( $strategies ) {
			if ( isset( $strategies['branch'] ) ) {
				return array( 'branch' => $strategies['branch'] );
			}
			return $strategies;
		}
	);
}

if ( ! class_exists( 'Email_Signatures_Pro' ) ) {

	class Email_Signatures_Pro {

		/**
		 * Singleton instance.
		 *
		 * @var Email_Signatures_Pro
		 */
		private static $instance = null;

		/**
		 * Get singleton instance.
		 */
		public static function instance() {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		/**
		 * Constructor.
		 */
		private function __construct() {
			// Activation & deactivation.
			register_activation_hook( __FILE__, array( $this, 'activate' ) );

			// Hooks.
			add_action( 'init', array( $this, 'register_post_type' ) );
			add_action( 'after_setup_theme', array( $this, 'add_thumbnail_support' ) );
			add_filter( 'use_block_editor_for_post_type', array( $this, 'disable_gutenberg_for_cpt' ), 10, 2 );
			add_filter( 'template_include', array( $this, 'load_signature_template' ) );
			add_action( 'template_redirect', array( $this, 'restrict_signature_access' ) );
			add_action( 'wp_head', array( $this, 'signature_noindex' ) );
			// AJAX for generating & saving signature image.
			add_action( 'wp_ajax_esp_upload_signature_image', array( $this, 'ajax_upload_signature_image' ) );

			// AJAX for regenerating (clearing) signature images so they can be recreated on the front-end.
			add_action( 'wp_ajax_esp_regenerate_signature', array( $this, 'ajax_regenerate_signature' ) );

			// Admin.
			if ( is_admin() ) {
				add_action( 'add_meta_boxes_signature', array( $this, 'register_meta_boxes' ) );
				add_action( 'save_post_signature', array( $this, 'save_signature_meta' ) );
			}
		}

		/* --------------------------------------------------------------------- */
		/* Activation                                                           */
		/* --------------------------------------------------------------------- */

		public function activate() {
			// Register post type on activation then flush rewrite.
			$this->register_post_type();
			flush_rewrite_rules();
		}

		/* --------------------------------------------------------------------- */
		/* Custom Post Type                                                     */
		/* --------------------------------------------------------------------- */

		public function register_post_type() {
			$labels = array(
				'name'               => __( 'Signatures', 'email-signatures-pro' ),
				'singular_name'      => __( 'Signature', 'email-signatures-pro' ),
				'menu_name'          => __( 'Signatures', 'email-signatures-pro' ),
				'add_new'            => __( 'Add New', 'email-signatures-pro' ),
				'add_new_item'       => __( 'Add New Signature', 'email-signatures-pro' ),
				'edit_item'          => __( 'Edit Signature', 'email-signatures-pro' ),
				'new_item'           => __( 'New Signature', 'email-signatures-pro' ),
				'view_item'          => __( 'View Signature', 'email-signatures-pro' ),
				'view_items'         => __( 'View Signatures', 'email-signatures-pro' ),
				'not_found'          => __( 'No signatures found', 'email-signatures-pro' ),
			);

			$args = array(
				'labels'             => $labels,
				'public'             => false, // not publicly listed.
				'publicly_queryable' => true,  // still allow direct URLs.
				'show_ui'            => true,
				'show_in_menu'       => true,
				'menu_icon'          => 'dashicons-email',
				'exclude_from_search' => true,
				'has_archive'        => false,
				'supports'           => array( 'title', 'thumbnail' ), // Add featured image support.
				'rewrite'            => array( 'slug' => 'signature', 'with_front' => false ),
				'show_in_rest'       => false,
			);

			register_post_type( 'signature', $args );
		}

		public function disable_gutenberg_for_cpt( $use_block_editor, $post_type ) {
			if ( 'signature' === $post_type ) {
				return false;
			}
			return $use_block_editor;
		}

		/* --------------------------------------------------------------------- */
		/* Meta Boxes for Signature CPT                                         */
		/* --------------------------------------------------------------------- */

		public function register_meta_boxes() {
			add_meta_box(
				'esp_signature_details',
				__( 'Signature Details', 'email-signatures-pro' ),
				array( $this, 'render_signature_meta_box' ),
				'signature',
				'normal',
				'default'
			);
		}

		public function render_signature_meta_box( $post ) {
			wp_nonce_field( 'esp_save_signature', 'esp_signature_nonce' );

			$job_title    = get_post_meta( $post->ID, '_esp_job_title', true );
			$phone_number = get_post_meta( $post->ID, '_esp_phone_number', true );
			?>
		<p>
			<label for="esp_job_title"><strong><?php esc_html_e( 'Title / Position', 'email-signatures-pro' ); ?></strong></label><br />
			<input type="text" id="esp_job_title" name="esp_job_title" class="widefat" value="<?php echo esc_attr( $job_title ); ?>" />
		</p>

		<p>
			<label for="esp_phone_number"><strong><?php esc_html_e( 'Phone Number', 'email-signatures-pro' ); ?></strong></label><br />
			<input type="text" id="esp_phone_number" name="esp_phone_number" class="widefat" value="<?php echo esc_attr( $phone_number ); ?>" />
		</p>
			<?php
		}

		public function save_signature_meta( $post_id ) {
			// Verify nonce.
			if ( ! isset( $_POST['esp_signature_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['esp_signature_nonce'] ) ), 'esp_save_signature' ) ) {
				return;
			}

			// Check autosave.
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
				return;
			}

			// Check permissions.
			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return;
			}

			$job_title    = isset( $_POST['esp_job_title'] ) ? sanitize_text_field( wp_unslash( $_POST['esp_job_title'] ) ) : '';
			$phone_number = isset( $_POST['esp_phone_number'] ) ? sanitize_text_field( wp_unslash( $_POST['esp_phone_number'] ) ) : '';

			update_post_meta( $post_id, '_esp_job_title', $job_title );
			update_post_meta( $post_id, '_esp_phone_number', $phone_number );

			// Remove generated images so they can regenerate.
			// Retired keys (name, title, phone_only) stay listed so older signatures drop their orphaned attachments.
			$img_keys = array( '_esp_signature_image_header', '_esp_signature_image_phone', '_esp_signature_image_site', '_esp_signature_image_name', '_esp_signature_image_title', '_esp_signature_image_phone_only' );
			foreach ( $img_keys as $key ) {
				$attachment_id = get_post_meta( $post_id, $key, true );
				if ( $attachment_id ) {
					wp_delete_attachment( $attachment_id, true );
					delete_post_meta( $post_id, $key );
				}
			}
		}

		public function add_thumbnail_support() {
			// Ensure thumbnails enabled for our custom post type.
			add_theme_support( 'post-thumbnails', array( 'signature' ) );
		}

		public function load_signature_template( $template ) {
			if ( is_singular( 'signature' ) ) {
				$custom = plugin_dir_path( __FILE__ ) . 'templates/single-signature.php';
				if ( file_exists( $custom ) ) {
					return $custom;
				}
			}
			return $template;
		}

		public function restrict_signature_access() {
			if ( is_singular( 'signature' ) && ! is_user_logged_in() ) {
				auth_redirect(); // Redirect to login and back.
			}
		}

		public function signature_noindex() {
			if ( is_singular( 'signature' ) ) {
				echo "<meta name=\"robots\" content=\"noindex, nofollow\" />\n";
			}
		}

		/* --------------------------------------------------------------------- */
		/* AJAX: Upload Signature Image                                        */
		/* --------------------------------------------------------------------- */

		public function ajax_upload_signature_image() {
			// Validate nonce.
			check_ajax_referer( 'esp_signature_image', 'nonce' );

			$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
			if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
				wp_send_json_error( __( 'Permission denied.', 'email-signatures-pro' ) );
			}

			if ( empty( $_POST['image'] ) ) {
				wp_send_json_error( __( 'No image data.', 'email-signatures-pro' ) );
			}

			$field          = isset( $_POST['field'] ) ? sanitize_key( wp_unslash( $_POST['field'] ) ) : '';
			$allowed_fields = array( 'header', 'phone', 'site' );
			if ( ! in_array( $field, $allowed_fields, true ) ) {
				wp_send_json_error( __( 'Invalid field.', 'email-signatures-pro' ) );
			}

			$image_data = isset( $_POST['image'] ) ? sanitize_text_field( wp_unslash( $_POST['image'] ) ) : '';
			$image_data = str_replace( 'data:image/png;base64,', '', $image_data );
			$image_data = str_replace( ' ', '+', $image_data );

			$decoded = base64_decode( $image_data );
			if ( ! $decoded ) {
				wp_send_json_error( __( 'Invalid image data.', 'email-signatures-pro' ) );
			}

			// Prepare uploads dir.
			$upload_dir = wp_upload_dir();
			if ( ! empty( $upload_dir['error'] ) ) {
				wp_send_json_error( $upload_dir['error'] );
			}

			$file_name = 'signature-' . $post_id . '-' . $field . '-' . uniqid() . '.png';
			$file_path = trailingslashit( $upload_dir['path'] ) . $file_name;

			if ( ! file_put_contents( $file_path, $decoded ) ) {
				wp_send_json_error( __( 'Could not write file.', 'email-signatures-pro' ) );
			}

			$file_type  = wp_check_filetype( $file_name, null );
			$attachment = array(
				'post_mime_type' => $file_type['type'] ?? 'image/png',
				'post_title'     => 'Signature ' . $post_id,
				'post_content'   => '',
				'post_status'    => 'inherit',
			);

			$attach_id = wp_insert_attachment( $attachment, $file_path );

			if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
				require_once ABSPATH . 'wp-admin/includes/image.php';
			}

			$attach_data = wp_generate_attachment_metadata( $attach_id, $file_path );
			wp_update_attachment_metadata( $attach_id, $attach_data );

			$meta_key = '_esp_signature_image_' . $field;
			$prev_id  = get_post_meta( $post_id, $meta_key, true );
			if ( $prev_id && $prev_id !== $attach_id ) {
				wp_delete_attachment( $prev_id, true );
			}
			update_post_meta( $post_id, $meta_key, $attach_id );

			wp_send_json_success( array( 'url' => wp_get_attachment_url( $attach_id ), 'field' => $field ) );
		}

		/* --------------------------------------------------------------------- */
		/* AJAX: Regenerate Signature Images                                   */
		/* --------------------------------------------------------------------- */

		public function ajax_regenerate_signature() {
			check_ajax_referer( 'esp_regenerate_signature', 'nonce' );

			$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
			if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
				wp_send_json_error( __( 'Permission denied.', 'email-signatures-pro' ) );
			}

			// Retired keys (name, title, phone_only) stay listed so older signatures drop their orphaned attachments.
			$img_keys = array( '_esp_signature_image_header', '_esp_signature_image_phone', '_esp_signature_image_site', '_esp_signature_image_name', '_esp_signature_image_title', '_esp_signature_image_phone_only' );
			foreach ( $img_keys as $key ) {
				$attachment_id = get_post_meta( $post_id, $key, true );
				if ( $attachment_id ) {
					wp_delete_attachment( $attachment_id, true );
					delete_post_meta( $post_id, $key );
				}
			}

			// Also clear object cache for this post in case.
			clean_post_cache( $post_id );

			wp_send_json_success();
		}

	}

	// Initialize plugin.
	Email_Signatures_Pro::instance();
}
