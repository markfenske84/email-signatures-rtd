<?php
/**
 * Plugin Name: Email Signatures RTD
 * Description: Manage email signature templates, global styles and assets.
 * Version: 1.0.0
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
}

if ( ! class_exists( 'Email_Signatures_Pro' ) ) {

	class Email_Signatures_Pro {

		/**
		 * Option key where all plugin settings are stored.
		 */
		const OPTION_KEY = 'esp_settings';

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
				add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
				add_action( 'admin_init', array( $this, 'register_settings' ) );
				add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );

				// Meta boxes.
				add_action( 'add_meta_boxes_signature', array( $this, 'register_meta_boxes' ) );
				add_action( 'save_post_signature', array( $this, 'save_signature_meta' ) );
			}

			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'add_settings_action_link' ) );
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
			$meeting_url  = get_post_meta( $post->ID, '_esp_meeting_url', true );
		?>
		<p>
			<label for="esp_job_title"><strong><?php esc_html_e( 'Title / Position', 'email-signatures-pro' ); ?></strong></label><br />
			<input type="text" id="esp_job_title" name="esp_job_title" class="widefat" value="<?php echo esc_attr( $job_title ); ?>" />
		</p>

		<p>
			<label for="esp_phone_number"><strong><?php esc_html_e( 'Phone Number', 'email-signatures-pro' ); ?></strong></label><br />
			<input type="text" id="esp_phone_number" name="esp_phone_number" class="widefat" value="<?php echo esc_attr( $phone_number ); ?>" />
		</p>

		<p>
			<label for="esp_meeting_url"><strong><?php esc_html_e( 'Meeting Link URL', 'email-signatures-pro' ); ?></strong></label><br />
			<input type="url" id="esp_meeting_url" name="esp_meeting_url" class="widefat" value="<?php echo esc_url( $meeting_url ); ?>" />
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
		$meeting_url  = isset( $_POST['esp_meeting_url'] ) ? esc_url_raw( wp_unslash( $_POST['esp_meeting_url'] ) ) : '';

			update_post_meta( $post_id, '_esp_job_title', $job_title );
			update_post_meta( $post_id, '_esp_phone_number', $phone_number );
			update_post_meta( $post_id, '_esp_meeting_url', $meeting_url );

			// Remove generated images so they can regenerate.
			$img_keys = array( '_esp_signature_image_name', '_esp_signature_image_title', '_esp_signature_image_phone', '_esp_signature_image_phone_only', '_esp_signature_image_site' );
			foreach ( $img_keys as $key ) {
				$attachment_id = get_post_meta( $post_id, $key, true );
				if ( $attachment_id ) {
					wp_delete_attachment( $attachment_id, true );
					delete_post_meta( $post_id, $key );
				}
			}
		}

		/* --------------------------------------------------------------------- */
		/* Settings Page                                                        */
		/* --------------------------------------------------------------------- */

		public function add_settings_page() {
			// Add settings as a submenu under the "Signatures" CPT menu.
			add_submenu_page(
				'edit.php?post_type=signature',        // Parent slug.
				__( 'Email Signatures Pro Settings', 'email-signatures-pro' ), // Page title.
				__( 'Settings', 'email-signatures-pro' ),                         // Menu label.
				'manage_options',
				'email-signatures-pro',
				array( $this, 'render_settings_page' )
			);
		}

		public function register_settings() {
			register_setting( 'esp_settings_group', self::OPTION_KEY, array( $this, 'sanitize_settings' ) );

			// Map of tab slugs to page slugs used by Settings API.
			$pages = array(
				'fonts'   => 'esp_tab_fonts',
				'colors'  => 'esp_tab_colors',
				'images'  => 'esp_tab_images',
				'social'  => 'esp_tab_social',
				'general' => 'esp_tab_general',
			);

			/* ---------------- Fonts Tab ---------------- */
			add_settings_section( 'esp_fonts_section', __( 'Fonts', 'email-signatures-pro' ), '__return_false', $pages['fonts'] );
			add_settings_field( 'fonts_url', __( 'Fonts Embed URL', 'email-signatures-pro' ), array( $this, 'text_input_callback' ), $pages['fonts'], 'esp_fonts_section', array( 'id' => 'fonts_url' ) );
			add_settings_field( 'heading_font_css', __( 'Heading Font CSS Family', 'email-signatures-pro' ), array( $this, 'text_input_callback' ), $pages['fonts'], 'esp_fonts_section', array( 'id' => 'heading_font_css' ) );
			add_settings_field( 'body_font_css', __( 'Body Font CSS Family', 'email-signatures-pro' ), array( $this, 'text_input_callback' ), $pages['fonts'], 'esp_fonts_section', array( 'id' => 'body_font_css' ) );

			/* ---------------- Colors Tab ---------------- */
			add_settings_section( 'esp_colors_section', __( 'Colors', 'email-signatures-pro' ), '__return_false', $pages['colors'] );
			add_settings_field( 'primary_color', __( 'Primary Color', 'email-signatures-pro' ), array( $this, 'color_input_callback' ), $pages['colors'], 'esp_colors_section', array( 'id' => 'primary_color' ) );
			add_settings_field( 'secondary_color', __( 'Secondary Color', 'email-signatures-pro' ), array( $this, 'color_input_callback' ), $pages['colors'], 'esp_colors_section', array( 'id' => 'secondary_color' ) );
			add_settings_field( 'tertiary_color', __( 'Tertiary Color', 'email-signatures-pro' ), array( $this, 'color_input_callback' ), $pages['colors'], 'esp_colors_section', array( 'id' => 'tertiary_color' ) );
			add_settings_field( 'neutral_color', __( 'Neutral Color', 'email-signatures-pro' ), array( $this, 'color_input_callback' ), $pages['colors'], 'esp_colors_section', array( 'id' => 'neutral_color' ) );

			/* ---------------- Images Tab ---------------- */
			add_settings_section( 'esp_images_section', __( 'Images', 'email-signatures-pro' ), '__return_false', $pages['images'] );
			add_settings_field( 'default_avatar', __( 'Default Avatar', 'email-signatures-pro' ), array( $this, 'image_input_callback' ), $pages['images'], 'esp_images_section', array( 'id' => 'default_avatar' ) );
			add_settings_field( 'company_logo', __( 'Company Logo', 'email-signatures-pro' ), array( $this, 'image_input_callback' ), $pages['images'], 'esp_images_section', array( 'id' => 'company_logo' ) );
			add_settings_field( 'cta_button', __( 'CTA Button Image', 'email-signatures-pro' ), array( $this, 'image_input_callback' ), $pages['images'], 'esp_images_section', array( 'id' => 'cta_button' ) );

			/* ---------------- Social Links Tab ---------------- */
			add_settings_section( 'esp_social_section', __( 'Social Links', 'email-signatures-pro' ), '__return_false', $pages['social'] );
			add_settings_field( 'social_links', __( 'Social Links', 'email-signatures-pro' ), array( $this, 'social_links_callback' ), $pages['social'], 'esp_social_section' );

			/* ---------------- General Tab ---------------- */
			add_settings_section( 'esp_general_section', __( 'General', 'email-signatures-pro' ), '__return_false', $pages['general'] );
			add_settings_field( 'website_url', __( 'Signature Website URL', 'email-signatures-pro' ), array( $this, 'text_input_callback' ), $pages['general'], 'esp_general_section', array( 'id' => 'website_url' ) );
            // Office phone number field below website URL.
            add_settings_field( 'office_phone', __( 'Office Phone Number', 'email-signatures-pro' ), array( $this, 'text_input_callback' ), $pages['general'], 'esp_general_section', array( 'id' => 'office_phone' ) );
		}

		public function sanitize_settings( $input ) {
			// Start with existing saved settings so we don\'t lose data from other tabs.
			$existing   = get_option( self::OPTION_KEY, array() );
			$sanitized  = is_array( $existing ) ? $existing : array();

			$fields = [
				'fonts_url', 'heading_font_css', 'body_font_css',
				'primary_color', 'secondary_color', 'tertiary_color', 'neutral_color',
				'default_avatar', 'company_logo', 'cta_button',
				'website_url',
				'office_phone',
			];

			foreach ( $fields as $field ) {
				// Use array_key_exists so that empty strings still overwrite prior values.
				if ( array_key_exists( $field, $input ) ) {
					if ( 'website_url' === $field ) {
						$sanitized[ $field ] = esc_url_raw( $input[ $field ] );
					} else {
						$sanitized[ $field ] = esc_html( $input[ $field ] );
					}
				}
			}

			// Social links is expected to be an array of arrays with icon and url.
			if ( array_key_exists( 'social_links', $input ) && is_array( $input['social_links'] ) ) {
				$sanitized['social_links'] = array();
				foreach ( $input['social_links'] as $row ) {
					$icon = ! empty( $row['icon'] ) ? esc_url_raw( $row['icon'] ) : '';
					$url  = ! empty( $row['url'] ) ? esc_url_raw( $row['url'] ) : '';
					if ( $icon && $url ) {
						// Re-tint icon to match secondary color and obtain URL of colored variant.
						$tertiary  = $sanitized['tertiary_color']  ?? ( $this->get_option( 'tertiary_color',  '#aaaaaa' ) );
						$secondary = $sanitized['secondary_color'] ?? ( $this->get_option( 'secondary_color', '#777777' ) );
						$tinted_icon_url = $this->esp_generate_gradient_icon( $icon, $tertiary, $secondary );
						$sanitized['social_links'][] = array( 'icon' => $tinted_icon_url, 'url' => $url );
					}
				}
			} elseif ( array_key_exists( 'social_links', $input ) ) {
				// If the field is present but empty, clear existing social links.
				$sanitized['social_links'] = array();
			}

			return $sanitized;
		}

		/**
		 * Generate a color-tinted copy of a social icon PNG so it matches the chosen secondary color.
		 * Returns the URL to the new image or falls back to original if processing fails.
		 *
		 * @param string $icon_url      Source icon URL.
		 * @param string $secondary_hex Color in #rrggbb format.
		 * @return string               URL to tinted image (or original URL on failure).
		 */
		private function esp_generate_gradient_icon( $icon_url, $tertiary_hex, $secondary_hex ) {
			// Accept only hex colors.
			if ( ! preg_match( '/^#?[0-9a-fA-F]{6}$/', $secondary_hex ) || ! preg_match( '/^#?[0-9a-fA-F]{6}$/', $tertiary_hex ) ) {
				return $icon_url;
			}

			// Ensure leading # removed for sscanf.
			$secondary_hex = ltrim( $secondary_hex, '#' );
			$tertiary_hex  = ltrim( $tertiary_hex, '#' );

			// Build deterministic filename so we reuse when same icon/color requested again.
			$upload_dir = wp_upload_dir();
			if ( ! empty( $upload_dir['error'] ) ) {
				return $icon_url;
			}

			$sub_dir     = trailingslashit( $upload_dir['basedir'] ) . 'esp_social/';
			$sub_dir_url = trailingslashit( $upload_dir['baseurl'] ) . 'esp_social/';

			// Create dir if needed.
			if ( ! file_exists( $sub_dir ) ) {
				wp_mkdir_p( $sub_dir );
			}

			$dest_filename = 'icon-' . md5( $icon_url . $tertiary_hex . $secondary_hex ) . '.png';
			$dest_path     = $sub_dir . $dest_filename;
			$dest_url      = $sub_dir_url . $dest_filename;

			// Reuse if already generated.
			if ( file_exists( $dest_path ) ) {
				return $dest_url;
			}

			// Fetch source image to a local path.
			$src_path = '';
			if ( strpos( $icon_url, $upload_dir['baseurl'] ) === 0 ) {
				// Same uploads dir – convert URL to path.
				$src_path = str_replace( $upload_dir['baseurl'], $upload_dir['basedir'], $icon_url );
			} else {
				// Remote or external – download.
				$tmp = download_url( $icon_url );
				if ( is_wp_error( $tmp ) ) {
					return $icon_url; // Could not download.
				}
				$src_path  = $tmp;
			}

		// Try loading PNG.
		$im = @imagecreatefrompng( $src_path );
		if ( ! $im ) {
			// Clean tmp file if used.
			if ( isset( $tmp ) && file_exists( $tmp ) ) {
				wp_delete_file( $tmp );
			}
			return $icon_url;
		}

			// Create new image preserving alpha.
			$width  = imagesx( $im );
			$height = imagesy( $im );

			$new_im = imagecreatetruecolor( $width, $height );
			imagealphablending( $new_im, false );
			imagesavealpha( $new_im, true );

			// Extract RGB components for start & end colours.
			list( $r1, $g1, $b1 ) = sscanf( $tertiary_hex,   '%02x%02x%02x' );
			list( $r2, $g2, $b2 ) = sscanf( $secondary_hex, '%02x%02x%02x' );

			$color_cache = array(); // keyed by alpha|x to reuse allocations.

			$max_x = $width > 1 ? ( $width - 1 ) : 1;

			for ( $y = 0; $y < $height; $y++ ) {
				for ( $x = 0; $x < $width; $x++ ) {
					$rgba  = imagecolorat( $im, $x, $y );
					$alpha = ( $rgba >> 24 ) & 0x7F;

					if ( 127 === $alpha ) {
						// keep transparent
						imagesetpixel( $new_im, $x, $y, imagecolorallocatealpha( $new_im, 0, 0, 0, 127 ) );
						continue;
					}

					$t = $x / $max_x; // 0 → 1 across width.
					$r = (int) round( $r1 + ( $r2 - $r1 ) * $t );
					$g = (int) round( $g1 + ( $g2 - $g1 ) * $t );
					$b = (int) round( $b1 + ( $b2 - $b1 ) * $t );

					$cache_key = $alpha . '|' . $r . '|' . $g . '|' . $b;
					if ( ! isset( $color_cache[ $cache_key ] ) ) {
						$color_cache[ $cache_key ] = imagecolorallocatealpha( $new_im, $r, $g, $b, $alpha );
					}

					imagesetpixel( $new_im, $x, $y, $color_cache[ $cache_key ] );
				}
			}

			// Save tinted PNG.
			imagepng( $new_im, $dest_path );

		imagedestroy( $im );
		imagedestroy( $new_im );

		// Clean tmp if we downloaded.
		if ( isset( $tmp ) && file_exists( $tmp ) ) {
			wp_delete_file( $tmp );
		}

		return $dest_url;
		}

		/* --------------------------------------------------------------------- */
		/* Settings Callbacks                                                   */
		/* --------------------------------------------------------------------- */

		private function get_option( $key, $default = '' ) {
			$options = get_option( self::OPTION_KEY, array() );
			return $options[ $key ] ?? $default;
		}

	public function text_input_callback( $args ) {
		$id    = $args['id'];
		$value = esc_attr( $this->get_option( $id ) );
		printf(
			'<input type="text" name="%s[%s]" id="%s" class="regular-text" value="%s" />',
			esc_attr( self::OPTION_KEY ),
			esc_attr( $id ),
			esc_attr( $id ),
			esc_attr( $value )
		);
	}

	public function color_input_callback( $args ) {
		$id    = $args['id'];
		$value = esc_attr( $this->get_option( $id, '#ffffff' ) );
		printf(
			'<input type="text" class="esp-color-field" name="%s[%s]" id="%s" value="%s" data-default-color="#ffffff" />',
			esc_attr( self::OPTION_KEY ),
			esc_attr( $id ),
			esc_attr( $id ),
			esc_attr( $value )
		);
	}

	public function image_input_callback( $args ) {
		$id    = $args['id'];
		$value = esc_url( $this->get_option( $id ) );

		$img_preview = $value ? sprintf( '<img src="%s" style="max-width:100px;height:auto;display:block;margin-bottom:10px;" />', esc_url( $value ) ) : '';

		echo '<div class="esp-image-wrap">' . wp_kses_post( $img_preview ) . '</div>';
		printf(
			'<input type="hidden" name="%s[%s]" id="%s" value="%s" />',
			esc_attr( self::OPTION_KEY ),
			esc_attr( $id ),
			esc_attr( $id ),
			esc_url( $value )
		);
		printf(
			'<button type="button" class="button esp-upload-image" data-target="%s">%s</button> ',
			esc_attr( $id ),
			esc_html__( 'Upload', 'email-signatures-pro' )
		);
		printf(
			'<button type="button" class="button esp-remove-image" data-target="%s">%s</button>',
			esc_attr( $id ),
			esc_html__( 'Remove', 'email-signatures-pro' )
		);
	}

	public function social_links_callback() {
		$options = get_option( self::OPTION_KEY, array() );
		$social_links = $options['social_links'] ?? array();

		echo '<table class="widefat fixed" id="esp-social-links-table" style="max-width:800px;">';
		echo '<thead><tr><th class="icon-col">' . esc_html__( 'Icon', 'email-signatures-pro' ) . '</th><th class="url-col">' . esc_html__( 'URL', 'email-signatures-pro' ) . '</th><th class="remove-col"></th></tr></thead>';
		echo '<tbody>';

		if ( ! empty( $social_links ) ) {
			foreach ( $social_links as $index => $row ) {
				$icon = esc_url( $row['icon'] );
				$url  = esc_url( $row['url'] );
				$preview = $icon ? sprintf( '<img src="%s" alt="" />', esc_url( $icon ) ) : '';
				echo '<tr>';
				printf(
					'<td class="esp-icon-cell"><span class="esp-drag-handle dashicons dashicons-move" title="%s"></span><div class="esp-icon-preview">%s</div><input type="hidden" class="esp-image-url" name="%s[social_links][%d][icon]" value="%s" /> <button type="button" class="button button-small esp-upload-image" title="%s"><span class="dashicons dashicons-edit"></span></button></td>',
					esc_attr__( 'Drag to reorder', 'email-signatures-pro' ),
					wp_kses_post( $preview ),
					esc_attr( self::OPTION_KEY ),
					absint( $index ),
					esc_url( $icon ),
					esc_attr__( 'Edit icon', 'email-signatures-pro' )
				);
				printf(
					'<td><input type="url" class="regular-text" name="%s[social_links][%d][url]" value="%s" placeholder="https://" /></td>',
					esc_attr( self::OPTION_KEY ),
					absint( $index ),
					esc_url( $url )
				);
				printf(
					'<td><button type="button" class="button button-small esp-remove-row" title="%s"><span class="dashicons dashicons-trash"></span></button></td>',
					esc_attr__( 'Remove', 'email-signatures-pro' )
				);
				echo '</tr>';
			}
		}

		echo '</tbody>'; // tbody
		echo '</table>';
		echo '<p><button type="button" class="button" id="esp-add-social-row">' . esc_html__( 'Add Social Link', 'email-signatures-pro' ) . '</button></p>';
	}

		/* --------------------------------------------------------------------- */
		/* Admin Assets                                                         */
		/* --------------------------------------------------------------------- */

		public function enqueue_admin_assets( $hook ) {
			// Load only on our settings page (now under Signatures) or signature edit pages.
			if ( 'signature_page_email-signatures-pro' !== $hook && 'post.php' !== $hook && 'post-new.php' !== $hook ) {
				return;
			}

			// WP color picker.
			wp_enqueue_style( 'wp-color-picker' );
			wp_enqueue_script( 'wp-color-picker' );

			// Media uploader.
			wp_enqueue_media();

		// Custom script (jQuery included only for wp-color-picker dependency).
		wp_enqueue_script( 'esp-admin', plugin_dir_url( __FILE__ ) . 'assets/js/esp-admin.js', array( 'wp-color-picker' ), '1.2.1', true );
		wp_localize_script( 'esp-admin', 'esp_admin', array( 'title' => __( 'Select or Upload Image', 'email-signatures-pro' ), 'choose' => __( 'Use this image', 'email-signatures-pro' ) ) );
		wp_enqueue_style( 'esp-admin', plugin_dir_url( __FILE__ ) . 'assets/css/esp-admin.css', array(), '1.0.0' );

		// Ensure dashicons are available in admin.
		wp_enqueue_style( 'dashicons' );
	}

		/* --------------------------------------------------------------------- */
		/* Tab helpers                                                          */
		/* --------------------------------------------------------------------- */

		/**
		 * Returns associative array of tab slug => label.
		 */
		private function get_setting_tabs() {
			return array(
				'fonts'   => __( 'Fonts', 'email-signatures-pro' ),
				'colors'  => __( 'Colors', 'email-signatures-pro' ),
				'images'  => __( 'Images', 'email-signatures-pro' ),
				'social'  => __( 'Social Links', 'email-signatures-pro' ),
				'general' => __( 'General', 'email-signatures-pro' ),
			);
		}

		/* --------------------------------------------------------------------- */
		/* Settings Page Markup                                                 */
		/* --------------------------------------------------------------------- */

	public function render_settings_page() {
		$tabs = $this->get_setting_tabs();
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Tab navigation doesn't require nonce.
		$current = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'fonts';
		if ( ! isset( $tabs[ $current ] ) ) {
			$current = 'fonts';
		}

		// Base URL without tab param.
		$base_url = admin_url( 'edit.php?post_type=signature&page=email-signatures-pro' );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Email Signatures Pro Settings', 'email-signatures-pro' ); ?></h1>

			<?php
			// Show success message after save.
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Checking for WordPress settings update confirmation.
			if ( isset( $_GET['settings-updated'] ) && 'true' === $_GET['settings-updated'] ) :
				?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( 'Settings saved successfully!', 'email-signatures-pro' ); ?></p>
				</div>
			<?php endif; ?>

			<h2 class="nav-tab-wrapper">
					<?php foreach ( $tabs as $slug => $label ) :
						$tab_url = add_query_arg( 'tab', $slug, $base_url );
						$class   = ( $slug === $current ) ? 'nav-tab nav-tab-active' : 'nav-tab';
						echo '<a href="' . esc_url( $tab_url ) . '" class="' . esc_attr( $class ) . '">' . esc_html( $label ) . '</a>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					endforeach; ?>
				</h2>

				<form method="post" action="options.php">
					<?php
					settings_fields( 'esp_settings_group' );
					do_settings_sections( 'esp_tab_' . $current );
					submit_button();
					?>
				</form>
			</div>
			<?php
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

		$field     = isset( $_POST['field'] ) ? sanitize_key( wp_unslash( $_POST['field'] ) ) : '';
		$allowed_fields = array( 'name', 'title', 'phone', 'phone_only', 'site' );
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

			$file_type = wp_check_filetype( $file_name, null );
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

			$img_keys = array( '_esp_signature_image_name', '_esp_signature_image_title', '_esp_signature_image_phone', '_esp_signature_image_phone_only', '_esp_signature_image_site' );
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

	/* --------------------------------------------------------------------- */
	/* Plugin Row Action Links                                              */
	/* --------------------------------------------------------------------- */

	public function add_settings_action_link( $links ) {
		$settings_url = admin_url( 'edit.php?post_type=signature&page=email-signatures-pro' );
		$settings_link = '<a href="' . esc_url( $settings_url ) . '">' . __( 'Settings', 'email-signatures-pro' ) . '</a>';

		array_unshift( $links, $settings_link );
		return $links;
	}

	}

	// Initialize plugin.
	Email_Signatures_Pro::instance();
} 