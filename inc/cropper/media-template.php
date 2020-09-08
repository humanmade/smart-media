<script type="text/html" id="tmpl-hm-attachment-details-two-column">
	<# if ( data.sizes ) { #>
		<div class="media-image-edit attachment-media-view {{ data.orientation }}"></div>
	<# } else { #>
		<div class="attachment-media-view {{ data.orientation }}">
			<div class="thumbnail thumbnail-{{ data.type }}">
				<# if ( data.uploading ) { #>
					<div class="media-progress-bar"><div></div></div>
				<# } else if ( data.type === 'pdf' ) { #>
					<# if ( data.sizes && data.sizes.full ) { #>
						<img class="details-image icon" src="{{ data.sizes.full.url }}" draggable="false" alt="" />
					<# } else { #>
						<img class="details-image icon" src="{{ data.icon }}" draggable="false" alt="" />
					<# } #>
				<# } else if ( -1 === jQuery.inArray( data.type, [ 'audio', 'video' ] ) ) { #>
					<img class="details-image icon" src="{{ data.icon }}" draggable="false" alt="" />
				<# } #>

				<# if ( 'audio' === data.type ) { #>
				<div class="wp-media-wrapper">
					<audio style="visibility: hidden" controls class="wp-audio-shortcode" width="100%" preload="none">
						<source type="{{ data.mime }}" src="{{ data.url }}"/>
					</audio>
				</div>
				<# } else if ( 'video' === data.type ) {
					var w_rule = '';
					if ( data.width ) {
						w_rule = 'width: ' + data.width + 'px;';
					} else if ( wp.media.view.settings.contentWidth ) {
						w_rule = 'width: ' + wp.media.view.settings.contentWidth + 'px;';
					}
				#>
				<div style="{{ w_rule }}" class="wp-media-wrapper wp-video">
					<video controls="controls" class="wp-video-shortcode" preload="metadata"
						<# if ( data.width ) { #>width="{{ data.width }}"<# } #>
						<# if ( data.height ) { #>height="{{ data.height }}"<# } #>
						<# if ( data.image && data.image.src !== data.icon ) { #>poster="{{ data.image.src }}"<# } #>>
						<source type="{{ data.mime }}" src="{{ data.url }}"/>
					</video>
				</div>
				<# } #>

				<div class="attachment-actions">
					<# if ( 'pdf' === data.subtype && data.sizes ) { #>
					<?php _e( 'Document Preview' ); ?>
					<# } #>
				</div>
			</div>
		</div>
	<# } #>
	<div class="attachment-info">
		<span class="settings-save-status">
			<span class="spinner"></span>
			<span class="saved"><?php esc_html_e( 'Saved.' ); ?></span>
		</span>
		<div class="details">
			<div class="filename"><strong><?php _e( 'File name:' ); ?></strong> {{ data.filename }}</div>
			<div class="filename"><strong><?php _e( 'File type:' ); ?></strong> {{ data.mime }}</div>
			<div class="uploaded"><strong><?php _e( 'Uploaded on:' ); ?></strong> {{ data.dateFormatted }}</div>

			<div class="file-size"><strong><?php _e( 'File size:' ); ?></strong> {{ data.filesizeHumanReadable }}</div>
			<# if ( 'image' === data.type && ! data.uploading ) { #>
				<# if ( data.width && data.height ) { #>
					<div class="dimensions"><strong><?php _e( 'Dimensions:' ); ?></strong> {{ data.width }} &times; {{ data.height }}</div>
				<# } #>
			<# } #>

			<# if ( data.fileLength ) { #>
				<div class="file-length"><strong><?php _e( 'Length:' ); ?></strong> {{ data.fileLength }}</div>
			<# } #>

			<# if ( 'audio' === data.type && data.meta.bitrate ) { #>
				<div class="bitrate">
					<strong><?php _e( 'Bitrate:' ); ?></strong> {{ Math.round( data.meta.bitrate / 1000 ) }}kb/s
					<# if ( data.meta.bitrate_mode ) { #>
					{{ ' ' + data.meta.bitrate_mode.toUpperCase() }}
					<# } #>
				</div>
			<# } #>

			<div class="compat-meta">
				<# if ( data.compat && data.compat.meta ) { #>
					{{{ data.compat.meta }}}
				<# } #>
			</div>
		</div>

		<div class="settings">
			<label class="setting" data-setting="url">
				<span class="name"><?php _e( 'URL' ); ?></span>
				<input type="text" value="{{ data.url }}" readonly />
			</label>
			<# var maybeReadOnly = data.can.save || data.allowLocalEdits ? '' : 'readonly'; #>
			<?php if ( post_type_supports( 'attachment', 'title' ) ) : ?>
			<label class="setting" data-setting="title">
				<span class="name"><?php _e( 'Title' ); ?></span>
				<input type="text" value="{{ data.title }}" {{ maybeReadOnly }} />
			</label>
			<?php endif; ?>
			<# if ( 'audio' === data.type ) { #>
			<?php foreach ( [
				'artist' => __( 'Artist' ),
				'album' => __( 'Album' ),
			] as $key => $label ) : ?>
			<label class="setting" data-setting="<?php echo esc_attr( $key ) ?>">
				<span class="name"><?php echo $label ?></span>
				<input type="text" value="{{ data.<?php echo $key ?> || data.meta.<?php echo $key ?> || '' }}" />
			</label>
			<?php endforeach; ?>
			<# } #>
			<label class="setting" data-setting="caption">
				<span class="name"><?php _e( 'Caption' ); ?></span>
				<textarea {{ maybeReadOnly }}>{{ data.caption }}</textarea>
			</label>
			<# if ( 'image' === data.type ) { #>
				<label class="setting" data-setting="alt">
					<span class="name"><?php _e( 'Alt Text' ); ?></span>
					<input type="text" value="{{ data.alt }}" {{ maybeReadOnly }} />
				</label>
			<# } #>
			<label class="setting" data-setting="description">
				<span class="name"><?php _e( 'Description' ); ?></span>
				<textarea {{ maybeReadOnly }}>{{ data.description }}</textarea>
			</label>
			<label class="setting">
				<span class="name"><?php _e( 'Uploaded By' ); ?></span>
				<span class="value">{{ data.authorName }}</span>
			</label>
			<# if ( data.uploadedToTitle ) { #>
				<label class="setting">
					<span class="name"><?php _e( 'Uploaded To' ); ?></span>
					<# if ( data.uploadedToLink ) { #>
						<span class="value"><a href="{{ data.uploadedToLink }}">{{ data.uploadedToTitle }}</a></span>
					<# } else { #>
						<span class="value">{{ data.uploadedToTitle }}</span>
					<# } #>
				</label>
			<# } #>
			<div class="attachment-compat"></div>
		</div>

		<div class="actions">
			<a class="view-attachment" href="{{ data.link }}"><?php _e( 'View attachment page' ); ?></a>
			<# if ( ! data.uploading && data.can.remove ) { #> |
				<?php if ( MEDIA_TRASH ): ?>
					<# if ( 'trash' === data.status ) { #>
						<button type="button" class="button-link untrash-attachment"><?php _e( 'Untrash' ); ?></button>
					<# } else { #>
						<button type="button" class="button-link trash-attachment"><?php _ex( 'Trash', 'verb' ); ?></button>
					<# } #>
				<?php else: ?>
					<button type="button" class="button-link delete-attachment"><?php _e( 'Delete Permanently' ); ?></button>
				<?php endif; ?>
			<# } #>
		</div>

	</div>
