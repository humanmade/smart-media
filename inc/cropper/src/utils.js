import { clone } from 'lodash';
import BackBone from '@wordpress/backbone';

/**
 * Serialises nested collections and models
 *
 * @param object
 * @returns {*}
 */
export const toJSONDeep = object => {
	let json = clone( object.attributes );
	for ( let attr in json ) {
		if ( ( json[ attr ] instanceof Backbone.Model ) || ( json[ attr ] instanceof Backbone.Collection ) ) {
			json[ attr ] = json[ attr ].toJSON();
		}
	}
	return json;
}

/**
 * Get the maximum crop size within in a bounding box.
 *
 * @param {int} width
 * @param {int} height
 * @param {int} cropWidth
 * @param {int} cropHeight
 * @returns {array}
 */
export const getMaxCrop = ( width, height, cropWidth, cropHeight ) => {
	const maxHeight = width / cropWidth * cropHeight;

	if ( maxHeight < height ) {
		return [ width, Math.round( maxHeight ) ];
	}

	return [ Math.round( height / cropHeight * cropWidth ), height ];
}

/**
 * Registers a callback to map attachment data to block attributes when selecting
 * an image from the media modal.
 *
 * @param {String} block A block editor block name.
 * @param {Function} callback A callback that accepts image an attachment object
 *                            and returns an updated block attributes object.
 */
export const registerAttachmentToBlockAttributesMap = ( block, callback ) => {
	window.SmartMedia = window.SmartMedia || {};
	window.SmartMedia.ImageBlockAttributeMaps = window.SmartMedia.ImageBlockAttributeMaps || {};

	if ( typeof block !== 'string' ) {
		console.error( 'Block ID should be a string matching a registered block name. The following value was given.', block );
		return;
	}

	if ( typeof callback !== 'function' ) {
		console.error( 'Callback should be a function. The following value was given.', block );
		return;
	}

	window.SmartMedia.ImageBlockAttributeMaps[ block ] = callback;
}

/**
 * Maps attachment data to block attributes using a callback registered
 * with registerBlockAttachmentToAttributesMap.
 *
 * @param {String} block A block editor block name.
 * @param {String} size An image size string.
 * @param {Object} image The image size meta data object.
 * @param {Object} attachment The full attachment data object.
 */
export const mapAttachmentToBlockAttributes = ( block, size, image, attachment ) => {
	window.SmartMedia = window.SmartMedia || {};
	window.SmartMedia.ImageBlockAttributeMaps = window.SmartMedia.ImageBlockAttributeMaps || {};

	if ( ! window.SmartMedia.ImageBlockAttributeMaps[ block ] ) {
		return;
	}

	const result = window.SmartMedia.ImageBlockAttributeMaps[ block ]( size, image, attachment );

	if ( result && typeof result !== 'object' ) {
		console.error( `registerBlockAttachmentToAttributesMap() callback for ${block} must return an object or null.` );
		return;
	}

	return result;
}
