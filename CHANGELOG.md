Changelog
=========

## v0.2.22

- Bug: Allow for original image URLs with existing query string

## v0.2.21

- Bug: Fix gallery editor styling

## v0.2.20

- Bug: Fix uploader error after inserting existing media into post

## v0.2.19

- Enhancement: Add width and height attributes to images in media library to improve loading and fix layout jitter

## v0.2.18

- Bug: Ensure PDF thumbnails use Tachyon #81
- Bug: Fix REST API URLs to use manual crop dimensions #81

## v0.2.17

- Bug: Ensure cropping UI selectors are scoped to current frame #80
- Bug: Prevent dismissal of crop select area by clicking on crop background #79

## v0.2.16

- Bug: Fixed featured image "Replace Image" button behaviour #76

## v0.2.15

- Bug: Fixed fatal error uploading video and audio blocks. #71

## v0.2.14

- Bug: Deselect blocks on focus inside metabox area, prevents updating blocks on image selection outside editor

## v0.2.13

- Bug: Compatibility issue with Google AMP plugin
- Bug: Selecting a featured image on an auto draft / new post page could fail to trigger the select event

## v0.2.12

- Bug: Fix SVG preview display in media modal

## v0.2.11

- Bug: Tachyon no longer processes SVGs, they stopped displaying properly in the media modal as a result

## v0.2.10

- Bug: Ensure `srcset` sizes are never larger than the original
- Bug: Ensure originally requested size (or as close to) is represented in `srcset`

## v0.2.9

- Bug: Fix crop position in post featured images #52
- Bug: Fix cropper showing on Featured Media Gutenberg popup #51

## v0.2.8

- Bug: Fix image size selector in Image Block wasn't selecting the correct size and switching between sizes wasn't causing any resize in the block.

## v0.2.7

- Bug: Fix error when `media_details` is empty in REST response

## v0.2.6

- Bug: Fatal error on requesting PDF via REST API
- Bug: PDF thumbnails not using Tachyon
- Bug: Media modal showing incorrect URL for PDF preview

## v0.2.5

- Change: Remove default WP large image sizes 1536x1536 and 2048x2048 in favour of Tachyon zoom srcset
- Bug: An image cannot be selected until Change Image is clicked #36
- Bug: Image cropper not applying size selection to image blocks #37
- Bug: Ensure attachment ID in `image_downsize` filter is int

## v0.2.4

- Bug: Check metadata is an array before passing to `add_srcset_and_sizes()`

## v0.2.3

- Bug: Don't hide edit image link if smart media UI not active #30

## v0.2.2

- Bug: Add missing textdomains
- Bug: Register textdomain for script handle
- Bug: Add wp-i18n as a dependency of the cropper

## v0.2.1

- Bug: Handle max size when non-named crop size is requested #22

## v0.2.0

- Enhancement: Allow removal of custom crops
- Enhancement: Ensure tachyon URLs are used globally
- Enhancement: Add crop and focal point data to REST API responses
- Enhancement: Support srcset and sizes for stored tachyon URLs

## v0.1.15

- Bug: Allow cropper to update crops for sizes that contain special characters #17

## v0.1.14

- Bug: Fix SVG compatibility #15

## v0.1.13

- Bug: Fix warnings when trying to get crop data for special case image sizes #5

## v0.1.12

- Update: Upgraded all build scripts
- Update: Add support for images in posts added by the block editor #9
- Update: Add contributing docs

## v0.1.11

- Bug: Justified library CSS - increase specifity
- Bug: Load media templates on customiser
- Bug: Don't load edit mode when built in media modal cropper state is present

## v0.1.10

- Justified library CSS - Add media-views stylesheet as a dependency.
- Justified library CSS - Enqueued on the `wp_enqueue_media` action to ensure they are only loaded when required.

## v0.1.9

- Fix bug when `full` isn't in sizes list, eg. everywhere except the HM site.

## v0.1.8

- Added composer.json

## v0.1.7

- Bug fix, compat issues with CMB2
- Disable thumbnail file generation when tachyon is enabled

## v0.1.6

- Bug fixes for focal point generated thumbs, bypass smart crop entirely

## v0.1.5

- Added focal point cropping feature

## v0.1.4

- When editing an image in the post edit screen the editing experience is loaded with an option to change the image
- Split out the image editor views JS
- Fix justified library in non script debug mode

## v0.1.3

- Minor bug fixes
- Styling updates

## v0.1.2

- Ensure only image attachment JS is modified
- Use smaller thumbs in size picker where possible
- Fix re-render on navigation between images in Media Library (frame refresh event)

## v0.1.1

- Fix bug loading image editor when `SCRIPT_DEBUG` is false

## v0.1.0

- Initial release
- Justified media library
- New image editing experience (Media section of admin only so far)
