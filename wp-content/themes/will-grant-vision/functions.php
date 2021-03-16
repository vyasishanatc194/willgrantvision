<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

// BEGIN ENQUEUE PARENT ACTION
// AUTO GENERATED - Do not modify or remove comment markers above or below:

if ( !function_exists( 'chld_thm_cfg_locale_css' ) ):
    function chld_thm_cfg_locale_css( $uri ){
        if ( empty( $uri ) && is_rtl() && file_exists( get_template_directory() . '/rtl.css' ) )
            $uri = get_template_directory_uri() . '/rtl.css';
        return $uri;
    }
endif;
add_filter( 'locale_stylesheet_uri', 'chld_thm_cfg_locale_css' );
         
if ( !function_exists( 'child_theme_configurator_css' ) ):
    function child_theme_configurator_css() {
        if ( !file_exists( trailingslashit( get_stylesheet_directory() ) . 'css/theme.min.css' ) ):
            wp_deregister_style( 'understrap-styles' );
            wp_register_style( 'understrap-styles', trailingslashit( get_template_directory_uri() ) . 'css/theme.min.css' );
        endif;
        wp_enqueue_style( 'font_awesome', 'https://use.fontawesome.com/releases/v5.6.3/css/all.css', array( 'understrap-styles' ) );
        wp_enqueue_style( 'chld_thm_cfg_child', trailingslashit( get_stylesheet_directory_uri() ) . 'style.css', array( 'font_awesome' ) );
        wp_enqueue_style( 'custom_styles', get_stylesheet_directory_uri() . '/css/style.min.css?v='.time(), array( 'chld_thm_cfg_child' ) );
        wp_enqueue_script( 'custom-scripts', get_stylesheet_directory_uri() . '/js/javascript.js?v='.time(), array(), false);
        wp_enqueue_script( 'form-logic', 'https://js.createsend1.com/javascript/copypastesubscribeformlogic.js', array(), false);
    }
endif;
add_action( 'wp_enqueue_scripts', 'child_theme_configurator_css', 2000);

function arphabet_widgets_init() {

	register_sidebar( array(
		'name'          => 'Sign Up',
		'id'            => 'sign_up',
		'before_widget' => '<div>',
		'after_widget'  => '</div>',
		'before_title'  => '<h2>',
		'after_title'   => '</h2>',
	) );

}
add_action( 'widgets_init', 'arphabet_widgets_init' );

// END ENQUEUE PARENT ACTION

add_action( 'wp_footer', 'livehelpnow_widget' );
function livehelpnow_widget() {
?>
    <style type="text/css">
		
		div#lhnHocButton.lhnround {
            width: 216px;
            height: 112px;
            background: url(<?php echo get_stylesheet_directory_uri() . '/img/livehelpnow.png'; ?>) no-repeat;
            background-size: contain;
            outline: none;
		}
		
        #lhnHocButton .lhnHocChatBtn {
            display: none;
			outline: none;
        }
        div#lhnHocButton.lhnslide {
        }
    </style>
    <script type="text/javascript">
        window.lhnJsSdkInit = function () {
            lhnJsSdk.setup = {
                application_id: "6096174a-b2ed-46ac-ca4c-539c2b81a64e",
                application_secret: "8hn+wiwp5+x3nguifldmkyl7r0v2ehbhbhy0eccbktwd1pzwg4"
            };
            lhnJsSdk.controls = [{
                type: "hoc",
                id: "69788dcb-3e48-4baa-8448-4ffe298ac428"
            }];
        };
        (function (d, s) {
            var newjs, lhnjs = d.getElementsByTagName(s)[0];
            newjs = d.createElement(s);
            newjs.src = "https://developer.livehelpnow.net/js/sdk/lhn-jssdk-current.min.js";
            lhnjs.parentNode.insertBefore(newjs, lhnjs);
        }(document, "script"));
    </script>
<?php
}

/**
 * Remove custom read more link from all excerpts
 */
add_action('init', function () {
    remove_filter( 'wp_trim_excerpt', 'understrap_all_excerpts_get_more_link');
});

//convert post date in ago format
function my_post_time_ago_function() {
return sprintf( esc_html__( '%s ago', 'textdomain' ), human_time_diff(get_the_time ( 'U' ), current_time( 'timestamp' ) ) );
}
//add_filter( 'get_the_date', 'my_post_time_ago_function' );

//load more blog
function blog_scripts() {
    // Register the script
    wp_register_script( 'custom-script', get_stylesheet_directory_uri(). '/js/myloadmore.js', array('jquery'), false, true );
 
    // Localize the script with new data
    $script_data_array = array(
        'ajaxurl' => admin_url( 'admin-ajax.php' ),
        'security' => wp_create_nonce( 'load_more_posts' ),
    );
    wp_localize_script( 'custom-script', 'blog', $script_data_array );
 
    // Enqueued script with localized data.
    wp_enqueue_script( 'custom-script' );
}
add_action( 'wp_enqueue_scripts', 'blog_scripts' );

//load more posts
add_action('wp_ajax_load_posts_by_ajax', 'load_posts_by_ajax_callback');
add_action('wp_ajax_nopriv_load_posts_by_ajax', 'load_posts_by_ajax_callback');

