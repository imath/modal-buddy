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

/**
 * Check if the Modal Buddy has been requested
 *
 * @since 1.0.0
 */
function modal_buddy_is_modal() {
	return (bool) modal_buddy()->is_modal;
}

/**
 * Make sure we can safely load the iframe content
 *
 * @since 1.0.0
 */
function modal_buddy_is_safe() {
	$nonce_check = false;
	if ( isset( $_REQUEST['_modal-buddy_nonce'] ) ) {
		$nonce_check = wp_verify_nonce( $_REQUEST['_modal-buddy_nonce'], 'modal_buddy_iframe' );
	}

	if ( ! $nonce_check ) {
		wp_die( __( 'You are not allowed to directly access to this page.', 'modal-buddy') , __( 'Modal Buddy Failure', 'modal-buddy' ), 403 );
	}
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
