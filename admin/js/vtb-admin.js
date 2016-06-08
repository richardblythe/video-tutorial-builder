var VTB = VTB || {};

VTB.setWatching = function () {
	if ( !VTB.ajaxSetWatching ) {
		VTB.ajaxSetWatching = true;
		jQuery.post(ajaxurl, {
			action:'vtb_watched_video',
			watchNonce: VTB.watchNonce,
			videoID: VTB.videoID
		});
	} 
}

jQuery(document).ready( function($) {
	/**
	 *	Listen for the user to interact with the iframe
	 */
	var overiFrame = -1;
	$('iframe').hover( function() {
		overiFrame = $(this).closest('.vtb-container-content').attr('id');
	}, function() {
		overiFrame = -1
	});
	//
	$(window).blur( function() {
		if( overiFrame != -1 )
			VTB.setWatching();
	});

	/**
	 * Tutorials Settings Screen
	 */
	if ($('body').hasClass('tutorials_page_vtb-settings')) {
		var $tabs, tabEvent = false;
		// Initializes plugin features
		$.address.strict(false).wrap(true);

		// Address handler
		$.address.init(function(event) {
			// Tabs setup
			$tabs = $('#vtb-settings-tabs').tabs()

			// Enables the plugin for all the tabs
			$('#vtb-settings-tabs .ui-tabs-nav a').click(function(event) {
				tabEvent = true;
				$.address.value($(event.target).attr('href').replace(/^#/, ''));
				tabEvent = false;
				return false;
			});
		}).change(function(event) {
			var current = $('a[href=#' + event.value + ']:first');
			// Selects the proper tab
			if (!tabEvent) {
				$tabs.tabs('select', current.attr('href'));
			}

		});

		var $rows = $('#tab-import table tr.vtb-selection');
		$('#tab-import table input[name="import-type"]').change(function(){
			$rows.hide();
			$rows.filter('#' + $('#tab-import table input[name="import-type"]:checked').val()).show();
		});
	} //END Tutorials Settings Screen


	/**
	 * Tutorials Edit Screen
	 */
	if ($('body').hasClass('post-type-video_tutorial')) {
		$("tbody#the-list").sortable({
			cursor: "move",
			axis: "y",
			start: function(e, ui){
				ui.placeholder.height(ui.item.height());
			},
			update: function(event, ui) {
				var serialized = {};

				$(this).children().each( function(index, el) {
					serialized[el.id.split("-")[1]] = index;
				});

				$.post(ajaxurl, {
					action:'vtb_drag_sort_posts',
					posts: serialized
				});
			}
		});


		/**
		 * 	Tutorials Quick Edit
		 */
		if (typeof inlineEditPost != 'undefined') {
			// we create a copy of the WP inline edit post function
			var $wp_inline_edit = inlineEditPost.edit;
			// and then we overwrite the function with our own code
			inlineEditPost.edit = function( id ) {

				// "call" the original WP edit function
				// we don't want to leave WordPress hanging
				$wp_inline_edit.apply( this, arguments );

				// now we take care of our business

				// get the post ID
				var $post_id = 0;
				if ( typeof( id ) == 'object' )
					$post_id = parseInt( this.getId( id ) );

				if ( $post_id > 0 ) {

					// define the edit row
					var $edit_row = $( '#edit-' + $post_id );

					// get the release date
					var $video_location = $( '#video_location-' + $post_id ).text();

					// populate the release date
					$edit_row.find( 'input[name="video_location"]' ).val( $video_location );

				}

			};
		}
	} //END Tutorials Edit Screen

});