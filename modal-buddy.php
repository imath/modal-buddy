<?php
/**
 * Utility to add a modal Window for BuddyPress objects
 *
 *
 * @package   Modal Buddy
 * @author    imath
 * @license   GPL-2.0+
 * @link      https://imathi.eu
 *
 * @buddypress-plugin
 * Plugin Name:       Modal Buddy
 * Plugin URI:        https://github.com/imath/modal-buddy
 * Description:       Utility to add a modal Window for BuddyPress objects
 * Version:           1.0.0-alpha
 * Author:            imath
 * Author URI:        https://github.com/imath
 * Text Domain:       modal-buddy
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:       /languages/
 * GitHub Plugin URI: https://github.com/imath/modal-buddy
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Modal_Buddy' ) ) :
/**
 * Main Class
 *
 * @since 1.0.0
 */
class Modal_Buddy {
	/**
	 * Instance of this class.
	 */
	protected static $instance = null;

	/**
	 * BuddyPress db version
	 */
	public static $bp_db_version_required = 10000;

	/**
	 * Initialize the plugin
	 */
	private function __construct() {
		$this->setup_globals();
		$this->includes();
		$this->setup_hooks();
	}

	/**
	 * Return an instance of this class.
	 *
	 * @since 1.0.0
	 */
	public static function start() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Sets some globals for the plugin
	 *
	 * @since 1.0.0
	 */
	private function setup_globals() {
		/** Plugin globals ********************************************/
		$this->version       = '1.0.0-alpha';
		$this->domain        = 'modal-buddy';
		$this->name          = 'Modal Buddy';
		$this->file          = __FILE__;
		$this->basename      = plugin_basename( $this->file );
		$this->plugin_dir    = plugin_dir_path( $this->file );
		$this->plugin_url    = plugin_dir_url( $this->file );
		$this->includes_dir  = trailingslashit( $this->plugin_dir . 'includes'  );
		$this->lang_dir      = trailingslashit( $this->plugin_dir . 'languages' );
		$this->templates_dir = $this->plugin_dir . 'templates';
		$this->plugin_js     = trailingslashit( $this->plugin_url . 'js' );
		$this->plugin_css    = trailingslashit( $this->plugin_url . 'css' );
		$this->is_modal      = false;
		$this->minified      = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		/** Plugin config ********************************************/
		$this->config = $this->network_check();

		/** Blogs Component *****************************************/
		if ( is_multisite() && bp_is_active( 'blogs' ) ) {
			// Allow people to disable the Site logo feature using
			// add_filter( 'bp_is_blogs_site_logo_active', '__return_false' )
			buddypress()->blogs->features = array( 'site_logo' );
		}
	}

	/**
	 * Checks BuddyPress version
	 *
	 * @since 1.0.0
	 */
	public function version_check() {
		// taking no risk
		if ( ! function_exists( 'bp_get_db_version' ) ) {
			return false;
		}

		return self::$bp_db_version_required <= bp_get_db_version();
	}

	/**
	 * Checks if current blog is the one where BuddyPress is activated
	 *
	 * @since 1.0.0
	 */
	public function root_blog_check() {
		if ( ! function_exists( 'bp_get_root_blog_id' ) ) {
			return false;
		}

		if ( get_current_blog_id() != bp_get_root_blog_id() ) {
			return false;
		}

		return true;
	}

	/**
	 * Checks if current blog is the one where BuddyPress is activated
	 *
	 * @since 1.0.0
	 */
	public function network_check() {
		/*
		 * network_active : this plugin is activated on the network
		 * network_status : BuddyPress & this plugin share the same network status
		 */
		$config = array( 'network_active' => false, 'network_status' => true );
		$network_plugins = get_site_option( 'active_sitewide_plugins', array() );

		// No Network plugins
		if ( empty( $network_plugins ) ) {
			return $config;
		}

		$check = array( buddypress()->basename, $this->basename );
		$network_active = array_diff( $check, array_keys( $network_plugins ) );

		if ( count( $network_active ) == 1 )
			$config['network_status'] = false;

		$config['network_active'] = isset( $network_plugins[ $this->basename ] );

		return $config;
	}

	/**
	 * Include needed files
	 *
	 * @since 1.0.0
	 */
	private function includes() {
		if ( ! $this->version_check() || ! $this->root_blog_check() || ! $this->config['network_status'] ) {
			return;
		}

		require( $this->includes_dir . 'functions.php' );
		require( $this->includes_dir . 'screens.php'   );
		require( $this->includes_dir . 'templates.php' );
		require( $this->includes_dir . 'actions.php'   );

		// Make sure to be in an admin screen
		if ( is_admin() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) {
			require( $this->includes_dir . 'admin.php' );
		}

		// Blog avatars!
		if (  bp_is_active( 'blogs', 'site_logo' ) ) {
			require( $this->includes_dir . 'blogs.php' );
		}
	}

	/**
	 * Set hooks
	 *
	 * @since 1.0.0
	 */
	private function setup_hooks() {
		// This plugin && BuddyPress share the same config & BuddyPress version is ok
		if ( $this->version_check() && $this->root_blog_check() && $this->config['network_status'] ) {
			// Register the template directory
			add_action( 'bp_register_theme_directory', array( $this, 'register_template_dir' )    );

			// Set and locate the Modal Buddy
			add_action( 'bp_init',             array( $this, 'set_modal'    ),     3 );
			add_filter( 'bp_located_template', array( $this, 'locate_modal' ), 10, 2 );

			// Register the Javascript and the stylesheet
			add_filter( 'bp_core_register_common_scripts', array( $this, 'register_script' ) );
			add_filter( 'bp_core_register_common_styles',  array( $this, 'register_style'  ) );

			// Take care of forbidden/illegal names as long as we can!
			add_filter( 'groups_forbidden_names',    array( $this, 'restricted_name' ), 10, 1 );
			add_filter( 'site_option_illegal_names', array( $this, 'restricted_name' ), 10, 1 );

		// There's something wrong, inform the Administrator
		} else {
			add_action( $this->config['network_active'] ? 'network_admin_notices' : 'admin_notices', array( $this, 'admin_warning' ) );
		}

		// load the languages..
		add_action( 'bp_init', array( $this, 'load_textdomain' ), 5 );
	}

