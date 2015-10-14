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
			'link_text'    => __( 'Open window', 'modal-buddy' ),
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
 * Get the header edit avatar/cover image button
 *
 * @since  1.0.0
 */
function modal_buddy_get_edit_button( $args = array(), $type = 'avatar' ) {
	if ( empty( $type ) ) {
		return false;
	}

	// Get the button
	$button = bp_parse_args( $args, array(
		'id'                => '',
		'component'         => 'xprofile',
		'must_be_logged_in' => true,
		'block_self'        => false,
		'wrapper_class'     => '',
		'wrapper_id'        => '',
		'link_href'         => '',
		'link_text'         => '',
		'link_title'        => '',
		'link_class'        => 'modal-buddy'
	), "modal_buddy_get_{$type}_button" );

	if ( empty( $button['component'] ) || empty( $button['link_href'] ) ) {
		return false;
	}

	/**
	 * Filters the HTML for the edit avatar button.
	 *
	 * @since 1.0.0
	 *
	 * @param string $button HTML markup for edit avatar button.
	 */
	return bp_get_button( apply_filters( "modal_buddy_get_{$type}_button", $button, $args ) );
}

/** Avatar ********************************************************************/

/**
 * Is the Avatar self profile's button disabled ?
 *
 * @since  1.0.0
 */
function modal_buddy_user_avatar_button_is_disabled() {
	$is_disabled = (bool) ! bp_is_my_profile();

	if ( false === $is_disabled ) {
		$is_disabled = ! bp_is_active( 'xprofile' ) || ! buddypress()->avatar->show_avatars || ! bp_attachments_is_wp_version_supported() || bp_disable_avatar_uploads();
	}

	/**
	 * Filters here to allow/disallow the Self profile header's Avatar button.
	 *
	 * @since 1.0.0
	 *
	 * @param bool $is_disabled False if the button is enabled. True otherwise.
	 */
	return apply_filters( 'modal_buddy_user_avatar_button_is_disabled', $is_disabled );
}

/**
 * Is the Avatar group's header button disabled ?
 *
 * @since  1.0.0
 */
function modal_buddy_group_avatar_button_is_disabled() {
	if ( ! bp_is_group() ) {
		return false;
	}

	$is_disabled = (bool) ! groups_is_user_admin( bp_loggedin_user_id(), bp_get_current_group_id() ) && ! is_super_admin();

	if ( false === $is_disabled ) {
		$is_disabled = ! buddypress()->avatar->show_avatars || ! bp_attachments_is_wp_version_supported() || bp_disable_group_avatar_uploads();
	}

	/**
	 * Filters here to allow/disallow the Group's header Avatar button.
	 *
	 * @since 1.0.0
	 *
	 * @param bool $is_disabled False if the button is enabled. True otherwise.
	 */
	return apply_filters( 'modal_buddy_group_avatar_button_is_disabled', $is_disabled );
}

/**
 * Output the single item header's edit avatar button
 *
 * @since  1.0.0
 */
function modal_buddy_avatar_button() {
	$button_args = array();

	if ( bp_is_user() && ! modal_buddy_user_avatar_button_is_disabled() ) {
		// Build the modal parameters
		$modal_params = array(
			'item_id'       => bp_loggedin_user_id(),
			'object'        => 'user',
			'width'         => 800,
			'height'        => 480,
			'modal_title'   => __( 'Edit Profile Photo', 'modal-buddy' ),
			'modal_action'  => 'change-avatar',
			'html'          => false,
		);

		// Get the modal link
		$modal_link = modal_buddy_get_link( $modal_params );

		// Set the button arguments for the user
		$button_args = array(
			'id'            => 'edit_profile_photo',
			'component'     => 'xprofile',
			'wrapper_class' => 'js-self-profile-button edit-profile-photo-button',
			'wrapper_id'    => 'edit-profile-photo-button-' . $modal_params['item_id'],
			'link_href'     => esc_url( $modal_link ),
			'link_text'     => $modal_params['modal_title'],
			'link_title'    => $modal_params['modal_title'],
		);
	} elseif ( bp_is_group() && ! modal_buddy_group_avatar_button_is_disabled() ) {
		// Build the modal parameters for the current group
		$modal_params = array(
			'item_id'       => groups_get_current_group(),
			'object'        => 'group',
			'width'         => 800,
			'height'        => 480,
			'modal_title'   => __( 'Edit Group Profile Photo', 'modal-buddy' ),
			'modal_action'  => 'group-avatar',
			'html'          => false,
		);

		// Get the modal link
		$modal_link = modal_buddy_get_link( $modal_params );

		$button_args = array(
			'id'            => 'edit_group_photo',
			'component'     => 'groups',
			'wrapper_class' => 'js-self-group-button edit-group-photo-button',
			'wrapper_id'    => 'edit-group-photo-button-' . $modal_params['item_id']->id,
			'link_href'     => esc_url( $modal_link ),
			'link_text'     => $modal_params['modal_title'],
			'link_title'    => $modal_params['modal_title'],
		);

	// Any other single item!
	} else {
		$button_args = apply_filters( 'modal_buddy_avatar_button_single_item', $button_args );
	}

	// No need to carry on if the button args are not ready
	if ( empty( $button_args ) ) {
		return;
	}

	echo modal_buddy_get_edit_button( $button_args, 'avatar' );
}
add_action( 'bp_member_header_actions', 'modal_buddy_avatar_button' );
add_action( 'bp_group_header_actions' , 'modal_buddy_avatar_button' );

