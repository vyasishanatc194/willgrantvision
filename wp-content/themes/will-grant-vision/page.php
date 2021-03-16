<?php
/**
 * The template for displaying all pages.
 *
 * This is the template that displays all pages by default.
 * Please note that this is the WordPress construct of pages
 * and that other 'pages' on your WordPress site will use a
 * different template.
 *
 * @package understrap
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

get_header();

$container = get_theme_mod( 'understrap_container_type' );

if ( is_front_page() ) {
	?>
		<div class="home-hero one">
			<div class="hero-image image-one"></div>
			<div class="hero-image image-two"></div>
			<div class="home-hero-wrapper">
				<div class="content">
					<div class="spacer"></div>
					<div class="hero-text">
						<div class="questions">
							<span class="question-text blurry">is this blurry <span class="blue-text">to you?</span></span>
							<span class="question-text better display-none">better?</span>
							<span class="question-text hero-got-vision display-none">got vision?
							<span class="you-need-exam">you need an eye exam!</span>
							</span>
						</div>
						<div class="answers">
							<a href="#" class="btn-hero btn-hero-yes intro">Yes</a>
							<a href="#" class="btn-hero btn-hero-no intro">No</a>
							<img src="<?php bloginfo('stylesheet_directory'); ?>/img/we-deliver.png" alt="We deliver eye exams for free" class="we-deliver-exams" />
						</div>
					</div>
				</div>
			</div>
		</div>
	<?php
}
?>
<div class="wrapper" id="page-wrapper">

	<main class="site-main" id="main">

		<?php while ( have_posts() ) : the_post(); ?>

			<?php get_template_part( 'loop-templates/content', 'page' ); ?>

			<?php
			// If comments are open or we have at least one comment, load up the comment template.
			if ( comments_open() || get_comments_number() ) :
				comments_template();
			endif;
			?>

		<?php endwhile; // end of the loop. ?>

	</main><!-- #main -->

</div><!-- #page-wrapper -->

<?php get_footer(); ?>