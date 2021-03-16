<?php 
/**
 * Single post template.
 *
 */

get_header();
?>
<?php
	$img = get_the_post_thumbnail_url( $post->ID, 'full' );
	if($img == '') {
		$post_img = 'https://willgrantvision.com/willgrantvision/wp-content/uploads/2020/05/placeholder.png';
	} else {
		$post_img = $img;
	}
?>
<section class="blog-baner" >
    <div class="container">
        <div class="row">
            <div class="col-lg-12 bg-blog-banner" style="background-image:url('<?php echo $post_img; ?>')"  height="543px" >
                <div class="banner-img">

                </div>
            </div>
        </div>
    </div>
</section>

<section class="blog-detail" >
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-11">
                <div class="blog-description">
                    <div class="blog-description-title">
                    <?php 
                    $title = get_the_title();  ?>
                        <h2><?php echo $title; ?></h2>
                        <ul class="blog-owner">
                            <li>
                                <a href="<?php the_permalink();?>">
                            	<?php
                            		$uploads = wp_upload_dir();  
									$get_author_id = $post->post_author;
									$get_author_gravatar = get_avatar_url($get_author_id, array('size' => 450));
									$place_img = esc_url( $uploads['baseurl']).'/2020/05/author-3.jpg';
								?>
                            	<?php if($get_author_gravatar): ?>
		                            <img src="<?php echo $get_author_gravatar; ?>" alt="" class="owner-pic">
		                        <?php else: ?>
		                            <img src="<?php echo $place_img; ?>" class="owner-pic">
		                        <?php endif; ?>
		                        by <span><?php echo get_the_author_meta('display_name', $post->post_author); ?></span></a></li>
                                        <li><a href="<?php the_permalink();?>"><i class="icon fa fa-clock-o"></i><?php echo get_the_date( 'F j, Y' ); ?></a></li>
                        </ul>
                    </div>
                    <div class="blog-description-info">
                        <?php
                        if ( have_posts() ) :
                          while ( have_posts() ) : the_post();
                          the_content();
                          endwhile;
                          endif;
                        ?> 
                    </div>

                    <div class="social-media">
                        <h3>Share on:</h3>
                        <?php echo do_shortcode('[Sassy_Social_Share]') ?>
                    </div>

                    <?php understrap_blog_post_nav(); ?>

                    <div class="comment-section">
                        
                        <?php
                            // If comments are open or we have at least one comment, load up the comment template.
                            if ( comments_open() || get_comments_number() ) :
                                    comments_template();
                            endif;

                            ?>
						

                    </div>                  
                    
                </div>

            </div>
        </div>
    </div>
</section>

<?php get_footer(); ?>
