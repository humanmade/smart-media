import { applyFilters, addFilter } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';
import Media from '@wordpress/media';
import template from '@wordpress/template';
import ImageEditView from './views/image-edit';

import './cropper.scss';

// Register attachment to attribute map for core blocks.
addFilter(
  'smartmedia.cropper.updateBlockAttributesOnSelect.core.image',
  'smartmedia/cropper/update-block-on-select/core/image',
  ( attributes, image ) => {
    // Only user selectable image sizes have a label so return early if this is missing.
    if ( ! image.label ) {
      return attributes;
    }

    return {
      sizeSlug: image.size,
      url: image.url,
    };
  }
);

addFilter(
  'smartmedia.cropper.selectSizeFromBlockAttributes.core.image',
  'smartmedia/cropper/select-size-from-block-attributes/core/image',
  ( size, block ) => {
    return size || block.attributes.sizeSlug || 'full';
  }
);

// Ensure blocks are deselected when focusing or clicking into the meta boxes.
if ( wp && wp.data && window._wpLoadBlockEditor ) {
  // Wait for editor to load.
  window._wpLoadBlockEditor.then( () => {
    // Ensure this is an editor page.
    const editor = document.querySelector( '.block-editor' );
    if ( ! editor ) {
      return;
    }
    // Callback to deselect current block.
    function deselectBlocks( event ) {
      if ( ! event.target.closest( '.edit-post-meta-boxes-area' ) ) {
        return;
      }
      wp.data.dispatch( 'core/block-editor' ).clearSelectedBlock();
    };
    editor.addEventListener( 'focusin', deselectBlocks );
  } );
}


// Create a high level event we can hook into for media frame creation.
const MediaFrame = Media.view.MediaFrame;

// Override the MediaFrame on the global - this is used for media.php.
Media.view.MediaFrame = MediaFrame.extend( {
  initialize() {
    MediaFrame.prototype.initialize.apply( this, arguments );

    // Fire a high level init event.
    Media.events.trigger( 'frame:init', this );
  },
} );

