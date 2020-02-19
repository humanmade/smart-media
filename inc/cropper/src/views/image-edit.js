import Media from '@wordpress/media';
import template from '@wordpress/template';
import ImageEditSizes from './image-edit-sizes';
import ImageEditor from './image-editor';
import ImagePreview from './image-preview';

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

		// Get current image block size if available.
		this.setSizeForBlock();

		// Re-render on certain updates.
		this.listenTo( this.model, 'change:url', this.onUpdate );

		// Initial render.
		this.onUpdate();
	},
	setSizeForBlock() {
		if ( ! wp || ! wp.data ) {
			return;
		}

		const selectedBlock = wp.data.select( 'core/block-editor' ).getSelectedBlock();
		if ( ! selectedBlock || selectedBlock.name !== 'core/image' ) {
			return;
		}

		this.model.set( { size: selectedBlock.attributes.sizeSlug } );
	},
	onUpdate() {
		const views = [];

		// If the attachment info hasn't loaded yet show a spinner.
		if ( this.model.get( 'uploading' ) || ( this.model.get( 'id' ) && ! this.model.get( 'url' ) ) ) {
			views.push( new Media.view.Spinner() );
		} else {
			views.push( new ImageEditSizes( {
				controller: this.controller,
				model: this.model,
				priority: 10,
			} ) );

			// Ensure this attachment is editable.
			if ( this.model.get( 'mime' ).match( /image\/(gif|jpe?g|png)/ ) ) {
				views.push( new ImageEditor( {
					controller: this.controller,
					model: this.model,
					priority: 50,
				} ) );
			} else {
				views.push( new ImagePreview( {
					controller: this.controller,
					model: this.model,
					priority: 50,
				} ) );
			}
		}

		this.views.set( views );
	},
} );

ImageEditView.load = controller => new ImageEditView( {
	controller: controller,
	model: controller.model,
	el: document.querySelector( '.media-image-edit' ),
} );

export default ImageEditView;
