<?php
/**
 * Modal Buddy Blogs
 *
 * @since 1.0.0
 *
 * This part is a preview of:
 * - https://buddypress.trac.wordpress.org/ticket/6544
 * - https://buddypress.trac.wordpress.org/ticket/6026
 *
 * @package Modal Buddy
 * @subpackage includes
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handle the display for a Blog Modal Buddy
 *
 * @since  1.0.0
 */
function modal_buddy_screen_single_blog( $has_access = false ) {
	if ( ! bp_is_blogs_component() ) {
		return $has_access;
	}

	// Get plugin's & BuddyPress instances
	$mb = modal_buddy();
	$bp = buddypress();

	if ( bp_is_current_action( 'root' ) ) {
		$blog_id = get_current_site()->id;
	} else {
		$blog_id = get_id_from_blogname( bp_current_action() );
	}

	if ( ! empty( $blog_id ) && current_user_can_for_blog( $blog_id, 'manage_options' ) ) {
		bp_update_is_item_admin( true, $bp->blogs->id );
	} else {
		return $has_access;
	}

 	// Fake a "current blog" object
	$mb->current_blog = new stdClass();
	$mb->current_blog->id   = $blog_id;
	$mb->current_blog->slug = sanitize_title( bp_current_action() );

	// Update some BuddyPress globals
	$bp->current_item   = $mb->current_blog->slug;
	$bp->is_single_item = true;

	$bp->current_action = bp_action_variable( 0 );
	array_shift( $bp->action_variables );

	if ( 'modal-buddy' === bp_current_action() ) {
		if ( ! empty( $_GET['action'] ) ) {
			$bp->current_action = sanitize_file_name( $_GET['action'] );
		}

	} elseif ( bp_action_variable( 0 ) ) {
		if ( ! empty( $_GET['action'] ) ) {
			$bp->action_variables = array( sanitize_file_name( $_GET['action'] ) );
		}
	}

	return true;
}
add_filter( 'modal_buddy_screen_has_access', 'modal_buddy_screen_single_blog', 10, 1 );

/**
 * Return whether a blog has an avatar.
 *
 * @since 1.0.0
 */
function modal_buddy_blog_has_avatar( $blog_id = 0 ) {
	if ( empty( $blog_id ) ) {
		return false;
	}

	$blog_avatar = bp_core_fetch_avatar( array(
		'item_id' => $blog_id,
		'object'  => 'blog',
		'no_grav' => true,
		'html'    => false,
	) );

	if ( bp_core_avatar_default( 'local' ) === $blog_avatar ) {
		return false;
	}

	return true;
}

/**
 * Site Icon were introduced in 4.3, this checks everything is ok
 *
 * @since  1.0.0
 */
function modal_buddy_use_site_icon() {
	/**
	 * filter here to disable the "site icon avatar"
	 *
	 * @since  1.0.0
	 *
	 * @param  bool $value True to use the site icon, false otherwise.
	 */
	return (bool) apply_filters( 'modal_buddy_use_site_icon', function_exists( 'get_site_icon_url' ) );
}

/**
 * Add the Backbone script to be able to set an avatar using a site icon
 *
 * @since  1.0.0
 */
function modal_buddy_blog_register_script( $bp_scripts = array() ) {
	$mb = modal_buddy();

	return array_merge( $bp_scripts, array(
		'modal-buddy-site-icon' => array(
			'file' => "{$mb->plugin_js}site-icon{$mb->minified}.js",
			'dependencies' => array( 'bp-avatar' ),
			'footer' => true
		),
	) );
}
add_filter( 'bp_core_register_common_scripts', 'modal_buddy_blog_register_script', 12, 1 );


/**
 * Add some strings to bring some feedback to the user
 *
 * @since  1.0.0
 */
