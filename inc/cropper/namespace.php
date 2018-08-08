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
		add_action( 'wp_enqueue_media', __NAMESPACE__ . '\\enqueue_scripts' );
		add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\\enqueue_scripts' );

		add_action( 'wp_ajax_hm_thumbnail_save', __NAMESPACE__ . '\\ajax_thumbnail_save' );
		add_action( 'admin_footer', __NAMESPACE__ . '\\templates' );
	}

	// Tachyon settings.
	add_filter( 'tachyon_pre_args', function ( $args ) {
		if ( isset( $args['resize'] ) ) {
			$args['crop_strategy'] = 'smart';
		}
		return $args;
	} );

	add_filter( 'tachyon_disable_in_admin', '__return_false' );
}

function enqueue_scripts( $hook = false ) {
	wp_enqueue_script(
		'hm-media-tools-cropper',
		plugins_url( '/cropper.js', __FILE__ ),
		[
			'jquery',
			'media-views',
			'imgareaselect',
		],
		null,
		true
	);

	wp_enqueue_style( 'hm-smart-media-cropper', plugins_url( '/cropper.css', __FILE__ ), [], null );

	wp_localize_script(
		'hm-media-tools-cropper', 'HM_MEDIA_TOOLS', [
			'i18n' => [
				'cropTitle' => __( 'Edit image', 'hm-media-tools' ),
				'cropSave'  => __( 'Save changes', 'hm-media-tools' ),
				'cropClose' => __( 'Close editor', 'hm-media-tools' ),
				'cropEdit'  => __( 'Edit crop', 'hm-media-tools' ),
			],
			'nonces' => [
				'crop'  => wp_create_nonce( 'hm_save_crop' ),
			],
			'sizes' => get_image_sizes(),
		]
	);
}

/**
 * Add crop data to attachment js
 *
 * @param  array $response
 * @param  WP_Post $attachment
 * @return array
 */
function attachment_js( $response, $attachment ) {
	$response['crop'] = array_filter( (array) get_post_meta( $attachment->ID, '_hm_smart_media_crop', true ) );

	return $response;
}

/**
 * AJAX handler for saving the cropping coordinates of a thumbnail size for a given attachment.
 */
function ajax_thumbnail_save() {
	$attachment = validate_parameters();

	check_ajax_referer( 'hm_save_crop' );

	if ( ! isset( $_POST['crop'] ) ) {
		wp_send_json_error( __( 'No cropping data was received, that shouldn\'t happen :/', 'hm-media-tools' ) );
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
	?>
	<script type="text/template" id="tmpl-hm-thumbnail-container">
		<div id="hm-thumbnail-container">
			<div class="spinner"></div>
		</div>
	</script>
	<script type="text/template" id="tmpl-hm-thumbnail-sizes">
		<div class="hm-thumbnail-sizes">
			<h2><?php esc_html_e( 'Thumbnails', 'hm-smart-media' ); ?></h2>
			<div class="hm-thumbnail-sizes-list"></div>
		</div>
	</script>
	<script type="text/template" id="tmpl-hm-thumbnail-size">
		<div class="hm-thumbnail-size-select">
			<label><?php esc_html_e( 'Currently cropping', 'hm-media-tools' ); ?></label>
			<select>
				<# _.each( data.attachment.sizes, function( props, size ) { #>
					<# if ( size !== 'full' && HM_MEDIA_TOOLS.sizes[ size ].crop ) { #>
						<option value="{{size}}">{{ size.replace(/[_-]+/g,' ') }}
							&mdash; {{ props.width }}px / {{ props.height }}px
						</option>
					<# } #>
				<# }); #>
			</select>
		</div>
	</script>
	<script type="text/template" id="tmpl-hm-thumbnail-image">
		<div class="hm-thumbnail-edit-wrap">
			<h3>
				{{ data.size.replace(/[_-]+/g,' ') }} &mdash; {{ data.attachment.sizes[ data.size ].width }}px / {{ data.attachment.sizes[ data.size ].height }}px
			</h3>
			<fieldset class="hm-thumbnail-crop-strategy">
				<legend><?php esc_html_e( 'Crop', 'hm-smart-media' ); ?></legend>
				<label><input type="radio" name="hm-thumbnail-crop-strategy-{{ data.size }}" value="smart" {{ data.attachment.crop_strategy === 'smart' ? 'checked' : '' }} /> <?php esc_html_e( 'Smart' ); ?></label>
				<label><input type="radio" name="hm-thumbnail-crop-strategy-{{ data.size }}" value="manual" {{ data.attachment.crop_strategy === 'manual' ? 'checked' : '' }} /> <?php esc_html_e( 'Manual' ); ?></label>
			</fieldset>
			<div class="hm-thumbnail-edit">
				<div class="hm-thumbnail-edit-image">
					<img
						src="{{ data.attachment.sizes[ data.size ].url }}"
						width="{{ data.attachment.sizes[ data.size ].width }}"
						height="{{ data.attachment.sizes[ data.size ].height }}"
						alt=""
					/>
				</div>
				<input class="hm-thumbnail-edit-save button button-primary" type="button" value="<?php esc_attr_e( 'Save' ); ?>" />
			</div>
		</div>
	</script>
	<?php

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
		wp_die( sprintf( esc_html__( 'Invalid %s parameter.', 'hm-media-tools' ), '<code>id</code>' ) );
	}

	if ( 'attachment' !== $attachment->post_type || ! wp_attachment_is_image( $attachment->ID ) ) {
		wp_die( sprintf( esc_html__( 'That is not a valid image attachment.', 'hm-media-tools' ), '<code>id</code>' ) );
	}

	if ( ! current_user_can( get_post_type_object( $attachment->post_type )->cap->edit_post, $attachment->ID ) ) {
		wp_die( esc_html__( 'You are not allowed to edit this attachment.', 'hm-media-tools' ) );
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
 * Gets all image sizes as keyed array with width, height and crop values
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
				'width'  => $width,
				'height' => $height,
				'crop'   => $crop,
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
 * @param  int $attachment_id Attachment ID.
 * @param  string $size Thumbnail size name.
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