</script>

<script type="text/html" id="tmpl-hm-thumbnail-container">
	<div class="spinner is-active"></div>
</script>

<script type="text/html" id="tmpl-hm-thumbnail-sizes">
	<h2 class="screen-reader-text"><?php esc_html_e( 'Image sizes', 'hm-smart-media' ); ?></h2>
	<ul class="hm-thumbnail-sizes__list">
		<li>
			<button type="button" data-size="full" class="{{ data.model.get( 'size' ) === 'full' ? 'current' : '' }}">
				<h3>
					<?php esc_html_e( 'Original' ); ?>
					<small>{{ data.model.get( 'width' ) }} x {{ data.model.get( 'height' ) }}</small>
				</h3>
				<img src="{{ data.model.get( 'url' ) + ( data.model.get( 'url' ).indexOf( '?' ) >= 0 ? '&amp;' : '?' ) }}fit=0,120" width="{{ data.model.get( 'width' ) }}" height="{{ data.model.get( 'height' ) }}" alt="original" draggable="false" />
			</button>
		</li>
		<# if ( data.model.get( 'mime' ).match( /image\/(jpe?g|png|gif)/ ) ) { #>
			<# _.each( data.model.get( 'sizes' ), function ( props, size ) { #>
				<# if ( size && size !== 'full' && size !== 'full-orig' ) { #>
				<li>
					<button type="button" data-size="{{ size }}" class="{{ data.model.get( 'size' ) === size ? 'current' : '' }}">
						<h3>
							<# if ( props.label ) { #>
								{{ props.label }}
							<# } else { #>
								<code>{{ size }}</code>
							<# } #>
							<small>{{ props.width }} x {{ props.height }}</small>
						</h3>
						<img src="{{ props.url }}" height="80" alt="{{ size }}" draggable="false" />
					</button>
				</li>
				<# } #>
			<# } ); #>
		<# } #>
	</ul>
