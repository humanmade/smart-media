import jQuery from 'jQuery';
import BackBone from '@wordpress/backbone';
import { bind } from 'lodash';
import Media from '@wordpress/media';
import template from '@wordpress/template';

import { toJSONDeep } from './utils';

import './cropper.scss';

const $ = jQuery;

// Create a high level event we can hook into for media frame creation.
const MediaFrame = Media.view.MediaFrame;

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

      this.model.on( 'change', function () { console.log( 'model change', ...arguments ) } )
      this.controller.on( 'all', function () { console.log( 'controller events', ...arguments ) } )

      // Update on URL change eg. edit.
      this.listenTo( this.model, 'change:url', () => {
        this.render();
        ImageEditView.load( this.controller );
      } );

      // Load ImageEditView when the frame is ready.
      this.controller.on( 'ready', () => ImageEditView.load( this.controller ) );
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
      success: () => this.render(),
      error: () => {},
    } );
  }
} );

/**
 * Override attachments browser to give us hooks in the sidebar rendering
 */
// var AttachmentsBrowser = Media.view.AttachmentsBrowser;

// Media.view.AttachmentsBrowser = AttachmentsBrowser.extend( {

//   createSingle: function ( attachment, selection ) {
//     AttachmentsBrowser.prototype.createSingle.apply( this, arguments );
//     this.controller.trigger( 'attachment:render:details', attachment, selection );
//   },

//   disposeSingle: function () {
//     this.controller.trigger( 'attachment:dispose:details', this.sidebar );
//   }

// } );
