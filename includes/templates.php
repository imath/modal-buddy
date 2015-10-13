<?php
/**
 * Modal Buddy template tags
 *
 * @since 1.0.0
 *
 * @package Modal Buddy
 * @subpackage includes
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Output the modal iframe title tag
 *
 * @since  1.0.0
 */
function modal_buddy_title() {
	echo modal_buddy_get_title();
}

	/**
	 * Get the modale iframe title tag
	 *
	 * @since  1.0.0
	 *
	 * @return string the title tag for the modal iframe
	 */
	function modal_buddy_get_title() {
		$title = _x( 'Modal Buddy', 'Modal Buddy default title', 'modal-buddy' );

		if ( isset( $_GET['title'] ) ) {
			$title = $_GET['title'];
		}

		return apply_filters( 'modal_buddy_get_title', esc_html( strip_tags( $title ) ), $title );
	}

/**
 * Output the modal content
 *
 * @since  1.0.0
 */
function modal_buddy_content() {
	$action = 'modal_buddy_content';

	if ( isset( $_GET['action'] ) ) {
		$action .= '_' .sanitize_file_name( $_GET['action'] );
	}

	/**
	 * This is a dynamic action depending on the $modal_action
	 * defined in modal_buddy_get_link.
	 *
	 * @since  1.0.0
	 *
	 * eg: "modal_buddy_content_$modal_action"
	 */
	do_action( $action );
}

/**
 * Output a modal link
 *
 * @since  1.0.0
 *
 * @param  array  $args the attributes of the BuddyPress modal
 */
function modal_buddy_link( $args = array() ) {
	echo modal_buddy_get_link( $args );
}

	/**
	 * Get a modal link
	 *
	 * @since  1.0.0
	 *
	 * @param  array  $args {
	 *     An array of arguments.
	 *
	 *     @type int    $item_id      The ID of the item. Default: current user_id. Required.
	 *     @type string $object       The type of object (eg: user, group..). Default:user. Required.
	 *     @type string $width        The width of the modal. Default:600. Optionnal.
	 *     @type string $height       The height of the modal. Default:500. Optionnal.
	 *     @type string $modal_title  The title tag for the generated iframe on link click. Default: ''. Optionnal.
	 *     @type string $modal_action The name of the action that will be executed by the modal. Default: ''.Required.
	 *     @type array  $link_class   The list of class to add to the generated link. Default: empty array. Optionnal.
	 *     @type string $link_title   The title attribute of the link. Default: 'Open window'. Optionnal.
	 *     @type string $link_text    The text of the link. Default: 'Open window'. Optionnal.
	 *     @type bool   $html         Whether to return an <a> HTML element, vs a raw URL
	 *                                to a modal link. If false, <a>-specific arguments (like 'link_class')
	 *                                will be ignored. Default: true.
	 * }
	 * @return string the html to link to the modal
	 */
	function modal_buddy_get_link( $args = array() ) {
		$r = bp_parse_args( $args, array(
			'item_id'      => bp_loggedin_user_id(),
			'object'       => 'user',
			'width'        => 600,
			'height'       => 500,
			'modal_title'  => '',
			'modal_action' => '',
			'link_title'   => array(),
			'link_title'   => '',
			'link_text'    => __( 'Open window', 'buddypress' ),
			'html'         => true,
		), 'modal_buddy_link' );

		if ( empty( $r['item_id'] ) || empty( $r['object'] ) || empty( $r['modal_action'] ) ) {
			return;
		}

		if ( empty( $r['link_title'] ) ) {
			$r['link_title'] = $r['link_text'];
		}

		// Add Modal Buddy Scripts once!
		modal_buddy_enqueue();

		$query_args = array(
			'is_admin'  => 0,
			'title'     => $r['modal_title'],
			'action'    => $r['modal_action'],
			'TB_iframe' => true,
			'width'     => $r['width'],
			'height'    => $r['height'],
		);

		if ( is_admin() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) {
			$query_args['is_admin'] = 1;
		}

		if ( 'group' === $r['object'] ) {
			if ( is_a( $r['item_id'], 'BP_Groups_Group' ) ) {
				$group = $r['item_id'];
			} else {
				$group = groups_get_group( array( 'group_id' => $r['item_id'] ) );
			}

			$group_link = bp_get_group_permalink( $group );

			if ( empty( $group_link ) ) {
				return;
			}

			if ( 'group-avatar' === $r['modal_action'] || 'group-cover-image' === $r['modal_action'] ) {
				$group_link = trailingslashit( $group_link . 'admin' );
			}

			$modal_link = add_query_arg( $query_args, wp_nonce_url( trailingslashit( $group_link . 'modal-buddy' ), 'modal_buddy_iframe', '_modal-buddy_nonce' ) );

		} elseif ( 'user' === $r['object'] ) {
			$user_link = bp_core_get_user_domain( $r['item_id'] );

			if ( empty( $user_link ) ) {
				return;
			}

			if ( 'change-avatar' === $r['modal_action'] || 'change-cover-image' === $r['modal_action'] ) {
				$user_link = trailingslashit( $user_link . 'profile' );
			}

			$modal_link = add_query_arg( $query_args, wp_nonce_url( trailingslashit( $user_link . 'modal-buddy' ), 'modal_buddy_iframe', '_modal-buddy_nonce' ) );

		} else {
			return;
		}

		if ( ! $r['html'] ) {
			/**
			 * Filter here to edit the modal link
			 *
			 * @since  1.0.0
			 *
			 * @param  string $value the modal link
			 * @param  array  $r     the modal link parameter
			 */
			return apply_filters( 'modal_buddy_get_link', $modal_link, $r );
		} else {
			/**
			 * Filter here to edit the modal link
			 *
			 * @since  1.0.0
			 *
			 * @param  string $value the modal link
			 * @param  array  $r     the modal link parameter
			 */
			return apply_filters( 'modal_buddy_html_link', sprintf( '<a href="%1$s" class="modal-buddy %2$s" title="%3$s">%4$s</a>',
				esc_url( $modal_link ),
				esc_attr( join( ' ', (array) $r['link_class'] ) ),
				esc_attr( $r['link_title'] ),
				esc_html( $r['link_text'] )
			), $r );
		}
	}

