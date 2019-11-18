Changelog
=========

## v0.1.16

- Bug: Don't hide image edit link when Smart Media UI not in effect #30

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