function modal_buddy_blog_plupload_l10n( $strings = array() ) {
	$strings['site_icon'] = array(
		'saved'    => esc_html__( 'Success: Site Logo saved.', 'modal-buddy' ),
		'notSaved' => esc_html__( 'Error: the Site Logo was not saved.', 'modal-buddy' ),
		'explain'  => esc_html__( 'You can use the Site Icon to set your Site Logo.', 'modal-buddy' ),
		'noIcon'   => esc_html__( 'The Site Icon is not set for this site. Please use the Upload tab.', 'modal-buddy' ),
		'inUse'    => esc_html__( 'The Site Icon is already used as the Site Logo. You can either delete it or upload a new one using the corresponding tabs.', 'modal-buddy' ),
	);

	$strings['has_avatar_warning'] = __( 'If you&#39;d like to delete the existing site logo but not upload a new one, please use the delete tab.', 'modal-buddy' );

	return $strings;
}

/**
 * Build the Avatar UI needed parameters to manage the site logo
 *
 * @since  1.0.0
 */
function modal_buddy_blog_avatar_script_data( $script_data = array() ) {
	if ( bp_is_blogs_component() && bp_is_single_item() && bp_is_current_action( 'manage' ) && 'edit-blog-logo' === bp_action_variable( 0 ) ) {
		$blog_id = (int) modal_buddy()->current_blog->id;

		$script_data['bp_params'] = array(
			'object'     => 'blog',
			'item_id'    => $blog_id,
			'has_avatar' => modal_buddy_blog_has_avatar( $blog_id ),
			'nonces'     => array(
				'set'    => wp_create_nonce( 'bp_avatar_cropstore' ),
				'remove' => wp_create_nonce( 'bp_delete_avatar_link' ),
			),
		);

		// Set feedback messages
		$script_data['feedback_messages'] = array(
			1 => __( 'There was a problem cropping the site logo.', 'modal-buddy' ),
			2 => __( 'The site logo was uploaded successfully.', 'modal-buddy' ),
			3 => __( 'There was a problem deleting the site logo. Please try again.', 'modal-buddy' ),
			4 => __( 'The site logo was deleted successfully!', 'modal-buddy' ),
		);

		if ( modal_buddy_use_site_icon() ) {
			if ( $blog_id !== get_current_blog_id() ) {
				switch_to_blog( $blog_id );
			}

			// Defaults to no site icon
			$si_params = array( 'no_icon' => true );

			$site_icon_id = (int) get_option( 'site_icon' );

			if ( ! empty( $site_icon_id ) ) {
				// Find the site icon size which is nearest to the avatar one
				$src = wp_get_attachment_image_src( $site_icon_id, array(
					bp_core_avatar_full_width(),
					bp_core_avatar_full_height()
				) );

				if ( ! empty( $src[0] ) ) {
					$si_params = array(
						'id'      => $site_icon_id,
						'src'     => $src[0],
						'no_icon' => false,
						'max_dim' => bp_core_avatar_full_width(),
						'nonce'   => wp_create_nonce( 'modal_buddy_site_icon' ),
						'in_use'  => false,
					);
				}
			}

			if ( ms_is_switched() ) {
				restore_current_blog();
			}

			// Check if the site icon is already used as the site logo.
			if ( 'site_icon' === bp_blogs_get_blogmeta( $blog_id, 'avatar_type' ) ) {
				$si_params['in_use'] = true;
			}

			// Include specific params for the site icon
			$script_data['bp_params']['site_icon'] = $si_params;

			// Include specific scripts for the site icon
			$script_data['extra_js'][] = 'modal-buddy-site-icon';
		}
	}

	return $script_data;
}

/**
 * Add the Site Icon nav to the Avatar UI
 *
 * @since  1.0.0
 */
function modal_buddy_blog_avatar_ui_nav( $avatar_nav = array(), $object = '' ) {
	if ( 'blog' === $object ) {

		$avatar_nav['site_icon'] = array(
			'id' => 'site_icon',
			'caption' => __( 'Site Icon', 'modal-buddy' ),
			'order' => 15,
		);
	}

	return $avatar_nav;
}
add_filter( 'bp_attachments_avatar_nav', 'modal_buddy_blog_avatar_ui_nav', 10, 2 );

