import Media from '@wordpress/media';
import template from '@wordpress/template';

/**
 * Image size selector.
 */
const ImageEditSizes = Media.View.extend( {
	tagName: 'div',
	className: 'hm-thumbnail-sizes',
	template: template( 'hm-thumbnail-sizes' ),
	events: {
		'click button': 'setSize',
	},
	initialize() {
		this.listenTo( this.model, 'change:sizes', this.render );
		this.on( 'ready', () => {
			this.el.querySelector( '.current' ).scrollIntoView();
		} );
	},
	setSize( e ) {
		this.model.set( { size: e.currentTarget.dataset.size } );
		e.currentTarget.parentNode.parentNode.querySelectorAll( 'button' ).forEach( button => {
			button.className = '';
		} );
		e.currentTarget.className = 'current';
	},
} );

export default ImageEditSizes;
