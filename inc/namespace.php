<?php
/**
 * Media tools main plugin file.
 *
 * @package media-tools
 */

namespace HM\Media;

function setup() {
	/**
	 * Toggles the cropper tool.
	 *
	 * @param bool $use_cropper True to enable the cropper tool, false to disable.
	 */
	$use_cropper = apply_filters( 'hm.smart-media.cropper', false );
	if ( $use_cropper ) {
		require_once __DIR__ . '/cropper/namespace.php';
		Cropper\setup();
	}

	/**
	 * Toggles the justified media gallery display.
	 *
	 * @param bool $use_justified_gallery True to enable, false to disable.
	 */
	$use_justified_gallery = apply_filters( 'hm.smart-media.justified-library', true );
	if ( $use_justified_gallery ) {
		require_once __DIR__ . '/justified-library/namespace.php';
		Justified_Gallery\setup();
	}
}
