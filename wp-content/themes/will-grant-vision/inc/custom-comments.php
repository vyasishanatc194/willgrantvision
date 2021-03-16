<?php
/**
 * Comment layout.
 *
 * @package understrap
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Comments form.
add_filter( 'comment_form_default_fields', 'understrap_bootstrap_comment_form_fields' );

/**
 * Creates the comments form.
 *
 * @param string $fields Form fields.
 *
 * @return array
 */

if ( ! function_exists( 'understrap_bootstrap_comment_form_fields' ) ) {

	function understrap_bootstrap_comment_form_fields( $fields ) {
		$commenter = wp_get_current_commenter();
		$req       = get_option( 'require_name_email' );
		$aria_req  = ( $req ? " aria-required='true'" : '' );
		$html5     = current_theme_supports( 'html5', 'comment-form' ) ? 1 : 0;
		$consent  = empty( $commenter['comment_author_email'] ) ? '' : ' checked="checked"';
		$fields    = array(
			'author'  => '<div class="form-group comment-form-author col-md-6"><input class="form-control" placeholder="Name*" id="author" name="author" type="text" value="' . esc_attr( $commenter['comment_author'] ) . '" size="30"' . $aria_req . '></div>',
			'email'   => '<div class="form-group comment-form-email col-md-6"><input class="form-control" placeholder="Email*" id="email" name="email" ' . ( $html5 ? 'type="email"' : 'type="text"' ) . ' value="' . esc_attr( $commenter['comment_author_email'] ) . '" size="30"' . $aria_req . '></div>',
			/*'url'     => '<div class="form-group comment-form-url"><label for="url">' . __( 'Website',
					'understrap' ) . '</label> ' .
			            '<input class="form-control" id="url" name="url" ' . ( $html5 ? 'type="url"' : 'type="text"' ) . ' value="' . esc_attr( $commenter['comment_author_url'] ) . '" size="30"></div>',
			'cookies' => '<div class="form-group form-check comment-form-cookies-consent"><input class="form-check-input" id="wp-comment-cookies-consent" name="wp-comment-cookies-consent" type="checkbox" value="yes"' . $consent . ' /> ' .
			         '<label class="form-check-label" for="wp-comment-cookies-consent">' . __( 'Save my name, email, and website in this browser for the next time I comment', 'understrap' ) . '</label></div>',*/
		);

		return $fields;
	}
} // endif function_exists( 'understrap_bootstrap_comment_form_fields' )

add_filter( 'comment_form_defaults', 'understrap_bootstrap_comment_form' );

/**
 * Builds the form.
 *
 * @param string $args Arguments for form's fields.
 *
 * @return mixed
 */

if ( ! function_exists( 'understrap_bootstrap_comment_form' ) ) {

	function understrap_bootstrap_comment_form( $args ) {
		$args['comment_field'] = '<div class="form-group comment-form-comment col-md-12">
	    <textarea placeholder="Let us know what you have to say...*" class="form-control" id="comment" name="comment" aria-required="true" cols="45" rows="8"></textarea>
	    </div>';
		$args['class_submit']  = 'btn btn-secondary'; // since WP 4.1.
		return $args;
	}
} // endif function_exists( 'understrap_bootstrap_comment_form' )


/**
 * Builds a better comment list
 */
if( ! function_exists( 'better_comments' ) ):
function better_comments($comment, $args, $depth) {
    ?>
	<li <?php comment_class('media'); ?> id="li-comment-<?php comment_ID() ?>">
		<div class="media-left">
			<?php echo get_avatar($comment,$size='62' ); ?>
		</div>
		<div class="media-body">
			<h4 class="media-heading"><?php echo get_comment_author() ?></h4>
			<small>
				<span><?php printf(/* translators: 1: date and time(s). */ esc_html__('%1$s' , '5balloons_theme'), timeago(get_comment_time())) ?></span>
				| <a class="comment-reply-link" href="#"><?php comment_reply_link(array_merge( $args, array('depth' => $depth, 'max_depth' => $args['max_depth']))) ?></a></small>
			<?php if ($comment->comment_approved == '0') : ?>
				<div>
					<em><?php esc_html_e('Your comment is awaiting moderation.','5balloons_theme') ?></em>
				<div>
			<?php endif; ?>

			<?php comment_text() ?>
		</div>
	</li>

<?php }
endif;