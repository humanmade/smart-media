import Media from '@wordpress/media';
import template from '@wordpress/template';
import ImageEditSizes from './image-edit-sizes';
import ImageEditor from './image-editor';

/**
 * The main image editor content area.
 */
const ImageEditView = Media.View.extend( {
	template: template( 'hm-thumbnail-container' ),
	initialize() {
		// Set the current size being edited.
		if ( ! this.model.get( 'size' ) ) {
			this.model.set( { size: 'full' } );
		}

		// Re-render on certain updates.
		this.listenTo( this.model, 'change:url', this.onUpdate );

		// Initial render.
		this.onUpdate();
	},
	onUpdate() {
		// If the attachment info hasn't loaded yet show a spinner.
		if ( this.model.get( 'id' ) && ! this.model.get( 'url' ) ) {
			this.views.set( [
				new Media.view.Spinner(),
			] );
		} else {
			this.views.set( [
				new ImageEditSizes( {
					controller: this.controller,
					model: this.model,
					priority: 10,
				} ),
				new ImageEditor( {
					controller: this.controller,
					model: this.model,
					priority: 50,
				} ),
			] );
		}
	},
} );

ImageEditView.load = controller => new ImageEditView( {
	controller: controller,
	model: controller.model,
	el: document.querySelector( '.media-image-edit' ),
} );

export default ImageEditView;
