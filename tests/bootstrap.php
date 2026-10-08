<?php
/**
 * Bootstrap the WordPress integration tests with Tachyon.
 */

$tests_dir = getenv( 'WP_TESTS_DIR' ) ?: '/tmp/wordpress-tests-lib';
$tachyon_dir = getenv( 'TACHYON_PLUGIN_DIR' );

if ( ! $tachyon_dir || ! file_exists( $tachyon_dir . '/tachyon.php' ) ) {
	throw new RuntimeException( 'Set TACHYON_PLUGIN_DIR to a Tachyon plugin checkout.' );
}

require_once $tests_dir . '/includes/functions.php';

tests_add_filter( 'muplugins_loaded', function () use ( $tachyon_dir ) {
	if ( ! defined( 'TACHYON_URL' ) ) {
		define( 'TACHYON_URL', 'http://example.org/tachyon' );
	}
	require_once $tachyon_dir . '/tachyon.php';
	require_once dirname( __DIR__ ) . '/plugin.php';
	// Register alongside theme image sizes, before Tachyon caches them.
	add_action( 'after_setup_theme', function () {
		add_image_size( 'repro-crop', 640, 360, true );
	} );
} );

require $tests_dir . '/includes/bootstrap.php';
