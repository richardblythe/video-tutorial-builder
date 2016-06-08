<?php
$options = get_option( 'vtb_settings' );
$post_type = get_post_type_object('video_tutorial');

switch ($args['field']) {
	case 'youtube_api_key':
		?>
		<input id="vtb-youtube-api-key" type='text' name='vtb_settings[youtube_api_key]' value='<?php echo $options['youtube_api_key']; ?>'>
		<label for="vtb-youtube-api-key"><?php printf(__('Is required to interact the %s', 'vtb'),
				sprintf('<a href="https://www.google.com/search?q=how+to+get+a+youtube+api+key" target="_blank">%s</a>', __('YouTube API', 'vtb'))); ?></label>
		<?php
		break;
	case 'show_watch_nag':
		if (!isset($options['show_watch_nag']))
			$options['show_watch_nag'] = true;
		?>
		<input id="vtb-show-watch-nag" type='checkbox' name='vtb_settings[show_watch_nag]' <?php checked( $options['show_watch_nag'], 1 ); ?> value='1'>
		<label for="vtb-show-watch-nag"><?php printf(__('Shows an admin notice if a user has not watched any %s', 'vtb'), $post_type->labels->name); ?></label>
		<?php
		break;
	case 'tutorial_name':
		if (empty($options['tutorial_name']))
			$options['tutorial_name'] = __('Tutorials', 'vtb');
		?>
		<input id="vtb-tutorial-name" type='text' name='vtb_settings[tutorial_name]' value='<?php echo $options['tutorial_name']; ?>'>
		<label for="vtb-tutorial-name"><?php _e('Specifies the display name', 'vtb'); ?></label>
		<?php
		break;
	case 'tutorial_name_single':
		if (empty($options['tutorial_name_single']))
			$options['tutorial_name_single'] = __('Tutorial', 'vtb');
		?>
		<input id="vtb-tutorial-name-single" type='text' name='vtb_settings[tutorial_name_single]' value='<?php echo $options['tutorial_name_single']; ?>'>
		<label for="vtb-tutorial-name-single"><?php _e('Specifies the singular name', 'vtb'); ?></label>
		<?php
		break;
}