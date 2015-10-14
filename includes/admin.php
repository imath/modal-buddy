<?php
/**
 * Modal Buddy Admin
 *
 * @since 1.0.0
 *
 * @package Modal Buddy
 * @subpackage includes
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Modal_Buddy_Admin' ) ) :
/**
 * Admin Class
 *
 * @since 1.0.0
 */
class Modal_Buddy_Admin {

	/**
	 * Let's hook BuddyPress!
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->show_avatars = buddypress()->avatar->show_avatars;

		do_action( 'modal_buddy_admin_before_hooks' );

		// Members
		if ( bp_is_active( 'xprofile' ) ) {
			add_action( 'bp_members_admin_xprofile_metabox', array( $this, 'disable_bp_admin_avatar' ),      9 );
			add_action( 'bp_admin_enqueue_scripts',          array( $this, 'enable_bp_admin_avatar'  ),     11 );
			add_action( 'bp_members_admin_user_metaboxes',   array( $this, 'members_add_meta_boxes'  ), 10,  2 );
		}

		// Groups
		if ( bp_is_active( 'groups' ) ) {
			add_action( 'bp_admin_enqueue_scripts',   array( $this, 'groups_inline_style'   ), 11 );
			add_action( 'bp_groups_admin_meta_boxes', array( $this, 'groups_add_meta_boxes' )     );
		}

		do_action( 'modal_buddy_admin_after_hooks' );
	}

	/**
	 * Starts the class
	 *
	 * @since 1.0.0
	 */
	public static function start() {
		$mb = modal_buddy();

		if ( empty( $mb->admin ) ) {
			$mb->admin = new self;
		}

		return $mb->admin;
	}

	/**
	 * Checks an Admin screen
	 *
	 * @since 1.0.0
	 */
	public function is_screen( $screen = '' ) {
		if ( ! function_exists( 'get_current_screen' ) ) {
			return false;
		}

		$current_screen = get_current_screen();

		if ( empty( $current_screen->id ) ) {
			return false;
		}

		return (bool) false !== strpos( $current_screen->id, $screen );
	}

	/**
	 * Make sure the BuddyPress xProfile Metabox is not displayed
	 * by temporarly setting the BuddyPress show avatars global to false
	 *
	 * @since 1.0.0
	 */
	public function disable_bp_admin_avatar() {
		buddypress()->avatar->show_avatars = false;
	}

	/**
	 * Restore the BuddyPress show avatars global
	 *
	 * @since 1.0.0
	 */
	public function enable_bp_admin_avatar() {
		if ( ! $this->is_screen( 'users_page_bp-profile-edit' ) && ! $this->is_screen( 'profile_page_bp-profile-edit' ) ) {
			return;
		}

		buddypress()->avatar->show_avatars = $this->show_avatars;
	}

	/**
	 * Add metaboxes to the WP Admin/extended Profile
	 *
	 * @since 1.0.0
	 */
	public function members_add_meta_boxes() {
		if ( $this->show_avatars ) {
			// Avatar Metabox.
			add_meta_box(
				'bp_xprofile_user_admin_avatar',
				_x( 'Profile Photo', 'xprofile user-admin edit screen', 'modal-buddy' ),
				array( $this, 'user_admin_avatar_metabox' ),
				buddypress()->members->admin->user_page,
				'side',
				'low'
			);
		}
	}

	/**
	 * Displays the Avatar metabox
	 *
	 * @since 1.0.0
	 */
	public function user_admin_avatar_metabox( $user = null ) {
		if ( empty( $user->ID ) ) {
			return;
		}
		/**
		 * Before the output is done
		 *
		 * @since 1.0.0
		 */
		do_action( 'modal_buddy_admin_before_avatar_output' );
		?>

		<div class="avatar">

			<?php echo bp_core_fetch_avatar( array(
				'item_id' => $user->ID,
				'object'  => 'user',
				'type'    => 'full',
				'title'   => $user->display_name
			) );

			// Add the Modal Buddy link to edit the avatar
			if ( ! bp_core_get_root_option( 'bp-disable-avatar-uploads' ) && bp_attachments_is_wp_version_supported() ) :
				modal_buddy_link( array(
					'item_id'       => $user->ID,
					'object'        => 'user',
					'width'         => 800,
					'height'        => 400,
					'modal_title'   => __( 'Edit Profile Photo', 'modal-buddy' ),
					'modal_action'  => 'change-avatar',
					'link_text'     => __( 'Edit Profile Photo', 'modal-buddy' ),
					'link_class'    => array( 'bp-xprofile-avatar-user-edit' ),
				) );

			elseif ( bp_get_user_has_avatar( $user->ID ) ) :

				$query_args = array(
					'user_id' => $user->ID,
					'action'  => 'delete_avatar'
				);

				if ( ! empty( $_REQUEST['wp_http_referer'] ) ) {
					$query_args['wp_http_referer'] = urlencode( wp_unslash( $_REQUEST['wp_http_referer'] ) );
				}

				$community_url = add_query_arg( $query_args, buddypress()->members->admin->edit_profile_url );
				$delete_link   = wp_nonce_url( $community_url, 'delete_avatar' ); ?>

				<a href="<?php echo esc_url( $delete_link ); ?>" title="<?php esc_attr_e( 'Delete Profile Photo', 'modal-buddy' ); ?>" class="bp-xprofile-avatar-user-admin"><?php esc_html_e( 'Delete Profile Photo', 'modal-buddy' ); ?></a></li>

			<?php endif;  ?>

		</div>
		<?php
		/**
		 * Once the output is done
		 *
		 * @since 1.0.0
		 */
		do_action( 'modal_buddy_admin_before_avatar_output' );
	}

