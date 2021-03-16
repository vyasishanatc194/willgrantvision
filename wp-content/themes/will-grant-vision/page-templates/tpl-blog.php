<?php
/**
 * Template Name: Blog Template Category
 *
 */

get_header();
?>
<?php 
$parellex_image = get_field('blog_parellex_image');
?>
<style>
@media (min-width: 1025px) {
  .blog-banner-section {
    background-position: center 90px !important;
  }
}
</style>
<section class="blog-banner-section" style="background: url('<?php echo $parellex_image['url']; ?>') no-repeat; background-size: cover;background-attachment: fixed;">
    <div class="container">
        <div class="blog-heading">
            <div class="row">
                <div class="col-lg-12">
					<h1><?php the_title(); ?></h1>
				</div>
			</div>
		</div>

		<div class="blog-img-div">
        <?php $featured_img_url = get_the_post_thumbnail_url(get_the_ID()); ?>
			<img src="<?php echo $featured_img_url; ?>" class="banner-img" alt="" />

			<div class="banner-content-div">
				<p><?php echo get_field('your_que_ans_text'); ?></p>
				<?php echo get_field('content'); ?>
				<div class="link-btn">
					<a href="<?php echo get_field('ask_dr_link'); ?>" class="link"> <?php echo get_field('ask_dr_text'); ?> > </a>
				</div>
			</div>
		</div>		

	</div>
</section>
<section class="blog-listing-section" id="cat_list">
    <div class="container">
        
        
        <div class="blog-categories filters filter-button-group">
            <a href="<?php echo home_url('blog') ?>#cat_list" class="blog-categories active">Views</a>
	        <?php
	            $custom_terms = get_terms('category');
	            foreach($custom_terms as $custom_term) {
	                wp_reset_query();
	                $args = array('post_type' => 'post');
	                $loop = new WP_Query($args);
	                if($loop->have_posts()) {
	                    if($custom_term->slug != 'uncategorized') {
	                        echo '<a href="'.get_category_link( $custom_term ).'#cat_list">'.$custom_term->name.'</a>';
	                    }
	                }
	            }
	        ?>
        </div>

		<div class="blog-listing" >
		    <?php
            $post_per_page =  get_option('posts_per_page');
		    $args = array(
		        'post_type' => 'post',
		        'post_status' => 'publish',
		        'posts_per_page' => $post_per_page,
		        'paged' => 1,
		    );
		    $blog_posts = new WP_Query( $args );
		    ?>
		 
		    <?php if ( $blog_posts->have_posts() ) : ?>
		        <div class="row blog_list grid">
		            <?php 
		            	$uploads = wp_upload_dir();
		            	while ( $blog_posts->have_posts() ) : $blog_posts->the_post(); 
			            	$img1 = wp_get_attachment_image_url($post_thumbnail_id = get_post_thumbnail_id($post->ID), 'full');
							$place_img = esc_url( $uploads['baseurl']).'/2020/05/placeholder.png';
					        get_the_post_thumbnail();                    	
							$get_title = $post->post_title;                    	
							$data = $post->post_content;
							$trimmed_content = wp_trim_words( $data, 20, NULL );
                            $trim_title = wp_trim_words( $get_title, 5, NULL );
                            $total = $blog_posts->max_num_pages;	
                            $categories = get_the_category();
							$separator = ', ';
							$output = '';
								if ( ! empty( $categories ) ) {
                                    foreach( $categories as $category ) {
                                        $output .= $category->name . $separator;
                                    }
                                } 
		            	?>
                        <div id="max_page" style="display: none;"><?php echo $total; ?></div>
		                <div class="col-lg-4 col-md-4 co-sm-6 grid-item">
		                    <div class="blog-item">
		                        <a href="<?php echo get_permalink( $post->ID ); ?>">
		                        	<?php if($img1 == '') {?>
		                        		<img src="<?php echo $place_img; ?>" class="img-fluid" alt="" />
		                        	<?php } else { ?>
			                            <img src="<?php echo $img1; ?>" class="img-fluid" alt="" />
			                        <?php } ?>
		                        </a>
		                        <div class="blog-item-content">
		                            <h3><?php echo trim( $output, $separator ); ?></h3>
		                            <h2><a href="<?php echo get_permalink( $post->ID ); ?>"><?php echo $trim_title; ?></a></h2>
		                            <p><?php echo $trimmed_content; ?></p>
		                            <ul class="post-meta">
		                                <li><i class="icon fa fa-user"></i>by <span><?php echo get_the_author_meta('display_name', $post->post_author); ?></span></li>
		                                <li><i class="icon fa fa-clock-o"></i><?php echo get_the_date( 'F j, Y' ); ?></li>
		                            </ul>
		                        </div>
		                    </div>
		                </div>
		            <?php endwhile; ?>
		        </div>
		    <?php endif; ?>
		</div>

        <?php 
        $post_per_page =  get_option('posts_per_page');
	    $args = array(
	        'post_type' => 'post',
	        'posts_per_page' => '-1',
	    );
	    $blog_posts = new WP_Query( $args );
	    if ( $blog_posts->have_posts() ) {
    	if($blog_posts->post_count > $post_per_page) {   
    	?>
	        <div class="row justify-content-center">
	            <div class="col-lg-2 mt-3 mb-3">
	                <button class="btn btn-secondary btn-loadmore loadmore">Load More</button>
	            </div>
	        </div>

        <?php } } ?>

    </div>
