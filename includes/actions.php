<?php
/**
 * Modal Buddy actions
 *
 * @since 1.0.0
 *
 * @package Modal Buddy
 * @subpackage includes
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueue scripts/styles specific to the Modal Buddy
 *
 * @since  1.0.0
 */
function modal_buddy_enqueue_scripts( $is_admin = false ) {
	$admin = '';

	wp_enqueue_scripts();

	// Clean up scripts
	foreach ( wp_scripts()->queue as $js_handle ) {
		wp_dequeue_script( $js_handle );
	}

	if ( true === $is_admin ) {
		// Clean up styles
		foreach ( wp_styles()->queue as $css_handle ) {
			wp_dequeue_style( $css_handle );
		}

		$admin = '_admin';
		wp_enqueue_style( 'colors' );
	}

	/**
	 * Hook here to add your scripts
	 */
	do_action( "modal_buddy{$admin}_enqueue_scripts" );
}

/**
 * Fire the 'modal_buddy_header' action, or the 'modal_buddy_admin_header' in the modal was opened from
 * an Administration screen.
 *
 * @since 1.0.0
 */
function modal_buddy_header( $is_admin = false ) {
	$action = 'modal_buddy_header';

	if ( true === (bool) $is_admin ) {
		$action = 'modal_buddy_admin_header';
	}

	/**
	 * Fires inside the 'modal_buddy_header' function.
	 *
	 * @since 1.0.0
	 *
	 * @param  bool $is_admin True for a wp-admin context, false otherwise
	 */
	do_action( $action, $is_admin );
}

/**
 * Fire the 'modal_buddy_footer' action, or the 'modal_buddy_admin_footer' in the modal was opened from
 * an Administration screen.
 *
 * @since 1.0.0
 */
function modal_buddy_footer( $is_admin = false ) {
	$action = 'modal_buddy_footer';

	if ( true === (bool) $is_admin ) {
		$action = 'modal_buddy_admin_footer';
	}

	/**
	 * Fires inside the 'modal_buddy_footer' function.
	 *
	 * @since 1.0.0
	 *
	 * @param  bool $is_admin True for a wp-admin context, false otherwise
	 */
	do_action( $action, $is_admin );
}

// Front end context
add_action( 'modal_buddy_header', 'modal_buddy_enqueue_scripts',  1 );
add_action( 'modal_buddy_header', 'locale_stylesheet'               );
add_action( 'modal_buddy_header', 'wp_print_styles',              8 );
add_action( 'modal_buddy_header', 'wp_print_head_scripts',        9 );
add_action( 'modal_buddy_footer', 'wp_print_footer_scripts',     20 );

// Admin context
add_action( 'modal_buddy_admin_header', 'modal_buddy_enqueue_scripts',  1 );
add_action( 'modal_buddy_admin_header', 'register_admin_color_schemes', 2 );
add_action( 'modal_buddy_admin_header', 'print_admin_styles',           3 );
add_action( 'modal_buddy_admin_footer', 'wp_print_footer_scripts',      20 );
