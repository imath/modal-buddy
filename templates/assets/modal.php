<?php
/**
 * Modal Buddy template
 *
 * This template is used to render the Modal Buddy
 *
 * @since 1.0.0
 *
 * @package Modal Buddy
 * @subpackage templates
 */

// Check nonce.
$nonce_check = false;
if ( isset( $_REQUEST['_modal-buddy_nonce'] ) ) {
	$nonce_check = wp_verify_nonce( $_REQUEST['_modal-buddy_nonce'], 'modal_buddy_iframe' );
}

if ( ! $nonce_check ) {
	wp_die( __( 'You are not allowed to directly access to this page.', 'modal-buddy') , __( 'Modal Buddy Failure', 'modal-buddy' ), 403 );
}

// Check if the modal was opened from an Admin page
$is_admin = false;
if ( ! empty( $_GET['is_admin'] ) ) {
	$is_admin = true;
}
?>
<!DOCTYPE html>
<!--[if IE 8]>
<html xmlns="http://www.w3.org/1999/xhtml" class="ie8" <?php language_attributes(); ?>>
<![endif]-->
<!--[if !(IE 8) ]><!-->
<html xmlns="http://www.w3.org/1999/xhtml" <?php language_attributes(); ?> style="background-color:#FFF">
<!--<![endif]-->
<head>
<meta http-equiv="Content-Type" content="<?php bloginfo( 'html_type' ); ?>; charset=<?php echo get_option( 'blog_charset' ); ?>" />
<title><?php bloginfo('name') ?> &rsaquo; <?php modal_buddy_title(); ?></title>

<?php modal_buddy_header( $is_admin ); ?>

<style type="text/css">
	body.modal-buddy,
	body.admin {
		background-color: #FFF;
		width:98%;
	}

	body.admin #bp-webcam-avatar #avatar-crop-actions a.button {
		padding:0;
	}

	#buddypress {
		margin: 1em;
	}

	#buddypress h1 {
		margin: 1em 0;
	}

	#buddypress.wrap .bp-screen-reader-text {
		position: absolute;
		margin: -1px;
		padding: 0;
		height: 1px;
		width: 1px;
		overflow: hidden;
		clip: rect(0 0 0 0);
		border: 0;
		word-wrap: normal !important;
	}
</style>

</head>
<body <?php if ( true === $is_admin ) { echo 'class="admin wp-core-ui no-js"'; } else {  body_class( 'modal-buddy iframe no-js' ); }?>>
<script type="text/javascript">
document.body.className = document.body.className.replace('no-js', 'js');
</script>

	<div id="buddypress" class="<?php echo ( true === $is_admin ) ? 'wrap' : 'entry-content' ;?>">

		<?php modal_buddy_content(); ?>

	</div>

	<?php modal_buddy_footer( $is_admin ); ?>

</body>
</html>
