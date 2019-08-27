<?php
/**
 * @package hm-media
 */

namespace HM\Media\Cropper;

use function HM\Media\get_asset_url;

/**
 * Initialize the class by registering various hooks.
 */
function setup() {
	// Add initial crop data for js attachment models.
	add_filter( 'wp_prepare_attachment_for_js', __NAMESPACE__ . '\\attachment_js', 200, 3 );

	// Add scripts for cropper whenever media modal is loaded.
	add_action( 'wp_enqueue_media', __NAMESPACE__ . '\\enqueue_scripts', 1 );

	// Save crop data.
	add_action( 'wp_ajax_hm_save_crop', __NAMESPACE__ . '\\ajax_save_crop' );
	add_action( 'wp_ajax_hm_save_focal_point', __NAMESPACE__ . '\\ajax_save_focal_point' );
	add_action( 'wp_ajax_image-editor', __NAMESPACE__ . '\\on_edit_image', -10 );

	// Output backbone templates.
	add_action( 'admin_footer', __NAMESPACE__ . '\\templates' );
	add_action( 'customize_controls_print_footer_scripts', __NAMESPACE__ . '\\templates' );

	// Preserve quality when editing original.
	add_filter( 'jpeg_quality', __NAMESPACE__ . '\\jpeg_quality', 10, 2 );

	/**
	 * Tachyon settings.
	 */
	add_filter( 'tachyon_pre_args', __NAMESPACE__ . '\\tachyon_args' );
	add_filter( 'tachyon_disable_in_admin', '__return_false' );

	// Disable intermediate thumbnail file generation.
	add_filter( 'intermediate_image_sizes_advanced', __NAMESPACE__ . '\\prevent_thumbnail_generation' );

	// Add crop data.
	add_filter( 'tachyon_image_downsize_string', __NAMESPACE__ . '\\image_downsize', 10, 2 );
	add_filter( 'tachyon_post_image_args', __NAMESPACE__ . '\\image_downsize', 10, 2 );
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
		get_asset_url( 'cropper.js' ),
		[
			'jquery',
			'media-views',
			'imgareaselect',
		],
		null,
		false
	);

	/**
	 * Toggle focal point cropping support.
	 *
	 * @param bool $use_focal_point Pass true to enable focal point support.
	 */
	$use_focal_point = apply_filters( 'hm.smart-media.cropper.focal-point', true );

	wp_add_inline_script(
		'hm-smart-media-cropper',
		sprintf( 'var HM = HM || {}; HM.SmartMedia = %s;', wp_json_encode( [
			'i18n' => [
				'cropTitle' => __( 'Edit image', 'hm-smart-media' ),
				'cropSave'  => __( 'Save changes', 'hm-smart-media' ),
				'cropClose' => __( 'Close editor', 'hm-smart-media' ),
				'cropEdit'  => __( 'Edit crop', 'hm-smart-media' ),
			],
			'FocalPoint' => $use_focal_point,
		] ) )
	);
}

/**
 * Add crop data to image_downsize() tachyon args.
 *
 * @param array $tachyon_args
 * @param array $downsize_args
 * @return array
 */
function image_downsize( $tachyon_args, $downsize_args ) {
	if ( ! isset( $downsize_args['attachment_id'] ) || ! isset( $downsize_args['size'] ) ) {
		return $tachyon_args;
	}

	$crop = get_crop( $downsize_args['attachment_id'], $downsize_args['size'] );

	if ( $crop ) {
		$tachyon_args['crop'] = $crop;
	}

	return $tachyon_args;
}

/**
 * Get crop data for a given image and size.
 *
 * @param int $attachment_id
 * @param string $size
 * @return array|false
 */
