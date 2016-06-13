var VTB = VTB || {};

VTB.setWatching = function () {
	if ( !VTB.ajaxSetWatching ) {
		VTB.ajaxSetWatching = true;
		jQuery.post(ajaxurl, {
			action:'vtb_watched_video',
			watchNonce: VTB.watchNonce,
			videoInfo: VTB.videoInfo
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
		var $tabs, tabEvent = false, activeTabIndex = 0;
		// Initializes plugin features
		$.address.strict(false).wrap(true);

		// Address handler
		$.address.init(function(event) {
			// Tabs setup
			$tabs = $('#vtb-settings-tabs').tabs();

			//Enables the plugin for all the tabs
			$tabs.on('click', '.ui-tabs-nav a', function(event) {
				event.preventDefault();
				tabEvent = true;
				if ($.address.value() == '') {
					var hash = $tabs.find('ul.ui-tabs-nav > li').eq(activeTabIndex).children().attr('href').replace(/^#/, '');
					$.address.history(false).value(hash).history(true);
				}
				var pos = $(window).scrollTop();
				$.address.value($(event.target).attr('href').replace(/^#/, ''));
				$(window).scrollTop(pos);
				//update our active tab index var
				activeTabIndex = $tabs.tabs("option", "active");
				tabEvent = false;
				return false;
			});

		}).change(function(event) {
			// Selects the proper tab
			if (!tabEvent) {
				activeTabIndex = Math.max(0, $tabs.find('a[href=#' + event.value + ']:first').parent().index());
				$tabs.tabs("option", "active", activeTabIndex);
			}
		});

		var $rows = $('#tab-import table tr.vtb-selection');
		$('#tab-import table input[name="import-type"]').change(function(){
			$rows.hide();
			$rows.filter('#' + $('#tab-import table input[name="import-type"]:checked').val()).show();
		});

		//Get the entire list of import options
		var $vtb_options = $('#vtb-json-import-options input[type="checkbox"]');
		$vtb_options.click(function(e) {
			var $this =  $(this);
			var checked = $this.is(":checked");
			if ($this.is(":first-child")) {
				$vtb_options.prop('checked', checked);
			} else {
				if (checked && $vtb_options.length - 1 == $vtb_options.not(':first').filter(':checked').length) {
					$vtb_options.first().prop('checked', true);
				} else if (!checked) {
					$vtb_options.first().prop('checked', false);
				}
			}
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

					// get the video info
					var $video_source = $( '#vtb_video_source_' + $post_id ).text();
					var $video_id = $( '#vtb_video_id_' + $post_id ).text();

					// populate the video info
					$edit_row.find( 'select[name="vtb_video_source"]' ).val( $video_source );
					$edit_row.find( 'input[name="vtb_video_id"]' ).val( $video_id );
				}

			};
		}
	} //END Tutorials Edit Screen

});