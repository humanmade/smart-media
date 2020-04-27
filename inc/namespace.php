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
	$use_cropper = apply_filters( 'hm.smart-media.cropper', true );
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

/**
 * Get the URL of an asset in the manifest by name.
 *
 * @param string $filename
 * @return string|false
 */
function get_asset_url( $filename ) {
	$manifest_file = dirname( __FILE__, 2 ) . '/manifest.json';

	if ( ! file_exists( $manifest_file ) ) {
		return false;
	}

	//phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	$manifest = file_get_contents( $manifest_file );
	$manifest = json_decode( $manifest, true );

	if ( ! $manifest || ! isset( $manifest[ $filename ] ) ) {
		return false;
	}

	$path = $manifest[ $filename ];

	if ( strpos( $path, 'http' ) !== false ) {
		return $path;
	}

	return plugins_url( $manifest[ $filename ], dirname( __FILE__ ) );
}
