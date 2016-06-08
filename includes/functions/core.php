<?php

function vtb_plugin_url( $append ) {
	return VTB_URL . $append;
}

function vtb_tutorial_url( $post, $echo = true ) {
	if (null != $post)
		$post = get_post($post);
	$url = admin_url( "admin.php?page=vtb" . ($post instanceof WP_Post ? "&tutorial={$post->ID}" : '' ));
	if ($echo)
		echo $url;
	return $url;
}

function vtb_get_video_location( $post ) {
	$post = get_post( $post );
	return $post ? get_post_meta( $post->ID, 'video_location', true ) : '';
}

function vtb_get_video_id( $data ) {
	$video_location = '';
	if ( isset( $data ) && is_string( $data ) ) {
		if ( strlen( $data ) == 11 || strpos( $data, 'v=' ) !== false ) {
			$video_location = $data;
		}
	}

	$post = null;
	/*
	 * Assume that the data passed in is to retrieve a post
	 */
	if ( empty( $video_location ) && $post = get_post( $data ) ) {
		$video_location = get_post_meta( $post->ID, 'video_location', true );
	}

	/*
	 * If we have some data to work with, we'll try to get it stripped down to the key only
	 */
	if ( ! empty( $video_location ) ) {
		if ( strpos( $video_location, 'v=' ) !== false ) { //If it's the full url
			parse_str( parse_url( $video_location, PHP_URL_QUERY ), $my_array_of_vars ); //Strip the video ID from the URL
			$video_location = $my_array_of_vars['v'];
		}
	}

	return ( strlen( $video_location ) == 11 ) ? $video_location : false;
}

function vtb_get_rss_data( $cache, $transientId, $rss_url) {
	//use cache
	if ( $cache == 1 ) {

		//if cache does not exist
		if ( false === ( $videos_result = get_transient( $transientId ) ) ) {
			//get rss
			$videos_result = wp_remote_get( $rss_url );

			$response_code = wp_remote_retrieve_response_code( $videos_result );
			if ( $response_code == 200 ) {

				set_transient( $transientId, $videos_result, $cache_time * HOUR_IN_SECONDS );
			}
		}

		//not to use cache
	} else {
		//get rss
		$videos_result = wp_remote_get( $rss_url );

		//delete cache
		if ( ! empty( $transientId ) ) {
			delete_transient( $transientId );
		}
	}

	return $videos_result;
}

/**
 * @param $video_id. Specifies the Youtube id of the video
 *
 * @return mixed|string|null Returns the video data on success, null on failure
 */
function vtb_get_video( $video_id ) {
	$key = vtb_youtube_api_key();
	if (is_wp_error($key))
		return $key;

	$result = vtb_get_rss_data( false, '', 'https://www.googleapis.com/youtube/v3/videos'
	                                       . '?part=snippet'
	                                       . '&id=' . $video_id
	                                       . '&key=' . $key
	);

	$json = json_decode( $result['body'] );
	if (isset($json->error)) {
		//return a nice clean Wordpress error. ;)
		return new WP_Error(
			$json->error->code,
			sprintf(__('Could not retrieve video: %s', 'vtb'), $json->error->errors[0]->reason),
			$json->error);
	}
	return $json->items[0];
}

function vtb_get_videos( $playlist_id ) {
	$key = vtb_youtube_api_key();
	if (is_wp_error($key))
		return $key;

	$result = vtb_get_rss_data( false, '', 'https://www.googleapis.com/youtube/v3/playlistItems'
	                                       . '?part=snippet'
	                                       . '&maxResults=50'
	                                       . '&playlistId=' . $playlist_id
	                                       . '&key=' . $key
	);

	$json = json_decode( $result['body'] );
	if (isset($json->error)) {
		//return a nice clean Wordpress error. ;)
		return new WP_Error(
			$json->error->code,
			sprintf( __( 'Could not retrieve playlist: %s', 'vtb' ),
			$json->error->errors[0]->reason ), $json->error );
	}
	//else
	return $json->items;
}

