<?php
/**
 * @package hm-media
 */

namespace HM\Media\Cropper;

use Tachyon;
use WP_Post;
use WP_REST_Response;

use function HM\Media\get_asset_url;

/**
 * Initialize the class by registering various hooks.
 */
function setup() {
	// Add initial crop data for js attachment models.
	add_filter( 'wp_prepare_attachment_for_js', __NAMESPACE__ . '\\attachment_js', 200, 3 );
	add_filter( 'wp_prepare_attachment_for_js', __NAMESPACE__ . '\\attachment_thumbs', 100, 2 );

	// Add tachyon URL to REST responses.
	add_filter( 'rest_prepare_attachment', __NAMESPACE__ . '\\rest_api_fields', 10, 3 );

	// Add scripts for cropper whenever media modal is loaded.
	add_action( 'wp_enqueue_media', __NAMESPACE__ . '\\enqueue_scripts', 1 );
	add_action( 'enqueue_block_editor_assets', __NAMESPACE__ . '\\unhook_amp_media_library_notice', 11 );

	// Save crop data.
	add_action( 'wp_ajax_hm_save_crop', __NAMESPACE__ . '\\ajax_save_crop' );
	add_action( 'wp_ajax_hm_remove_crop', __NAMESPACE__ . '\\ajax_save_crop' );
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
	add_filter( 'tachyon_remove_size_attributes', '__return_false' );
	if ( class_exists( 'Tachyon' ) ) {
		remove_filter( 'rest_request_before_callbacks', [ Tachyon::instance(), 'should_rest_image_downsize' ], 10, 3 );
	}

	// Ignore $content_width in REST API responses.
	add_action( 'rest_api_init', function () {
		add_filter( 'editor_max_image_size', __NAMESPACE__ . '\\editor_max_image_size', 10, 2 );
	} );

	// Disable intermediate thumbnail file generation.
	add_filter( 'intermediate_image_sizes_advanced', __NAMESPACE__ . '\\prevent_thumbnail_generation' );

	// Fake the image meta data.
	add_filter( 'wp_get_attachment_metadata', __NAMESPACE__ . '\\filter_attachment_meta_data', 20, 2 );

	// Prevent fake image meta sizes being saved to the database.
	add_filter( 'wp_update_attachment_metadata', __NAMESPACE__ . '\\filter_update_attachment_meta_data' );

	// Add crop data.
	add_filter( 'tachyon_image_downsize_string', __NAMESPACE__ . '\\image_downsize', 20, 2 );
	add_filter( 'tachyon_post_image_args', __NAMESPACE__ . '\\image_downsize', 20, 2 );

	/*
	 * Replace WordPress Core's responsive image filter with our own as
	 * the Core one doesn't work with Tachyon due to the sizing details
	 * being stored in the query string.
	 */
	add_filter( 'wp_img_tag_add_width_and_height_attr', __NAMESPACE__ . '\\img_tag_add_attr', 10, 2 );
	add_filter( 'wp_img_tag_add_srcset_and_sizes_attr', __NAMESPACE__ . '\\img_tag_add_attr', 10, 2 );

	/**
	 * Filters an img tag within the content for a given context.
	 *
	 * @param string $filtered_image Full img tag with attributes that will replace the source img tag.
	 * @param string $context        Additional context, like the current filter name or the function name from where this was called.
	 * @param int    $attachment_id  The image attachment ID. May be 0 in case the image is not an attachment.
	 * @return string Full img tag with attributes that will replace the source img tag.
	 */
	add_filter( 'wp_content_img_tag', __NAMESPACE__ . '\\content_img_tag', 10, 3 );

	// Ensure the get dimensions function understands Tachyon.
	add_filter( 'wp_image_src_get_dimensions', __NAMESPACE__ . '\\src_get_dimensions', 10, 4 );

	// Calculate srcset based on zoom modifiers.
	add_filter( 'wp_calculate_image_srcset', __NAMESPACE__ . '\\image_srcset', 10, 5 );

	// Remove the core additional sizes 1536x1536 and 2048x2048 as we use Tachyon's zoom.
	remove_image_size( '1536x1536' );
	remove_image_size( '2048x2048' );

	// Add support for URL encoded image paths.
	add_filter( 'wp_image_file_matches_image_meta', __NAMESPACE__ . '\\image_file_matches_image_meta', 10, 4 );
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
			'wp-i18n',
			'wp-hooks',
		],
		null,
		false
	);

	wp_set_script_translations( 'hm-smart-media-cropper', 'hm-smart-media' );
}

