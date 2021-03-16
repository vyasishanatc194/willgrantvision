<?php
/**
 * Post rendering content according to caller of get_template_part.
 *
 * @package understrap
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>
<div class="masonry__item js-masonry-item">
    <article <?php post_class('card booking-card mt-2 mb-4'); ?> id="post-<?php the_ID(); ?>">
        <div class="blog-item">
            <!-- Card image -->
            <div class="view overlay">
                    <a href="<?php echo get_permalink($post->ID); ?>">
                        <?php echo get_the_post_thumbnail( $post->ID, 'medium', array( 'class' => 'card-img-top img-fluid' ) ); ?></a>
            </div>
            <div class="blog-item-content">
                <!-- Card content -->
                <div class="card-body">
                        <!-- Category -->
                        <p class="card-category h5 font-weight-bold"><?php echo get_the_category( $post->ID )[0]->name ?></p>
                        <!-- Title -->
                        <?php
                        the_title(
                                sprintf( '<h4 class="card-title font-weight-bold"><a href="%s" rel="bookmark">', esc_url( get_permalink() ) ),
                                '</a></h4>'
                        );
                        ?>
                        <!-- Text -->
                        <?php the_excerpt(); ?>

                        <footer class="entry-footer">

                                <?php if ( 'post' == get_post_type() ) : ?>
                                <div class="entry-meta clearfix">
                                        <?php understrap_posted_on(); ?>
                                </div><!-- .entry-meta -->
                                <?php endif; ?>

                                <?php //understrap_entry_footer(); ?>
                        </footer><!-- .entry-footer -->

                </div>
            </div>
        </div>
    </article><!-- #post-## -->
</div>