function get_crop( $attachment_id, $size ) {
	// Fetch all registered image sizes.
	$sizes = get_image_sizes();

	// Check it's that passed in size exists.
	if ( ! isset( $sizes[ $size ] ) ) {
		return false;
	}

	$crop = get_post_meta( $attachment_id, "_crop_{$size}", true ) ?: [];

	// Infer crop from focal point if available.
	if ( empty( $crop ) ) {
		$meta_data = wp_get_attachment_metadata( $attachment_id );
		$size      = $sizes[ $size ];

		$focal_point = get_post_meta( $attachment_id, '_focal_point', true ) ?: [];
		$focal_point = array_map( 'absint', $focal_point );

		if ( ! empty( $focal_point ) && $size['crop'] ) {
			// Get max size of crop aspect ratio within original image.
			$dimensions = get_maximum_crop( $meta_data['width'], $meta_data['height'], $size['width'], $size['height'] );

			if ( $dimensions[0] === $meta_data['width'] && $dimensions[1] === $meta_data['height'] ) {
				return false;
			}

			$crop['width']  = $dimensions[0];
			$crop['height'] = $dimensions[1];

			// Set x & y but constrain within original image bounds.
			$crop['x'] = min( $meta_data['width'] - $crop['width'], max( 0, $focal_point['x'] - ( $crop['width'] / 2 ) ) );
			$crop['y'] = min( $meta_data['height'] - $crop['height'], max( 0, $focal_point['y'] - ( $crop['height'] / 2 ) ) );
		}
	}

	if ( empty( $crop ) ) {
		return false;
	}

	return sprintf( '%dpx,%dpx,%dpx,%dpx', $crop['x'], $crop['y'], $crop['width'], $crop['height'] );
}

/**
 * Get the maximum size of a target crop within the original image width & height.
 *
 * @param integer $width
 * @param integer $height
 * @param integer $crop_width
 * @param integer $crop_height
 * @return array
 */
function get_maximum_crop( int $width, int $height, int $crop_width, int $crop_height ) {
	$max_height = $width / $crop_width * $crop_height;

	if ( $max_height < $height ) {
		return [ $width, round( $max_height ) ];
	}

	return [ round( $height / $crop_height * $crop_width ), $height ];
}

/**
 * Add extra meta data to attachment js.
 *
 * @param  array $response
 * @param  WP_Post $attachment
 * @return array
 */
function attachment_js( $response, $attachment ) {
	if ( ! wp_attachment_is_image( $attachment ) ) {
		return $response;
	}

	if ( $response['mime'] === 'image/svg+xml' ) {
		return $response;
	}

	$meta         = wp_get_attachment_metadata( $attachment->ID );
	$backup_sizes = get_post_meta( $attachment->ID, '_wp_attachment_backup_sizes', true );

	// Check width and height are set, in rare cases it can fail.
	if ( ! isset( $meta['width'] ) || ! isset( $meta['height'] ) ) {
		trigger_error( sprintf( 'Image metadata generation failed for image ID "%d", this may require manual resolution.', $attachment->ID ), E_USER_WARNING );
		return $response;
	}

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

	// Add base Tachyon URL.
	if ( function_exists( 'tachyon_url' ) ) {
		$response['original_url'] = $response['url'];
		$response['url']          = tachyon_url( $response['url'] );
	}

	// Fill intermediate sizes array.
	$sizes = get_image_sizes();

	$response['sizes'] = array_map( function ( $size, $name ) use ( $attachment, $meta ) {
		$src = wp_get_attachment_image_src( $attachment->ID, $name );

		$size['url']      = $src[0];
		$size['width']    = $src[1];
		$size['height']   = $src[2];
		$size['cropData'] = (object) ( get_post_meta( $attachment->ID, "_crop_{$name}", true ) ?: [] );

		return $size;
	}, $sizes, array_keys( $sizes ) );

	$response['sizes'] = array_combine( array_keys( $sizes ), $response['sizes'] );

	// Focal point.
	$response['focalPoint'] = (object) ( get_post_meta( $attachment->ID, '_focal_point', true ) ?: [] );

	return $response;
}

/**
 * AJAX handler for saving the cropping coordinates of a thumbnail size for a given attachment.
 */
