<?php

require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');

class VTB_List_Table extends WP_List_Table
{
    public function prepare_items() {
        global $tutorial_search, $role, $wpdb, $mode;

        $tutorial_search = isset( $_REQUEST['s'] ) ? wp_unslash( trim( $_REQUEST['s'] ) ) : '';

        $tutorials_per_page = $this->get_items_per_page( 'tutorials_per_page' );

        $paged = $this->get_pagenum();

        $hidden = array();
        $sortable = array();
        $this->_column_headers = array($this->get_columns(), $hidden, $sortable);

        $args = array(
            'post_type' => 'video_tutorial',
            'posts_per_page' => $tutorials_per_page,
            'offset' => ( $paged-1 ) * $tutorials_per_page,
            'search' => $tutorial_search
        );

        if ( '' !== $args['search'] ) {
            $args['search'] = trim( $args['search'], '*' );
            $args['search'] = '*' . $args['search'] . '*';
        }

        if ( isset( $_REQUEST['orderby'] ) )
            $args['orderby'] = $_REQUEST['orderby'];

        if ( isset( $_REQUEST['order'] ) )
            $args['order'] = $_REQUEST['order'];


        $args = apply_filters( 'vtb_list_table_query_args', $args );

        // Query the tutorials this page
        $wp_tutorial_search = new WP_Query( $args );

        $this->items = $wp_tutorial_search->get_posts();

        $this->set_pagination_args( array(
            'total_items' => $wp_tutorial_search->found_posts,
            'per_page' => $tutorials_per_page,
        ) );
    }

    /**
     *
     * @return array
     */
    public function get_columns() {
        $tutorial_columns = array(
            'thumbnail'     => __('Video'),
            'title'         => __( 'Title' ),
            'description'   => __( 'Description' ),
            'watched'       => __( 'Watched' ),
            'date'          => __( 'Date' )
        );
        /**
         * Filter the columns displayed in the Network Admin Users list table.
         *
         * @since MU
         *
         * @param array $users_columns An array of user columns. Default 'cb', 'username',
         *                             'name', 'email', 'registered', 'blogs'.
         */
        return apply_filters( 'vtb_columns', $tutorial_columns );
    }

    /**
     *
     * @return array
     */
    protected function get_sortable_columns() {
        return array(
            'title'      => 'post_title',
            'date'       => 'date'
        );
    }

    function column_thumbnail($post) {
        $url = vtb_get_video_thumbnail($post);

        $link = vtb_tutorial_url($post, false);
        echo "
            <a href='{$link}'>
                $url
            </a>
        ";
    }
    
    function column_title($post){
        $link = vtb_tutorial_url( $post, false );
        echo "
        <strong>
            <a class='row-title' href='{$link}'>{$post->post_title}</a>
        </strong>";
    }

    function column_description($post)
    {
        //Return the description contents
        echo $post->post_content;
    }

    public function column_watched( $post ) {
        $times = vtb_tutorial_watched( $post->ID );
        echo "<span class='watched-{$times}'>{$times}</span>";
    }
    
    public function column_date( $post ) {
        global $mode;

        if ( '0000-00-00 00:00:00' === $post->post_date ) {
            $t_time = $h_time = __( 'Unpublished' );
            $time_diff = 0;
        } else {
            $t_time = get_the_time( __( 'Y/m/d g:i:s a' ) );
            $m_time = $post->post_date;
            $time = get_post_time( 'G', true, $post );

            $time_diff = time() - $time;

            if ( $time_diff > 0 && $time_diff < DAY_IN_SECONDS ) {
                $h_time = sprintf( __( '%s ago' ), human_time_diff( $time ) );
            } else {
                $h_time = mysql2date( __( 'Y/m/d' ), $m_time );
            }
        }

        if ( 'publish' === $post->post_status ) {
            _e( 'Published' );
        } elseif ( 'future' === $post->post_status ) {
            if ( $time_diff > 0 ) {
                echo '<strong class="error-message">' . __( 'Missed schedule' ) . '</strong>';
            } else {
                _e( 'Scheduled' );
            }
        } else {
            _e( 'Last Modified' );
        }
        echo '<br />';
        if ( 'excerpt' === $mode ) {
            /**
             * Filter the published time of the post.
             *
             * If `$mode` equals 'excerpt', the published time and date are both displayed.
             * If `$mode` equals 'list' (default), the publish date is displayed, with the
             * time and date together available as an abbreviation definition.
             *
             * @since 2.5.1
             *
             * @param string  $t_time      The published time.
             * @param WP_Post $post        Post object.
             * @param string  $column_name The column name.
             * @param string  $mode        The list display mode ('excerpt' or 'list').
             */
            echo apply_filters( 'post_date_column_time', $t_time, $post, 'date', $mode );
        } else {

            /** This filter is documented in wp-admin/includes/class-wp-posts-list-table.php */
            echo '<abbr title="' . $t_time . '">' . apply_filters( 'post_date_column_time', $h_time, $post, 'date', $mode ) . '</abbr>';
        }
    }
}