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
