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
	wp_enqueue_style( 'hm-smart-media-justified-gallery', plugins_url( '/justified-gallery.css', __FILE__ ), [], null );
}
