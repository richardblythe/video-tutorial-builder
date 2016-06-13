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
            'vtb_video_source',
            'Video Info',
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
        $vtb_video_source = ! isset( $meta['vtb_video_source'][0] ) ? '' : $meta['vtb_video_source'][0];
        $vtb_video_id = ! isset( $meta['vtb_video_id'][0] ) ? '' : $meta['vtb_video_id'][0];
        ?>
        <table class="form-table">
            <tr>
                <td class="vtb_meta_box_td" colspan="2">
                    <label for="vtb_video_source"><?php _e( 'Source', 'vtb' ); ?>
                    </label>
                </td>
                <td colspan="4">
                    <select name="vtb_video_source">
                        <option value="youtube" selected="selected"><?php _e('Youtube', 'vtb'); ?></option>
                        <?php /*
                        //TODO Vimeo Support
                        <option value="vimeo" <?php selected($video_source, 'vimeo'); ?>><?php _e('Vimeo', 'vtb'); ?></option>
                        */?>
                    </select>
                </td>
            </tr>
            <tr>
                <td class="vtb_meta_box_td" colspan="2">
                    <label for="vtb_video_id"><?php _e( 'ID', 'vtb' ); ?>
                    </label>
                </td>
                <td colspan="4">
                    <input type="text" name="vtb_video_id" class="regular-text" value="<?php echo $vtb_video_id; ?>">
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

        //Update the video fields
        $vtb_video_source = (isset( $_POST['vtb_video_source'] ) ? esc_textarea( $_POST['vtb_video_source'] ) : '');
        $vtb_video_id = (isset( $_POST['vtb_video_id'] ) ? esc_textarea( $_POST['vtb_video_id'] ) : '');
        update_post_meta( $post->ID, 'vtb_video_source', $vtb_video_source );
        update_post_meta( $post->ID, 'vtb_video_id', $vtb_video_id );
    }
}