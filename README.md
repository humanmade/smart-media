Smart Media
===========

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

## Roadmap

Planned features include:

- Duplicate image detection and consolidation
- EXIF data editor
