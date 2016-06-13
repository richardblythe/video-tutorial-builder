<?php
$id = isset($_REQUEST['tutorial']) ? (int)$_REQUEST['tutorial'] : 0;
global $post;
$post = get_post( $id );

if ( !$video_info = vtb_get_video_info( $post ) ) {
    echo "<div class=\"wrap\"><h2>Tutorial Not Found</h2></div>";
    return;
}

$prev_post = get_previous_post();
$prev_video_info = ($prev_post instanceof WP_Post ? vtb_get_video_info($prev_post) : null);

$next_post = get_next_post();
$next_video_info = ($next_post instanceof WP_Post ? vtb_get_video_info($next_post) : null);

$origin = get_bloginfo('url');
?>
<div class="wrap">
    <div class="vtb-description">
        <h2><?php echo $post->post_title; ?></h2>
        <p><?php echo $post->post_content; ?></p>
    </div>

    <div class="vtb-container">
        <div class="vtb-primary-video">
            <div class="vtb-aspect-ratio">
                <div data-source="<?php echo $video_info['source']; ?>" data-id="<?php echo $video_info['id']; ?>" class="vtb-aspect-ratio-content">
                    <iframe id="player" type="text/html"
                            src="http://www.youtube.com/embed/<?php echo $video_info['id']; ?>?enablejsapi=1&origin=<?php echo urlencode($origin); ?>"
                            <?php /* TODO Vimeo Support */ ?>
                            frameborder="0"
                            allowfullscreen="1">
                    </iframe>
                </div> <!-- .vtb-aspect-ratio-content -->
            </div> <!-- .vtb-aspect-ratio -->
        </div> <!-- .vtb-primary-video -->
        <?php if(!empty($prev_video_info)) : ?>

        <div class="vtb-prev-video">
            <a href="<?php vtb_tutorial_url( $prev_post ); ?>">
                <?php vtb_video_thumbnail($prev_post); ?>
            </a>
            <p>Previous Video:</p>
            <h3>
                <a href="<?php vtb_tutorial_url( $prev_post ); ?>">
                    <?php echo $prev_post->post_title; ?>
                </a>
            </h3>
        </div> <!-- .vtb-prev-video -->

        <?php endif; ?>
        <?php if (!empty($next_video_info)) : ?>

        <div class="vtb-next-video">
            <a href="<?php vtb_tutorial_url( $next_post ); ?>">
                <?php vtb_video_thumbnail($next_post); ?>
            </a>
            <p>Next Video:</p>
            <h3>
                <a href="<?php vtb_tutorial_url( $next_post ); ?>">
                    <?php echo $next_post->post_title; ?>
                </a>
            </h3>
        </div> <!-- .vtb-next-video -->
        <?php endif; ?>

    </div> <!-- .vtb-container -->
</div>
