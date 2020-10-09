import Media from '@wordpress/media';
import template from '@wordpress/template';

/**
 * Image preview.
 */
const ImagePreview = Media.View.extend( {
	tagName: 'div',
	className: 'hm-thumbnail-editor',
	template: template( 'hm-thumbnail-preview' ),
} );

export default ImagePreview;
