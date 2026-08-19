<?php
/**
 * Plugin Name: Email Signatures RTD
 * Description: RTD Logistics email signatures — create and copy a fixed-layout signature per team member.
 * Version: 1.0.5
 * Author: Webfor Agency
 * Author URI: https://webfor.com
 * Text Domain: email-signatures-rtd
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

require_once __DIR__ . '/includes/esp-signature-render.php';

// Initialize plugin update checker.
if ( file_exists( __DIR__ . '/plugin-update-checker/plugin-update-checker.php' ) ) {
	require_once __DIR__ . '/plugin-update-checker/plugin-update-checker.php';

	$esp_update_checker = YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
		'https://github.com/markfenske84/email-signatures-rtd/',
		__FILE__,
		'email-signatures-rtd'
	);

	$esp_update_checker->setBranch( 'main' );

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
		 * Snapshot of signature fields before post update.
		 *
		 * @var array<int, array<string, mixed>>
		 */
		private $pre_save_snapshot = array();

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
			register_activation_hook( __FILE__, array( $this, 'activate' ) );

			add_action( 'init', array( $this, 'register_post_type' ) );
			add_action( 'after_setup_theme', array( $this, 'add_thumbnail_support' ) );
			add_filter( 'use_block_editor_for_post_type', array( $this, 'disable_gutenberg_for_cpt' ), 10, 2 );
			add_filter( 'template_include', array( $this, 'load_signature_template' ) );
			add_action( 'template_redirect', array( $this, 'restrict_signature_access' ) );
			add_action( 'wp_head', array( $this, 'signature_noindex' ) );

			add_action( 'wp_ajax_esp_upload_signature_image', array( $this, 'ajax_upload_signature_image' ) );
			add_action( 'wp_ajax_esp_regenerate_signature', array( $this, 'ajax_regenerate_signature' ) );
			add_action( 'wp_ajax_esp_stage_preview', array( $this, 'ajax_stage_preview' ) );
			add_action( 'wp_ajax_esp_get_signature_html', array( $this, 'ajax_get_signature_html' ) );

			add_action( 'pre_post_update', array( $this, 'snapshot_signature_before_save' ), 10, 2 );

			if ( is_admin() ) {
				add_action( 'add_meta_boxes_signature', array( $this, 'register_meta_boxes' ) );
				add_action( 'save_post_signature', array( $this, 'save_signature_meta' ) );
				add_action( 'edit_form_after_title', array( $this, 'render_editor_actions' ) );
				add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
				add_filter( 'admin_post_thumbnail_html', array( $this, 'avatar_thumbnail_note' ), 10, 2 );
			}
		}

		public function activate() {
			$this->register_post_type();
			flush_rewrite_rules();
		}

		public function register_post_type() {
			$labels = array(
				'name'          => __( 'Signatures', 'email-signatures-pro' ),
				'singular_name' => __( 'Signature', 'email-signatures-pro' ),
				'menu_name'     => __( 'Signatures', 'email-signatures-pro' ),
				'add_new'       => __( 'Add New', 'email-signatures-pro' ),
				'add_new_item'  => __( 'Add New Signature', 'email-signatures-pro' ),
				'edit_item'     => __( 'Edit Signature', 'email-signatures-pro' ),
				'new_item'      => __( 'New Signature', 'email-signatures-pro' ),
				'view_item'     => __( 'View Signature', 'email-signatures-pro' ),
				'view_items'    => __( 'View Signatures', 'email-signatures-pro' ),
				'not_found'     => __( 'No signatures found', 'email-signatures-pro' ),
			);

			register_post_type(
				'signature',
				array(
					'labels'              => $labels,
					'public'              => false,
					'publicly_queryable'  => true,
					'show_ui'             => true,
					'show_in_menu'        => true,
					'menu_icon'           => 'dashicons-email',
					'exclude_from_search' => true,
					'has_archive'         => false,
					'supports'            => array( 'title', 'thumbnail' ),
					'rewrite'             => array(
						'slug'       => 'signature',
						'with_front' => false,
					),
					'show_in_rest'        => false,
				)
			);
		}

		public function disable_gutenberg_for_cpt( $use_block_editor, $post_type ) {
			return ( 'signature' === $post_type ) ? false : $use_block_editor;
		}

		public function add_thumbnail_support() {
			add_theme_support( 'post-thumbnails', array( 'signature' ) );
			add_image_size( 'esp-avatar', 172, 172, true );
		}

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

		public function snapshot_signature_before_save( $post_id, $data ) {
			if ( 'signature' !== get_post_type( $post_id ) ) {
				return;
			}

			$this->pre_save_snapshot[ $post_id ] = array(
				'title'        => get_post_field( 'post_title', $post_id ),
				'job_title'    => get_post_meta( $post_id, '_esp_job_title', true ),
				'phone_number' => get_post_meta( $post_id, '_esp_phone_number', true ),
				'thumbnail_id' => (int) get_post_thumbnail_id( $post_id ),
			);
		}

		public function save_signature_meta( $post_id ) {
			if ( ! isset( $_POST['esp_signature_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['esp_signature_nonce'] ) ), 'esp_save_signature' ) ) {
				return;
			}

			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
				return;
			}

			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return;
			}

			$job_title    = isset( $_POST['esp_job_title'] ) ? sanitize_text_field( wp_unslash( $_POST['esp_job_title'] ) ) : '';
			$phone_number = isset( $_POST['esp_phone_number'] ) ? sanitize_text_field( wp_unslash( $_POST['esp_phone_number'] ) ) : '';

			update_post_meta( $post_id, '_esp_job_title', $job_title );
			update_post_meta( $post_id, '_esp_phone_number', $phone_number );

			$this->clear_post_preview_transients( $post_id );

			if ( $this->signature_fields_changed( $post_id, $job_title, $phone_number ) ) {
				esp_clear_signature_images( $post_id );
			}
		}

		/**
		 * Determine whether signature-affecting fields changed on save.
		 *
		 * @param int    $post_id      Post ID.
		 * @param string $job_title    New job title.
		 * @param string $phone_number New phone number.
		 * @return bool
		 */
		private function signature_fields_changed( $post_id, $job_title, $phone_number ) {
			$before = $this->pre_save_snapshot[ $post_id ] ?? null;
			if ( ! $before ) {
				return true;
			}

			$incoming_title = isset( $_POST['post_title'] ) ? sanitize_text_field( wp_unslash( $_POST['post_title'] ) ) : $before['title'];
			$incoming_thumb = isset( $_POST['_thumbnail_id'] ) ? absint( $_POST['_thumbnail_id'] ) : $before['thumbnail_id'];

			return $incoming_title !== $before['title']
				|| $job_title !== $before['job_title']
				|| $phone_number !== $before['phone_number']
				|| $incoming_thumb !== $before['thumbnail_id'];
		}

		/**
		 * Clear staged preview transients associated with a post.
		 *
		 * @param int $post_id Post ID.
		 */
		private function clear_post_preview_transients( $post_id ) {
			$user_id = get_current_user_id();
			if ( ! $user_id ) {
				return;
			}

			$index_key = 'esp_preview_index_' . $user_id . '_' . $post_id;
			$keys      = get_transient( $index_key );
			if ( is_array( $keys ) ) {
				foreach ( $keys as $preview_key ) {
					esp_delete_preview_transient( $preview_key );
				}
			}
			delete_transient( $index_key );
		}

		public function render_editor_actions( $post ) {
			if ( ! $post || 'signature' !== $post->post_type ) {
				return;
			}

			$can_view = $post->ID && 'auto-draft' !== $post->post_status;
			?>
			<div class="esp-signature-actions" style="margin:12px 0 8px;">
				<?php if ( $can_view ) : ?>
					<a href="<?php echo esc_url( get_permalink( $post->ID ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'View Signature', 'email-signatures-pro' ); ?></a>
					<span aria-hidden="true"> | </span>
					<a href="#" id="esp-preview-signature-btn" data-post-id="<?php echo esc_attr( (string) $post->ID ); ?>"><?php esc_html_e( 'Preview Signature', 'email-signatures-pro' ); ?></a>
				<?php else : ?>
					<span class="description"><?php esc_html_e( 'Save draft to enable signature links.', 'email-signatures-pro' ); ?></span>
				<?php endif; ?>
			</div>
			<?php
		}

		public function enqueue_admin_assets( $hook ) {
			global $post;

			if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) || ! $post || 'signature' !== $post->post_type ) {
				return;
			}

			wp_enqueue_script(
				'esp-admin',
				plugins_url( 'assets/js/esp-admin.js', __FILE__ ),
				array(),
				(string) filemtime( plugin_dir_path( __FILE__ ) . 'assets/js/esp-admin.js' ),
				true
			);

			$generated_image_ids = array(
				get_post_meta( $post->ID, '_esp_signature_image_header', true ),
				get_post_meta( $post->ID, '_esp_signature_image_phone', true ),
				get_post_meta( $post->ID, '_esp_signature_image_site', true ),
			);

			wp_localize_script(
				'esp-admin',
				'espAdmin',
				array(
					'ajaxUrl'            => admin_url( 'admin-ajax.php' ),
					'nonce'              => wp_create_nonce( 'esp_stage_preview' ),
					'hasGeneratedImages' => (bool) array_filter( $generated_image_ids ),
					'initialValues'      => array(
						'title'       => get_post_field( 'post_title', $post->ID ),
						'jobTitle'    => get_post_meta( $post->ID, '_esp_job_title', true ),
						'phoneNumber' => get_post_meta( $post->ID, '_esp_phone_number', true ),
						'thumbnailId' => (string) get_post_thumbnail_id( $post->ID ),
					),
					'replaceWarning'    => __( 'Saving these changes will replace the images used by your current email signature. After saving, copy the new signature and replace the old one in your email app. Continue?', 'email-signatures-pro' ),
				)
			);
		}

		public function avatar_thumbnail_note( $content, $post_id ) {
			if ( 'signature' !== get_post_type( $post_id ) ) {
				return $content;
			}

			$content .= '<p class="description">' . esc_html__( 'Photos are center-cropped to a circle in the signature.', 'email-signatures-pro' ) . '</p>';
			return $content;
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
				auth_redirect();
			}
		}

		public function signature_noindex() {
			if ( is_singular( 'signature' ) ) {
				echo "<meta name=\"robots\" content=\"noindex, nofollow\" />\n";
			}
		}

		public function ajax_stage_preview() {
			check_ajax_referer( 'esp_stage_preview', 'nonce' );

			$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
			if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
				wp_send_json_error( __( 'Permission denied.', 'email-signatures-pro' ) );
			}

			$title        = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : get_the_title( $post_id );
			$job_title    = isset( $_POST['job_title'] ) ? sanitize_text_field( wp_unslash( $_POST['job_title'] ) ) : '';
			$phone_number = isset( $_POST['phone_number'] ) ? sanitize_text_field( wp_unslash( $_POST['phone_number'] ) ) : '';
			$thumbnail_id = isset( $_POST['thumbnail_id'] ) ? absint( $_POST['thumbnail_id'] ) : (int) get_post_thumbnail_id( $post_id );

			$this->clear_post_preview_transients( $post_id );

			$preview_key = 'esp_pv_' . wp_generate_password( 16, false );
			$payload     = array(
				'user_id'       => get_current_user_id(),
				'post_id'       => $post_id,
				'title'         => $title,
				'job_title'     => $job_title,
				'phone_number'  => $phone_number,
				'thumbnail_id'  => $thumbnail_id,
				'images'        => array(),
			);

			set_transient( $preview_key, $payload, 10 * MINUTE_IN_SECONDS );

			$index_key = 'esp_preview_index_' . get_current_user_id() . '_' . $post_id;
			$keys      = get_transient( $index_key );
			$keys      = is_array( $keys ) ? $keys : array();
			$keys[]    = $preview_key;
			set_transient( $index_key, $keys, 10 * MINUTE_IN_SECONDS );

			wp_send_json_success(
				array(
					'url' => add_query_arg( 'esp_preview', $preview_key, get_permalink( $post_id ) ),
				)
			);
		}

		public function ajax_get_signature_html() {
			check_ajax_referer( 'esp_signature_image', 'nonce' );

			$post_id     = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
			$preview_key = isset( $_POST['preview_key'] ) ? sanitize_key( wp_unslash( $_POST['preview_key'] ) ) : '';

			if ( ! $post_id || ! current_user_can( 'read' ) ) {
				wp_send_json_error( __( 'Permission denied.', 'email-signatures-pro' ) );
			}

			$post = get_post( $post_id );
			if ( ! $post || 'signature' !== $post->post_type ) {
				wp_send_json_error( __( 'Invalid signature.', 'email-signatures-pro' ) );
			}

			$context = esp_build_signature_context( $post, array(), $preview_key );
			$html    = esp_render_signature_email_html( $context );

			if ( ! $html ) {
				wp_send_json_error( __( 'Signature is not ready.', 'email-signatures-pro' ) );
			}

			wp_send_json_success( array( 'html' => $html ) );
		}

		public function ajax_upload_signature_image() {
			check_ajax_referer( 'esp_signature_image', 'nonce' );

			$post_id     = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
			$preview_key = isset( $_POST['preview_key'] ) ? sanitize_key( wp_unslash( $_POST['preview_key'] ) ) : '';

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

			$upload_dir = wp_upload_dir();
			if ( ! empty( $upload_dir['error'] ) ) {
				wp_send_json_error( $upload_dir['error'] );
			}

			$suffix    = $preview_key ? 'preview-' . substr( $preview_key, -8 ) : (string) $post_id;
			$file_name = 'signature-' . $suffix . '-' . $field . '-' . uniqid() . '.png';
			$file_path = trailingslashit( $upload_dir['path'] ) . $file_name;

			if ( ! file_put_contents( $file_path, $decoded ) ) {
				wp_send_json_error( __( 'Could not write file.', 'email-signatures-pro' ) );
			}

			$file_type  = wp_check_filetype( $file_name, null );
			$attachment = array(
				'post_mime_type' => $file_type['type'] ?? 'image/png',
				'post_title'     => 'Signature ' . $post_id . ' ' . $field,
				'post_content'   => '',
				'post_status'    => 'inherit',
			);

			$attach_id = wp_insert_attachment( $attachment, $file_path );

			if ( is_wp_error( $attach_id ) || ! $attach_id ) {
				@unlink( $file_path );
				wp_send_json_error( __( 'Could not save image.', 'email-signatures-pro' ) );
			}

			// Full metadata generation is skipped for speed, but the dimensions
			// are required for the email markup to size the PNG.
			$image_size = getimagesize( $file_path );
			esp_store_attachment_dimensions(
				$attach_id,
				$file_path,
				$image_size ? (int) $image_size[0] : 0,
				$image_size ? (int) $image_size[1] : 0
			);

			if ( $preview_key ) {
				$preview = esp_get_preview_transient( $preview_key );
				if ( ! $preview || (int) $preview['user_id'] !== get_current_user_id() || (int) $preview['post_id'] !== $post_id ) {
					wp_delete_attachment( $attach_id, true );
					wp_send_json_error( __( 'Invalid preview session.', 'email-signatures-pro' ) );
				}

				if ( ! empty( $preview['images'][ $field ] ) ) {
					wp_delete_attachment( absint( $preview['images'][ $field ] ), true );
				}

				$preview['images'][ $field ] = $attach_id;
				set_transient( $preview_key, $preview, 10 * MINUTE_IN_SECONDS );
			} else {
				$meta_key = '_esp_signature_image_' . $field;
				$prev_id  = get_post_meta( $post_id, $meta_key, true );
				if ( $prev_id && (int) $prev_id !== (int) $attach_id ) {
					wp_delete_attachment( $prev_id, true );
				}
				update_post_meta( $post_id, $meta_key, $attach_id );
			}

			list( $width, $height ) = esp_get_attachment_dimensions( $attach_id );
			wp_send_json_success(
				array(
					'url'    => wp_get_attachment_url( $attach_id ),
					'field'  => $field,
					'width'  => (int) round( $width / 2 ),
					'height' => (int) round( $height / 2 ),
				)
			);
		}

		public function ajax_regenerate_signature() {
			check_ajax_referer( 'esp_regenerate_signature', 'nonce' );

			$post_id     = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
			$preview_key = isset( $_POST['preview_key'] ) ? sanitize_key( wp_unslash( $_POST['preview_key'] ) ) : '';

			if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
				wp_send_json_error( __( 'Permission denied.', 'email-signatures-pro' ) );
			}

			if ( $preview_key ) {
				$preview = esp_get_preview_transient( $preview_key );
				if ( $preview && (int) $preview['user_id'] === get_current_user_id() && (int) $preview['post_id'] === $post_id ) {
					if ( ! empty( $preview['images'] ) && is_array( $preview['images'] ) ) {
						foreach ( $preview['images'] as $attachment_id ) {
							if ( $attachment_id ) {
								wp_delete_attachment( absint( $attachment_id ), true );
							}
						}
					}
					$preview['images'] = array();
					set_transient( $preview_key, $preview, 10 * MINUTE_IN_SECONDS );
				}
			} else {
				esp_clear_signature_images( $post_id );
			}

			wp_send_json_success();
		}
	}

	Email_Signatures_Pro::instance();
}
