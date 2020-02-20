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
      width: image.width,
      height: image.height,
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

    const button = Object.assign( {}, options.button || {} );

    // Fire a high level init event.
    Media.events.trigger( 'frame:select:init', this );

    // Prevent previously hidden regions coming back.
    this.on( 'activate', () => {
      if ( this.$el.hasClass( 'hide-menu' ) && this.lastState() ) {
        this.lastState().set( 'menu', false );
      }
    } );

    // Reset the button as options are updated globally and causes some setup steps not to run.
    this.on( 'toolbar:create:select', () => {
      if ( button ) {
        this.options.mutableButton = Object.assign( {}, this.options.button );
        this.options.button = Object.assign( {}, button );
      }
    } );
  },
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

// Add edit state to MediaFrameSelect.
Media.events.on( 'frame:select:init', frame => {

  // Don't do any unnecessary work.
  if ( ! frame.states.get( 'library' ) ) {
    return;
  }
  if ( frame.states.get( 'edit' ) ) {
    return;
  }

  // Check we don't have the cropper state available, not yet compatible.
  if ( frame.states.get( 'cropper' ) ) {
    return;
  }

  const libraryState = frame.state( 'library' );

  // Create new editing state.
  const editState = frame.states.add( {
    id: 'edit',
    title: __( 'Edit image', 'hm-smart-media' ),
    router: false,
    menu: false,
    uploader: false,
    selection: frame.state( 'library' ).get( 'selection' ),
    library: frame.state( 'library' ).get( 'library' ),
  } );

  // Set region modes when entering and leaving edit state.
  editState.on( 'activate', () => {
    frame.$el.toggleClass( 'mode-select mode-edit-image' );
    frame.content.mode( 'edit' );
    frame.toolbar.mode( 'edit' );
  } );

  editState.on( 'deactivate', () => {
    frame.$el.toggleClass( 'mode-select mode-edit-image' );
  } );

  editState.sidebar = new Media.view.Sidebar( {
    controller: frame,
  } );

  // Update the views for the regions in edit mode.
  frame.on( 'content:create:edit', region => {
    region.view = [
      new ImageEditView( {
        tagName: 'div',
        className: 'media-image-edit',
        controller: frame,
        model: frame.state( 'edit' ).get( 'selection' ).first(),
      } ),
      editState.sidebar,
    ];
  } );

  frame.on( 'toolbar:create:edit', region => {
    region.view = new Media.view.Toolbar( {
      controller: frame,
      requires: { selection: true },
      reset: false,
      event: 'select',
      items: {
        change: {
          text: __( 'Change image', 'hm-smart-media' ),
          click() {
            frame.setState( frame.lastState() );
          },
          priority: 20,
          requires: { selection: true },
        },
        apply: {
          style: 'primary',
          text: __( 'Select', 'hm-smart-media' ),
          click: () => {
            const { close, event, reset, state } = Object.assign( frame.options.mutableButton || frame.options.button, {
              event: 'select',
              close: true,
            } );

            if ( close ) {
              frame.close();
            }

            if ( event ) {
              frame.state().trigger( event || 'select' );
            }

            if ( state ) {
              frame.setState( state );
            }

            if ( reset ) {
              frame.reset();
            }

            // Update current block if we can map the attachment to attributes.
            if ( wp && wp.data ) {
              const selectedBlock = wp.data.select( 'core/block-editor' ).getSelectedBlock();
              if ( ! selectedBlock ) {
                return;
              }

              // Get the attachment data and selected image size data.
              const attachment = frame.state( 'edit' ).get( 'selection' ).first() || ( frame._selection && frame._selection.single );

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
                `smartmedia.cropper.updateBlockAttributesOnSelect`,
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
  } );

  // Switch state on selection of a new single image.
  editState.get( 'selection' ).on( 'selection:single', function () {
    const { sidebar } = editState;
    const single = editState.get( 'selection' ).single();

    // Check we're not still uploading.
    if ( single.get( 'uploading' ) ) {
      return;
    }

    // Switch to edit mode.
    frame.setState( 'edit' );

    // Set sidebar views.
    sidebar.set( 'details', new Media.view.Attachment.Details( {
      controller: frame,
      model: single,
      priority: 80
    } ) );

    sidebar.set( 'compat', new Media.view.AttachmentCompat( {
      controller: frame,
      model: single,
      priority: 120
    } ) );

    const display = libraryState.has( 'display' ) ? libraryState.get( 'display' ) : libraryState.get( 'displaySettings' );

    if ( display ) {
      sidebar.set( 'display', new Media.view.Settings.AttachmentDisplay( {
        controller:   frame,
        model:        this.model.display( single ),
        attachment:   single,
        priority:     160,
        userSettings: this.model.get( 'displayUserSettings' )
      } ) );
    }

    // Show the sidebar on mobile
    if ( this.model.id === 'insert' ) {
      sidebar.$el.addClass( 'visible' );
    }
  } );

  editState.get( 'selection' ).on( 'selection:unsingle', function () {
    frame.setState( 'library' );
  } );

} );