/**
 * Include the delete blog site logo _.template
 *
 * @since  1.0.0
 */
function modal_buddy_delete_avatar_template() {
	?>
	<# if ( 'blog' === data.object ) { #>
		<p><?php _e( "If you'd like to delete the current site logo but not upload a new one, please use the delete site logo button.", 'modal-buddy' ); ?></p>
		<p><a class="button edit" id="bp-delete-avatar" href="#" title="<?php esc_attr_e( 'Delete Site Logo', 'modal-buddy' ); ?>"><?php esc_html_e( 'Delete Site Logo', 'modal-buddy' ); ?></a></p>
	<# } #>
	<?php
}
add_action( 'bp_attachments_avatar_delete_template', 'modal_buddy_delete_avatar_template' );

/**
 * Include the site icon _.template
 *
 * @since  1.0.0
 */
function modal_buddy_site_icon_avatar_template() {
	/**
	 * I know.. I'm a bit lazy using the same markup than the crop avatar view
	 * but it saves me some times so...
	 */
	?>
	<script id="tmpl-modal-buddy-site-icon" type="text/html">
		<# if ( ! data.no_icon && ! data.in_use ) { #>
			<div class="avatar-crop-management">
				<div id="avatar-crop-pane" class="avatar" style="max-width:{{data.max_dim}}px; max-height:{{data.max_dim}}px">
					<img src="{{data.src}}" id="avatar-crop-preview"/>
				</div>
				<div id="avatar-crop-actions">
					<a class="button avatar-crop-submit" href="#"><?php esc_html_e( 'Use Site Icon', 'modal-buddy' ); ?></a>
				</div>
			</div>
		<# } #>
	</script>
	<?php
}
add_action( 'bp_attachments_avatar_main_template', 'modal_buddy_site_icon_avatar_template' );

/**
 * Make sure the current user can manage the Site logo
 *
 * @since  1.0.0
 */
function modal_buddy_blog_can_edit_avatar( $can = false, $capability = '', $args = array() ) {
	if ( 'edit_avatar' !== $capability || ! isset( $args['item_id'] ) || ! isset( $args['object'] ) || 'blog' !== $args['object'] ) {
		return $can;
	}

	return apply_filters( 'modal_buddy_blog_can_edit_avatar', current_user_can_for_blog( (int) $args['item_id'], 'manage_options' ) );
}
add_filter( 'bp_attachments_current_user_can', 'modal_buddy_blog_can_edit_avatar', 10, 3 );

/**
 * Create a new site's logo out of a site icon
 *
 * @since  1.0.0
 */
function modal_buddy_blog_site_icon_create_avatar( $blog_id = 0, $site_icon_id = 0 ) {
	if ( empty( $blog_id ) || empty( $site_icon_id ) ) {
		return false;
	}

	if ( $blog_id !== get_current_blog_id() ) {
		switch_to_blog( $blog_id );
	}

	$site_icon_img = get_attached_file( $site_icon_id );

	if ( ms_is_switched() ) {
		restore_current_blog();
	}

	if ( empty( $site_icon_img ) ) {
		return false;
	}

	$site_icon_data = BP_Attachment::get_image_data( $site_icon_img );

	if ( empty( $site_icon_data['width'] ) || empty( $site_icon_data['height'] ) ) {
		return false;
	}

	if ( ! bp_attachments_create_item_type( 'avatar', array(
		'item_id'   => $blog_id,
		'object'    => 'blog',
		'component' => 'modal_buddy_blog',
		'image'     => $site_icon_img,
		'crop_w'    => $site_icon_data['width'],
		'crop_h'    => $site_icon_data['height'],
	) ) ) {
		return false;
	}

	// Set the avatar type.
	bp_blogs_update_blogmeta( $blog_id, 'avatar_type', 'site_icon' );

	return true;
}

/**
 * Ajax Set a site icon as a site logo
 *
 * @since  1.0.0
 */
