<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    VTB
 * @subpackage VTB/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    VTB
 * @subpackage VTB/includes
 * @author     Your Name <email@example.com>
 */
class VTB {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      VTB_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $vtb    The string used to uniquely identify this plugin.
	 */
	protected $vtb;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {

		$this->vtb = 'video-tutorial-builder';
		$this->version = '1.0.0';

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        //$this->define_public_hooks();

        $this->register_post_type();
    }

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - VTB_Loader. Orchestrates the hooks of the plugin.
	 * - VTB_i18n. Defines internationalization functionality.
	 * - VTB_Admin. Defines all hooks for the admin area.
	 * - VTB_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/functions/core.php';

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-vtb-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-vtb-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-vtb-admin.php';

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-vtb-metaboxes.php';

		$this->loader = new VTB_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the VTB_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new VTB_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new VTB_Admin( $this->get_plugin_name(), $this->get_version() );

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			$this->loader->add_action( 'wp_ajax_vtb_drag_sort_posts', $plugin_admin, 'ajax_update_sorting');
			$this->loader->add_action( 'wp_ajax_vtb_watched_video', $plugin_admin, 'ajax_watched_video');
		} else {

			$this->loader->add_action( 'admin_init', $plugin_admin, 'init_settings' );
			$this->loader->add_action( 'admin_notices', $plugin_admin, 'render_admin_notices');
			$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
			$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
			$this->loader->add_action( 'admin_menu', $plugin_admin, 'add_menus' );

			add_filter( 'custom_menu_order', '__return_true' ); //modify our video_tutorial admin menu
			$this->loader->add_filter( 'menu_order', $plugin_admin, 'edit_menu_items' );
			$this->loader->add_filter( 'manage_video_tutorial_posts_columns', $plugin_admin, 'edit_columns' );
			$this->loader->add_filter( 'manage_edit-video_tutorial_sortable_columns', $plugin_admin, 'sortable_columns' );
			$this->loader->add_action( 'manage_video_tutorial_posts_custom_column', $plugin_admin, 'render_column', 10, 2 );
			$this->loader->add_action( 'quick_edit_custom_box', $plugin_admin, 'render_quick_edit_column', 10, 2);

			$this->loader->add_action( 'get_previous_post_where', $plugin_admin, 'modify_adjacent_post_where', 10, 5);
			$this->loader->add_action( 'get_next_post_where', $plugin_admin, 'modify_adjacent_post_where', 10, 5);
			$this->loader->add_action( 'get_previous_post_sort', $plugin_admin, 'modify_adjacent_post_sort', 10, 2 );
			$this->loader->add_action( 'get_next_post_sort', $plugin_admin, 'modify_adjacent_post_sort', 10, 2 );
		}
		
		$this->loader->add_filter( 'wp_insert_post_data' , $plugin_admin, 'modify_tutorial_post' , '99', 1 );
		$this->loader->add_filter('pre_get_posts', $plugin_admin, 'set_post_order' );

		$meta_boxes = new VTB_Metaboxes();
		$this->loader->add_action( 'add_meta_boxes', $meta_boxes, 'vtb_meta_boxes'  );
		$this->loader->add_action( 'save_post', $meta_boxes, 'save_meta_boxes',  10, 2 );
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new VTB_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

	}

    /**
     * Registers the custom post type for the tutorials
     */
    public function register_post_type() {
        $settings = get_option('vtb_settings');
	    $tutorial_name = !empty($settings['tutorial_name']) ? $settings['tutorial_name'] : __('Tutorials', 'vtb');
	    $tutorial_name_single = !empty($settings['tutorial_name_single']) ? $settings['tutorial_name_single'] : __('Tutorial', 'vtb');

	    register_post_type( 'video_tutorial', array(
            'description'           => __( 'Stores a video tutorial to be displayed in the admin', 'vtb' ), // string
            'public'                => false,
            'show_ui'               => true,
            'show_in_nav_menus'     => false,
            'exclude_from_search'   => true,
            'publicly_queryable'    => false,
            'menu_position'   		=> '10.2',
			'hierarchical' 			=> false,
            'capability_type' 		=> 'post',
//            'capabilities' => array(
//                'create_posts' => false, // Removes support for the "Add New" function ( use 'do_not_allow' instead of false for multisite set ups )
//            ),
            'map_meta_cap' => true, // Set to `false`, if users are not allowed to edit/delete existing posts
            'labels'          => array(
                'name'               => $tutorial_name,
                'singular_name'      => $tutorial_name_single,
                'menu_name'          => $tutorial_name,
                'add_new'            => __( 'Add New',                      'vtb' ),
                'add_new_item'       => sprintf( __('Add New %s', 'vtb' ),  $tutorial_name_single),
                'edit_items'         => sprintf( __('Edit %s', 'vtb' ),     $tutorial_name),
                'edit_item'          => sprintf( __('Edit %s', 'vtb' ),     $tutorial_name_single),
                'new_item'           => sprintf( __('New %s', 'vtb' ),      $tutorial_name_single),
                'view_item'          => sprintf( __('View %s', 'vtb' ),     $tutorial_name_single),
                'search_items'       => sprintf( __('Search %s', 'vtb' ), $tutorial_name),
                'not_found'          => sprintf( __('No %s found', 'vtb' ), $tutorial_name),
                'not_found_in_trash' => sprintf( __('No %s found in trash', 'vtb' ), $tutorial_name),
                'all_items'          => sprintf( __('All %s', 'vtb' ), $tutorial_name),
            )
        ));
    }


    /**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->vtb;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    VTB_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

    /**
     * Gets a value indicating if the current user can add new tutorials
     *
     * @return bool
     */
    public function userCanAddNew() {
        $user = wp_get_current_user();
       return user_can($user, 'manage_options');
    }
}