</script>

<script type="text/html" id="tmpl-hm-thumbnail-editor">
	<# if ( data.model.get( 'size' ) === 'full' || data.model.get( 'size' ) === 'full-orig' ) { #>
		<h2><?php esc_html_e( 'Edit original image', 'hm-smart-media' ) ?> <small>{{ data.model.get( 'width' ) }} x {{ data.model.get( 'height' ) }}</small></h2>
	<# } else { #>
		<h2><?php esc_html_e( 'Edit crop', 'hm-smart-media' ) ?></h2>
	<# } #>
	<# if ( data.model.get( 'size' ) === 'full' || data.model.get( 'size' ) === 'full-orig' ) { #>
		<div class="imgedit-menu wp-clearfix">

			<# if ( data.model.get('editor').can.rotate ) { #>
			<button type="button" class="imgedit-rleft button" onclick="imageEdit.rotate( 90, {{ data.model.get( 'id' ) }}, '{{ data.model.get( 'editor' ).nonce }}', this )"><span class="screen-reader-text"><?php esc_html_e( 'Rotate counter-clockwise' ); ?></span></button>
			<button type="button" class="imgedit-rright button" onclick="imageEdit.rotate( -90, {{ data.model.get( 'id' ) }}, '{{ data.model.get( 'editor' ).nonce }}', this )"><span class="screen-reader-text"><?php esc_html_e( 'Rotate clockwise' ); ?></span></button>
			<# } else { #>
			<button type="button" class="imgedit-rleft button disabled" disabled></button>
			<button type="button" class="imgedit-rright button disabled" disabled></button>
			<# } #>

			<button type="button" onclick="imageEdit.flip( 1, {{ data.model.get( 'id' ) }}, '{{ data.model.get( 'editor' ).nonce }}', this )" class="imgedit-flipv button"><span class="screen-reader-text"><?php esc_html_e( 'Flip vertically' ); ?></span></button>
			<button type="button" onclick="imageEdit.flip( 2, {{ data.model.get( 'id' ) }}, '{{ data.model.get( 'editor' ).nonce }}', this )" class="imgedit-fliph button"><span class="screen-reader-text"><?php esc_html_e( 'Flip horizontally' ); ?></span></button>

			<button type="button" id="image-undo-{{ data.model.get( 'id' ) }}" onclick="imageEdit.undo( {{ data.model.get( 'id' ) }}, '{{ data.model.get( 'editor' ).nonce }}', this )" class="imgedit-undo button disabled" disabled><span class="screen-reader-text"><?php esc_html_e( 'Undo' ); ?></span></button>
			<button type="button" id="image-redo-{{ data.model.get( 'id' ) }}" onclick="imageEdit.redo( {{ data.model.get( 'id' ) }}, '{{ data.model.get( 'editor' ).nonce }}', this )" class="imgedit-redo button disabled" disabled><span class="screen-reader-text"><?php esc_html_e( 'Redo' ); ?></span></button>

			<input type="hidden" id="imgedit-sizer-{{ data.model.get( 'id' ) }}" value="{{ data.model.get( 'editor' ).sizer }}" />
			<input type="hidden" id="imgedit-history-{{ data.model.get( 'id' ) }}" value="" />
			<input type="hidden" id="imgedit-undone-{{ data.model.get( 'id' ) }}" value="0" />
			<input type="hidden" id="imgedit-selection-{{ data.model.get( 'id' ) }}" value="" />
			<input type="hidden" id="imgedit-x-{{ data.model.get( 'id' ) }}" value="{{ data.model.get( 'width' ) || 0 }}" />
			<input type="hidden" id="imgedit-y-{{ data.model.get( 'id' ) }}" value="{{ data.model.get( 'height' ) || 0 }}" />

			<div class="imgedit-wait" id="imgedit-wait-{{ data.model.get( 'id' ) }}"></div>

			<p class="note-focal-point"><?php esc_html_e( 'Click anywhere on the image to set a focal point for automatic cropping.', 'hm-smart-media' ); ?></p>

			<# if ( ! data.model.get('editor').can.rotate ) { #>
				<p class="note-no-rotate"><em><?php esc_html_e( 'Image rotation is not supported by your web host.' ); ?></em></p>
			<# } #>
		</div>
	<# } else { #>
		<div class="imgedit-menu wp-clearfix">
			<button type="button" class="button imgedit-crop button-apply-changes" disabled><span class="screen-reader-text"><?php esc_html_e( 'Apply changes', 'hm-smart-media' ); ?></span></button>
			<button type="button" class="button imgedit-undo button-reset" disabled><span class="screen-reader-text"><?php esc_html_e( 'Reset', 'hm-smart-media' ); ?></span></button>
			<# if ( data.model.get( 'size' ) && data.model.get( 'sizes' )[ data.model.get( 'size' ) ].hasOwnProperty( 'cropData' ) && ! data.model.get( 'sizes' )[ data.model.get( 'size' ) ].cropData.hasOwnProperty( 'x' ) ) { #>
				<p class="note-auto-crop"><?php esc_html_e( 'The crop was set automatically, to override it click and drag on the image then use the crop button.', 'hm-smart-media' ); ?></p>
			<# } else { #>
				<button type="button" class="button button-secondary button-remove-crop"><?php esc_html_e( 'Remove custom crop', 'hm-smart-media' ); ?></button>
			<# } #>
		</div>
	<# } #>
	<div class="hm-thumbnail-editor__image-wrap">
		<div class="hm-thumbnail-editor__image">
			<div  class="hm-thumbnail-editor__image-crop imgedit-crop-wrap" id="imgedit-crop-{{ data.model.get( 'id' ) }}">
				<img
					class="image-preview image-preview-{{ data.model.get( 'size' ) }}"
					id="image-preview-{{ data.model.get( 'id' ) }}"
					src="{{ data.model.get( 'url' ) }}"
					width="{{ data.model.get( 'width' ) }}"
					height="{{ data.model.get( 'height' ) }}"
					alt=""
					draggable="false"
				/>
			</div>
			<div class="hm-thumbnail-editor__focal-point focal-point" title="<?php esc_attr_e( 'Click to remove focal point', 'hm-smart-media' ); ?>"></div>
		</div>
	</div>
	<div class="hm-thumbnail-editor__actions" id="imgedit-panel-{{ data.model.get( 'id' ) }}">
		<# if ( data.model.get( 'size' ) === 'full' || data.model.get( 'size' ) === 'full-orig' ) { #>
			<input type="button" onclick="imageEdit.save( {{ data.model.get( 'id' ) }}, '{{ data.model.get( 'editor' ).nonce }}' )" disabled="disabled" class="button button-primary imgedit-submit-btn" value="<?php esc_attr_e( 'Save' ); ?>" />
			<# if ( data.model.get( 'editor' ).can.restore ) { #>
				<input type="button" onclick="imageEdit.action( {{ data.model.get( 'id' ) }}, '{{ data.model.get( 'editor' ).nonce }}', 'restore' )" class="button button-secondary" value="<?php esc_attr_e( 'Restore image' ); ?>" />
			<# } #>
		<# } #>
	</div>
</script>

<script type="text/html" id="tmpl-hm-thumbnail-preview">
	<div class="hm-thumbnail-editor__image-wrap">
		<div class="hm-thumbnail-editor__image hm-thumbnail-editor__image--preview">
			<# if ( data.model.get( 'sizes' ) ) { #>
				<img
					class="image-preview"
					src="{{ data.model.get( 'sizes' ).full.url }}"
					width="{{ data.model.get( 'sizes' ).full.width }}"
					height="{{ data.model.get( 'sizes' ).full.height }}"
					draggable="false"
					alt=""
				/>
			<# } else { #>
				<img class="details-image icon" src="{{ data.model.get( 'icon' ) }}" draggable="false" alt="" />
			<# } #>
		</div>
	</div>
</script>
