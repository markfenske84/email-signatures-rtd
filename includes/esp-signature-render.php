<?php
/**
 * Signature rendering helpers — email-safe output and shared context.
 *
 * @package Email_Signatures_RTD
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Brand constants used by preview and email renderers.
 *
 * @return array<string, mixed>
 */
function esp_get_signature_brand() {
	return array(
		'primary'        => '#384E89',
		'secondary'      => '#54A6DB',
		'neutral'        => '#42454C',
		'tertiary'       => '#EB3546',
		'fonts_url'      => 'https://fonts.googleapis.com/css2?family=Outfit:wght@500;700&family=Red+Hat+Mono:wght@300&display=swap',
		'heading_css'    => "'Outfit', Arial, sans-serif",
		'body_css'       => "'Red Hat Mono', 'Courier New', monospace",
		'company_logo'   => plugins_url( 'assets/imgs/rtd-logo@2x.png', dirname( __DIR__ ) . '/email-signatures-rtd.php' ),
		'company_logo_w' => 138,
		'company_logo_h' => 45,
		'avatar_display' => 86,
		'site_url_raw'   => 'https://rtdlogistics.com/',
		'site_domain'    => 'rtdlogistics.com',
	);
}

/**
 * Format phone number for display with dot separators.
 *
 * @param string $phone_number Raw phone input.
 * @return array{phone_digits: string, phone_display: string}
 */
function esp_format_phone( $phone_number ) {
	$phone_digits  = preg_replace( '/\D+/', '', (string) $phone_number );
	$phone_display = $phone_digits;

	if ( 10 === strlen( $phone_digits ) ) {
		$phone_display = substr( $phone_digits, 0, 3 ) . '.' . substr( $phone_digits, 3, 3 ) . '.' . substr( $phone_digits, 6 );
	} elseif ( 11 === strlen( $phone_digits ) && '1' === $phone_digits[0] ) {
		$phone_display = substr( $phone_digits, 1, 3 ) . '.' . substr( $phone_digits, 4, 3 ) . '.' . substr( $phone_digits, 7 );
	} elseif ( 7 === strlen( $phone_digits ) ) {
		$phone_display = substr( $phone_digits, 0, 3 ) . '.' . substr( $phone_digits, 3 );
	}

	return array(
		'phone_digits'  => $phone_digits,
		'phone_display' => $phone_display,
	);
}

/**
 * Resolve avatar URL for a post or attachment ID.
 *
 * @param int $post_id       Signature post ID.
 * @param int $thumbnail_id  Optional override attachment ID.
 * @return string
 */
function esp_get_avatar_url( $post_id, $thumbnail_id = 0 ) {
	$attachment_id = $thumbnail_id ? absint( $thumbnail_id ) : get_post_thumbnail_id( $post_id );

	if ( $attachment_id ) {
		$url = wp_get_attachment_image_url( $attachment_id, 'esp-avatar' );
		if ( $url ) {
			return $url;
		}
		$url = wp_get_attachment_image_url( $attachment_id, 'large' );
		if ( $url ) {
			return $url;
		}
		return (string) wp_get_attachment_image_url( $attachment_id, 'full' );
	}

	$url = get_the_post_thumbnail_url( $post_id, 'esp-avatar' );
	if ( $url ) {
		return $url;
	}
	$url = get_the_post_thumbnail_url( $post_id, 'large' );
	if ( $url ) {
		return $url;
	}
	return (string) get_the_post_thumbnail_url( $post_id, 'full' );
}

/**
 * Load generated PNG metadata from post meta or preview transient.
 *
 * @param int    $post_id     Signature post ID.
 * @param string $preview_key Preview transient key.
 * @return array<string, array{url: string, width: int, height: int}|null>
 */
function esp_get_generated_images( $post_id, $preview_key = '' ) {
	$generated   = array(
		'header' => null,
		'phone'  => null,
		'site'   => null,
	);
	$image_sources = array();

	if ( $preview_key ) {
		$preview = esp_get_preview_transient( $preview_key );
		if ( $preview && ! empty( $preview['images'] ) && is_array( $preview['images'] ) ) {
			$image_sources = $preview['images'];
		}
	} else {
		foreach ( array_keys( $generated ) as $field ) {
			$attach_id = get_post_meta( $post_id, '_esp_signature_image_' . $field, true );
			if ( $attach_id ) {
				$image_sources[ $field ] = absint( $attach_id );
			}
		}
	}

	foreach ( array_keys( $generated ) as $field ) {
		if ( empty( $image_sources[ $field ] ) ) {
			continue;
		}
		$src = wp_get_attachment_image_src( absint( $image_sources[ $field ] ), 'full' );
		if ( $src ) {
			$generated[ $field ] = array(
				'url'    => $src[0],
				'width'  => (int) round( $src[1] / 2 ),
				'height' => (int) round( $src[2] / 2 ),
			);
		}
	}

	return $generated;
}

