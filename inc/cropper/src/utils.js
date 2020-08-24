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
