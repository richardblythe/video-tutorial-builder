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

function vtb_get_video_thumbnail($post) {
	$info = vtb_get_video_info($post);
	$info_set = is_array($info);
	if ($info_set && 'youtube' == $info['source']) {
		$url = "http://img.youtube.com/vi/{$info['id']}/mqdefault.jpg";
	} else {
		$url = vtb_plugin_url('admin/images/no-video-medium.jpg');
	}

	return $url;
}


function vtb_get_video_info( $post ) {
	if ($post = get_post( $post ) ) {
		return array(
			'source' => get_post_meta( $post->ID, 'vtb_video_source', true ),
			'id'     => get_post_meta( $post->ID, 'vtb_video_id', true )
		);
	}
	return null;
}

function vtb_sanitize_id($video_source, $raw_video_id) {

	if ('youtube' == $video_source) {
		if ( ! empty( $raw_video_id ) ) {
			if ( strpos( $raw_video_id, 'v=' ) !== false ) { //If it's the full url
				parse_str( parse_url( $raw_video_id, PHP_URL_QUERY ), $my_array_of_vars ); //Strip the video ID from the URL
				$raw_video_id = $my_array_of_vars['v'];
			}
		}

		return ( strlen( $raw_video_id ) == 11 ) ? $raw_video_id : false;
	} else {
		return false;
	}
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

				set_transient( $transientId, $videos_result );
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
function vtb_get_video( $info, $id ) {
	$key = vtb_youtube_api_key();
	if (is_wp_error($key))
		return $key;

	if (isset($info) && isset($id))
		$info = array('source' => $info, 'id' => $id);

	if (!(isset($info) && isset($info['source']) && isset($info['id']))) {
		return new WP_Error(400, 'Video info is invalid', $info);
	}
	
	if ('youtube' == $info['source']) {
		$result = vtb_get_rss_data( false, '', 'https://www.googleapis.com/youtube/v3/videos'
		                                       . '?part=snippet'
		                                       . '&id=' . $info['id']
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

	//else
	return new WP_Error('501', 'The video source is not supported', $info);
}

function vtb_get_videos( $info, $id ) {
	$key = vtb_youtube_api_key();
	if (is_wp_error($key))
		return $key;

	if (isset($info) && isset($id))
		$info = array('source' => $info, 'id' => $id);

	if (!(isset($info) && isset($info['source']) && isset($info['id']))) {
		return new WP_Error(400, 'Video info is invalid', $info);
	}

	if ('youtube' == $info['source']) {

		$result = vtb_get_rss_data( false, '', 'https://www.googleapis.com/youtube/v3/playlistItems'
		                                       . '?part=snippet'
		                                       . '&maxResults=50'
		                                       . '&playlistId=' . $info['id']
		                                       . '&key=' . $key
		);

		$json = json_decode( $result['body'] );
		if ( isset( $json->error ) ) {
			//return a nice clean Wordpress error. ;)
			return new WP_Error(
				$json->error->code,
				sprintf( __( 'Could not retrieve playlist: %s', 'vtb' ),
					$json->error->errors[0]->reason ), $json->error );
		}

		//else
		return $json->items;
	}

	//else
	return new WP_Error('501', 'The video source is not supported', $info);
}

function _vtb_import_file($file, $import_options, $menu_order) {
	if ($file['size'] == 0) {
		return new WP_Error(204, __('No file was uploaded', 'vtb'));
	}

	try {
		$raw = file_get_contents($file['tmp_name']);
		$data = json_decode($raw, true);
	} catch (Exception $e) {
		return new WP_Error($e->getCode(), __('Error in parsing import file.', 'vtb', $e));
	}


	//import general settings
	if (isset($import_options) && count($import_options) && isset($data['vtb_settings'])) {
		$new_settings = array();

		foreach ($import_options as $opt) {
			 $new_settings[$opt] = $data['vtb_settings'][$opt];
		}

		$prev_settings = get_option('vtb_settings');
		if (!is_array($prev_settings)) {
			$prev_settings = array();
		}
		update_option('vtb_settings', array_merge($prev_settings, $new_settings));
	}


	$id_map = array();
	foreach ($data['posts'] as $post_id => $post) {
		unset($post['ID']);//make sure that the post id is not set.  we are adding here, not updating
		$post['post_type']   = 'video_tutorial';
		$post['post_status'] = 'publish';
		$post['menu_order']  = $menu_order + (int)$post['menu_order'];

		$insert_id = wp_insert_post($post);
		$id_map[$post_id] = $insert_id;
	}

	//import the user data
	$user_map = $data['user_data']['users'];
	foreach ($data['user_data']['usermeta'] as $meta) {
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
		add_post_meta($id, 'vtb_video_source', 'youtube');
		add_post_meta($id, 'vtb_video_id', $item->snippet->resourceId->videoId);
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

	$info = vtb_get_video_info($post);
	$video_id = $info['source'] . $info['id'];
	return isset($vtb_user_watched[$video_id]) ? $vtb_user_watched[$video_id] : 0;
}
