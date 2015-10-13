/* global TB_WIDTH, TB_HEIGHT */

( function( $ ) {
	var originalWidth, originalHeight, originalIframeHeight;

	// Asjust Thickbox
	window.bp_tb_position = function() {
		var availableWidth  = $( window ).width(),
			availableHeight = $( window ).height(),
			needReposition  = false;

		// Don't do anything if we don't have needed Thickbox attributes
		if ( typeof TB_WIDTH === 'undefined' || typeof TB_HEIGHT === 'undefined' || typeof tb_position === 'undefined' ) {
			return;
		}

		// Only set originalWidth and originalHeight if not defined
		if ( typeof originalWidth === 'undefined' ) {
			originalWidth = TB_WIDTH;
		}

		if ( typeof originalHeight === 'undefined' ) {
			originalHeight = TB_HEIGHT;
		}

		if ( availableWidth < TB_WIDTH ) {
			window.TB_WIDTH = availableWidth - 50;
			needReposition = true;
		} else if ( availableWidth > originalWidth ) {
			window.TB_WIDTH = originalWidth;
			needReposition = true;
		}

		if ( availableHeight < TB_HEIGHT ) {
			window.TB_HEIGHT = availableHeight - 50;
			needReposition = true;
		} else if ( availableHeight > originalHeight ) {
			window.TB_HEIGHT = originalHeight;
			needReposition = true;
		}

		// Ask Thickbox to "reposition"
		if ( true === needReposition ) {
			window.tb_position();

			$( '#TB_window' ).css( {
				'height' : TB_HEIGHT + 'px'
			} );

			$( '#TB_iframeContent' ).css( {
				'width' : originalWidth - 1 + 'px',
				'height' : TB_HEIGHT - 1 + 'px'
			} );
		}
	};

	// Make sure links are not containing bigger dimensions than available ones
	$( 'a.modal-buddy' ).each( function() {
		var href = $( this ).attr( 'href' ),
			availableWidth  = $( window ).width(),
			availableHeight = $( window ).height();

		if ( ! href ) {
			return;
		}

		// Requested Thickbox width & height
		var tb_width  = href.match( /width=([0-9]+)/ );
		var tb_height = href.match( /height=([0-9]+)/ );

		if ( tb_width[1] ) {
			// If too large resize
			if ( parseInt( tb_width[1], 10 ) > availableWidth ) {
				href = href.replace( /width=[0-9]+/g, 'width=' + Number( availableWidth - 50 ) );

			// Leave unchanged
			} else {
				href = href.replace( /width=[0-9]+/g, 'width=' + tb_width[1] );
			}
		}

		if ( tb_height[1] ) {
			// If too large resize
			if ( parseInt( tb_height[1], 10 ) > availableHeight ) {
				href = href.replace( /height=[0-9]+/g, 'height=' + Number( availableHeight - 50 ) );

			// Leave unchanged
			} else {
				href = href.replace( /height=[0-9]+/g, 'height=' + tb_height[1] );
			}
		}

		// Reset links
		$( this ).attr( 'href', href );
	} );

	// Listen to modal-buddy links
	$( document ).ready( function() {
		window.tb_init( 'a.modal-buddy' );
		$( 'body' ).addClass( 'modal-buddy' );
	} );

	// Add a button to the BuddyPress modal
	window.tb_showIframe = function() {
		var isBPiframe = $( '#TB_iframeContent' ).prop( 'src' ).match( /modal-buddy/ ), bpIframe;

		if ( null === isBPiframe ) {
			return;
		}

		// Is it an Attachments iframe ?
		if ( 'undefined' !== typeof $( '#TB_iframeContent' ).get( 0 ).contentWindow.bp ) {
			bpIframe = $( '#TB_iframeContent' ).get( 0 ).contentWindow.bp;
		}

		// Listen to avatar changes
		if ( bpIframe && 'undefined' !== typeof bpIframe.Avatar ) {
			bpIframe.Avatar.Attachment.on( 'change:url', updateAvatars );
		}

		// Listen to cover image changes
		if ( bpIframe && 'undefined' !== typeof bpIframe.CoverImage ) {
			bpIframe.CoverImage.Attachment.on( 'change:url', updateCoverImage );
		}

		$( '#TB_title' ).append( '<div id="bp-modal-buttons"><a href="#" id="bp-modal-full-height"><span class="screen-reader-text">Zoom</span><div class="bp-full-height-icon"></div></a>' );
	};

	// Toggle the BuddyPress modal height from original to full
	$( 'body' ).on( 'click', '#bp-modal-buttons .bp-full-height-icon', function( event ) {
		event.preventDefault();

		var bpFullHeighBtn = $( this );

		window.bp_tb_position();

		if ( typeof originalIframeHeight === 'undefined' ) {
			originalIframeHeight = $( '#TB_iframeContent' ).prop( 'style' ).height;
		}

		if ( ! bpFullHeighBtn.hasClass( 'max' ) ) {
			window.TB_HEIGHT = $( window ).height() - 50;
			bpFullHeighBtn.addClass( 'max' );
		} else {
			window.TB_HEIGHT = originalIframeHeight;
			bpFullHeighBtn.removeClass( 'max' );
		}

		// Reposition
		window.tb_position();

		$( '#TB_window' ).css( {
			'height' : TB_HEIGHT + 'px'
		} );

		$( '#TB_iframeContent' ).css( {
			'width' : originalWidth - 1 + 'px',
			'height' : TB_HEIGHT - 1 + 'px'
		} );
	} );

	// Reposition on window resize
	$( window ).resize( function() { window.bp_tb_position(); } );

	/**
	 * Update the avatars for a given class
	 *
	 * @param  {object} data the Avatar Model
	 */
	updateAvatars = function( data ) {
		if ( 'undefined' === typeof data.attributes ) {
			return;
		}

		// Update the avatars for the parent window
		$( '.' + data.attributes.object + '-' + data.attributes.item_id + '-avatar' ).each( function() {
			$( this ).prop( 'src', data.attributes.url );
		} );
	};

	/**
	 * Update the cover image
	 *
	 * @param  {string} cover_url The url of the cover image
	 */
	updateCoverImage = function( data ) {
		console.log( data.attributes );
		/*if ( 'undefined' === typeof event.cover_image ||
			 'undefined' === typeof event.object      ||
			 'undefined' === typeof event.item_id
		) {
			return;
		}

		$( '#header-cover-image' ).css( {
			'background-image': 'url( ' + event.cover_image + ' ) '
		} );

		if ( $( '.bp-cover-image-preview' ).length ) {

			if ( ! event.cover_image ) {
				$( '.bp-cover-image-preview' ).addClass( 'hide' );
				$( '.bp-cover-image-preview' ).removeClass( 'thickbox' );
			} else {
				$( '.bp-cover-image-preview' ).addClass( 'thickbox' );
				$( '.bp-cover-image-preview' ).removeClass( 'hide' );
			}

			$( '.bp-cover-image-preview' ).prop( 'href', event.cover_image );
		}*/
	};

} )( jQuery );
