<?php
$import_btn_clicked = isset($_POST['vtb-import-submit']);
$import_error = null;
$import_type = isset($_POST['import-type']) ? $_POST['import-type'] : false;

//if the import button was clicked...
if ($import_btn_clicked) {
	check_admin_referer('vtb-import', 'vtb_import_nonce');
	if (!in_array($_POST['import-type'], array('vtb-json', 'youtube-playlist')))
		$import_error = __('You must specify a valid data type to import', 'vtb');

	if (!$import_error) {
		global $wpdb;
		$menu_order = 0;

		if (isset($_POST['vtb-overwrite-posts'])) {
			if ($IDs = $wpdb->get_col("SELECT ID FROM $wpdb->posts WHERE post_type = 'video_tutorial'")) {
				$IDs = apply_filters('vtb_delete_posts', $IDs);
				$sql_in = implode(',', $IDs);
				$wpdb->query("DELETE FROM $wpdb->posts WHERE ID IN ($sql_in)");
				$wpdb->query("DELETE FROM $wpdb->postmeta WHERE post_id IN ($sql_in)");
			}
		} else {
			$menu_order = (int)$wpdb->get_var("SELECT menu_order FROM $wpdb->posts WHERE post_type = 'video_tutorial' ORDER BY menu_order DESC LIMIT 1");
		}

		switch($import_type) {
			case 'vtb-json':
				$import_settings = isset($_POST['import_settings']) ? $_POST['import_settings'] : array();
				unset($import_settings['overwrite_all']);
				if (isset($import_settings['name'])) {
					$import_settings['name_single'] = true;
				}
				$data = _vtb_import_file($_FILES['vtb-json'], array_keys( $import_settings), $menu_order );
				if (is_wp_error($data))  { $import_error = $data->get_error_message(); }
				break;
			case 'youtube-playlist':
				$data = vtb_get_videos('youtube', sanitize_text_field(isset($_POST['youtube-playlist-id']) ? $_POST['youtube-playlist-id'] : ''));
				if (!is_wp_error($data)) { $data = _vtb_import_youtube_playlist( $data, $menu_order );  }
				if (is_wp_error($data))  { $import_error = $data->get_error_message(); }
				break;
		}

	}
}

?>

<div class="wrap">
	<?php
	if ($import_error)
		echo "<div class='notice notice-error'>$import_error</div>"
	?>
	<h2><?php _e('Settings', 'vtb'); ?></h2>
	<div id="vtb-settings-tabs">
		<ul class="ui-tabs-nav">
			<li><a href="#tab-general"><?php _e('General', 'vtb'); ?></a></li>
			<li><a href="#tab-import"><?php _e('Import', 'vtb'); ?></a></li>
			<li><a href="#tab-export"><?php _e('Export', 'vtb'); ?></a></li>
		</ul>
		<?php
		//*********  General Tab  ***************
		?>
		<div id="tab-general">
			<form action='options.php' method='post'>
				<?php
				settings_fields( 'vtb_settings' );
				do_settings_sections( 'vtb_settings' );
				submit_button();
				?>
			</form>
		</div>
		<?php
		//*********  Import Tab  ***************
		?>
		<div id="tab-import" style="display: none;">
			<form enctype="multipart/form-data" method="post">
				<?php wp_nonce_field('vtb-import', 'vtb_import_nonce'); ?>
				<table class="form-table">
					<tbody>
					<tr>
						<th scope="row"><?php _e('Import From:', 'vtb'); ?></th>
						<td>
							<input type="radio" id="import-youtube-playlist" name="import-type" value="youtube-playlist" <?php checked(!$import_type || 'youtube-playlist' == $import_type); ?>><label for="import-youtube-playlist"><?php _e('YouTube Playlist', 'vtb'); ?></label><br>
							<input id="import-vtb-json" type="radio" name="import-type" value="vtb-json" <?php checked('vtb-json' == $import_type); ?>><label for="import-vtb-json"><?php _e('Tutorials Export File', 'vtb'); ?></label>
						</td>
					</tr>

					<tr id="youtube-playlist" class="vtb-selection" <?php if($import_type && 'youtube-playlist' != $import_type) echo 'style="display: none;"'; ?>>
						<th scope="row"><?php _e('Playlist ID', 'vtb'); ?></th>
						<td>
							<input type='text' id="youtube-playlist-id" name='youtube-playlist-id' value='' />
							<label for="youtube-playlist-id"><?php _e('Specifies Youtube playlist to import', 'vtb'); ?></label>
						</td>
					</tr>

					<tr id="vtb-json" class="vtb-selection" <?php if('vtb-json' != $import_type) echo 'style="display: none;"'; ?>>
						<th scope="row"><?php _e('Tutorials Export File', 'vtb'); ?></th>
						<td>
							<input type="file" id="vtb-json" name="vtb-json" accept=".json" value="<? _e('Browse', 'vtb') ?>">
							<label for="vtb-json"><?php _e('Select a previously exported file ', 'vtb'); ?></label>
							<div id="vtb-json-import-options" style="padding: 10px;background-color: rgba(165, 165, 165, 0.3);">
								<?php
								$import_options = array(
									'overwrite_all' => array(__('Overwrite all general settings', 'vtb'), true),
									'youtube_api_key' => array(__('Youtube API key', 'vtb'), true),
									'show_watch_notice' => array(__('Admin watch notice', 'vtb'), true),
									'name' => array(__('Tutorial name', 'vtb'), true),
									'menu_position' => array(__('Menu position', 'vtb'), true),
								);
								foreach ($import_options as $key => $value) {
									if ($import_btn_clicked)
										$import_options[$key][1] = isset($_POST['import_settings'][$key]);
									?>
									<input type="checkbox" name="import_settings[<?php echo $key; ?>]" <?php checked($import_options[$key][1]); ?>>
									<label for="<?php echo $key; ?>"><?php echo $import_options[$key][0]; ?></label>
									<br>
									<?php
								}
								?>
							</div>
						</td>
					</tr>

					<tr>
						<th scope="row"><?php _e('Remove Existing Tutorials', 'vtb'); ?></th>
						<td>
							<input type="checkbox" id="vtb-overwrite-posts" name="vtb-overwrite-posts" <?php checked($import_btn_clicked ? isset($_POST['vtb-overwrite-posts']) : false); ?>>
							<label for="vtb-overwrite-posts"><?php _e('If checked, the existing tutorials in this website will be removed.', 'vtb'); ?></label>
						</td>
					</tr>

					</tbody>
				</table>
				<input type="submit" id="vtb-import-submit" name="vtb-import-submit" class="button-primary" value="<?php _e('Import', 'vtb') ?>">
			</form>
		</div>
		<?php
		//*********  Export Tab  ***************
		?>
		<div id="tab-export" style="display: none;">
			<a id="vtb-export-btn" class="button-primary" href="<?php echo admin_url('admin.php?page=vtb-download'); ?>"><?php _e('Export', 'vtb'); ?></a>
			<label for="vtb-export-btn"><?php _e('Exports all settings and videos into a downloadable file.', 'vtb'); ?></label>
		</div>
	</div>
</div>