function modal_buddy_blog_ajax_set_avatar() {
	$response = array(
		'feedback_code' => 'notSaved',
	);

	if ( empty( $_POST['item_id'] ) || empty( $_POST['site_icon'] ) || empty( $_POST['item_object'] ) || 'blog' !== $_POST['item_object'] ) {
		wp_send_json_error( $response );
	}

	check_ajax_referer( 'modal_buddy_site_icon', 'nonce' );

	$blog_id      = absint( $_POST['item_id'] );
	$site_icon_id = absint( $_POST['site_icon'] );

	if ( ! modal_buddy_blog_site_icon_create_avatar( $blog_id, $site_icon_id ) ) {
		wp_send_json_error( $response );
	} else {
		// Send the response
		wp_send_json_success( array( 'feedback_code' => 'saved' ) );
	}
}
add_action( 'wp_ajax_modal_buddy_use_site_icon', 'modal_buddy_blog_ajax_set_avatar' );

/**
 * Update the site's logo if the site icon was updated
 *
 * @since 1.0.0
 */
function modal_buddy_blog_avatar_update_site_icon( $option = 'site_icon', $value = 0 ) {
	$site_icon_id   = (int) $value;
	$blog_id    = get_current_blog_id();

	if ( 'site_icon' !== bp_blogs_get_blogmeta( $blog_id, 'avatar_type' ) ) {
		return;
	}

	// Delete the Site's logo
	if ( empty( $value ) ) {
		bp_core_delete_existing_avatar( array( 'item_id' => $blog_id, 'object' => 'blog' ) );

	// Update the Site's logo
	} else {
		modal_buddy_blog_site_icon_create_avatar( $blog_id, $site_icon_id );
	}
}
add_action( 'update_option_site_icon', 'modal_buddy_blog_avatar_update_site_icon', 10, 2 );

/**
 * When a blog logo is deleted, remove the avatar type blog meta
 *
 * @since  1.0.0
 */
function modal_buddy_blog_avatar_delete_type( $args = array() ) {
	if ( isset( $args['object'] ) && 'blog' === $args['object'] && ! empty( $args['item_id'] ) ) {
		bp_blogs_delete_blogmeta( $args['item_id'], 'avatar_type' );
	}
}
add_action( 'bp_core_delete_existing_avatar', 'modal_buddy_blog_avatar_delete_type', 10, 1 );

/**
 * Delete the site's logo if the site icon was deleted
 * and if the site icon was used as site's logo
 *
 * @since 1.0.0
 */
function modal_buddy_blog_avatar_delete_site_icon() {
	$blog_id = get_current_blog_id();

	// Only delete the blog's avatar if synced with site icon
	if ( 'site_icon' !== bp_blogs_get_blogmeta( $blog_id, 'avatar_type' ) ) {
		return;
	}

	// Remove the blog's profile photo
	bp_core_delete_existing_avatar( array( 'item_id' => $blog_id, 'object' => 'blog' ) );
}
add_action( 'delete_option_site_icon', 'modal_buddy_blog_avatar_delete_site_icon' );

/**
 * Set the blogs avatar uploads dir filter
 *
 * @since  1.0.0
 */
function modal_buddy_blog_avatar_upload_params( $bp_params = array() ) {
	if ( isset( $bp_params['object'] ) && 'blog' === $bp_params['object'] && ! empty( $bp_params['item_id'] ) ) {
		// Set the blog id to use for the avatar
		modal_buddy()->avatar_blog_id = (int) $bp_params['item_id'];

		$bp_params['upload_dir_filter'] = 'modal_buddy_blog_avatar_upload_dir';
	}

	return $bp_params;
}
add_filter( 'bp_core_avatar_ajax_upload_params', 'modal_buddy_blog_avatar_upload_params', 10, 1 );

/**
 * Filter the upload dir to use a path looking like
 * /webroot/wp-content/uploads/buddypress/blogs/{$blog_id}/avatar
 *
 * @since  1.0.0
 */
