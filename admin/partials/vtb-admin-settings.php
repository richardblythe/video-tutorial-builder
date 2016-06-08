<?php
//If the user has clicked the import button
$import_error = null;
$import_type = isset($_POST['import-type']) ? $_POST['import-type'] : false;

//if the import button was clicked...
if (isset($_REQUEST['vtb-import-submit'])) {
	check_admin_referer('vtb-import', 'vtb_import_nonce');	
	$overwrite = filter_var($_POST['vtb-overwrite'], FILTER_VALIDATE_BOOLEAN);
	
	switch($_POST['import-type']) {
		case 'vtb-json':
			
			break;
		case 'youtube-playlist':
			$data = vtb_get_videos(sanitize_text_field(isset($_POST['youtube-playlist-id']) ? $_POST['youtube-playlist-id'] : ''));
		    if (!is_wp_error($data)) { $data = vtb_import('youtube-playlist', $data, $overwrite );  }
			if (is_wp_error($data))  { $import_error = $data->get_error_message(); }
			break;
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
			<form method="post">
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
							<input type="file" id="vtb-json" name="vtb-json" value="<? _e('Browse', 'vtb') ?>">
							<label for="vtb-json"><?php _e('Select a previously exported file ', 'vtb'); ?></label>
						</td>
					</tr>

					<tr>
						<th scope="row"><?php _e('Remove Existing Tutorials', 'vtb'); ?></th>
						<td>
							<input type="checkbox" id="vtb-overwrite" name="vtb-overwrite">
							<label for="vtb-overwrite"><?php _e('If checked, the existing tutorials in this website will be removed.', 'vtb'); ?></label>
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
			<input id="vtb-export-general" type='checkbox' checked="checked" value='1'>
			<label for="vtb-export-general"><?php _e('Exports the settings in the general tab.', 'vtb'); ?></label>

			<p>
				<a class="button-primary" href="<?php echo admin_url('admin.php?page=vtb-download'); ?>">
					<?php _e('Export', 'vtb'); ?>
				</a>
			</p>

		</div>
	</div>
</div>