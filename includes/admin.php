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

class Modal_Buddy_Admin {

	public function __construct() {
		$this->show_avatars = buddypress()->avatar->show_avatars;

		// Members
		add_action( 'bp_members_admin_xprofile_metabox', array( $this, 'disable_bp_admin_avatar' ),      9 );
		add_action( 'bp_admin_enqueue_scripts',          array( $this, 'enable_bp_admin_avatar'  ),     11 );
		add_action( 'bp_members_admin_user_metaboxes',   array( $this, 'add_meta_box'            ), 10,  2 );
	}

	public static function start() {
		$mb = modal_buddy();

		if ( empty( $mb->admin ) ) {
			$mb->admin = new self;
		}

		return $mb->admin;
	}

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

	public function disable_bp_admin_avatar() {
		buddypress()->avatar->show_avatars = false;
	}

	public function enable_bp_admin_avatar() {
		if ( ! $this->is_screen( 'users_page_bp-profile-edit' ) && ! $this->is_screen( 'profile_page_bp-profile-edit' ) ) {
			return;
		}

		buddypress()->avatar->show_avatars = $this->show_avatars;
	}

	public function add_meta_box() {
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

	public function user_admin_avatar_metabox( $user = null ) {
		if ( empty( $user->ID ) ) {
			return;
		} ?>

		<div class="avatar">

			<?php echo bp_core_fetch_avatar( array(
				'item_id' => $user->ID,
				'object'  => 'user',
				'type'    => 'full',
				'title'   => $user->display_name
			) );

			// Add a BuddyPress modal to edit the avatar
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
	}
}

endif;

add_action( 'bp_init', array( 'Modal_Buddy_Admin', 'start' ) );
