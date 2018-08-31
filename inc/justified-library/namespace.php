<?php
/**
 * Justified Media Gallery
 *
 * @package hm-smart-media
 */

namespace HM\Media\Justified_Gallery;

function setup() {
	add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\\enqueue_scripts' );
}

function enqueue_scripts() {
	wp_enqueue_style( 'hm-smart-media-justified-library', plugins_url( '/justified-library.css', __FILE__ ), [], null );
}
