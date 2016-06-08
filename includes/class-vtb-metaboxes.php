<?php
/**
 * Video Tutorial Builder
 *
 * @package   Vuideo_Tutorial_Builder
 * @license   GPL-2.0+
 */
/**
 * Register metaboxes.
 *
 * @package Video_Tutorial_Builder
 */
class VTB_Metaboxes {
    /**
     * Register the metaboxes to be used for the video tutorials
     *
     * @since 0.1.0
     */
    public function vtb_meta_boxes() {
        add_meta_box(
            'video_location',
            'Video Location',
            array( $this, 'render_meta_boxes' ),
            'video_tutorial',
            'normal',
            'high'
        );
    }
    /**
     * The HTML for the fields
     *
     * @since 0.1.0
     */
    function render_meta_boxes( $post ) {
        $meta = get_post_custom( $post->ID );
        $video_location = ! isset( $meta['video_location'][0] ) ? '' : $meta['video_location'][0];
        ?>
        <table class="form-table">

            <tr>
                <td class="vtb_meta_box_td" colspan="2">
                    <label for="video_location"><?php _e( 'Location', 'vtb' ); ?>
                    </label>
                </td>
                <td colspan="4">
                    <input type="text" name="video_location" class="regular-text" value="<?php echo $video_location; ?>">
                    <p class="description"><?php _e( 'The url or id of the video', 'vtb' ); ?></p>
                </td>
            </tr>
        </table>

    <?php }
    /**
     * Save metaboxes
     *
     * @since 0.1.0
     */
    function save_meta_boxes( $post_id ) {
        global $post;

        // Check Autosave
        if ($_POST['action'] != 'inline-save' && ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || ( defined('DOING_AJAX') && DOING_AJAX) || isset($_REQUEST['bulk_edit']) ) ) {
            return $post_id;
        }

        // Check permissions
        if ( !current_user_can( 'edit_post', $post->ID ) ) {
            return $post_id;
        }

        //Update the video location field
        $video_location = ( isset( $_POST['video_location'] ) ? esc_textarea( $_POST['video_location'] ) : '');
        update_post_meta( $post->ID, 'video_location', $video_location );
    }
}