	/**
	 * Register the template dir into BuddyPress template stack
	 *
	 * @since 1.0.0
	 */
	public function register_template_dir() {
		bp_register_template_stack( array( $this, 'template_dir' ),  20 );
	}

	/**
	 * Get the template dir
	 *
	 * @since 1.0.0
	 */
	public function template_dir() {
		if ( ! $this->is_modal ) {
			return;
		}

		return apply_filters( 'modal_buddy_templates_dir', $this->templates_dir );
	}

	/**
	 * Just after BuddyPress parsed the requested UI, checks if a Modal Buddy
	 * is requested
	 *
	 * @since 1.0.0
	 */
	public function set_modal() {
		$bp = buddypress();

		if ( isset( $bp->unfiltered_uri ) && array_search( 'modal-buddy', $bp->unfiltered_uri ) ) {
			$this->is_modal = true;

			// No Admin Bar into the iFrame!
			add_filter( 'show_admin_bar', '__return_false' );

			// We will enqueue the script later
			remove_action( 'bp_enqueue_scripts', 'bp_core_avatar_scripts' );

			// We will enqueue the script later
			remove_action( 'bp_enqueue_scripts', 'bp_core_cover_image_scripts' );

			// No need to include this css
			remove_action( 'bp_enqueue_scripts', 'bp_add_cover_image_inline_css', 11 );
		}
	}

	/**
	 * Use the modal template when the Modal Buddy is requested
	 *
	 * @since 1.0.0
	 */
	public function locate_modal( $located = '', $filtered = array() ) {
		if ( $this->is_modal ) {
			$located = bp_locate_template( reset( $filtered ) );
		}

		return $located;
	}

	/**
	 * Register the Javascript
	 *
	 * @since 1.0.0
	 */
	public function register_script( $bp_scripts = array() ) {
		return array_merge( $bp_scripts, array(
			'modal-buddy' => array(
				'file' => "{$this->plugin_js}modal-buddy{$this->minified}.js",
				'dependencies' => array( 'thickbox' ),
				'footer' => true
			),
		) );
	}

	/**
	 * Register the Stylesheet
	 *
	 * @since 1.0.0
	 */
	public function register_style( $bp_styles = array() ) {
		return array_merge( $bp_styles, array(
			'modal-buddy' => array(
				'file' => "{$this->plugin_css}modal-buddy{$this->minified}.css",
				'dependencies' => array( 'thickbox' ),
			),
		) );
	}

	/**
	 * Take care of forbidden names!
	 *
	 * There's still a possibility of trouble on non ms configs
	 * if the user login is modal-buddy! But i don't want to return
	 * an empty login filtering pre_user_login because the error message
	 * will then be very confusing...
	 *
	 * @since 1.0.0
	 */
	public function restricted_name( $names = array() ) {
		if ( ! in_array( $this->domain, $names ) ) {
			$names = array_merge( $names, array( $this->domain ) );
		}

		return $names;
	}

	/**
	 * Display a message to admin in case config is not as expected
	 *
	 * @since 1.0.0
	 */
	public function admin_warning() {
		$warnings = array();

		if( ! $this->version_check() ) {
			$warnings[] = sprintf( __( '%s requires at least version %s of BuddyPress.', 'modal-buddy' ), $this->name, '2.4.0' );
		}

		if ( ! bp_core_do_network_admin() && ! $this->root_blog_check() ) {
			$warnings[] = sprintf( __( '%s requires to be activated on the blog where BuddyPress is activated.', 'modal-buddy' ), $this->name );
		}

		if ( bp_core_do_network_admin() && ! is_plugin_active_for_network( $this->basename ) ) {
			$warnings[] = sprintf( __( '%s and BuddyPress need to share the same network configuration.', 'modal-buddy' ), $this->name );
		}

		if ( ! empty( $warnings ) ) :
		?>
		<div id="message" class="error">
			<?php foreach ( $warnings as $warning ) : ?>
				<p><?php echo esc_html( $warning ) ; ?>
			<?php endforeach ; ?>
		</div>
		<?php
		endif;
	}

	/**
	 * Loads the translation files
	 *
	 * @since 1.0.0
	 */
	public function load_textdomain() {
		// Traditional WordPress plugin locale filter
		$locale        = apply_filters( 'plugin_locale', get_locale(), $this->domain );
		$mofile        = sprintf( '%1$s-%2$s.mo', $this->domain, $locale );

		// Setup paths to current locale file
		$mofile_local  = $this->lang_dir . $mofile;
		$mofile_global = WP_LANG_DIR . '/modal-buddy/' . $mofile;

		// Look in global /wp-content/languages/modal-buddy folder
		load_textdomain( $this->domain, $mofile_global );

		// Look in local /wp-content/plugins/modal-buddy/languages/ folder
		load_textdomain( $this->domain, $mofile_local );
	}
}

endif;

// Let's start !
function modal_buddy() {
	return Modal_Buddy::start();
}
add_action( 'bp_include', 'modal_buddy', 9 );