function modal_buddy_blog_avatar_upload_dir( $blog_id = 0 ) {
	$attachments_upload_dir = bp_attachments_uploads_dir_get();

	$mb = modal_buddy();

	if ( empty( $blog_id ) && ! empty( $mb->avatar_blog_id ) ) {
		$blog_id = $mb->avatar_blog_id;
	}

	if ( empty( $blog_id ) ) {
		return array( 'error' => __( 'Unable to create the directory for the avatar of the site.', 'modal-buddy' ) );
	}

	// Set the subdir
	$subdir  = '/' . $attachments_upload_dir['dir'] . '/blogs/' . $blog_id . '/avatar';
	$basedir = dirname( $attachments_upload_dir['basedir'] );
	$baseurl = dirname( $attachments_upload_dir['baseurl'] );

	/**
	 * Filters the Blog's avatar upload directory.
	 *
	 * @since 1.0.0
	 *
	 * @param array $value Array containing the path, URL, and other helpful settings.
	 */
	return apply_filters( 'modal_buddy_blog_avatar_upload_dir', array(
		'path'    => $basedir . $subdir,
		'url'     => $baseurl . $subdir,
		'subdir'  => $subdir,
		'basedir' => $basedir,
		'baseurl' => $baseurl,
		'error'   => false
	) );
}

/**
 * Override the blogs avatar dir to use a path looking like
 * /webroot/wp-content/uploads/buddypress/blogs/{$item_id}/avatar
 *
 * @since  1.0.0
 */
function modal_buddy_blog_avatar_folder_dir( $folder_path = '', $item_id = 0, $object = '', $avatars_dir = '' ) {
	if ( false === strpos( $avatars_dir, 'blog' ) || 'blog' !== $object || empty( $item_id ) ) {
		return $folder_path;
	}

	// Get the uploads data for the blogs component
	$blog_avatars_uploads = modal_buddy_blog_avatar_upload_dir( $item_id );

	return apply_filters( 'modal_buddy_blog_avatar_folder_dir', $blog_avatars_uploads['path'], $item_id, $object, $avatars_dir );
}
add_filter( 'bp_core_avatar_folder_dir', 'modal_buddy_blog_avatar_folder_dir', 10, 4 );

/**
 * Override the blogs avatar url to use an url looking like
 * site.url/wp-content/uploads/buddypress/blogs/{$item_id}/avatar
 *
 * @since  1.0.0
 */
function modal_buddy_blog_avatar_folder_url( $folder_url = '', $item_id = 0, $object = '', $avatars_dir = '' ) {
	if ( 'blog-avatars' !== $avatars_dir || 'blog' !== $object || empty( $item_id ) ) {
		return $folder_url;
	}

	// Get the uploads data for the blogs component
	$blog_avatars_uploads = modal_buddy_blog_avatar_upload_dir( $item_id );

	return apply_filters( 'modal_buddy_blog_avatar_folder_url', $blog_avatars_uploads['url'], $item_id, $object, $avatars_dir );
}
add_filter( 'bp_core_avatar_folder_url', 'modal_buddy_blog_avatar_folder_url', 10, 4 );

/**
 * Get a real blog avatar!
 *
 * @since  1.0.0
 */
function modal_buddy_get_blog_avatar( $avatar = '', $blog_id = 0, $args = array(), $blog_name = '' ) {
	global $blogs_template;

	// Refetch the avatar :( No way to do else
	if ( ! empty( $blog_id ) ) {

		if ( empty( $blog_name ) && ! empty( $blogs_template->blog ) ) {
			$blog_name = bp_get_blog_name();
		}
		/**
		 * This time, filter here to edit the argument before fetching
		 * the avatar !
		 *
		 * @since  1.0.0
		 *
		 * @param array $value The Blog avatar arguments.
		 */
		$avatar = bp_core_fetch_avatar( apply_filters( 'modal_buddy_get_blog_avatar', array(
			'item_id'    => $blog_id,
			'title'      => esc_attr( sprintf( __( 'Site logo of %s', 'modal-buddy' ), $blog_name ) ),
			'avatar_dir' => 'blog-avatars',
			'object'     => 'blog',
			'type'       => $args['type'],
			'alt'        => esc_attr( sprintf( __( 'Site logo of %s', 'modal-buddy' ), $blog_name ) ),
			'css_id'     => $args['id'],
			'class'      => $args['class'],
			'width'      => $args['width'],
			'height'     => $args['height']
		) ) );
	}

	return $avatar;
}
add_filter( 'bp_get_blog_avatar', 'modal_buddy_get_blog_avatar', 10, 3 );

