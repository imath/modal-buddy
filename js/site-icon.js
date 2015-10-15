/* globals bp, _, BP_Uploader, Backbone */

window.bp = window.bp || {};

( function( exports, $ ) {

	// Bail if not set
	if ( typeof BP_Uploader === 'undefined' ) {
		return;
	}

	bp.Models      = bp.Models || {};
	bp.Collections = bp.Collections || {};
	bp.Views       = bp.Views || {};

	bp.AvatarSiteIcon = {
		start: function() {
			bp.Avatar.nav.on( 'bp-avatar-view:changed', _.bind( this.setView, this ) );

			this.site_icon = new Backbone.Model( _.extend(
				_.pick( BP_Uploader.settings.defaults.multipart_params.bp_params,
					'object',
					'item_id'
				),
				BP_Uploader.settings.defaults.multipart_params.bp_params.site_icon
			) );

			bp.Avatar.Attachment.on( 'change:url', _.bind( this.updateSiteIcon, this ) );
		},

		setView: function( view ) {
			if ( 'site_icon' !== view ) {
				return;
			}

			// Create the view
			var AvatarSiteIconView = new bp.Views.SiteIconAvatar( { model: this.site_icon } );

			// Add it to Avatar views
			bp.Avatar.views.add( { id: 'site_icon', view: AvatarSiteIconView } );

			// Display it
	        AvatarSiteIconView.inject( '.bp-avatar' );
		},

		updateSiteIcon: function( model ) {
			if ( ( 'deleted' === model.get( 'action' ) && true === this.site_icon.get( 'in_use' ) ) ||
				( 'uploaded' === model.get( 'action' ) && model.get( 'url' ) !== this.site_icon.get( 'src' ) )
			) {
				this.site_icon.set( 'in_use', false );
			}
		}
	};

	// Main view
	bp.Views.SiteIconAvatar = bp.View.extend( {
		tagName: 'div',
		id: 'modal-buddy-site-icon',
		template: bp.template( 'modal-buddy-site-icon' ),

		events: {
			'click .avatar-crop-submit': 'setAvatar'
		},

		initialize: function() {
			// The site does not have an icon
			if ( true === this.model.get( 'no_icon' ) ) {
				bp.Avatar.displayWarning( BP_Uploader.strings.site_icon.noIcon );

			// Message to inform the site icon can be used as an avatar
			} else if ( true === this.model.get( 'in_use' ) ) {
				bp.Avatar.displayWarning( BP_Uploader.strings.site_icon.inUse );

			} else {
				bp.Avatar.displayWarning( BP_Uploader.strings.site_icon.explain );
			}
		},

		setAvatar: function( event ) {
			var self = this;

			event.preventDefault();

			// Remove the suggestions view
			if ( ! _.isUndefined( bp.Avatar.views.get( 'site_icon' ) ) ) {
				var siteIconView = bp.Avatar.views.get( 'site_icon' );
				siteIconView.get( 'view' ).remove();
				bp.Avatar.views.remove( { id: 'site_icon', view: siteIconView } );
			}

			return wp.ajax.post( 'modal_buddy_use_site_icon', {
				item_id:     self.model.get( 'item_id' ),
				item_object: self.model.get( 'object' ),
				site_icon:   self.model.get( 'id' ),
				nonce:       self.model.get( 'nonce' )
			} ).done( function( resp ) {
				self.model.set( 'in_use', true );

				var avatarStatus = new bp.Views.AvatarStatus( {
					value : BP_Uploader.strings.site_icon[ resp.feedback_code ],
					type : 'success'
				} );

				bp.Avatar.views.add( {
					id   : 'status',
					view : avatarStatus
				} );

				avatarStatus.inject( '.bp-avatar-status' );

				// Update each avatars of the page
				$( '.' + self.model.get( 'object' ) + '-' + self.model.get( 'item_id' ) + '-avatar' ).each( function() {
					$( this ).prop( 'src', self.model.get( 'src' ) );
				} );

				// Show the delete nav
				bp.Avatar.navItems.get( 'delete' ).set( { hide: 0 } );

				// Update the Avatar Attachment object
				bp.Avatar.Attachment.set( _.extend(
					_.pick( self.model.attributes, ['object', 'item_id'] ),
					{ url: self.model.get( 'src' ), action: 'uploaded' }
				) );

			} ).fail( function( resp ) {

				var avatarStatus = new bp.Views.AvatarStatus( {
					value : BP_Uploader.strings.site_icon[ resp.feedback_code ],
					type : 'error'
				} );

				bp.Avatar.views.add( {
					id   : 'status',
					view : avatarStatus
				} );

				avatarStatus.inject( '.bp-avatar-status' );
			} );
		}
	} );

	bp.AvatarSiteIcon.start();

} )( bp, jQuery );
