<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    VTB
 * @subpackage VTB/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    VTB
 * @subpackage VTB/admin
 * @author     Your Name <email@example.com>
 */
class VTB_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $vtb    The ID of this plugin.
	 */
	private $vtb;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $vtb       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $vtb, $version ) {

		$this->vtb = $vtb;
		$this->version = $version;

		# Frontend Download
		if ( isset( $_GET['page'] ) && 'vtb-download' == $_GET['page'] ) {
			$this->download_page( );
		}
	}

	public function add_menus() {
		$post_type = get_post_type_object('video_tutorial');

		add_menu_page( $post_type->labels->menu_name, $post_type->labels->menu_name, 'read', 'vtb', array( &$this, 'render_tutorials' ), 'dashicons-video-alt3', $post_type->menu_position );
		add_submenu_page( 'vtb', $post_type->labels->edit_items, $post_type->labels->edit_items, 'manage_options', 'edit.php?post_type=video_tutorial' );
		add_submenu_page( 'vtb', __('Settings', 'vtb'), __('Settings', 'vtb'), 'manage_options', 'vtb-settings', array( &$this, 'render_settings' ) );

		add_submenu_page( 'vtb-hidden', '_download', '_download', 'manage_options', 'vtb-download', array( &$this, 'download_page' ) );
	}

	public function init_settings() {
		register_setting( 'vtb_settings', 'vtb_settings', array(&$this, 'sanitize_settings') );

		add_settings_section(
			'vtb_settings_section',
			'',
			'__return_null',
			'vtb_settings'
		);

		add_settings_field(
			'youtube_api_key',
			__('YouTube API Key', 'vtb' ),
			array(&$this, 'settings_field_render'),
			'vtb_settings',
			'vtb_settings_section',
			array('tab' => 'general', 'field' => 'youtube_api_key')
		);

		add_settings_field(
			'show_watch_notice',
			 __('Show Watch Notice', 'vtb' ),
			array(&$this, 'settings_field_render'),
			'vtb_settings',
			'vtb_settings_section',
			array('tab' => 'general', 'field' => 'show_watch_notice')
		);

		add_settings_field(
			'tutorial_name',
			__( 'Tutorial Name', 'vtb' ),
			array(&$this, 'settings_field_render'),
			'vtb_settings',
			'vtb_settings_section',
			array('tab' => 'general', 'field' => 'tutorial_name')
		);

		add_settings_field(
			'tutorial_name_single',
			__( 'Tutorial Name Single', 'vtb' ),
			array(&$this, 'settings_field_render'),
			'vtb_settings',
			'vtb_settings_section',
			array('tab' => 'general', 'field' => 'tutorial_name_single')
		);

		add_settings_field(
			'tutorial_menu_position',
			__( 'Menu Position', 'vtb' ),
			array(&$this, 'settings_field_render'),
			'vtb_settings',
			'vtb_settings_section',
			array('tab' => 'general', 'field' => 'tutorial_menu_position')
		);

		//**********  Import Tab ********************
		
		
	}

	public function settings_field_render($args) {
		$tab = $args['tab'];
		include plugin_dir_path(__FILE__) . "partials/vtb-admin-settings-{$tab}.php";
	}

	public function sanitize_settings( $settings ) {
		if (!isset($settings['show_watch_notice']))
			$settings['show_watch_notice'] = false;
		return $settings;
	}

	public function download_page( ) {

		if ( !current_user_can('manage_options') ) {
			wp_redirect( wp_get_referer( ) );
		}

		if ( headers_sent( ) ) {
			error_log( "Headers already sent.  Export failed." );
			die( 'Headers already sent' );
		}
		
		$date = date( 'Y-m-d-His' );
		$download_name = "vtb-export-{$date}.json";

		header( "Content-type: application/json" );
		header( "Content-Disposition: attachment; filename={$download_name}" );
		header( 'Cache-Control: no-store, no-cache' );

		global $wpdb;
		$export = array('version' => $this->version);

		//Export the VTB settings
		$export['vtb_settings'] = get_option('vtb_settings');

		//Export the user data
		$users = $wpdb->get_results("SELECT ID, user_login FROM $wpdb->users");
		//create an associative array that stores the user ID and login in a key/value array
		$user_map = array();
		foreach($users as $user) {
			$user_map[$user->ID] = $user->user_login;
		}
		
		$export['user_data'] = array(
			'users'    => $user_map,
			'usermeta' => $wpdb->get_results("SELECT user_id, meta_key, meta_value FROM $wpdb->usermeta WHERE meta_key LIKE 'vtb_%'")
		);

		//Get all of the video_tutorial posts
		$posts = get_posts(array(
			'post_type'     => 'video_tutorial',
			'numberposts'   => -1,
			'orderby'       => 'menu_order',
			'order'         => 'ASC'
		));

		//Get all of the video_tutorial postmeta...
		$post_ids = array();
		$export_posts = array();
		//use these fields to only export the relevant values, thereby reducing the exported file size
		$export_posts_fields = apply_filters('vtb_export_post_fields', array('ID', 'post_author', 'post_date', 'post_title', 'post_content', 'menu_order'));
		$arrPost = null;

		foreach ($posts as $p) {
			$post_ids[] = $p->ID;
			$arrPost = (array)$p; //cast to an array
			foreach ($export_posts_fields as $field) {
				$export_posts[$p->ID][$field] = $arrPost[$field];
			}
		}

		//store the exported posts
		$export['posts'] = $export_posts;

		$posts_in = implode(',', $post_ids);
		$export['postmeta'] = $wpdb->get_results("SELECT post_id, meta_key, meta_value FROM {$wpdb->postmeta} WHERE post_id IN ({$posts_in}) AND meta_key LIKE 'vtb_%'");

		//now convert the array into a json format
		$json = json_encode($export);
		echo $json;

		die( );
	}


	/**
	 * Removes the default edit menu for our video_tutorial post type
	 * @param $menu_order
	 *
	 * @return mixed
	 */
	function edit_menu_items( $menu_order ){
		global $menu;

		foreach ( $menu as $mkey => $m ) {
			$key = array_search( 'edit.php?post_type=video_tutorial', $m );

			if ( $key )
				unset( $menu[$mkey] );
		}

		return $menu_order;
	}

	/**
	 * Display a nag if the user has not watched any tutorials
	 */
	public function render_admin_notices() {
		$screen = get_current_screen();
		if ((isset($_GET['page']) && 0 === strpos($_GET['page'], 'vtb')) ||
		    'video_tutorial' == $screen->post_type || vtb_tutorial_watched_any())
			return;
		?>
		<div class="update-nag">
			<?php
				echo get_option('vtb_nag_message', sprintf(__('Please visit the %s menu to familiarize yourself with the Wordpress Admin.', 'vtb'),
					sprintf('<a href="%s">%s</a>', vtb_tutorial_url(null, false), __('Tutorials', 'vtb'))));
			?>
		</div>
		<?php
	}

	/**
	 * Video tutorials are sorted by mouse drag and drop, which is stored in the menu_order field.
	 * @param $wp_query
	 */
	public function set_post_order($wp_query) {
		if ('video_tutorial' == $wp_query->query_vars['post_type']) {
			$wp_query->set( 'orderby', 'menu_order' );
			$wp_query->set( 'order', 'ASC' );
		}
	}

	/**
	 * Specifies the columns in the video_tutorial edit screen
	 * @param $columns The default columns
	 *
	 * @return array Returns a set of columns specific for the video_tutorial post type
	 */
	public function edit_columns( $columns ) {
		$columns = array(
			'dragsort' => '',
			'cb' 		=> '<input type="checkbox" />',
			'thumbnail' => __( 'Video' ),
			'title' 	=> __( 'Title' ),
			'date'	 	=> __( 'Date' )
		);

		return $columns;
	}

	/**
	 * Clears the sortable columns because we only sort by mouse drag and drop
	 * @param $columns The default columns
	 *
	 * @return array Returns an empty array
	 */
	public function sortable_columns( $columns ) {
		return array();
	}

	/**
	 * Renders the columns in our video_tutorial edit screen
	 * @param $column Specifies the current column
	 * @param $post_id Specifies the current post_id
	 */
	public function render_column( $column, $post_id) {
		switch ( $column ) {
			case 'thumbnail' :
				$info = vtb_get_video_info($post_id);
				$img = vtb_get_video_thumbnail($post_id);
				$link = get_edit_post_link($post_id);

				//render quick link info and the thumbnail column
				echo "
            		<div style='display: none;' id='vtb_video_source_{$post_id}'>{$info['source']}</div>
					<div style='display: none;' id='vtb_video_id_{$post_id}'>{$info['id']}</div>
					<a href='{$link}'>
						$img
					</a>
				";
				break;
		}
	}

	/**
	 * Renders the video_tutorial column for the quick edit box
	 * @param $column_name
	 * @param $post_type
	 */
 	public function render_quick_edit_column($column_name, $post_type) {
		if ('video_tutorial' !== $post_type || $column_name != 'thumbnail') return;
		?>
		<fieldset class="inline-edit-col-left">
			<div class="inline-edit-col">
				<span class="title">Video Source</span>
				<select name="vtb_video_source">
					<option value="youtube" selected="selected"><?php _e('Youtube', 'vtb'); ?></option>
					<?php /*
                        //TODO Vimeo Support
                        <option value="vimeo" <?php selected($video_source, 'vimeo'); ?>><?php _e('Vimeo', 'vtb'); ?></option>
                        */?>
				</select>
			</div>
			<div class="inline-edit-col">
				<span class="title">Video ID</span>
				<input id="vtb_video_id" type="text" name="vtb_video_id" value=""/>
			</div>
		</fieldset>
		<?php
	}

	/**
	 * Renders the appropriate tutorial screen
	 */
	public function render_tutorials() {
		if ( isset($_REQUEST['tutorial']) ) {
			require_once( plugin_dir_path(__FILE__) . 'partials/vtb-admin-tutorial.php' );
		} else {
			require_once( plugin_dir_path(__FILE__) . 'partials/vtb-admin-list.php' );
		}
	}

	public function render_settings() {
		require_once( plugin_dir_path(__FILE__) . 'partials/vtb-admin-settings.php' );
	}
	
	
	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		//only enqueue style if the current admin screen is editing the video_tutorial post type
		$screen = get_current_screen();
		if ((isset($_GET['page']) && 0 === strpos($_GET['page'], 'vtb')) ||
		    'video_tutorial' == $screen->post_type) {
			wp_enqueue_style( $this->vtb, plugin_dir_url( __FILE__ ) . 'css/vtb-admin.min.css', array(), $this->version, 'all' );
			wp_enqueue_style('jquery-ui', 'http://code.jquery.com/ui/1.10.1/themes/base/jquery-ui.css');
		}
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		//only enqueue scripts if the current admin screen is editing the video_tutorial post type
		$screen = get_current_screen();
		if ((isset($_GET['page']) && 0 === strpos($_GET['page'], 'vtb')) ||
		    'video_tutorial' == $screen->post_type) {
			wp_enqueue_script('jquery-ui-core');
			wp_enqueue_script('jquery-ui-tabs');
			wp_enqueue_script('jquery-ui-sortable');
			wp_enqueue_script('jquery-address', plugin_dir_url( __FILE__ ) . 'js/jquery-address.min.js', array('jquery'), '1.5' );
			wp_enqueue_script( $this->vtb, plugin_dir_url( __FILE__ ) . 'js/vtb-admin.js', array( 'jquery', 'jquery-ui-core', 'jquery-ui-sortable', 'jquery-ui-tabs', 'jquery-address' ), $this->version, false );

			wp_localize_script($this->vtb, 'VTB', array(
				videoInfo => vtb_get_video_info($_GET['tutorial']),
				watchNonce  => wp_create_nonce('vtb_watched_video'),
			));
		}

	}

	/**
	 * This filter function is used when the WP function: get_{next/previous}_post() is called
	 * @param $where
	 * @param $in_same_term
	 * @param $excluded_terms
	 * @param $taxonomy
	 * @param $post
	 *
	 * @return string
	 */
	public function modify_adjacent_post_where( $where, $in_same_term, $excluded_terms, $taxonomy, $post ) {
		//if the post is our video tutorial, then we need to change the default WHERE parameters...
		$direction = strpos($where, '<') ? '<' : '>';
		return 'video_tutorial' != $post->post_type ? $where :
			"WHERE p.menu_order {$direction} {$post->menu_order} AND p.post_type = 'video_tutorial'  AND ( p.post_status = 'publish' OR p.post_status = 'private' )";
	}

	/**
	 * This filter function is used when the WP function: get_{next/previous}_post() is called
	 * @param $sort
	 * @param $post
	 *
	 * @return string
	 */
	public function modify_adjacent_post_sort( $sort, $post ) {
		//if the post is our video tutorial, then we need to change the default sorting...
		$direction = false != strpos($sort, 'DESC') ? 'DESC' : 'ASC';
		return 'video_tutorial' != $post->post_type ? $sort : "ORDER BY p.menu_order $direction LIMIT 1";
	}

	/**
	 * This ajax function is used to update drag drop sorting to the database
	 */
	public function ajax_update_sorting() {
		$posts = isset($_REQUEST['posts']) ? $_REQUEST['posts'] : array();
		foreach ( $posts as $post_id => $menu_order ) {
			wp_update_post( array( 'ID' => $post_id, 'menu_order' =>  $menu_order) );
		}

		wp_send_json_success();
	}

	/**
	 * Is called when the user clicks to watch a tutorial.
	 */
	public function ajax_watched_video() {
		check_ajax_referer('vtb_watched_video', 'watchNonce');

		$info = $_REQUEST['videoInfo'];
		if (is_array($info)) {
			$meta = get_user_meta( get_current_user_id(), 'vtb_watch_history', true );
			$meta = maybe_unserialize( $meta );
			if (!is_array($meta)) {
				$meta = array();
			}

			$id = $info['source'] . $info['id'];
			$times = isset($meta[$id]) ? $meta[$id] : 0;
			$meta[$id] = (++$times);

			update_user_meta(get_current_user_id(), 'vtb_watch_history', $meta);
			global $vtb_watch_history;
			$vtb_watch_history = null; //clear the cache
		}
	}
		
	
	/**
	 * Filters a tutorial and adds default data if necessary
	 *
	 * @param $data The WP post data
	 * @return mixed
     */
	public function modify_tutorial_post($data )
	{
		if('video_tutorial' != $data['post_type'])
			return $data;

		//if the menu order has not been set. Then we will set it to the highest menu_order plus 1
		if (0 === (int)$data['menu_order']) {
			global $wpdb;
			$data['menu_order'] = $wpdb->get_var("SELECT MAX(menu_order)+1 AS menu_order FROM {$wpdb->posts} WHERE post_type='video_tutorial'");
			if (empty($data['menu_order']))
				$data['menu_order'] = 0;
		}

		if (empty($data['post_title']) || empty($data['post_content'])) {
			$video_id = null;
			$video    = null;
			if ( ( empty( $data['post_title'] ) || empty( $data['post_content'] ) ) ) {
				$video_source = ( isset( $_REQUEST['vtb_video_source'] ) ? esc_textarea( $_REQUEST['vtb_video_source'] ) : '' );
				$video_id       = ( isset( $_REQUEST['vtb_video_id'] ) ? esc_textarea( $_REQUEST['vtb_video_id'] ) : '' );
				$video          = vtb_get_video( $video_source, $video_id );
				if ( $video ) {
					if ( empty( $data['post_title'] ) ) {
						$data['post_title'] = $video->snippet->title;

						//since the title was empty, we'll update the date of the tutorial to video's publish date
						$date                  = date_create( $video->snippet->publishedAt );
						$data['post_date']     = $date->format( 'Y-m-d H:i:s' );
						$data['post_date_gmt'] = $data['post_date'];
					}

					if ( empty( $data['post_content'] ) ) {
						$data['post_content'] = $video->snippet->description;
					}
				}
			}
		}
		return $data; // Returns the modified data.
	}

}