function vtb_import($import_type, $data, $overwrite) {
	if (empty($data)) return false;
	if (!in_array($import_type, array('vtb-json', 'youtube-playlist')))
		return new WP_Error(400, __('You must specify a valid data type to import', 'vtb'));

	global $wpdb;
	$menu_order = 0;
	if ($overwrite) {
		if ($IDs = $wpdb->get_col("SELECT ID FROM $wpdb->posts WHERE post_type = 'video_tutorial'")) {
			$IDs = apply_filters('vtb_delete_posts', $IDs);
			$sql_in = implode(',', $IDs);
			$wpdb->query("DELETE FROM $wpdb->posts WHERE ID IN ($sql_in)");
			$wpdb->query("DELETE FROM $wpdb->postmeta WHERE post_id IN ($sql_in)");
		}
	} else {
		$menu_order = $wpdb->get_var("SELECT menu_order FROM $wpdb->posts WHERE post_type = 'video_tutorial' ORDER BY menu_order DESC LIMIT 1");
	}

	//import

	if ('vtb' == $import_type) {
	    return	_vtb_import_file($data, $menu_order);
	} else if ('youtube-playlist' == $import_type) {
		return _vtb_import_youtube_playlist($data, $menu_order);
	} else {
		return new WP_Error(400, __('You must specify a valid data type to import', 'vtb'));
	}
}

function _vtb_import_file($data, $menu_order) {
	$id_map = array();
	foreach ($data['posts'] as $post) {
		$ID = $post->ID;
		unset($post->ID);
		$post->menu_order = $menu_order + (int)$post->menu_order;
		
		$insert_id = wp_insert_post($post);
		$id_map[$ID] = $insert_id;
	}

	//import the user data
	$user_map = $data->user_data['users'];
	foreach ($data->user_data['usermeta'] as $meta) {
		if ($user = get_user_by('login', $user_map[$meta['user_id']]))
			add_user_meta($user->ID, 'video_tutorials', $id_map[$meta['post_id']], $meta['meta_key'], $meta['meta_value'], true);
	}

	//import the postmeta data...
	foreach ($data['postmeta'] as $meta) {
		add_post_meta($id_map[$meta['post_id']], $meta['meta_key'], $meta['meta_value']);
	}

	return true;
}

function _vtb_import_youtube_playlist($playlist, $menu_order) {
	foreach ($playlist as $item) {
		$id = wp_insert_post(array(
			'post_type' => 'video_tutorial',
			'post_status' => 'publish',
			'post_title' => $item->snippet->title,
			'post_content' => $item->snippet->description,
			'post_date'    => $item->snippet->publishedAt,
			'menu_order'   => $menu_order + (int)$item->snippet->position
		));
		add_post_meta($id, 'video_location', $item->snippet->resourceId->videoId);
	}
	return true;
}

function vtb_youtube_api_key() {
	global $vtb_youtube_api_key;

	if (!isset($vtb_youtube_api_key)) {
		$vtb_settings =	get_option('vtb_settings');
		$vtb_youtube_api_key = is_array($vtb_settings) ? $vtb_settings['youtube_api_key'] : '';
	}

	return empty($vtb_youtube_api_key) ? new WP_Error(400, "You must specify a Youtube API key") : $vtb_youtube_api_key;
}

function vtb_tutorial_watched_any() {
	return false;
	
	$meta = get_user_meta( get_current_user_id(), 'vtb_watch_history', true );
	$meta = maybe_unserialize( $meta );
	return is_array($meta) && count($meta);
}

function vtb_tutorial_watched( $post ) {
	global $vtb_user_watched;

	if ( ! isset( $vtb_user_watched ) ) {
		$meta = get_user_meta( get_current_user_id(), 'vtb_watch_history', true );
		$meta = maybe_unserialize( $meta );
		$vtb_user_watched = (is_array($meta) ? $meta : array());
	}

	$video_id = vtb_get_video_id($post);
	return isset($vtb_user_watched[$video_id]) ? $vtb_user_watched[$video_id] : 0;
}