/**
 * Build full template context for a signature view.
 *
 * @param WP_Post $post        Signature post object.
 * @param array   $overrides   Optional field overrides.
 * @param string  $preview_key Preview transient key.
 * @return array<string, mixed>
 */
function esp_build_signature_context( $post, $overrides = array(), $preview_key = '' ) {
	$brand = esp_get_signature_brand();
	$data  = array_merge(
		array(
			'post_id'      => $post->ID,
			'title'        => get_the_title( $post ),
			'job_title'    => get_post_meta( $post->ID, '_esp_job_title', true ),
			'phone_number' => get_post_meta( $post->ID, '_esp_phone_number', true ),
			'thumbnail_id' => get_post_thumbnail_id( $post->ID ),
			'is_preview'   => false,
			'preview_key'  => '',
		),
		$brand
	);

	if ( $preview_key ) {
		$preview = esp_get_preview_transient( $preview_key );
		if ( $preview && (int) $preview['user_id'] === get_current_user_id() && (int) $preview['post_id'] === (int) $post->ID ) {
			$data['title']        = $preview['title'] ?? $data['title'];
			$data['job_title']    = $preview['job_title'] ?? $data['job_title'];
			$data['phone_number'] = $preview['phone_number'] ?? $data['phone_number'];
			$data['thumbnail_id'] = isset( $preview['thumbnail_id'] ) ? absint( $preview['thumbnail_id'] ) : $data['thumbnail_id'];
			$data['is_preview']   = true;
			$data['preview_key']  = $preview_key;
		}
	}

	if ( ! empty( $overrides ) ) {
		$data = array_merge( $data, $overrides );
	}

	$phone                         = esp_format_phone( $data['phone_number'] );
	$data['phone_digits']          = $phone['phone_digits'];
	$data['phone_display']         = $phone['phone_display'];
	$data['avatar_url']            = esp_get_avatar_url( $post->ID, $data['thumbnail_id'] );
	$data['generated']             = esp_get_generated_images( $post->ID, $data['preview_key'] );
	$data['need_render']           = ( ! $data['generated']['header'] || ! $data['generated']['site'] || ( $data['phone_display'] && ! $data['generated']['phone'] ) );
	$data['header_alt']            = trim( $data['title'] . ( $data['job_title'] ? ', ' . $data['job_title'] : '' ) );

	return $data;
}

/**
 * Render email-safe signature table HTML (PNG images only, no live text/divs).
 *
 * @param array<string, mixed> $context Signature context from esp_build_signature_context().
 * @return string
 */
function esp_render_signature_email_html( $context ) {
	if ( ! empty( $context['need_render'] ) ) {
		return '';
	}

	ob_start();
	include dirname( __DIR__ ) . '/templates/partials/signature-email.php';
	return (string) ob_get_clean();
}

/**
 * Get preview transient payload.
 *
 * @param string $preview_key Transient key.
 * @return array<string, mixed>|null
 */
function esp_get_preview_transient( $preview_key ) {
	$preview_key = sanitize_key( $preview_key );
	if ( ! $preview_key ) {
		return null;
	}
	$data = get_transient( $preview_key );
	return is_array( $data ) ? $data : null;
}

/**
 * Delete preview transient and its staged attachment IDs.
 *
 * @param string $preview_key Transient key.
 */
function esp_delete_preview_transient( $preview_key ) {
	$preview = esp_get_preview_transient( $preview_key );
	if ( $preview && ! empty( $preview['images'] ) && is_array( $preview['images'] ) ) {
		foreach ( $preview['images'] as $attachment_id ) {
			if ( $attachment_id ) {
				wp_delete_attachment( absint( $attachment_id ), true );
			}
		}
	}
	delete_transient( $preview_key );
}

/**
 * Image meta keys for generated signature PNGs.
 *
 * @return string[]
 */
function esp_signature_image_meta_keys() {
	return array(
		'_esp_signature_image_header',
		'_esp_signature_image_phone',
		'_esp_signature_image_site',
		'_esp_signature_image_name',
		'_esp_signature_image_title',
		'_esp_signature_image_phone_only',
	);
}

/**
 * Delete generated signature PNG attachments for a post.
 *
 * @param int $post_id Post ID.
 */
function esp_clear_signature_images( $post_id ) {
	foreach ( esp_signature_image_meta_keys() as $key ) {
		$attachment_id = get_post_meta( $post_id, $key, true );
		if ( $attachment_id ) {
			wp_delete_attachment( absint( $attachment_id ), true );
			delete_post_meta( $post_id, $key );
		}
	}
	clean_post_cache( $post_id );
}
