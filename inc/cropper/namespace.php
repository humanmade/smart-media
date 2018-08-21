<?php
/**
 * @package hm-media
 */

namespace HM\Media\Cropper;

/**
 * Initialize the class by registering various hooks.
 */
function setup() {
	// Add initial crop data for js attachment models.
	add_filter( 'wp_prepare_attachment_for_js', __NAMESPACE__ . '\\attachment_js', 10, 3 );

	// Admin-only hooks.
	if ( is_admin() ) {
		// Add scripts for cropper whenever media modal is loaded.
		add_action( 'wp_enqueue_media', __NAMESPACE__ . '\\enqueue_scripts', 200 );
		// Modify attachment JS data.
		add_action( 'wp_ajax_hm_thumbnail_save', __NAMESPACE__ . '\\ajax_thumbnail_save' );
		// Output backbone templates.
		add_action( 'admin_footer', __NAMESPACE__ . '\\templates' );
	}

	// Preserve quality when editing original.
	add_filter( 'jpeg_quality', __NAMESPACE__ . '\\jpeg_quality', 10, 2 );

	/**
	 * Tachyon settings.
	 */

	// Default to smart cropping.
	add_filter( 'tachyon_pre_args', function ( $args ) {
		if ( isset( $args['resize'] ) ) {
			$args['crop_strategy'] = 'smart';
		}
		return $args;
	} );

	// Use tachyon in the admin.
	add_filter( 'tachyon_disable_in_admin', '__return_false' );

	// @todo Disable intermediate size generation but preserve sizes metadata.
}

/**
 * Queue up the image editing views & states.
 *
 * @param boolean $hook
 * @return void
 */
function enqueue_scripts( $hook = false ) {
	wp_enqueue_script(
		'hm-smart-media-cropper',
		plugins_url( '/build/cropper.js', __FILE__ ),
		[
			'jquery',
			'media-views',
			'imgareaselect',
		],
		null,
		true
	);

	wp_add_inline_script(
		'hm-smart-media-cropper',
		sprintf( 'var HM = HM || {}; HM.SmartMedia = %s;', wp_json_encode( [
			'i18n' => [
				'cropTitle' => __( 'Edit image', 'hm-smart-media' ),
				'cropSave'  => __( 'Save changes', 'hm-smart-media' ),
				'cropClose' => __( 'Close editor', 'hm-smart-media' ),
				'cropEdit'  => __( 'Edit crop', 'hm-smart-media' ),
			],
			'nonces' => [
				'crop' => wp_create_nonce( 'hm_save_crop' ),
			],
			'sizes' => get_image_sizes(),
		] ) )
	);
}

/**
 * Add extra meta data to attachment js.
 *
 * @param  array $response
 * @param  WP_Post $attachment
 * @return array
 */
function attachment_js( $response, $attachment ) {
	$meta         = wp_get_attachment_metadata( $attachment->ID );
	$backup_sizes = get_post_meta( $attachment->ID, '_wp_attachment_backup_sizes', true );

	$big   = max( $meta['width'], $meta['height'] );
	$sizer = $big > 400 ? 400 / $big : 1;

	// Add capabilities and permissions for imageEdit.
	$response['editor'] = [
		'nonce' => wp_create_nonce( "image_editor-{$attachment->ID}" ),
		'sizer' => $sizer,
		'can'   => [
			'rotate' => wp_image_editor_supports( [
				'mime_type' => get_post_mime_type( $attachment->ID ),
				'methods'   => [ 'rotate' ],
			] ),
			'restore' => false,
		],
	];

	if ( ! empty( $backup_sizes ) && isset( $backup_sizes['full-orig'], $meta['file'] ) ) {
		$response['editor']['can']['restore'] = $backup_sizes['full-orig']['file'] !== basename( $meta['file'] );
	}

	// Fill intermediate sizes array.
	$sizes = get_image_sizes();

	$response['sizes'] = array_map( function ( $size, $name ) use ( $attachment, $meta ) {
		$src = wp_get_attachment_image_src( $attachment->ID, $name );

		$size['url']    = $src[0];
		$size['width']  = $src[1];
		$size['height'] = $src[2];
		$size['cropTo'] = implode( ',', (array) get_post_meta( $attachment->ID, "_crop_{$name}", true ) ?: [] );

		return $size;
	}, $sizes, array_keys( $sizes ) );
	$response['sizes'] = array_combine( array_keys( $sizes ), $response['sizes'] );

	return $response;
}

/**
 * AJAX handler for saving the cropping coordinates of a thumbnail size for a given attachment.
 */
function ajax_thumbnail_save() {
	$attachment = validate_parameters();

	check_ajax_referer( 'hm_save_crop' );

	if ( ! isset( $_POST['crop'] ) ) {
		wp_send_json_error( __( 'No cropping data was received, that shouldn\'t happen :/', 'hm-smart-media' ) );
	}

	$crop = map_deep( wp_unslash( $_POST['crop'] ), 'absint' );

	// Save crop coordinates.
	update_post_meta( $attachment->ID, '_hm_smart_media_crop', $crop );

	wp_send_json_success();
}

/**
 * Output the Backbone templates for the Media Manager-based image cropping functionality.
 */
function templates() {
	include 'media-template.php';
}