function load_posts_by_ajax_callback() {
    check_ajax_referer('load_more_posts', 'security');
    $paged = $_POST['page'];
    $post_per_page =  get_option('posts_per_page');
    $args = array(
        'post_type' => 'post',
        'post_status' => 'publish',
        'posts_per_page' => $post_per_page,
        'paged' => $paged,
    );
    $blog_posts = new WP_Query( $args );
    ?>
 
    <?php if ( $blog_posts->have_posts() ) : ?>
        <?php 
            $uploads = wp_upload_dir();
            while ( $blog_posts->have_posts() ) : $blog_posts->the_post(); 
                $img1 = wp_get_attachment_image_url($post_thumbnail_id = get_post_thumbnail_id($post->ID), 'full');
                $place_img = esc_url( $uploads['baseurl']).'/2020/05/placeholder.png';
                get_the_post_thumbnail();                       
                $get_title = get_the_title();                     
                $data = get_the_content();
                $trimmed_content = wp_trim_words( $data, 20, NULL );
                $trim_title = wp_trim_words( $get_title, 5, NULL );
                //$category_link  =  get_the_category( $post->ID )[0]->cat_ID;
            ?>
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
                        <h3><?php echo get_the_category( $post->ID )[0]->name ?></h3>
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
        <?php
    endif;
 
    wp_die();
}

//change comment butoon text
function wcs_change_submit_button_text( $defaults ) {
    $defaults['label_submit'] = 'Post';
    return $defaults;
}
add_filter( 'comment_form_defaults', 'wcs_change_submit_button_text' );

//show reply link
add_filter( 'wpseo_remove_reply_to_com', '__return_false' );


//load more category base post
add_action('wp_ajax_load_cat_by_ajax', 'load_cat_by_ajax_callback');
add_action('wp_ajax_nopriv_load_cat_by_ajax', 'load_cat_by_ajax_callback');

function load_cat_by_ajax_callback() {
    check_ajax_referer('load_more_posts', 'security');
    $paged = $_POST['page_cat'];
    $cat_id = $_POST['cat_id'];
    $post_per_page =  get_option('posts_per_page');

    $args = array(
        'post_type' => 'post',
        'post_status' => 'publish',
        'posts_per_page' => $post_per_page,
        'paged' => $paged,
        'cat' => $cat_id
    );
    $blog_posts = new WP_Query( $args );
    ?>
 
    <?php if ( $blog_posts->have_posts() ) : ?>
        <?php 
            $uploads = wp_upload_dir();
            while ( $blog_posts->have_posts() ) : $blog_posts->the_post(); 
                $img1 = wp_get_attachment_image_url($post_thumbnail_id = get_post_thumbnail_id($post->ID), 'full');
                $place_img = esc_url( $uploads['baseurl']).'/2020/05/placeholder.png';
                get_the_post_thumbnail();                       
                $get_title = get_the_title();                     
                $data = get_the_content();
                $trimmed_content = wp_trim_words( $data, 20, NULL );
                $trim_title = wp_trim_words( $get_title, 5, NULL );
            ?>
            <div class="col-lg-4 col-md-4 co-sm-6 grid-item" >
                <div class="blog-item">
                    <a href="<?php echo $post->guid; ?>">
                        <?php if($img1 == '') {?>
                            <img src="<?php echo $place_img; ?>" class="img-fluid" alt="" />
                        <?php } else { ?>
                            <img src="<?php echo $img1; ?>" class="img-fluid" alt="" />
                        <?php } ?>
                    </a>
                    <div class="blog-item-content">
                        <h3><?php echo get_the_category( $post->ID )[0]->name; ?></h3>
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
        <?php
    endif;
 
    wp_die();
}

//comment time ago
function timeago($time, $tense='ago') {
    // declaring periods as static function var for future use
    static $periods = array('year', 'month', 'day', 'hour', 'minute', 'second');

    // checking time format
    if(!(strtotime($time)>0)) {
        return trigger_error("Wrong time format: '$time'", E_USER_ERROR);
    }

    // getting diff between now and time
    $now  = new DateTime('now');
    $time = new DateTime($time);
    $diff = $now->diff($time)->format('%y %m %d %h %i %s');
    // combining diff with periods
    $diff = explode(' ', $diff);
    $diff = array_combine($periods, $diff);
    // filtering zero periods from diff
    $diff = array_filter($diff);
    // getting first period and value
    $period = key($diff);
    $value  = current($diff);

    // if input time was equal now, value will be 0, so checking it
    if(!$value) {
        $period = 'seconds';
        $value  = 0;
    } else {
        // converting days to weeks
        if($period=='day' && $value>=7) {
            $period = 'week';
            $value  = floor($value/7);
        }
        // adding 's' to period for human readability
        if($value>1) {
            $period .= 's';
        }
    }

    // returning timeago
    return "$value $period $tense";
}

//change place of comment box
function wpb_move_comment_field_to_bottom( $fields ) {
$comment_field = $fields['comment'];
unset( $fields['comment'] );
$fields['comment'] = $comment_field;
return $fields;
}
 
add_filter( 'comment_form_fields', 'wpb_move_comment_field_to_bottom' );