function ajax_save_crop() {
	// Get the attachment.
	$attachment = validate_parameters();

	check_ajax_referer( 'image_editor-' . $attachment->ID );

	if ( ! isset( $_POST['crop'] ) ) {
		wp_send_json_error( __( 'No cropping data received', 'hm-smart-media' ) );
	}

	$crop = map_deep( wp_unslash( $_POST['crop'] ), 'absint' );
	$name = sanitize_text_field( wp_unslash( $_POST['size'] ) );

	if ( ! in_array( $name, array_keys( get_image_sizes() ), true ) ) {
		wp_send_json_error( __( 'Invalid thumbnail size received', 'hm-smart-media' ) );
	}

	// Save crop coordinates.
	update_post_meta( $attachment->ID, "_crop_{$name}", $crop );

	wp_send_json_success();
}

/**
 * AJAX handler for saving the cropping coordinates of a thumbnail size for a given attachment.
 */
function ajax_save_focal_point() {
	// Get the attachment.
	$attachment = validate_parameters();

	check_ajax_referer( 'image_editor-' . $attachment->ID );

	if ( ! isset( $_POST['focalPoint'] ) ) {
		wp_send_json_error( __( 'No focal point data received', 'hm-smart-media' ) );
	}

	if ( empty( $_POST['focalPoint'] ) ) {
		delete_post_meta( $attachment->ID, '_focal_point' );
	} else {
		$focal_point = map_deep( wp_unslash( $_POST['focalPoint'] ), 'absint' );
		update_post_meta( $attachment->ID, '_focal_point', $focal_point );
	}

	wp_send_json_success();
}

/**
 * Output the Backbone templates for the Media Manager-based image cropping functionality.
 */
function templates() {
	include 'media-template.php';
}

/**
 * Makes sure that the "id" (attachment ID) is valid
 * and dies if not. Returns attachment object with matching ID on success.
 *
 * @param string $id_param The request parameter to retrieve the ID from.
 * @return WP_Post
 */
function validate_parameters( $id_param = 'id' ) {
	// phpcs:ignore
	$attachment = get_post( intval( $_REQUEST[ $id_param ] ) );

	// phpcs:ignore
	if ( empty( $_REQUEST[ $id_param ] ) || ! $attachment ) {
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
		case 'medium_large':
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
				'orientation' => $width >= $height ? 'landscape' : 'portrait',
			];
		}, $sizes
	);

	return $sizes;
}

/**
 * Deletes the coordinates for a custom crop for a given attachment ID and thumbnail size.
 *
 * @param int $attachment_id Attachment ID.
 * @param string $size Thumbnail size name.
 * @return bool False on failure (probably no such custom crop), true on success.
 */
function delete_coordinates( $attachment_id, $size ) {
	return delete_post_meta( $attachment_id, "_crop_{$size}" );
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

/**
 * Filter the default tachyon URL args.
 *
 * @param array $args
 * @return array
 */
function tachyon_args( $args ) {
	// Use smart cropping by default for resizes.
	if ( isset( $args['resize'] ) && ! isset( $args['crop'] ) ) {
		$args['crop_strategy'] = 'smart';
	}

	return $args;
}

/**
 * Remove crop data when editing original.
 */
function on_edit_image() {
	// Get the attachment.
	$attachment = validate_parameters( 'postid' );

	check_ajax_referer( 'image_editor-' . $attachment->ID );

	// Only run on a save operation.
	if ( isset( $_POST['do'] ) && ! in_array( $_POST['do'], [ 'save', 'restore' ], true ) ) {
		return;
	}

	// Only run if transformations being applied.
	if ( $_POST['do'] === 'save' && ( ! isset( $_POST['history'] ) || empty( json_decode( wp_unslash( $_POST['history'] ), true ) ) ) ) {
		return;
	}

	// Remove crops as dimensions / orientation may have changed.
	foreach ( array_keys( get_image_sizes() ) as $size ) {
		delete_coordinates( $attachment->ID, $size );
	}

	// Remove focal point.
	delete_post_meta( $attachment->ID, '_focal_point' );

	// @todo update crop coordinates according to history steps
	// @todo update focal point coordinates according to history steps
}

/**
 * Prevents WordPress generated resized thumbnails for an image.
 * We let tachyon handle this.
 *
 * @param array $sizes
 * @return array
 */
function prevent_thumbnail_generation( $sizes ) {
	if ( ! function_exists( 'tachyon_url' ) ) {
		return $sizes;
	}

	return [];
}
