<?php
/**
 * Virtual image sizes must be usable outside the image_downsize API.
 */

namespace HM\Media\Tests;

use RuntimeException;
use WP_UnitTestCase;

use function HM\Media\Cropper\filter_intermediate_image_size;

/**
 * Exercise core image lookups with real Smart Media and Tachyon filters.
 */
class Test_Intermediate_Image_Size extends WP_UnitTestCase {
	/**
	 * Create an attachment with no physical intermediate files.
	 *
	 * @param array $sizes Stored image sizes, if any.
	 * @return int
	 */
	private function attachment( array $sizes = [] ) : int {
		$id = self::factory()->attachment->create( [
			'post_mime_type' => 'image/jpeg',
			'file' => '2026/10/original.jpg',
		] );
		wp_update_attachment_metadata( $id, [
			'width' => 2400,
			'height' => 1256,
			'file' => '2026/10/original.jpg',
			'filesize' => 4_680_949,
			'sizes' => $sizes,
		] );
		return $id;
	}

	/**
	 * Named virtual sizes resolve once through Tachyon.
	 */
	public function test_named_virtual_size_uses_tachyon_without_recursion() : void {
		$id = $this->attachment();
		$calls = 0;
		$counter = function ( $image ) use ( &$calls ) {
			$calls++;
			return $image;
		};
		add_filter( 'image_downsize', $counter, 1 );
		$data = image_get_intermediate_size( $id, 'large' );
		remove_filter( 'image_downsize', $counter, 1 );

		self::assertSame( 1, $calls );
		self::assertSame( wp_get_attachment_image_url( $id, 'large' ), $data['url'] );
		self::assertStringContainsString( '/tachyon/2026/10/original.jpg?', $data['url'] );
		self::assertTrue( $data['_tachyon_dynamic'] );
		self::assertSame( 1024, $data['width'] );
		self::assertSame( 536, $data['height'] );
		self::assertSame( 'original-1024x536.jpg', $data['file'] );
		self::assertArrayNotHasKey( 'filesize', $data );
	}

	/**
	 * Stored physical thumbnails retain their existing metadata and URL.
	 */
	public function test_physical_size_is_unchanged() : void {
		$id = $this->attachment( [
			'large' => [
				'width' => 1024,
				'height' => 536,
				'file' => 'physical-1024x536.jpg',
				'mime-type' => 'image/jpeg',
				'filesize' => 12345,
			],
		] );
		$data = image_get_intermediate_size( $id, 'large' );
		self::assertArrayNotHasKey( '_tachyon_dynamic', $data );
		self::assertStringContainsString( '/uploads/2026/10/physical-1024x536.jpg', $data['url'] );
		self::assertSame( $data, filter_intermediate_image_size( $data, $id, 'large' ) );
	}

	/**
	 * Dimension lookups retain the crop of the thumbnail selected by core.
	 */
	public function test_dimensions_request_preserves_the_selected_crop() : void {
		$id = $this->attachment();
		$data = image_get_intermediate_size( $id, [ 100, 100 ] );
		self::assertSame( wp_get_attachment_image_url( $id, 'thumbnail' ), $data['url'] );
		self::assertStringContainsString( 'resize=150%2C150', $data['url'] );
		self::assertSame( 100, $data['width'] );
		self::assertSame( 100, $data['height'] );
	}

	/**
	 * Dimension lookups also preserve an uncropped intermediate.
	 */
	public function test_dimensions_request_preserves_an_uncropped_size() : void {
		$id = $this->attachment();
		$data = image_get_intermediate_size( $id, [ 512, 268 ] );
		self::assertSame( wp_get_attachment_image_url( $id, 'medium_large' ), $data['url'] );
		self::assertSame( 512, $data['width'] );
		self::assertSame( 268, $data['height'] );
	}

	/**
	 * Custom crop coordinates and downstream URL filters still apply.
	 */
	public function test_custom_crop_and_url_filters_are_preserved() : void {
		$id = $this->attachment();
		update_post_meta( $id, '_crop_repro-crop', [
			'x' => 100,
			'y' => 150,
			'width' => 1200,
			'height' => 675,
		] );
		$filter = function ( $url ) {
			return add_query_arg( 'custom', 'preserved', $url );
		};
		add_filter( 'tachyon_url', $filter );
		try {
			$data = image_get_intermediate_size( $id, 'repro-crop' );
			self::assertSame( wp_get_attachment_image_url( $id, 'repro-crop' ), $data['url'] );
			self::assertStringContainsString( 'crop=100px%2C150px%2C1200px%2C675px', $data['url'] );
			self::assertStringContainsString( 'custom=preserved', $data['url'] );
			self::assertSame( $data['url'], image_get_intermediate_size( $id, [ 640, 360 ] )['url'] );
		} finally {
			remove_filter( 'tachyon_url', $filter );
		}
	}

	/**
	 * Unknown sizes keep the core false result.
	 */
	public function test_missing_size_remains_false() : void {
		self::assertFalse( image_get_intermediate_size( $this->attachment(), 'not-registered' ) );
	}

	/**
	 * A failing third-party filter must not disable later URL resolution.
	 */
	public function test_guard_is_released_when_an_image_filter_throws() : void {
		$id = $this->attachment();
		$filter = function () {
			throw new RuntimeException( 'Test filter failed.' );
		};
		add_filter( 'image_downsize', $filter, 1 );
		try {
			image_get_intermediate_size( $id, 'large' );
			self::fail( 'Expected the image filter exception.' );
		} catch ( RuntimeException $exception ) {
			self::assertSame( 'Test filter failed.', $exception->getMessage() );
		} finally {
			remove_filter( 'image_downsize', $filter, 1 );
		}
		self::assertStringContainsString( '/tachyon/', image_get_intermediate_size( $id, 'large' )['url'] );
	}

	/**
	 * An unavailable rendering URL does not erase existing metadata.
	 */
	public function test_unavailable_image_url_leaves_metadata_unchanged() : void {
		$id = $this->attachment();
		$data = [
			'_tachyon_dynamic' => true,
			'url' => 'existing-url',
		];
		$filter = function () {
			return [ '', 1024, 536, true ];
		};
		add_filter( 'image_downsize', $filter, 100 );
		try {
			self::assertSame( $data, filter_intermediate_image_size( $data, $id, 'large' ) );
		} finally {
			remove_filter( 'image_downsize', $filter, 100 );
		}
	}
}