</section>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>  
<script>
	jQuery('.post-previous a').addClass('btn-previous');
	jQuery('.post-next a').addClass('btn-next');
	jQuery('#commentform #submit').addClass('btn-loadmore');
</script>
<script src="<?php echo get_stylesheet_directory_uri(); ?>/js/bootstrap.min.js"></script>
<script src="https://unpkg.com/isotope-layout@3/dist/isotope.pkgd.min.js"></script>
<script src="https://unpkg.com/imagesloaded@4/imagesloaded.pkgd.min.js"></script>


<script>

var $grid = $('.grid').isotope({		     
    itemSelector: '.grid-item',
    percentPosition: true,
    initLayout: false,
    horizontalOrder: false,
    stagger: 30,
    layoutMode: 'masonry'
});
$grid.imagesLoaded().progress( function() {
    $grid.isotope();
});



var page = 2;
var max_page = $('#max_page').text();
jQuery(function($) {
    $('body').on('click', '.loadmore', function() {	

        var filtercat = jQuery('.filter-button-group a.blog-categories.cats.active'); 
        var data = {
            'action': 'load_posts_by_ajax',
            'page': page,
            'security': blog.security
        };
        $.post(blog.ajaxurl, data, function(response) {
            if($.trim(response) != '') {   
                if(page == max_page) {
                    $('.loadmore').hide();
                } 
                $grid.imagesLoaded().progress( function() {
                    $grid.isotope('layout');
                });
                newItems = $(response).appendTo('.grid');
                $grid.isotope('insert', newItems );
                
                setTimeout(function(){ $grid.isotope() }, 300);

                page++;                

            } else {
                $('.loadmore').hide();
            }

        });			        
    });
});

var page_cat = 2;
var max_page_cat = $('#max_page').text();
jQuery(function($) {
    $('body').on('click', '.loadmore_cat', function() {	    	
                var cat_id = $('#cat_id').text();
        var data = {
            'action': 'load_cat_by_ajax',
            'page_cat': page_cat,
            'security': blog.security,
            'cat_id' : cat_id
        };
        $.post(blog.ajaxurl, data, function(response) {
            if($.trim(response) != '') {      
                if(page_cat == max_page_cat) {
                    $('.loadmore_cat').hide();
                }          
                 $grid.imagesLoaded().progress( function() {
                                $grid.isotope('layout');
                            });

                            newItems = $(response).appendTo('.grid');
                                $grid.isotope('insert', newItems );

                page_cat++;                


            } else {
                $('.loadmore_cat').hide();
            }
        });
    });
});

</script>
<?php get_footer(); ?>