<?php
/**
 * Custom functions that act independently of the theme templates.
 *
 * Eventually, some of the functionality here could be replaced by core features.
 *
 * @package understrap
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

add_filter( 'body_class', 'understrap_body_classes' );

if ( ! function_exists( 'understrap_body_classes' ) ) {
	/**
	 * Adds custom classes to the array of body classes.
	 *
	 * @param array $classes Classes for the body element.
	 *
	 * @return array
	 */
	function understrap_body_classes( $classes ) {
		// Adds a class of group-blog to blogs with more than 1 published author.
		if ( is_multi_author() ) {
			$classes[] = 'group-blog';
		}
		// Adds a class of hfeed to non-singular pages.
		if ( ! is_singular() ) {
			$classes[] = 'hfeed';
		}

		return $classes;
	}
}

// Removes tag class from the body_class array to avoid Bootstrap markup styling issues.
add_filter( 'body_class', 'understrap_adjust_body_class' );

if ( ! function_exists( 'understrap_adjust_body_class' ) ) {
	/**
	 * Setup body classes.
	 *
	 * @param string $classes CSS classes.
	 *
	 * @return mixed
	 */
	function understrap_adjust_body_class( $classes ) {

		foreach ( $classes as $key => $value ) {
			if ( 'tag' == $value ) {
				unset( $classes[ $key ] );
			}
		}

		return $classes;

	}
}

// Filter custom logo with correct classes.
add_filter( 'get_custom_logo', 'understrap_change_logo_class' );

if ( ! function_exists( 'understrap_change_logo_class' ) ) {
	/**
	 * Replaces logo CSS class.
	 *
	 * @param string $html Markup.
	 *
	 * @return mixed
	 */
	function understrap_change_logo_class( $html ) {

		$html = str_replace( 'class="custom-logo"', 'class="img-fluid"', $html );
		$html = str_replace( 'class="custom-logo-link"', 'class="navbar-brand custom-logo-link"', $html );
		$html = str_replace( 'alt=""', 'title="Home" alt="logo"', $html );

		return $html;
	}
}

/**
 * Display navigation to next/previous post when applicable.
 */

if ( ! function_exists ( 'understrap_post_nav' ) ) {
	function understrap_post_nav() {
		// Don't print empty markup if there's nowhere to navigate.
		$previous = ( is_attachment() ) ? get_post( get_post()->post_parent ) : get_adjacent_post( false, '', true );
		$next     = get_adjacent_post( false, '', false );

		if ( ! $next && ! $previous ) {
			return;
		}
		?>
		<nav class="container navigation post-navigation">
			<h2 class="sr-only"><?php esc_html_e( 'Post navigation', 'understrap' ); ?></h2>
			<div class="nav-links">
				<?php
				if ( get_previous_post_link() ) {
					previous_post_link( '<span class="nav-previous pull-left">%link</span>', _x( '<i class="fa fa-long-arrow-alt-left"></i>&nbsp;%title', 'Previous post link', 'understrap' ) );
				}
				if ( get_next_post_link() ) {
					next_post_link( '<span class="nav-next pull-right">%link</span><div class="clearfix"></div>', _x( '%title&nbsp;<i class="fa fa-long-arrow-alt-right"></i>', 'Next post link', 'understrap' ) );
				}
				?>
			</div><!-- .nav-links -->
		</nav><!-- .navigation -->
		<?php
	}
}

if ( ! function_exists( 'understrap_pingback' ) ) {
	/**
	 * Add a pingback url auto-discovery header for single posts of any post type.
	 */
	function understrap_pingback() {
		if ( is_singular() && pings_open() ) {
			echo '<link rel="pingback" href="' . esc_url( get_bloginfo( 'pingback_url' ) ) . '">' . "\n";
		}
	}
}
add_action( 'wp_head', 'understrap_pingback' );

if ( ! function_exists( 'understrap_mobile_web_app_meta' ) ) {
	/**
	 * Add mobile-web-app meta.
	 */
	function understrap_mobile_web_app_meta() {
		echo '<meta name="mobile-web-app-capable" content="yes">' . "\n";
		echo '<meta name="apple-mobile-web-app-capable" content="yes">' . "\n";
		echo '<meta name="apple-mobile-web-app-title" content="' . esc_attr( get_bloginfo( 'name' ) ) . ' - ' . esc_attr( get_bloginfo( 'description' ) ) . '">' . "\n";
	}
}
add_action( 'wp_head', 'understrap_mobile_web_app_meta' );

if ( ! function_exists ( 'understrap_blog_post_nav' ) ) {
	function understrap_blog_post_nav() {
		// Don't print empty markup if there's nowhere to navigate.
		//$previous = ( is_attachment() ) ? get_post( get_post()->post_parent ) : get_adjacent_post( false, '', true );
		//$next     = get_adjacent_post( false, '', false );
		$prev = get_previous_post()->ID;
		$next = get_next_post()->ID;

		if ( ! $next && ! $prev ) {
			return;
		}
		?>
                
		<div class="post-pagination">
            <div class="post-previous">
                 <?php 
                    if( $next ) {
                        $title = get_the_title( $next );
                        $link = get_the_permalink( $next );
                        $trim_title = wp_trim_words( $title, 5, NULL );
                        ?>
                        <a href="<?php echo $link; ?>" rel="next" class="btn-previous">
                            <img src="<?php echo get_stylesheet_directory_uri(); ?>/img/arrows_slim_left.png">
                            <h3>Previous Post <span> <?php echo $trim_title; ?></span></h3>
                           
                        </a>
                        <?php
                    }
                    ?>
                
            </div>
            <div class="post-next">
                <?php 
                
                if( $prev ) {
                    $title = get_the_title( $prev );
                    $link = get_the_permalink( $prev );
                    $trim_title = wp_trim_words( $title, 5, NULL );
                    ?>
                    <a href="<?php echo $link; ?>" rel="prev" class="btn-next">
                        
                        <h3>Next Post <span><?php echo $trim_title; ?></span></h3>
                         <img src="<?php echo get_stylesheet_directory_uri(); ?>/img/arrows_slim_right.png">
                    </a>
                    <?php
                }
                ?>
            </div>

        </div>
		<?php
	}
}