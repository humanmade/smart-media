/* global HM_MEDIA_TOOLS, _, wp, fm_media_frame, Backbone, Cropper */

(function ( $, w, d ) {

    'use strict';

    var media = wp.media;

    /**
     * Serialises nested collections and models
     *
     * @param object
     * @returns {*}
     */
    function toJSONDeep( object ) {
      var json = _.clone( object.attributes );
      for ( var attr in json ) {
        if ( (json[ attr ] instanceof Backbone.Model) || (json[ attr ] instanceof Backbone.Collection) ) {
          json[ attr ] = json[ attr ].toJSON();
        }
      }
      return json;
    }

    media.controller.CropThumbnails = media.controller.State.extend( {

      defaults: {
        id     : 'hm-crop-thumbnails',
        title  : HM_MEDIA_TOOLS.i18n.cropTitle,
        menu   : false,
        toolbar: 'hm-crop',
        content: 'hm-crop',
        router : false,
        url    : ''
      },

      ready: function () {
        var frame = this.frame;

        // Create model
        frame.model = new media.controller.State();

        // Set starting state
        this.set( 'initialState', frame.options.state );

        // Handle globals
        frame.on( 'close', this.close, this );

        // Set selection, required for rendering subviews
        if ( !this.get( 'selection' ) ) {
          this.set( 'selection', new Backbone.Collection( frame._selection.single ) );
        }

        // Add our button view to trigger cropping state
        frame.on( 'attachment:render:details', this.cropButtonReady, this );
        frame.on( 'content:render:browse', this.cropButtonBack, this );

        // Allows for deep state navigation
        frame.on( 'toolbar:create', this.fixState, this );
      },

      activate: function () {
        var frame = this.frame;

        // Create views
        frame.on( 'content:create:' + this.get( 'content' ), this.cropContent, this );
        frame.on( 'toolbar:create:' + this.get( 'toolbar' ), this.cropToolbar, this );

        // Prevent menu coming back
        if ( frame.$el.hasClass( 'hide-menu' ) ) {
          frame.lastState().set( 'menu', false );
        }
      },

      deactivate: function () {
        var frame = this.frame;

        // Cleanup the cropper instance
        if ( frame.cropper ) {
          $( w ).off( 'resize.cropper' );
          frame.cropper.setOptions( { remove: true } );
        }

        // If we have a button property then it's a custom popup
        // Need to reset this back to it's original format so it
        // inherits from the controller state
        if ( frame.options && frame.options.button ) {
          frame.options.button = {
            controller: frame,
            text      : frame.options.button.text
          };
        }
      },

      // Sometimes we'll end up a few states deep but most of the media
      // views code only handles switching to the previous state. This
      // makes sure we can back to the original state.
      fixState: function () {
        var frame     = this.frame,
            initial   = this.get( 'initialState' ),
            lastState = frame.lastState();
        if ( frame._state !== initial && lastState && lastState.id !== initial ) {
          frame._lastState = initial;
        }
      },

      // It's a bit weird the order these events are triggered, parts
      // of the frame / content haven't been switched yet
      cropButtonReady: function ( attachment ) {
        if ( attachment ) {
          this.createCropButton( attachment );
        }
      },

      cropButtonBack : function ( content ) {
        if ( content.options.selection &&
             content.options.selection._single ) {
          this.createCropButton( content.options.selection._single );
        }
      },

      createCropButton: function ( attachment ) {
        var frame = this.frame;
        frame.model.set( 'attachment', attachment );
        frame.toolbar.get().primary.set( 'cropButton', new media.view.Button( {
          priority: 200,
          text: HM_MEDIA_TOOLS.i18n.cropTitle,
          style: 'button',
          click: function () {
            frame.setState( 'hm-crop-thumbnails' );
          }
        } ) );
      },

      close: function () {
        this.frame.setState( this.get( 'initialState' ) );
        $( document ).trigger( 'frame:close', this );
      },

      cropContent: function () {
        this.frame.content.set( new media.view.HMImageEditView( {
          controller: this.frame,
          model     : this.frame.model,
          className : 'clearfix hm-crop-thumbnails-modal-content',
          priority  : 20
        } ) );
      },

      cropToolbar: function () {
        var frame     = this.frame,
            lastState = frame.lastState(),
            previous  = lastState && lastState.id;

        frame.toolbar.set( new media.view.Toolbar( {
          controller: frame,
          close     : false,
          items     : {
            cancel: {
              id      : 'hm-crop-button-cancel',
              style   : 'close',
              text    : HM_MEDIA_TOOLS.i18n.cropClose,
              priority: 80,
              click   : function () {
                if ( previous ) {
                  frame.setState( previous );
                }
                else {
                  frame.close();
                }
              }
            }
          }
        } ) );
      }

    } );

    /**
     * The cropper interface view
     */
    media.view.HMCropImage = wp.Backbone.View.extend( {

      className: 'hm-thumbnail-cropper-image-wrap',

      template: media.template( 'hm-thumbnail-image' ),

      events: {
        'change .hm-thumbnail-crop-strategy input': 'updateCropStrategy'
      },

      initialize: function () {
        this.controller = this.options.controller;

        // Handle updates in a non-destructive way until explicitly saved
        this.model.set( 'crop', this.model.get( 'attachment' ).get( 'crop' ) || {} );
      },

      updateCropStrategy: function( e ) {
        var strategy = this.model.get( 'cropStrategy' ) || {},
            size = this.options.size;

        strategy[ size ] = e.target.value || 'smart';
        this.model.set( 'cropStrategy', strategy );
      },

      render: function () {
        this.$el.html( this.template( toJSONDeep( this.model ) ) );

        this.$elements = {
          edit       : this.$( '#hm-thumbnail-edit' ),
          editPreview: this.$( '#hm-thumbnail-edit-preview' )
        };

        //this.loadEditor();

        return this;
      },

      loadEditor: function () {
        var frame     = this,
            $el       = this.$el,
            model     = this.model,
            image     = model.get( 'attachment' ),
            width     = image.attributes.sizes.full.width,
            height    = image.attributes.sizes.full.height,
            size      = model.get( 'size' ),
            crop      = model.get( 'crop' )[ size ] || false, // Use the reference so the WIP stays even if the crop
                                                              // isn't saved
            thumb     = image.attributes.sizes[ size ],
            options   = {
              instance: true,
              handles: true,
              imageWidth: width,
              imageHeight: height,
              aspectRatio: [ width, height ].join( ':' ),
              // maxWidth: width,
              // maxHeight: height,
              // minWidth: 1,
              // minHeight: 1,

              onSelectEnd: function ( img, coords ) {
                var x = Math.round( Math.max( 0, coords.x1 ) ),
                    y = Math.round( Math.max( 0, coords.y1 ) ),
                    w = Math.round( Math.min( coords.width, coords.width - x ) ),
                    h = Math.round( Math.min( coords.height, coords.height - y ) );

                // Update attachment crop data
                var coords = model.get( 'crop' ) || {};
                coords[ size ] = [ x, y, w, h ];
                model.set( 'crop', coords );
              },

              onSelectChange: function( img, selection ) {
                if ( ! selection.width || ! selection.height ) {
                  return;
                }

                var scaleX = 100 / selection.width;
                var scaleY = 100 / selection.height;

                $('img', frame.$elements.editPreview).css({
                    width: Math.round(scaleX * 300),
                    height: Math.round(scaleY * 300),
                    marginLeft: -Math.round(scaleX * selection.x1),
                    marginTop: -Math.round(scaleY * selection.y1)
                });
              }

            };

        // Set initial selection
        if ( crop ) {
          options.data = {
            x1: crop[0],
            y1: crop[1],
            x2: crop[0] + crop[2],
            y2: crop[1] + crop[3]
          };
        }

        // Add a reference to the frame for easy destroyage later
        this.controller.cropper = $( this.$elements.edit ).imgAreaSelect( options );
      }

    } );

    /**
     * The main image editor content area.
     */
    media.view.HMImageEditView = media.View.extend( {

      className: 'hm-thumbnail-cropper-image',

      template: media.template( 'hm-thumbnail-container' ),

      initialize: function () {
        var frame = this;

        // Add an editor for each image size.
        _.each( HM_MEDIA_TOOLS.sizes, function ( props, size ) {
          frame.views.add( 'edit-' + size, new media.view.HMCropImage( {
            controller: frame.controller,
            model: frame.model,
            options: {
              size: size,
              width: props.width,
              height: props.height,
              crop: props.crop,
            }
          } ) );
        } );

        // Allow external sources to hook in.
        this.controller.trigger( 'content:hm-media-edit:create', this );
        $( document ).trigger( 'hm-crop:create', this );

        // Render views - use the built in render method as it'll handle subviews too
        this.render();
      }

    } );

    /**
     * Override the wp.media.view.MediaFrame.Select prototype
     */
    var SelectFrame = media.view.MediaFrame.Select;

    media.view.MediaFrame.Select = SelectFrame.extend( {

      createStates: function () {
        SelectFrame.prototype.createStates.apply( this, arguments );
        this.states.add( new media.controller.CropThumbnails( {
          component: 'Select'
        } ) );
      }

    } );

    /**
     * Override the wp.media.view.MediaFrame.Post prototype
     */
    var PostFrame = media.view.MediaFrame.Post;

    media.view.MediaFrame.Post = PostFrame.extend( {

      createStates: function () {
        PostFrame.prototype.createStates.apply( this, arguments );
        this.states.add( new media.controller.CropThumbnails( {
          component: 'Post'
        } ) );
      }

    } );

    /**
     * Add crop state to the frame when created from the editor
     */
    media.events.on( 'editor:frame-create', function ( options ) {
      options.frame.states.add( new media.controller.CropThumbnails( {
        component: 'ImageDetails'
      } ) );
    } );

    /**
     * Override attachments browser to give us hooks in the sidebar rendering
     */
    var AttachmentsBrowser = media.view.AttachmentsBrowser;

    media.view.AttachmentsBrowser = AttachmentsBrowser.extend( {

      createSingle: function ( attachment, selection ) {
        AttachmentsBrowser.prototype.createSingle.apply( this, arguments );
        this.controller.trigger( 'attachment:render:details', attachment, selection );
      },

      disposeSingle: function () {
        this.controller.trigger( 'attachment:dispose:details', this.sidebar );
      }

    } );

  } )( jQuery, window, document );