	public function groups_inline_style() {
		if ( ! $this->is_screen( 'toplevel_page_bp-groups' ) ) {
			return;
		}

		wp_add_inline_style( 'bp_groups_admin_css', '
			div#bp_group_avatar div.avatar {
 				width: 150px;
 				margin: 0 auto;
 			}

 			div#bp_group_avatar div.avatar img {
 				max-width: 100%;
 				height: auto;
 			}

 			div#bp_group_avatar a.bp-groups-avatar-admin-edit,
 			div#bp_group_cover_image a.bp-groups-cover-image-admin-edit,
 			div#bp_group_cover_image a#bp-groups-cover-image-admin-preview {
 				margin: 1em 0;
 				text-decoration: none;
 				color: #888;
 			}

 			div#bp_group_cover_image a.bp-groups-cover-image-admin-edit,
 			div#bp_group_cover_image a#bp-groups-cover-image-admin-preview {
 				text-align: center;
 				display: inline-block;
 				margin-right: 5px;
 				font-size: 90%;
 			}

 			div#bp_group_avatar a.bp-groups-avatar-admin-edit:before,
 			div#bp_group_cover_image a.bp-groups-cover-image-admin-edit:before,
 			div#bp_group_cover_image a#bp-groups-cover-image-admin-preview:before {
 				font: normal 20px/1 "dashicons";
 				speak: none;
 				display: inline-block;
 				padding: 0 2px 0 0;
 				top: 0;
 				left: -1px;
 				position: relative;
 				vertical-align: top;
 				-webkit-font-smoothing: antialiased;
 				-moz-osx-font-smoothing: grayscale;
 				text-decoration: none !important;
 				color: #888;
 			}

 			div#bp_group_avatar a.bp-groups-avatar-admin-edit:before,
 			div#bp_group_cover_image a.bp-groups-cover-image-admin-edit:before {
 				content: "\f107";
 			}

 			div#bp_group_cover_image a#bp-groups-cover-image-admin-preview:before {
 				content: "\f179";
 			}

 			div#bp_group_cover_image a#bp-groups-cover-image-admin-preview.hide {
 				display:none;
 			}
		' );
	}

	public function groups_add_meta_boxes() {
		if ( ! bp_disable_group_avatar_uploads() && $this->show_avatars && bp_attachments_is_wp_version_supported() ) {
			add_meta_box(
				'bp_group_avatar',
				_x( 'Photo', 'group admin edit screen', 'modal-buddy' ),
				array( $this, 'groups_admin_avatar_metabox' ),
				get_current_screen()->id,
				'side',
				'core'
			);
		}
	}

	public function groups_admin_avatar_metabox( $group = null ) {
		if ( empty( $group->id ) ) {
			return;
		}
		/**
		 * Before the output is done
		 *
		 * @since 1.0.0
		 */
		do_action( 'modal_buddy_admin_before_group_avatar_output' );
		?>
		<div class="avatar">

			<?php
			echo bp_core_fetch_avatar( array(
				'item_id' => $group->id,
				'object'  => 'group',
				'type'    => 'full',
				'title'   => $group->name,
				'alt'     => sprintf( __( 'Group logo of %s', 'buddypress' ), $group->name ),
			) );

			modal_buddy_link( array(
				'item_id'       => $group,
				'object'        => 'group',
				'width'         => 800,
				'height'        => 400,
				'modal_title'   => __( 'Edit Group Photo', 'modal-buddy' ),
				'modal_action'  => 'group-avatar',
				'link_text'     => __( 'Edit Group Photo', 'buddypress' ),
				'link_class'    => array( 'bp-groups-avatar-admin-edit' ),
			) );
			?>

		</div>
		<?php
		/**
		 * Once the output is done
		 *
		 * @since 1.0.0
		 */
		do_action( 'modal_buddy_admin_after_group_avatar_output' );
	}
}

endif;

add_action( 'bp_init', array( 'Modal_Buddy_Admin', 'start' ) );
