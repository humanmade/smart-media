<?php
/**
 * Justified Media Gallery
 *
 * @package hm-smart-media
 */

namespace HM\Media\Justified_Gallery;

function setup() {
	add_action( 'wp_enqueue_media', __NAMESPACE__ . '\\enqueue_scripts', 20 );
}

function enqueue_scripts() {
	wp_enqueue_style( 'hm-smart-media-justified-library', plugins_url( '/justified-library.css', __FILE__ ), [ 'media-views' ], null );
}
