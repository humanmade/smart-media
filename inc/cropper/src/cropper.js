import jQuery from 'jQuery';
import Media from '@wordpress/media';
import template from '@wordpress/template';
import ajax from '@wordpress/ajax';
import smartcrop from 'smartcrop';

import './cropper.scss';

const $ = jQuery;

// Create a high level event we can hook into for media frame creation.
const MediaFrame = Media.view.MediaFrame;

// Override the MediaFrame on the global.
Media.view.MediaFrame = MediaFrame.extend( {
  initialize() {
    MediaFrame.prototype.initialize.apply( this, arguments );

    // Fire a high level init event.
    Media.events.trigger( 'frame:init', this );
  }
} );

// Replace TwoColumn view.
Media.events.on( 'frame:init', () => {
  Media.view.Attachment.Details.TwoColumn = Media.view.Attachment.Details.TwoColumn.extend( {
    template: template( 'hm-attachment-details-two-column' ),
    initialize() {
      Media.view.Attachment.Details.prototype.initialize.apply( this, arguments );

      // Update on URL change eg. edit.
      this.listenTo( this.model, 'change:url', () => {
        this.render();
        ImageEditView.load( this.controller );
      } );

      // Load ImageEditView when the frame is ready or refreshed.
      this.controller.on( 'ready refresh', () => ImageEditView.load( this.controller ) );
    }
  } );
} );

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
    this.listenTo( this.model, 'change', this.onUpdate );

    this.views.add( [
      // Add the sizes list.
      new ImageEditSizes( {
        controller: this.controller,
        model: this.model,
        priority: 10,
      } ),
      // Add the editor view.
      new ImageEditor( {
        controller: this.controller,
        model: this.model,
        priority: 50,
      } ),
    ] );

    // Render views - use the built in render method as it'll handle subviews too.
    this.render();
  },
} );

ImageEditView.load = controller => new ImageEditView( {
  controller: controller,
  model: controller.model,
  el: document.querySelector( '.media-image-edit' ),
} );

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
    //this.controller.on( 'refresh', this.render );
  },
  setSize( e ) {
    this.model.set( { size: e.currentTarget.dataset.size } );
    e.currentTarget.parentNode.parentNode.querySelectorAll( 'button' ).forEach( button => {
      button.className = '';
    } );
    e.currentTarget.className = 'current';
  },
} );

/**
 * Image editor.
 */
const ImageEditor = Media.View.extend( {
  tagName: 'div',
  className: 'hm-thumbnail-editor',
  template: template( 'hm-thumbnail-editor' ),
  events: {
    'click .button-apply-changes': 'saveCrop',
    'click .button-reset': 'reset',
  },
  initialize() {
    // Re-render on size change.
    this.listenTo( this.model, 'change:size', this.loadEditor );

    // Set window imageEdit._view to this.
    if ( window.imageEdit ) {
      window.imageEdit._view = this;
    }
  },
  loadEditor() {
    // Re-render.
    this.render();

    const size = this.model.get( 'size' );

    // Load cropper if we picked a thumbnail.
    if ( size !== 'full' && size !== 'full-orig' ) {
      this.initCropper();
    }
  },
  refresh() {
    this.update();
  },
  back() {},
  save() {
    this.update();
  },
  update() {
    this.model.fetch( {
      success: () => this.loadEditor(),
      error: () => {},
    } );
  },
  reset() {
    const $image   = $( 'img[id^="image-preview-"]' );
    const sizeName = this.model.get( 'size' );
    const sizes    = this.model.get( 'sizes' );
    const size     = sizes[ sizeName ] || null;

    if ( ! size ) {
      return;
    }

    const crop = size.cropData;

    if ( ! crop.x ) {
      smartcrop.crop( $image.get( 0 ), {
        width: size.width,
        height: size.height,
      } )
        .then( ( { topCrop } ) => {
          this.setSelection( topCrop );
        } );
    } else {
      this.setSelection( crop );
    }
  },
  saveCrop() {
    const crop = this.cropper.getSelection();

    // Disable buttons.
    this.onSelectStart();

    // @todo Show spinner.

    // Send AJAX request to save the crop coordinates.
    ajax.post( 'hm_save_crop', {
      _ajax_nonce: this.model.get( 'nonces' ).edit,
      id: this.model.get( 'id' ),
      crop: {
        x: crop.x1,
        y: crop.y1,
        width: crop.width,
        height: crop.height,
      },
      size: this.model.get( 'size' ),
    } )
      // Re-enable buttons.
      .always( () => {
        this.onSelectEnd();
      } )
      .done( () => {
        // Update & re-render.
        this.update();
      } )
      .fail( error => console.log( error ) );
  },
  setSelection( crop ) {
    this.onSelectStart();

    if ( ! crop || typeof crop.x === 'undefined' ) {
      this.cropper.setOptions( { show: true } );
      this.cropper.update();
      return;
    }

    this.cropper.setSelection( crop.x, crop.y, crop.x + crop.width, crop.y + crop.height );
    this.cropper.setOptions( { show: true } );
    this.cropper.update();
  },
  onSelectStart() {
    $( '.button-apply-changes, .button-reset' ).attr( 'disabled', 'disabled' );
  },
  onSelectEnd() {
    $( '.button-apply-changes, .button-reset' ).removeAttr( 'disabled' );
  },
  onSelectChange() {
    $( '.button-apply-changes:disabled, .button-reset:disabled' ).removeAttr( 'disabled' );
  },
  initCropper() {
    const view     = this;
    const $image   = $( 'img[id^="image-preview-"]' );
    const $parent  = $image.parent();
    const sizeName = this.model.get( 'size' );
    const sizes    = this.model.get( 'sizes' );
    const size     = sizes[ sizeName ] || null;

    if ( ! size ) {
      // Handle error.
      return;
    }

    const aspectRatio = `${size.width}:${size.height}`;

    // Load imgAreaSelect.
    this.cropper = $image.imgAreaSelect( {
			parent: $parent,
			instance: true,
			handles: true,
      keys: true,
      imageWidth: this.model.get( 'width' ),
      imageHeight: this.model.get( 'height' ),
			minWidth: size.width,
      minHeight: size.height,
      aspectRatio: aspectRatio,
      onInit( img ) {
        // Ensure that the imgAreaSelect wrapper elements are position:absolute.
        // (even if we're in a position:fixed modal)
        const $img = $( img );
        $img.next().css( 'position', 'absolute' )
          .nextAll( '.imgareaselect-outer' ).css( 'position', 'absolute' );

        // Set initial crop.
        view.reset();
      },
			onSelectStart() {
        view.onSelectStart( ...arguments );
      },
			onSelectEnd() {
        view.onSelectEnd( ...arguments );
			},
			onSelectChange() {
        view.onSelectChange( ...arguments );
			}
		});
  }
} );
