<table width="100%">
	<tr>
		<td align="left" width="70">
			<strong>Smart Media</strong><br />
			Enhanced media library features for WordPress.
		</td>
		<td align="right" width="20%">
		</td>
	</tr>
	<tr>
		<td>
			A <strong><a href="https://hmn.md/">Human Made</a></strong> project. Maintained by @roborourke.
		</td>
		<td align="center">
			<img src="https://hmn.md/content/themes/hmnmd/assets/images/hm-logo.svg" width="100" />
		</td>
	</tr>
</table>

Smarter media features for WordPress.

Some features in this plugin will work on their own however some are designed to augment the existing tools we use such as [Tachyon](https://github.com/humanmade/tachyon).

## Features

### Justified media library

The media library shows square thumbnails by default which can make it harder to find the right image. This feature makes the thumbnails keep their original aspect ratio, similar to the UI of Flickr.

To disable the feature add the following:

```php
<?php
add_filter( 'hm.smart-media.justified-library', '__return_false' );
```

### Image editor

This feature overrides the built in WordPress image editing experience and gives you control over the crops of individual thumbs. There are also some UX improvements meaning there are fewer clicks required to make edits.

To disable the feature add the following:

```php
<?php
add_filter( 'hm.smart-media.cropper', '__return_false' );
```

The image cropping UI provides support for updating Gutenberg block attributes based on the current selection using the following filters:

- `smartmedia.cropper.updateBlockAttributesOnSelect.<block name>`
- `smartmedia.cropper.selectSizeFromBlockAttributes.<block name>`

In the above filters `<block name>` should be replaced a dot separated version of your block name, for example `core/image` becomes `core.image`. The core image block attributes are mapped by default.

Mapping the selected image to block attributes:

```js
addFilter(
  'smartmedia.cropper.updateBlockAttributesOnSelect.core.image',
  'smartmedia/cropper/update-block-on-select/core/image',
  /**
   * @param {?Object} attributes  The filtered block attributes. Return null to bypass updating.
   * @param {Object} image  The image data has the following shape:
   *   {
   *     name: <string>,  The image size name
   *     url: <string>,  The URL for the sized image
   *     width: <int>,  The width in pixels
   *     height: <int>,  The height in pixels
   *     label: <string>,  The human readable name for the image size, only present for user selectable sizes
   *     cropData: <?object>,  Null or object containing x, y, width and height properties
   *   }
   */
  ( attributes, image ) => {
    // Only user selectable image sizes have a label so return early if this is missing.
    if ( ! image.label ) {
      return attributes;
    }

    return {
      sizeSlug: image.size,
      url: image.url,
    };
  }
);
```

Update the cropping UI selected size based on selected block attributes:

```js
addFilter(
  'smartmedia.cropper.selectSizeFromBlockAttributes.core.image',
  'smartmedia/cropper/select-size-from-block-attributes/core/image',
  /**
   * @param {?String} size  The image size slug.
   * @param {Object} block  The currently selected block.
   */
  ( size, block ) => {
    return size || block.attributes.sizeSlug || 'full';
  }
);
```

The function takes 2 parameters:

- `block`: The name of the block to map attributes for
- `callback`: A function that accepts the image `size` name, an `image` object containing `url`, `width`, `height`, crop data and label for the image size, and lastly the full `attachment` data object.

The callback should return an object or `null`. Passing `null` will prevent updating the currently selected block.

## Roadmap

Planned features include:

- Duplicate image detection and consolidation
- EXIF data editor

## Contributing

First of all thanks for using this plugin and thanks for contributing!

To get started check out the [contributing documentation](./CONTRIBUTING.md).