// Used on edit.php
const MediaFrameSelect = Media.view.MediaFrame.Select;
Media.view.MediaFrame.Select = MediaFrameSelect.extend( {
  initialize( options ) {
    MediaFrameSelect.prototype.initialize.apply( this, arguments );

    // Reset the button as options are updated globally and causes some setup steps not to run.
    this._button = Object.assign( {}, options.button || {} );
    this.on( 'toolbar:create:select', this.onCreateToolbarSetButton, this );

    // Add our image editor state.
    this.createImageEditorState();
    this.on( 'ready', this.createImageEditorState, this );

    // Bind edit state views.
    this.on( 'content:create:edit', this.onCreateImageEditorContent, this );
    this.on( 'toolbar:create:edit', this.onCreateImageEditorToolbar, this );

    // Fire a high level init event.
    Media.events.trigger( 'frame:select:init', this );
  },
  onCreateToolbarSetButton: function () {
    if ( this._button ) {
      this.options.mutableButton = Object.assign( {}, this.options.button );
      this.options.button = Object.assign( {}, this._button );
    }
  },
  createImageEditorState: function () {
    // Only single selection mode is supported.
    if ( this.options.multiple ) {
      return;
    }

    // Don't add the edit state if we also have the built in cropper state.
    if ( this.states.get( 'cropper' ) ) {
      return;
    }

    // If we already have the state it's safe to ignore.
    if ( this.states.get( 'edit' ) ) {
      return;
    }

    const libraryState = this.states.get( 'library' ) || this.states.get( 'featured-image' );
    if ( ! libraryState || ! libraryState.get( 'selection' ) ) {
      return;
    }

    const isFeaturedImage = libraryState.id === 'featured-image';

    // Hide the toolbar for the library mode.
    this.$el.addClass( 'hide-toolbar' );

    // Create new editing state.
    const editState = this.states.add( {
      id: 'edit',
      title: __( 'Edit image', 'hm-smart-media' ),
      router: false,
      menu: false,
      uploader: false,
      library: libraryState.get( 'library' ),
      selection: libraryState.get( 'selection' ),
      display: libraryState.get( 'display' ),
    } );

    // Set region modes when entering and leaving edit state.
    editState.on( 'activate', () => {
      // Preserve settings from previous view.
      if ( this.$el.hasClass( 'hide-menu' ) && this.lastState() ) {
        this.lastState().set( 'menu', false );
      }

      // Toggle edit mode on regions.
      this.$el.addClass( 'mode-select mode-edit-image' );
      this.$el.removeClass( 'hide-toolbar' );
      this.content.mode( 'edit' );
      this.toolbar.mode( 'edit' );
    } );
    editState.on( 'deactivate', () => {
      this.$el.removeClass( 'mode-select mode-edit-image' );
      this.$el.addClass( 'hide-toolbar' );
    } );

    // Handle selection events.
    libraryState.get( 'selection' ).on( 'selection:single', () => {
      const single = this.state( 'edit' ).get( 'selection' ).single();
      if ( single.get( 'uploading' ) ) {
        return;
      }

      // Update the placeholder the featured image frame uses to set its
      // default selection from.
      if ( isFeaturedImage ) {
        wp.media.view.settings.post.featuredImageId = single.get( 'id' );
      }

      this.setState( 'edit' );
    } );
    libraryState.get( 'selection' ).on( 'selection:unsingle', () => {
      // Update the placeholder the featured image frame uses to set its
      // default selection from.
      if ( isFeaturedImage ) {
        wp.media.view.settings.post.featuredImageId = -1;
      }

      this.setState( libraryState.id );
    } );
  },
  onCreateImageEditorContent: function ( region ) {
    const state = this.state( 'edit' );
    const single = state.get( 'selection' ).single();
    const sidebar = new Media.view.Sidebar( {
      controller: this,
    } );

    // Set sidebar views.
    sidebar.set( 'details', new Media.view.Attachment.Details( {
      controller: this,
      model: single,
      priority: 80
    } ) );

    sidebar.set( 'compat', new Media.view.AttachmentCompat( {
      controller: this,
      model: single,
      priority: 120
    } ) );

    const display = state.has( 'display' ) ? state.get( 'display' ) : state.get( 'displaySettings' );

    if ( display ) {
      sidebar.set( 'display', new Media.view.Settings.AttachmentDisplay( {
        controller:   this,
        model:        state.display( single ),
        attachment:   single,
        priority:     160,
        userSettings: state.model.get( 'displayUserSettings' )
      } ) );
    }

    // Show the sidebar on mobile
    if ( state.id === 'insert' ) {
      sidebar.$el.addClass( 'visible' );
    }

    region.view = [
      new ImageEditView( {
        tagName: 'div',
        className: 'media-image-edit',
        controller: this,
        model: single,
      } ),
      sidebar,
    ];
  },
  onCreateImageEditorToolbar: function ( region ) {
    region.view = new Media.view.Toolbar( {
      controller: this,
      requires: { selection: true },
      reset: false,
      event: 'select',
      items: {
        change: {
          text: __( 'Change image', 'hm-smart-media' ),
          click: () => {
            // Remove the current selection.
            this.state( 'edit' ).get( 'selection' ).reset( [] );
            // this.setState( libraryState.id );
          },
          priority: 20,
          requires: { selection: true },
        },
        apply: {
          style: 'primary',
          text: __( 'Select', 'hm-smart-media' ),
          click: () => {
            const { close, event, reset, state } = Object.assign( this.options.mutableButton || this.options.button || {}, {
              event: 'select',
              close: true,
            } );

            if ( close ) {
              this.close();
            }

            // Trigger the event on the current state if available, falling
            // back to last state and finally the frame.
            if ( event ) {
              if ( this.state()._events[ event ] ) {
                this.state().trigger( event );
              } else if ( this.lastState()._events[ event ] ) {
                this.lastState().trigger( event );
              } else {
                this.trigger( event );
              }
            }

            if ( state ) {
              this.setState( state );
            }

            if ( reset ) {
              this.reset();
            }

            // Update current block if we can map the attachment to attributes.
            if ( wp && wp.data ) {
              const selectedBlock = wp.data.select( 'core/block-editor' ).getSelectedBlock();
              if ( ! selectedBlock ) {
                return;
              }

              // Get the attachment data and selected image size data.
              const attachment = this.state( 'edit' ).get( 'selection' ).single();

              if ( ! attachment ) {
                return;
              }

              const sizes = attachment.get( 'sizes' );
              const size = attachment.get( 'size' );

              const image = sizes[ size ];
              image.id = attachment.get( 'id' );
              image.size = size;

              const attributesByBlock = applyFilters(
                `smartmedia.cropper.updateBlockAttributesOnSelect.${ selectedBlock.name.replace( /\W+/g, '.' ) }`,
                null,
                image,
                attachment
              );

              const attributesForAllBlocks = applyFilters(
                'smartmedia.cropper.updateBlockAttributesOnSelect',
                attributesByBlock,
                selectedBlock,
                image,
                attachment
              );

              // Don't update if a falsey value is returned.
              if ( ! attributesForAllBlocks ) {
                return;
              }

              wp.data.dispatch( 'core/block-editor' ).updateBlock( selectedBlock.clientId, {
                attributes: attributesForAllBlocks,
              } );
            }
          },
          priority: 10,
          requires: { selection: true },
        },
      },
    } );
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

// Add width & height attributes to library images for smoother loading.
const MediaAttachment = Media.view.Attachment;
const MediaAttachmentLibrary = Media.view.Attachment.Library;
const MediaAttachmentEditLibrary = Media.view.Attachment.EditLibrary;
const MediaAttachmentSelection = Media.view.Attachment.Selection;
const overrideRender = function () {
  MediaAttachment.prototype.render.apply( this, arguments );
  if ( this.model.get( 'type' ) === 'image' && ! this.model.get( 'uploading' ) ) {
    const size = this.imageSize();
    this.$el.find( 'img' ).attr( {
      width: size.width,
      height: size.height,
    } );
  }
};
Media.view.Attachment = MediaAttachment.extend( {
  render: overrideRender,
} );
Media.view.Attachment.Library = MediaAttachmentLibrary.extend( {
  render: overrideRender,
} );
Media.view.Attachment.EditLibrary = MediaAttachmentEditLibrary.extend( {
  render: overrideRender,
} );
Media.view.Attachment.Selection = MediaAttachmentSelection.extend( {
  render: overrideRender,
} );

/**
 * Ensure uploader status view is actually rendered before
 * updating info display.
 */
const MediaUploaderStatus = Media.view.UploaderStatus;
Media.view.UploaderStatus = MediaUploaderStatus.extend( {
  info: function () {
    if ( ! this.$index ) {
      return;
    }
    MediaUploaderStatus.prototype.info.apply( this, arguments );
  }
} );