/**
 * Output the self profile edit avatar button
 *
 * @since  1.0.0
 */
function modal_buddy_user_avatar_button() {
	if ( ! bp_is_my_profile() ) {
		return;
	}

	echo modal_buddy_get_user_avatar_button();
}
add_action( 'bp_member_header_actions', 'modal_buddy_user_avatar_button' );

	/**
	 * Get the self profile edit avatar button
	 *
	 * @since  1.0.0
	 *
	 * @return string HTML output
	 */
	function modal_buddy_get_user_avatar_button() {
		// Get the modal link
		$modal_link = modal_buddy_get_link( array(
			'item_id'       => bp_loggedin_user_id(),
			'object'        => 'user',
			'width'         => 800,
			'height'        => 480,
			'modal_title'   => __( 'Edit Profile Photo', 'buddypress' ),
			'modal_action'  => 'change-avatar',
			'html'          => false,
		) );

		// Get the button
		$button = array(
			'id'                => 'edit_profile_photo',
			'component'         => 'xprofile',
			'must_be_logged_in' => true,
			'block_self'        => false,
			'wrapper_class'     => 'js-self-profile-button edit-profile-photo-button',
			'wrapper_id'        => 'edit-profile-photo-button-' . bp_loggedin_user_id(),
			'link_href'         => esc_url( $modal_link ),
			'link_text'         => __( 'Edit Profile Photo', 'buddypress' ),
			'link_title'        => __( 'Edit Profile Photo', 'buddypress' ),
			'link_class'        => 'modal-buddy'
		);

		/**
		 * Filters the HTML for the edit avatar button.
		 *
		 * @since 1.0.0
		 *
		 * @param string $button HTML markup for edit avatar button.
		 */
		return bp_get_button( apply_filters( 'modal_buddy_get_user_avatar_button', $button ) );
	}

/**
 * Output the Edit avatar template part for the user modal
 *
 * @since 1.0.0
 */
function modal_buddy_user_avatar_iframe() {
	// Enqueue the Attachments scripts for the Avatar UI
	bp_attachments_enqueue_scripts( 'BP_Attachment_Avatar' );
	?>

	<h1><?php esc_html_e( 'Edit Profile Photo', 'buddypress' ); ?></h1>

	<?php bp_attachments_get_template_part( 'avatars/index' );
}
add_action( 'modal_buddy_content_change-avatar', 'modal_buddy_user_avatar_iframe' );
