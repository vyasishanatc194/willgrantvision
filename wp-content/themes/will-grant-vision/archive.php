<?php
/**
 * Template Name: Blog Template
 *
 */

get_header();
?>
<?php 
$parellex_image = get_field('blog_parellex_image',15);
?>
<section class="blog-banner-section" style="background: url('<?php echo $parellex_image['url']; ?>') no-repeat; background-size: cover;background-attachment: fixed;">
    <div class="container">
        <div class="blog-heading">
            <div class="row">
                <div class="col-lg-12">
					<h1><?php echo get_the_title( 15 ); ?></h1>
				</div>
			</div>
		</div>

		<div class="blog-img-div">
        <?php $featured_img_url = get_the_post_thumbnail_url(15); ?>
			<img src="<?php echo $featured_img_url; ?>" class="banner-img" alt="" />

			<div class="banner-content-div">
				<p><?php echo get_field('your_que_ans_text',15); ?></p>
				<?php echo get_field('content',15); ?>
				<div class="link-btn">
					<a href="<?php echo get_field('ask_dr_link',15); ?>" class="link"> <?php echo get_field('ask_dr_text'); ?> > </a>
				</div>
			</div>
		</div>		

	</div>
</section>

<section class="blog-listing-section" id="cat_list">
    <div class="container">

        <div class="blog-categories filters filter-button-group">
            <a href="<?php echo home_url('blog') ?>#cat_list" class="blog-categories">Views</a>
	        <?php
	            $custom_terms = get_terms('category');
	            $i = 1;
	            foreach($custom_terms as $custom_term) {
	                wp_reset_query();
	                $args = array('post_type' => 'post',
	                    'tax_query' => array(
	                        array(
	                            'taxonomy' => 'category',
	                            'field' => 'slug',
	                            'terms' => $custom_term->slug,
	                            'term_id' => $custom_term->term_id
	                        ),
	                    ),
	                );
	                $loop = new WP_Query($args);
	                if($loop->have_posts()) {
	                    if($custom_term->slug != 'uncategorized') {
                        	$cats =  get_the_category();
							$cat = $cats[0]; 
							$cat = get_category( get_query_var( 'cat' ) );
							$cat_name = $cat->name;
	                    	if($cat_name == $custom_term->name) {
	                    		$active = "active";
	                    	} else {
	                    		$active = '';
	                    	}
	                        echo '<a href="'.get_category_link( $custom_term ).'#cat_list" class="'.$active.'">'.$custom_term->name.'</a>';
	                    }
	                }
	                $i++;
	            }
	        ?>
        </div>

		<div class="blog-listing" >
		    <?php
		    $post_per_page =  get_option('posts_per_page');
		    $cat_id = get_queried_object_id();
		    $args = array(
		        'post_type' => 'post',
		        'post_status' => 'publish',
		        'posts_per_page' => $post_per_page,
		        'paged' => 1,
		        'cat' => $cat_id
		    );
		    $blog_posts = new WP_Query( $args );
		    ?>
		   
		    <?php if ( $blog_posts->have_posts() ) : ?>
		        <div class="row blog_list grid">
		        	<div id="cat_id" style="display: none;"><?php echo $cat_id; ?></div>
		            <?php 		            	
		            	$uploads = wp_upload_dir();
		            	while ( $blog_posts->have_posts() ) : $blog_posts->the_post(); 
			            	$img1 = wp_get_attachment_image_url($post_thumbnail_id = get_post_thumbnail_id($post->ID), 'full');
							$place_img = esc_url( $uploads['baseurl']).'/2020/05/placeholder.png';
					        get_the_post_thumbnail();                    	
							$get_title = $post->post_title;                    	
							$data = $post->post_content;
							$trimmed_content = wp_trim_words( $data, 20, NULL ); 
							$total = $blog_posts->max_num_pages;	
                            $trim_title = wp_trim_words( $get_title, 5, NULL );
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
		                        <a href="<?php echo $post->guid; ?>">
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
		$cat_id = get_queried_object_id();
	    $args = array(
	        'post_type' => 'post',
	        'posts_per_page' => '-1',
	        'cat' => $cat_id
	    );	    
	    $blog_posts = new WP_Query( $args );
	    if ( $blog_posts->have_posts() ) {
    	if($blog_posts->post_count > $post_per_page) {   
    	?>
	        <div class="row justify-content-center">
	            <div class="col-lg-2 mt-3 mb-3">
	                <button class="btn btn-secondary btn-loadmore loadmore_cat">Load More</button>
	            </div>
	        </div>
        <?php } } ?>

    </div>
</section>

<?php get_footer(); ?>