/**
 * Get the modal link for the Edit Site Logo button
 *
 * @since  1.0.0
 */
function modal_buddy_get_blog_link( $modal_link = '', $args = array(), $query_args = array() ) {
	if ( empty( $args['object'] ) || 'blog' !== $args['object'] || ! is_object( $args['item_id'] ) ) {
		return $modal_link;
	}

	$blog = $args['item_id'];

	if ( ! isset( $blog->path ) || 'edit-blog-logo' !== $args['modal_action'] ) {
		return $modal_link;
	}

	// The blog slug
	$slug = trim( str_replace( get_current_site()->path, '', $blog->path ), '/' );

	if ( empty( $slug ) ) {
		$slug = 'root';
	}

	// The blog's fake single item admin link !
	$site_link = trailingslashit( bp_get_root_domain() ) . bp_get_blogs_root_slug() . '/' . $slug . '/manage/';

	return add_query_arg( $query_args, wp_nonce_url( trailingslashit( $site_link . 'modal-buddy' ), 'modal_buddy_iframe', '_modal-buddy_nonce' ) );
}
add_filter( 'modal_buddy_get_object_link', 'modal_buddy_get_blog_link', 10, 3 );

/**
 * Output the Edit Site Logo Button
 *
 * @since  1.0.0
 */
function modal_buddy_blog_avatar_button() {
	global $blogs_template;

	if ( ! bp_is_user() || ! isset( $blogs_template->blog ) || ! buddypress()->avatar->show_avatars ) {
		return;
	}

	if ( current_user_can_for_blog( $blogs_template->blog->blog_id, 'manage_options' ) ) {
		$modal_params = array(
			'item_id'       => $blogs_template->blog,
			'object'        => 'blog',
			'width'         => 800,
			'height'        => 480,
			'modal_title'   => __( 'Edit Site Logo', 'modal-buddy' ),
			'modal_action'  => 'edit-blog-logo',
			'html'          => false,
		);

		// Get the modal link
		$modal_link = modal_buddy_get_link( $modal_params );

		echo modal_buddy_get_edit_button( array(
			'id'            => 'edit_blog_logo',
			'component'     => 'blogs',
			'wrapper_class' => 'js-blog-button edit-blog-logo-button',
			'wrapper_id'    => 'edit-blog-logo-button-' . $modal_params['item_id']->blog_id,
			'link_href'     => esc_url( $modal_link ),
			'link_text'     => $modal_params['modal_title'],
			'link_title'    => $modal_params['modal_title'],
		), 'avatar' );
	}
}
add_action( 'bp_directory_blogs_actions', 'modal_buddy_blog_avatar_button' );

/**
 * Output the Edit avatar template part for the blog modal
 *
 * @since 1.0.0
 */
function modal_buddy_blog_avatar_iframe() {
	// Set Custom strings & params for the blogs single item
	add_filter( 'bp_attachments_get_plupload_l10n', 'modal_buddy_blog_plupload_l10n'     , 10, 1 );
	add_filter( 'bp_attachment_avatar_script_data', 'modal_buddy_blog_avatar_script_data', 10, 1 );

	// Enqueue the Attachments scripts for the Avatar UI
	bp_attachments_enqueue_scripts( 'BP_Attachment_Avatar' );
	?>

	<h1><?php esc_html_e( 'Edit Logo', 'modal-buddy' ); ?></h1>

	<?php bp_attachments_get_template_part( 'avatars/index' );
}
add_action( 'modal_buddy_content_edit-blog-logo', 'modal_buddy_blog_avatar_iframe' );