/**
 * Makes sure that the "id" (attachment ID) and "size" (thumbnail size) query string parameters are valid
 * and dies if they are not. Returns attachment object with matching ID on success.
 *
 * @return null|object Dies on error, returns attachment object on success.
 */
function validate_parameters() {
	$attachment = get_post( intval( $_REQUEST['id'] ) );

	if ( empty( $_REQUEST['id'] ) || ! $attachment ) {
		// translators: %s is replaced by 'id' referring to the attachment ID.
		wp_die( sprintf( esc_html__( 'Invalid %s parameter.', 'hm-smart-media' ), '<code>id</code>' ) );
	}

	if ( 'attachment' !== $attachment->post_type || ! wp_attachment_is_image( $attachment->ID ) ) {
		wp_die( sprintf( esc_html__( 'That is not a valid image attachment.', 'hm-smart-media' ), '<code>id</code>' ) );
	}

	if ( ! current_user_can( get_post_type_object( $attachment->post_type )->cap->edit_post, $attachment->ID ) ) {
		wp_die( esc_html__( 'You are not allowed to edit this attachment.', 'hm-smart-media' ) );
	}

	return $attachment;
}

/**
 * Returns the width and height of a given thumbnail size.
 *
 * @param  string $size Thumbnail size name.
 * @return array|false Associative array of width and height in pixels. False on invalid size.
 */
function get_thumbnail_dimensions( $size ) {
	global $_wp_additional_image_sizes;

	switch ( $size ) {
		case 'thumbnail':
		case 'medium':
		case 'large':
			$width  = get_option( $size . '_size_w' );
			$height = get_option( $size . '_size_h' );
			break;
		default:
			if ( empty( $_wp_additional_image_sizes[ $size ] ) ) {
				return false;
			}

			$width  = $_wp_additional_image_sizes[ $size ]['width'];
			$height = $_wp_additional_image_sizes[ $size ]['height'];
	}

	// Just to be safe
	$width  = (int) $width;
	$height = (int) $height;

	return [
		'width' => $width,
		'height' => $height,
	];
}

/**
 * Gets all image sizes as keyed array with width, height and crop values.
 *
 * @return array
 */
function get_image_sizes() {
	global $_wp_additional_image_sizes;

	$sizes = \get_intermediate_image_sizes();
	$sizes = array_combine( $sizes, $sizes );

	// Extract dimensions and crop setting.
	$sizes = array_map(
		function ( $size ) use ( $_wp_additional_image_sizes ) {
			if ( isset( $_wp_additional_image_sizes[ $size ] ) ) {
				$width  = intval( $_wp_additional_image_sizes[ $size ]['width'] );
				$height = intval( $_wp_additional_image_sizes[ $size ]['height'] );
				$crop   = (bool) $_wp_additional_image_sizes[ $size ]['crop'];
			} else {
				$width  = intval( get_option( "{$size}_size_w" ) );
				$height = intval( get_option( "{$size}_size_h" ) );
				$crop   = (bool) get_option( "{$size}_crop" );
			}

			return [
				'width'       => $width,
				'height'      => $height,
				'crop'        => $crop,
				'orientation' => $width > $height ? 'landscape' : 'portrait',
			];
		}, $sizes
	);

	return $sizes;
}

/**
 * Fetches the coordinates for a custom crop for a given attachment ID and thumbnail size.
 *
 * @param  int $attachment_id Attachment ID.
 * @param  string $size Thumbnail size name.
 * @return array|false Array of crop coordinates or false if no custom selection set.
 */
function get_coordinates( $attachment_id, $size ) {
	$sizes = (array) get_post_meta( $attachment_id, '_hm_smart_media_crop', true );

	$coordinates = false;

	if ( ! empty( $sizes[ $size ] ) ) {
		$coordinates = $sizes[ $size ];
	}

	return $coordinates;
}

/**
 * Saves the coordinates for a custom crop for a given attachment ID and thumbnail size.
 *
 * @param int $attachment_id Attachment ID.
 * @param string $size Thumbnail size name.
 * @param array $coordinates Array of coordinates in the format array( x, y, width, height )
 */
function save_coordinates( $attachment_id, $size, $coordinates ) {
	$sizes = (array) get_post_meta( $attachment_id, '_hm_smart_media_crop', true );

	$sizes[ $size ] = $coordinates;

	update_post_meta( $attachment_id, '_hm_smart_media_crop', $sizes );
}

/**
 * Deletes the coordinates for a custom crop for a given attachment ID and thumbnail size.
 *
 * @param int $attachment_id Attachment ID.
 * @param string $size Thumbnail size name.
 * @return bool False on failure (probably no such custom crop), true on success.
 */
function delete_coordinates( $attachment_id, $size ) {
	$sizes = get_post_meta( $attachment_id, '_hm_smart_media_crop', true );

	if ( empty( $sizes ) ) {
		return false;
	}

	if ( empty( $sizes[ $size ] ) ) {
		return false;
	}

	unset( $sizes[ $size ] );

	return update_post_meta( $attachment_id, '_hm_smart_media_crop', $sizes );
}

/**
 * Force 100% quality for image edits as we only allow editing the original.
 *
 * @param int $quality Percentage quality from 0-100.
 * @param string $context The context for the change in jpeg quality.
 * @return int
 */
function jpeg_quality( $quality, $context = '' ) {
	if ( $context === 'edit_image' ) {
		return 100;
	}

	return $quality;
}