/**
 * Unhook Google AMP's media frame modifications.
 *
 * AMP adds some notices to the media library that interfere with Smart Media's edit state.
 *
 * @return void
 */
function unhook_amp_media_library_notice() {
	wp_add_inline_script(
		'amp-block-editor',
		'wp.hooks.removeFilter( \'editor.MediaUpload\', \'ampEditorBlocks/withMediaLibraryNotice\' );',
		'after'
	);
}

/**
 * Add the base tachyon URL to REST API responses.
 *
 * @param WP_REST_Response $response
 * @return WP_REST_Response
 */
function rest_api_fields( WP_REST_Response $response ) : WP_REST_Response {
	$data = $response->get_data();

	if ( is_wp_error( $data ) ) {
		return $response;
	}

	if ( is_object( $data ) ) {
		$data = (array) $data;
	}

	// Confirm it's definitely an image.
	if ( ! isset( $data['id'] ) || ! isset( $data['media_type'] ) || (strpos( $data['media_type'], 'image/' ) !== 0 ) ) {
		return $response;
	}

	// Check if we should skip this one.
	if ( skip_attachment( $data['id'] ) ) {
		return $response;
	}

	if ( isset( $data['source_url'] ) && $data['media_type'] === 'image' ) {
		$data['original_url'] = $data['source_url'];
		if ( function_exists( 'tachyon_url' ) ) {
			$data['source_url'] = tachyon_url( $data['source_url'] );
		}

		// Add focal point.
		$focal_point = get_post_meta( $data['id'], '_focal_point', true );
		if ( empty( $focal_point ) ) {
			$data['focal_point'] = null;
		} else {
			$data['focal_point'] = (object) array_map( 'absint', $focal_point );
		}
	}

	// Clean full size URL and ensure file thumbs use Tachyon URLs.
	if ( isset( $data['media_details'] ) && is_array( $data['media_details'] ) && isset( $data['media_details']['sizes'] ) ) {
		// Handle REST response sizes format.
		if ( is_object( $data['media_details']['sizes'] ) ) {
			$data['media_details']['sizes'] = (array) $data['media_details']['sizes'];
		}
		$full_size_thumb = $data['original_url'] ?? $data['media_details']['sizes']['full']['source_url'];
		foreach ( $data['media_details']['sizes'] as $name => $size ) {
			// Remove internal flag.
			unset( $size['_tachyon_dynamic'] );

			// Handle PDF / file thumbs.
			if ( $data['media_type'] !== 'image' && function_exists( 'tachyon_url' ) ) {
				if ( $name === 'full' ) {
					$size['source_url'] = tachyon_url( $full_size_thumb );
				} else {
					$size['source_url'] = tachyon_url( $full_size_thumb, [
						'resize' => sprintf( '%d,%d', $size['width'], $size['height'] ),
					] );
				}
			}

			// Handle image sizes.
			if ( $data['media_type'] === 'image' ) {
				// Correct full size image details.
				if ( $name === 'full' ) {
					$size['file'] = explode( '?', $size['file'] )[0];
					$size['source_url'] = $data['source_url'];
				}
			}

			$data['media_details']['sizes'][ $name ] = $size;
		}
	}

	$response->set_data( $data );
	return $response;
}

/**
 * Add crop data to image_downsize() tachyon args.
 *
 * @param array $tachyon_args
 * @param array $downsize_args
 * @return array
 */
function image_downsize( array $tachyon_args, array $downsize_args ) : array {
	if ( ! isset( $downsize_args['attachment_id'] ) || ! isset( $downsize_args['size'] ) ) {
		return $tachyon_args;
	}

	// The value we're picking up can be filtered and upstream bugs introduced, this will avoid fatal errors.
	// We have to check if value is "numeric" (int or string with a number) as in < WordPress 5.3
	// get_post_thumbnail_id() returns a string.
	if ( ! is_numeric( $downsize_args['attachment_id'] ) ) {
		return $tachyon_args;
	}

	$attachment_id = (int) $downsize_args['attachment_id'];
	$crop = get_crop( $attachment_id, $downsize_args['size'] );

	if ( $crop ) {
		// Remove crop strategy param if present.
		unset( $tachyon_args['crop_strategy'] );
		$tachyon_args['crop'] = sprintf( '%dpx,%dpx,%dpx,%dpx', $crop['x'], $crop['y'], $crop['width'], $crop['height'] );
	}

	return $tachyon_args;
}

/**
 * Get crop data for a given image and size.
 *
 * @param int $attachment_id
 * @param string $size
 * @return array
 */
