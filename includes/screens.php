<?php
/**
 * Modal Buddy screens
 *
 * @since 1.0.0
 *
 * @package Modal Buddy
 * @subpackage includes
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Handle the display for a Modal Buddy
 *
 * @since 1.0.0
 */
function modal_buddy_screen() {
	if ( ! modal_buddy_is_modal() ) {
		return;
	}

	// Get BuddyPress
	$bp = buddypress();

	// User
	if ( bp_is_user() && ( 'modal-buddy' === bp_current_component() || 'modal-buddy' === bp_current_action() ) ) {
		if ( ! empty( $_GET['action'] ) ) {
			$bp->current_action = sanitize_file_name( $_GET['action'] );
		}

	// Group
	} elseif ( bp_is_group() && ( 'modal-buddy' === bp_action_variable( 0 ) || 'modal-buddy' === bp_current_action() ) ) {

		if ( 'modal-buddy' === bp_current_action() ) {
			if ( ! empty( $_GET['action'] ) ) {
				$bp->current_action = sanitize_file_name( $_GET['action'] );
			}

		} elseif ( bp_action_variable( 0 ) ) {
			if ( ! empty( $_GET['action'] ) ) {
				$bp->action_variables = array( sanitize_file_name( $_GET['action'] ) );
			}
		}

	// Do what you need!
	} else {
		do_action( 'modal_buddy_screen' );
	}

	bp_core_load_template( 'assets/modal' );
}
add_action( 'bp_screens', 'modal_buddy_screen', 0 );
