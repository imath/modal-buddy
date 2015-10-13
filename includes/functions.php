<?php
/**
 * Modal Buddy functions
 *
 * @since 1.0.0
 *
 * @package Modal Buddy
 * @subpackage includes
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

function modal_buddy_is_modal() {
	return (bool) modal_buddy()->is_modal;
}

/**
 * Add the needed script and css for the Modal Buddy
 *
 * @since 1.0.0
 */
function modal_buddy_enqueue() {
	// Enqueue me just once per page, please.
	if ( did_action( 'modal_buddy_enqueued' ) ) {
		return;
	}

	wp_enqueue_script( 'modal-buddy' );
	wp_enqueue_style ( 'modal-buddy' );

	/**
	 * Fires at the conclusion of modal_buddy_enqueue()
	 * to avoid the scripts to be loaded more than once.
	 *
	 * @since 1.0.0
	 */
	do_action( 'modal_buddy_enqueued' );
}