/**
 * Output the Edit avatar template part for the user/group modal
 *
 * @since 1.0.0
 */
function modal_buddy_avatar_iframe() {
	// Enqueue the Attachments scripts for the Avatar UI
	bp_attachments_enqueue_scripts( 'BP_Attachment_Avatar' );
	?>

	<h1><?php esc_html_e( 'Edit Photo', 'modal-buddy' ); ?></h1>

	<?php bp_attachments_get_template_part( 'avatars/index' );
}
add_action( 'modal_buddy_content_change-avatar', 'modal_buddy_avatar_iframe' );
add_action( 'modal_buddy_content_group-avatar',  'modal_buddy_avatar_iframe' );

/** Cover Image ***************************************************************/

/**
 * Is the Cover Image self profile's button disabled ?
 *
 * @since  1.0.0
 */
function modal_buddy_user_cover_image_button_is_disabled() {
	$is_disabled = (bool) ! bp_is_my_profile();

	if ( false === $is_disabled ) {
		$is_disabled = ! bp_displayed_user_use_cover_image_header();
	}

	/**
	 * Filters here to allow/disallow the Self profile header's Cover Image button.
	 *
	 * @since 1.0.0
	 *
	 * @param bool $is_disabled False if the button is enabled. True otherwise.
	 */
	return apply_filters( 'modal_buddy_user_cover_image_button_is_disabled', $is_disabled );
}

/**
 * Is the Cover Image group's header button disabled ?
 *
 * @since  1.0.0
 */
function modal_buddy_group_cover_image_button_is_disabled() {
	if ( ! bp_is_group() ) {
		return false;
	}

	$is_disabled = (bool) ! groups_is_user_admin( bp_loggedin_user_id(), bp_get_current_group_id() ) && ! is_super_admin();

	if ( false === $is_disabled ) {
		$is_disabled = ! bp_group_use_cover_image_header();
	}

	/**
	 * Filters here to allow/disallow the Group's header Cover Image button.
	 *
	 * @since 1.0.0
	 *
	 * @param bool $is_disabled False if the button is enabled. True otherwise.
	 */
	return apply_filters( 'modal_buddy_group_cover_image_button_is_disabled', $is_disabled );
}

/**
 * Output the edit cover image button on single items header
 *
 * @since  1.0.0
 */
function modal_buddy_cover_image_button() {
	$button_args = array();

	if ( bp_is_user() && ! modal_buddy_user_cover_image_button_is_disabled() ) {
		// Build the modal parameters
		$modal_params = array(
			'item_id'       => bp_loggedin_user_id(),
			'object'        => 'user',
			'width'         => 800,
			'height'        => 480,
			'modal_title'   => __( 'Edit Cover Image', 'modal-buddy' ),
			'modal_action'  => 'change-cover-image',
			'html'          => false,
		);

		// Get the modal link
		$modal_link = modal_buddy_get_link( $modal_params );

		// Set the button arguments for the user
		$button_args = array(
			'id'            => 'edit_cover_image',
			'component'     => 'xprofile',
			'wrapper_class' => 'js-self-profile-button edit-cover-image-button',
			'wrapper_id'    => 'edit-cover-image-button-' . $modal_params['item_id'],
			'link_href'     => esc_url( $modal_link ),
			'link_text'     => $modal_params['modal_title'],
			'link_title'    => $modal_params['modal_title'],
		);
	} elseif ( bp_is_group() && ! modal_buddy_group_cover_image_button_is_disabled() ) {
		// Build the modal parameters for the current group
		$modal_params = array(
			'item_id'       => groups_get_current_group(),
			'object'        => 'group',
			'width'         => 800,
			'height'        => 480,
			'modal_title'   => __( 'Edit Group Cover Image', 'modal-buddy' ),
			'modal_action'  => 'group-cover-image',
			'html'          => false,
		);

		// Get the modal link
		$modal_link = modal_buddy_get_link( $modal_params );

		$button_args = array(
			'id'            => 'edit_group_cover_image',
			'component'     => 'groups',
			'wrapper_class' => 'js-self-group-button edit-group-cover-image-button',
			'wrapper_id'    => 'edit-group-cover-image-button-' . $modal_params['item_id']->id,
			'link_href'     => esc_url( $modal_link ),
			'link_text'     => $modal_params['modal_title'],
			'link_title'    => $modal_params['modal_title'],
		);
	// Any other single item!
	} else {
		$button_args = apply_filters( 'modal_buddy_cover_image_button_single_item', $button_args );
	}

	// No need to carry on if the button args are not ready
	if ( empty( $button_args ) ) {
		return;
	}

	echo modal_buddy_get_edit_button( $button_args, 'cover_image' );
}
add_action( 'bp_member_header_actions', 'modal_buddy_cover_image_button' );
add_action( 'bp_group_header_actions' , 'modal_buddy_cover_image_button' );

/**
 * Output the Edit Cover Image template part for the user/group modal
 *
 * @since 1.0.0
 */
function modal_buddy_cover_image_iframe() {
	// Enqueue the Attachments scripts for the Cover Image UI
	bp_attachments_enqueue_scripts( 'BP_Attachment_Cover_Image' );
	?>

	<h1><?php esc_html_e( 'Edit Cover Image', 'modal-buddy' ); ?></h1>

	<?php bp_attachments_get_template_part( 'cover-images/index' );
}
add_action( 'modal_buddy_content_change-cover-image', 'modal_buddy_cover_image_iframe' );
add_action( 'modal_buddy_content_group-cover-image',  'modal_buddy_cover_image_iframe' );