function get_crop( int $attachment_id, string $size ) : ?array {
	// Fetch all registered image sizes.
	$sizes = get_image_sizes();

	// Check it's that passed in size exists.
	if ( ! isset( $sizes[ $size ] ) ) {
		return null;
	}

	$crop = get_post_meta( $attachment_id, "_crop_{$size}", true ) ?: [];

	// Infer crop from focal point if available.
	if ( empty( $crop ) ) {
		$meta_data = wp_get_attachment_metadata( $attachment_id );
		if ( ! $meta_data ) {
			return null;
		}

		$size = $sizes[ $size ];

		$focal_point = get_post_meta( $attachment_id, '_focal_point', true ) ?: [];
		$focal_point = array_map( 'absint', $focal_point );

		if ( ! empty( $focal_point ) && $size['crop'] ) {
			// Get max size of crop aspect ratio within original image.
			$dimensions = get_maximum_crop( $meta_data['width'], $meta_data['height'], $size['width'], $size['height'] );

			if ( $dimensions[0] === $meta_data['width'] && $dimensions[1] === $meta_data['height'] ) {
				return null;
			}

			$crop['width']  = $dimensions[0];
			$crop['height'] = $dimensions[1];

			// Set x & y but constrain within original image bounds.
			$crop['x'] = min( $meta_data['width'] - $crop['width'], max( 0, $focal_point['x'] - ( $crop['width'] / 2 ) ) );
			$crop['y'] = min( $meta_data['height'] - $crop['height'], max( 0, $focal_point['y'] - ( $crop['height'] / 2 ) ) );
		}
	}

	if ( empty( $crop ) ) {
		return null;
	}

	return $crop;
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
 * @param array $response
 * @param WP_Post $attachment
 * @return array
 */
function attachment_js( $response, $attachment ) {
	if ( ! wp_attachment_is_image( $attachment ) ) {
		return $response;
	}

	// We can't edit SVGs.
	if ( $response['mime'] === 'image/svg+xml' ) {
		return $response;
	}

	// Check if we should skip.
	if ( skip_attachment( $attachment->ID ) ) {
		return $response;
	}

	$meta = wp_get_attachment_metadata( $attachment->ID );

	if ( ! $meta ) {
		return $response;
	}

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
	if ( method_exists( 'Tachyon', 'validate_image_url' ) && Tachyon::validate_image_url( $response['url'] ) ) {
		$response['original_url'] = $response['url'];
		$response['url'] = tachyon_url( $response['url'] );
	}

	// Fill intermediate sizes array.
	$sizes = get_image_sizes();

	if ( isset( $response['sizes']['full'] ) ) {
		$full_size_attrs = $response['sizes']['full'];

		// Fill the full size manually as the Media Library needs this size.
		$sizes['full'] = [
			'width'       => $full_size_attrs['width'],
			'height'      => $full_size_attrs['height'],
			'crop'        => false,
			'orientation' => $full_size_attrs['width'] >= $full_size_attrs['height'] ? 'landscape' : 'portrait',
		];
	}

	$size_labels = apply_filters( 'image_size_names_choose', [
		'thumbnail' => __( 'Thumbnail' ),
		'medium'    => __( 'Medium' ),
		'large'     => __( 'Large' ),
		'full'      => __( 'Full Size' ),
	] );

	$response['sizes'] = array_map( function ( $size, $name ) use ( $attachment, $size_labels ) {
		$src = wp_get_attachment_image_src( $attachment->ID, $name );

		$size['name']     = $name;
		$size['label']    = $size_labels[ $name ] ?? null;
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
 * Check whether to skip an attachment for Smart Media processing.
 *
 * @uses filter hm.smart-media.skip-attachment
 *
 * @param integer $attachment_id The attachment ID to check.
 * @return boolean
 */
function skip_attachment( int $attachment_id ) : bool {
	/**
	 * Filters whether to skip a given attachment.
	 *
	 * @param bool $skip If true then the attachment should be skipped, default false.
	 * @param int $attachment_id The attachment ID to check.
	 */
	return (bool) apply_filters( 'hm.smart-media.skip-attachment', false, $attachment_id );
}

/**
 * Updates attachments that aren't images but have thumbnails
 * like PDFs to use Tachyon URLs.
 *
 * @param array $response The attachment JS.
 * @param WP_Post $attachment The attachment post object.
 * @return array
 */
function attachment_thumbs( $response, $attachment ) : array {
	if ( ! function_exists( 'tachyon_url' ) ) {
		return $response;
	}

	if ( ! is_array( $response ) || wp_attachment_is_image( $attachment ) ) {
		return $response;
	}

	if ( skip_attachment( $attachment->ID ) ) {
		return $response;
	}

	// Handle attachment thumbnails.
	$full_size_thumb = $response['sizes']['full']['url'] ?? false;

	if ( ! $full_size_thumb || ! isset( $response['sizes'] ) ) {
		return $response;
	}

	foreach ( $response['sizes'] as $name => $size ) {
		if ( $name === 'full' ) {
			$response['sizes'][ $name ]['url'] = tachyon_url( $full_size_thumb );
		} else {
			$response['sizes'][ $name ]['url'] = tachyon_url( $full_size_thumb, [
				'resize' => sprintf( '%d,%d', $size['width'], $size['height'] ),
			] );
		}
	}

	return $response;
}

/**
 * AJAX handler for saving the cropping coordinates of a thumbnail size for a given attachment.
 */
function ajax_save_crop() {
	// Get the attachment.
	$attachment = validate_parameters();

	check_ajax_referer( 'image_editor-' . $attachment->ID );

	$name = sanitize_text_field( wp_unslash( $_POST['size'] ) );

	if ( ! in_array( $name, array_keys( get_image_sizes() ), true ) ) {
		wp_send_json_error( __( 'Invalid thumbnail size received', 'hm-smart-media' ) );
	}

	$action = sanitize_key( $_POST['action'] );

	if ( $action === 'hm_save_crop' ) {
		if ( ! isset( $_POST['crop'] ) ) {
			wp_send_json_error( __( 'No cropping data received', 'hm-smart-media' ) );
		}

		$crop = map_deep( wp_unslash( $_POST['crop'] ), 'absint' );
		update_post_meta( $attachment->ID, "_crop_{$name}", $crop );
	}

	if ( $action === 'hm_remove_crop' ) {
		delete_post_meta( $attachment->ID, "_crop_{$name}" );
	}

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
		// translators: %s is replaced by the text 'id' referring to the parameter name.
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

/**
 * Fake attachment meta data to include all image sizes.
 *
 * This attempts to fix two issues:
 *  - "new" image sizes are not included in meta data.
 *  - when using Tachyon in admin and disabling resizing,
 *    NO image sizes are included in the meta data.
 *
 * @TODO Work out how to name files if crop on upload is reintroduced.
 *
 * @param $data          array The original attachment meta data.
 * @param $attachment_id int   The attachment ID.
 *
 * @return array The modified attachment data including "new" image sizes.
 */
function filter_attachment_meta_data( $data, $attachment_id ) {
	// Save time, only calculate once.
	static $cache = [];

	if ( ! empty( $cache[ $attachment_id ] ) ) {
		return $cache[ $attachment_id ];
	}

	// Only modify if valid format and for images.
	if ( ! is_array( $data ) || ! wp_attachment_is_image( $attachment_id ) ) {
		return $data;
	}

	if ( empty( $data['file'] ) ) {
		return $data;
	}

	if ( skip_attachment( $attachment_id ) ) {
		return $data;
	}

	$data = massage_meta_data_for_orientation( $data );

	// Full size image info.
	$image_sizes = get_image_sizes();
	$mime_type = get_post_mime_type( $attachment_id );
	$filename = pathinfo( $data['file'], PATHINFO_FILENAME );
	$ext = pathinfo( $data['file'], PATHINFO_EXTENSION );
	$orig_w = $data['width'];
	$orig_h = $data['height'];

	foreach ( $image_sizes as $size => $crop ) {
		if ( isset( $data['sizes'][ $size ] ) ) {
			// Meta data is set.
			continue;
		}

		if ( 'full' === $size ) {
			// Full is a special case.
			continue;
		}

		/*
		 * $new_dims = [
		 *    0 => 0
		 *    1 => 0
		 *    2 => // Crop start X axis
		 *    3 => // Crop start Y axis
		 *    4 => // New width
		 *    5 => // New height
		 *    6 => // Crop width on source image
		 *    7 => // Crop height on source image
		 * ];
		*/
		$new_dims = image_resize_dimensions( $orig_w, $orig_h, $crop['width'], $crop['height'], $crop['crop'] );

		if ( ! $new_dims ) {
			continue;
		}

		$w = (int) $new_dims[4];
		$h = (int) $new_dims[5];

		// Set crop hash if source crop isn't 0,0,orig_width,orig_height
		$crop_details = "{$orig_w},{$orig_h},{$new_dims[2]},{$new_dims[3]},{$new_dims[6]},{$new_dims[7]}";
		$crop_hash = '';

		if ( $crop_details !== "{$orig_w},{$orig_h},0,0,{$orig_w},{$orig_h}" ) {
			/*
			 * NOTE: Custom file name data.
			 *
			 * The crop hash is used to help determine the correct crop to use for identically
			 * sized images.
			 */
			$crop_hash = '-c' . substr( strtolower( sha1( $crop_details ) ), 0, 8 );
		}

		// Add meta data with fake WP style file name.
		$data['sizes'][ $size ] = [
			'_tachyon_dynamic' => true,
			'width' => $w,
			'height' => $h,
			'file' => "{$filename}{$crop_hash}-{$w}x{$h}.{$ext}",
			'mime-type' => $mime_type,
		];
	}

	$cache[ $attachment_id ] = $data;
	return $data;
}

/**
 * When saving the attachment metadata remove the dynamic sizes added
 * by the above filter.
 *
 * @param array $data The image metadata array.
 * @return array
 */
function filter_update_attachment_meta_data( array $data ) : array {
	if ( ! isset( $data['sizes'] ) ) {
		return $data;
	}
	foreach ( $data['sizes'] as $size => $size_data ) {
		if ( isset( $size_data['_tachyon_dynamic'] ) ) {
			unset( $data['sizes'][ $size ] );
		}
	}
	return $data;
}

/**
 * Swap width and height if required.
 *
 * The Tachyon service/sharp library will automatically fix
 * the orientation but as a result, the width and height will
 * be the reverse of that calculated on upload.
 *
 * This swaps the width and height if needed but it does not
 * fix the image on upload as we have Tachy for that.
 *
 * @param array $meta_data Meta data stored in the database.
 *
 * @return array Meta data with correct width and height.
 */
function massage_meta_data_for_orientation( array $meta_data ) {
	if ( empty( $meta_data['image_meta']['orientation'] ) ) {
		// No orientation data to fix.
		return $meta_data;
	}

	$fix_width_height = false;

	switch ( $meta_data['image_meta']['orientation'] ) {
		case 5:
		case 6:
		case 7:
		case 8:
		case 9:
			$fix_width_height = true;
			break;
	}

	if ( ! $fix_width_height ) {
		return $meta_data;
	}

	$width = $meta_data['height'];
	$meta_data['height'] = $meta_data['width'];
	$meta_data['width'] = $width;
	unset( $meta_data['image_meta']['orientation'] );
	return $meta_data;
}

/**
 * Check if this image matches the tachyon host and path. Allows subdomains.
 *
 * @param string $image Image HTML or URL.
 * @return boolean
 */
function is_tachyon_url( string $image ) : bool {
	if ( ! defined( 'TACHYON_URL' ) ) {
		return false;
	}

	// TACHYON_URL can be filtered on output so this is the only reliable method to
	// check an image is handled by Tachyon.
	$uploads_dir = wp_upload_dir();
	$tachyon_base_url = dirname( tachyon_url( $uploads_dir['baseurl'] . '/image.jpg' ) );

	return strpos( $image, $tachyon_base_url ) !== false;
}

/**
 * Add our special handlers for width & height attrs and srcset attributes.
 *
 * @param string $filtered_image Full img tag with attributes that will replace the source img tag.
 * @param string $context Additional context, like the current filter name or the function name from where this was called.
 * @param int $attachment_id  The image attachment ID. May be 0 in case the image is not an attachment.
 * @return string Full img tag with attributes that will replace the source img tag.
 */
function content_img_tag( string $filtered_image, string $context, int $attachment_id ) : string {
	if ( ! is_tachyon_url( $filtered_image ) ) {
		return $filtered_image;
	}

	if ( $attachment_id === 0 ) {
		return $filtered_image;
	}

	$image_meta = wp_get_attachment_metadata( $attachment_id );

	// Add 'width' and 'height' attributes if applicable.
	if ( ! str_contains( $filtered_image, ' width=' ) && ! str_contains( $filtered_image, ' height=' ) ) {
		$filtered_image = add_width_and_height_attr( $filtered_image, $image_meta );
	}

	// Add 'srcset' and 'sizes' attributes if applicable.
	if ( ! str_contains( $filtered_image, ' srcset=' ) ) {
		$filtered_image = add_srcset_and_sizes_attr( $filtered_image, $image_meta, $attachment_id );
	}

	// Call core function to add loading optimization attributes again.
	// These rely on width/heights being set correctly which is not set at the point core calls them.
	// See wp_img_tag_add_auto_sizes
	$filtered_image = wp_img_tag_add_loading_optimization_attrs( $filtered_image, $context );

	// Available in WP 6.7 only.
	if ( function_exists( 'wp_img_tag_add_auto_sizes' ) ) {
		$filtered_image = wp_img_tag_add_auto_sizes( $filtered_image );
	}

	return $filtered_image;
}

/**
 * Filters whether to add various attributes to the img tag markup.
 *
 * We override this to ensure compatibility with Tachyon & smart media.
 *
 * @param bool $value The filtered value, defaults to <code>true</code>.
 * @param string $image The HTML <code>img</code> tag where the attribute should be added.
 * @param string $context Additional context about how the function was called or where the img tag is.
 * @param int $attachment_id The image attachment ID.
 * @return bool The filtered value, defaults to <code>true</code>.
 */
function img_tag_add_attr( bool $value, string $image ) : bool {
	return ! is_tachyon_url( $image ) ? $value : false;
}

/**
 * Get the image dimensions from the img src attribute.
 *
 * @TODO Deal with edit hashes by getting the previous version of the meta
 *       data if required for calculating the srcset using the meta value of
 *       `_wp_attachment_backup_sizes`. To get the edit hash, refer to
 *       wp/wp-includes/media.php:1380
 *
 * @param string $image_src The extracted image src.
 * @param array $image_meta The attachment meta data.
 * @return false|array Returns an array of [width, height] on success, false on failure.
 */
function get_img_src_dimensions( $image_src, $image_meta ) {
	if ( empty( $image_meta ) || empty( $image_meta['file'] ) ) {
		return false;
	}

	// Bail early if an image has been inserted and later edited.
	list( $image_path ) = explode( '?', $image_src );
	if ( preg_match( '/-e[0-9]{13}/', $image_meta['file'], $img_edit_hash ) &&
		strpos( wp_basename( $image_path ), $img_edit_hash[0] ) === false ) {
		return false;
	}

	$width = false;
	$height = false;

	parse_str( html_entity_decode( wp_parse_url( $image_src, PHP_URL_QUERY ) ?? '' ), $tachyon_args );

	// Need to work back width and height from various Tachyon options.
	if ( isset( $tachyon_args['resize'] ) ) {
		// Image is cropped.
		list( $width, $height ) = explode( ',', $tachyon_args['resize'] );
	} elseif ( isset( $tachyon_args['fit'] ) ) {
		// Image is uncropped.
		list( $width, $height ) = explode( ',', $tachyon_args['fit'] );
		list( $width, $height ) = wp_constrain_dimensions( $image_meta['width'], $image_meta['height'], $width, $height ?? 0 );
	} else {
		if ( isset( $tachyon_args['w'] ) ) {
			$width = (int) $tachyon_args['w'];
		}
		if ( isset( $tachyon_args['h'] ) ) {
			$height = (int) $tachyon_args['h'];
		}
		if ( ! $width && ! $height ) {
			$width = $image_meta['width'] ?: false;
			$height = $image_meta['height'] ?: false;
		}
		if ( $width && ! $height ) {
			list( $width, $height ) = wp_constrain_dimensions( $image_meta['width'], $image_meta['height'], $width );
		} elseif ( ! $width && $height ) {
			list( $width, $height ) = wp_constrain_dimensions( $image_meta['width'], $image_meta['height'], 0, $height );
		}
	}

	// Still stumped?
	if ( ! $width || ! $height ) {
		return false;
	}

	return [ $width, $height ];
}

/**
 * Filters the default method for getting image dimensions.
 *
 * @param array $dimensions List of width and height dimensions.
 * @param string $image_src The current image src URL.
 * @param array $image_meta Attachment metadata.
 * @return void
 */
function src_get_dimensions( $dimensions, $image_src, $image_meta ) {
	return get_img_src_dimensions( $image_src, $image_meta ) ?: $dimensions;
}

/**
 * Adds 'width' and 'height' attributes to an existing 'img' element.
 *
 * @param string $image The extracted image tag.
 * @param array $image_meta The image meta data as returned by 'wp_get_attachment_metadata()'.
 *
 * @return string Converted 'img' element with 'srcset' and 'sizes' attributes added.
 */
function add_width_and_height_attr( $image, $image_meta ) : string {
	if ( empty( $image_meta ) ) {
		return $image;
	}

	$image_src = preg_match( '/src="([^"]+)"/', $image, $match_src ) ? $match_src[1] : '';

	// Return early if we couldn't get the image source.
	if ( ! $image_src ) {
		return $image;
	}

	// Calculate width & height.
	$size_array = get_img_src_dimensions( $image_src, $image_meta );
	if ( ! $size_array ) {
		return $image;
	}

	// Make absolutely sure that height and width attributes are accurate.
	list( $width, $height ) = wp_constrain_dimensions( $size_array[0], $size_array[1], $image_meta['width'], $image_meta['height'] );

	$hw = trim( image_hwstring( $width, $height ) );
	return str_replace( '<img', "<img {$hw}", $image );
}

/**
 * Adds 'srcset' and 'sizes' attributes to an existing 'img' element.
 *
 * @param string $image The image tag.
 * @param array $image_meta The image meta data as returned by 'wp_get_attachment_metadata()'.
 * @param int   $attachment_id Image attachment ID.
 *
 * @return string Converted 'img' element with 'srcset' and 'sizes' attributes added.
 */
function add_srcset_and_sizes_attr( $image, $image_meta, $attachment_id ) : string {
	if ( empty( $image_meta ) ) {
		$image_meta = wp_get_attachment_metadata( $attachment_id );
	}

	if ( empty( $image_meta ) ) {
		return $image;
	}

	$image_src = preg_match( '/src="([^"]+)"/', $image, $match_src ) ? $match_src[1] : '';

	// Return early if we couldn't get the image source.
	if ( ! $image_src ) {
		return $image;
	}

	// Calculate width & height.
	$size_array = get_img_src_dimensions( $image_src, $image_meta );
	if ( ! $size_array ) {
		return $image;
	}

	$srcset = wp_calculate_image_srcset( $size_array, $image_src, $image_meta, $attachment_id );

	if ( $srcset ) {
		// Check if there is already a 'sizes' attribute.
		$sizes = strpos( $image, ' sizes=' );
		if ( ! $sizes ) {
			$sizes = wp_calculate_image_sizes( $size_array, $image_src, $image_meta, $attachment_id );
		}
	}

	if ( $srcset && $sizes ) {
		// Format the 'srcset' and 'sizes' string and escape attributes.
		$attr = sprintf( ' srcset="%s"', esc_attr( $srcset ) );
		if ( is_string( $sizes ) ) {
			$attr .= sprintf( ' sizes="%s"', esc_attr( $sizes ) );
		}
		// Add 'srcset' and 'sizes' attributes to the image markup.
		$image = preg_replace( '/<img ([^>]+?)[\/ ]*>/', '<img $1' . $attr . ' />', $image );
	}

	return $image;
}

/**
 * Return a list of modifiers for calculating image srcset and sizes from.
 *
 * @return array
 */
function get_image_size_modifiers( ?int $attachment_id = null ) : array {
	/**
	 * Filters the default image size modifiers. By default
	 * the srcset will contain a 2x, 1.5x, 0.5x and 0.25x version of the image.
	 *
	 * @param array $modifiers The zoom values for the srcset.
	 * @param int|null $attachment_id The attachment ID or null.
	 */
	$modifiers = apply_filters( 'hm.smart-media.image-size-modifiers', [ 2, 1.5, 0.5, 0.25 ], $attachment_id );

	// Ensure original size is part of srcset as some browsers won't use original
	// size if any srcset values are present.
	$modifiers[] = 1;
	$modifiers = array_unique( $modifiers );

	// Sort from highest to lowest.
	rsort( $modifiers );
	return $modifiers;
}

/**
 * Update the sources array to return tachyon URLs that respect
 * requested image aspect ratio and crop data.
 *
 * @param array   $sources       Array of source URLs and widths to generate the srcset attribute from.
 * @param array   $size_array    Width and height of the original image.
 * @param string  $image_src     The requested image URL.
 * @param array   $image_meta    The image meta data array.
 * @param integer $attachment_id The image ID.
 * @return array
 */
function image_srcset( array $sources, array $size_array, string $image_src, array $image_meta, int $attachment_id ) : array {

	list( $width, $height ) = array_map( 'absint', $size_array );

	// Ensure this is _not_ a tachyon image, not always the case when parsing from post content.
	if ( ! is_tachyon_url( $image_src ) ) {
		// If the aspect ratio requested matches a custom crop size, pull that
		// crop (in case there's a user custom crop). Otherwise just use the
		// given dimensions.
		$size = [ $width, $height ];
		// Avoid errors if either dimension is zero, natural aspect ratio requested so no crop.
		if ( $width && $height ) {
			$size = nearest_defined_crop_size( $width / $height ) ?: $size;
		}

		// Get the tachyon URL for this image size.
		$image_src = wp_get_attachment_image_url( $attachment_id, $size );
	}

	// Multipliers for output srcset.
	$modifiers = get_image_size_modifiers( $attachment_id );

	// Replace sources array.
	$sources = [];

	// Resize method.
	$method = 'resize';
	preg_match( '/(fit|resize|lb)=/', $image_src, $matches );
	if ( isset( $matches[1] ) ) {
		$method = $matches[1];
	}

	foreach ( $modifiers as $modifier ) {
		$target_width = round( $width * $modifier );

		// Do not append a srcset size larger than the original.
		if ( $target_width > $image_meta['width'] ) {
			continue;
		}

		// Apply zoom to the image to get automatic quality adjustment.
		$zoomed_image_url = add_query_arg( [
			'w' => false,
			'h' => false,
			$method => rawurlencode( "{$width},{$height}" ),
			'zoom' => rawurlencode( $modifier ),
		], $image_src );

		// Append the new target width to the sources array.
		$sources[ $target_width ] = [
			'url' => $zoomed_image_url,
			'descriptor' => 'w',
			'value' => $target_width,
		];
	}

	// Sort by keys largest to smallest.
	krsort( $sources );

	return $sources;
}

/**
 * Returns the closest defined crop size to a given ratio.
 *
 * If there is a theme crop defined with proportions similar enough to the
 * source image,returns the name of that crop size. Otherwise, returns "full".
 *
 * @param float $ratio Width to height ratio of an image.
 * @return string|null Closest defined image size to that ratio; null if none match.
 */
function nearest_defined_crop_size( $ratio ) {
	// Get only the custom image sizes that are croppable.
	$croppable_sizes = array_filter( wp_get_additional_image_sizes(), function ( $size ) {
		return absint( $size['width'] ) && absint( $size['height'] );
	} );
	/*
	 * Compare each of the theme crops to the ratio in question. Returns a
	 * sort-of difference where 0 is identical. Not mathematically meaningful
	 * at scale, but good enough for checking if something is within 2%.
	 */
	$difference_from_theme_crop_ratios = array_map(
		/**
		 * Get the difference between the aspect ratio of a given crop size and an expected ratio.
		 *
		 * @param [] $crop_data Image size definition for a custom crop size.
		 * @return float Difference between expected and actual aspect ratios: 0 = identical, 1.0 = +-100%.
		 */
		function( $crop_data ) use ( $ratio ) {
			$crop_ratio = ( $crop_data['width'] / $crop_data['height'] );
			return abs( $crop_ratio / $ratio - 1 );
		},
		// ... of all the custom image sizes defined.
		$croppable_sizes
	);
	// Sort the differences from most to least similar.
	asort( $difference_from_theme_crop_ratios, SORT_NUMERIC );
	/*
	 * If the most similar crop from the defined image sizes is within 2% of
	 * the requested dimensions, use it. Otherwise just treat this as an
	 * uncropped image and use the full size image.
	 */
	return ( current( $difference_from_theme_crop_ratios ) < 0.02 ) ?
		key( $difference_from_theme_crop_ratios ) : null;
}

/**
 * Ignore the $content_width global in the display context.
 *
 * @param array $size_array
 * @param string|array $size
 * @return array
 */
function editor_max_image_size( array $size_array, $size ) : array {

	if ( is_array( $size ) ) {
		return $size;
	}

	$sizes = get_image_sizes();

	if ( ! isset( $sizes[ $size ] ) ) {
		return $size_array;
	}

	return [
		$sizes[ $size ]['width'],
		$sizes[ $size ]['height'],
	];
}

/**
 * Filter wp_image_file_matches_image_meta() to add support for URL encoded images.
 *
 * In some cases Tachyon or smart media can return or store a URL encoded image src.
 * WordPress checks the passed image URL against the image metadata as a safety check
 * when editing images inline but it does not account for URL encoded paths.
 *
 * @param bool $match Whether the image has matched or not.
 * @param string $image_location The image URL to compare.
 * @param array $image_meta Attachment meta data.
 * @param int $attachment_id The attachment ID.
 * @return bool
 */
function image_file_matches_image_meta( bool $match, string $image_location, array $image_meta, int $attachment_id ) : bool {
	// Return found matches immediately.
	if ( $match ) {
		return $match;
	}

	// Bail if we've already checked this URL.
	if ( isset( $image_meta['is_url_decoded'] ) ) {
		return $match;
	}

	// Ignore any URLs that definitely don't contain URL encoded characters.
	if ( strpos( $image_location, '%' ) === false ) {
		return $match;
	}

	// URL decode the image src.
	$image_location = urldecode( $image_location );

	// Add a flag to image meta to avoid recursion.
	$image_meta['is_url_decoded'] = true;

	// Check again for a match.
	return wp_image_file_matches_image_meta( $image_location, $image_meta, $attachment_id );
}
