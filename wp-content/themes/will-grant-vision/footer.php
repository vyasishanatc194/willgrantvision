<?php
/**
 * The template for displaying the footer.
 *
 * Contains the closing of the #content div and all content after
 *
 * @package understrap
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>

</div><!-- #page we need this extra closing tag here -->

<footer class="site-footer">

	<div class="footer-top">

		<div class="container">
			<div class="row">
				<div class="col-md-4 help-column">
					<div class="help-us-wrapper">
						<p><span class='help-us'>Join us</span><br />
						<span class="build">build a foundation of</span>
						<span class="learning">learning, opportunity &</span>
						<span class="independence">independence <strong>for all</strong></span></p>
					</div>
				</div>
				<div class="col-md-8">
					<a href="<?php echo get_site_url(). "/give-something/"; ?>" class="btn-something">
						<img src="<?php bloginfo('stylesheet_directory'); ?>/img/icon-give.png" alt="give something">
						<span>give something</span>
					</a>
					<a href="https://www.towards2020glasses.com" class="btn-something btn-get-something">
						<img src="<?php bloginfo('stylesheet_directory'); ?>/img/icon-get.png" alt="get something">
						<span>get something</span>
					</a>
					<a href="<?php echo get_site_url(). "/do-something/"; ?>" class="btn-something">
						<img src="<?php bloginfo('stylesheet_directory'); ?>/img/icon-do.png" alt="do something">
						<span>do something</span>
					</a>
				</div>
			</div>
		</div>

	</div><!-- .footer-top -->

	<div class="footer-graphics">

		<div class="container">
			<div class="row">
				<div class="col-lg-8">
					<div class="footer-buttons">
						<div class="button-column">
							<a href="<?php echo get_site_url(); ?>" class="btn-home"><img src="<?php bloginfo('stylesheet_directory'); ?>/img/btn-footer-logo-will-grant-vision.jpg" alt="Will Grant Vision"></a>
						</div>
						<div class="button-column">
							<a href="https://www.instagram.com/willgrantvision/" class="btn-instagram" target="_blank"><img src="<?php bloginfo('stylesheet_directory'); ?>/img/btn-instagram.jpg" alt="Will Grant Vision"></a>
						</div>
						<div class="button-column">
							<a href="http://visionarystream.com/" class="btn-visionarystream" target="_blank"><img src="<?php bloginfo('stylesheet_directory'); ?>/img/btn-visionary-stream.jpg" alt="Visionary Stream"></a>
						</div>
						<div class="button-column double-images">
							<a href="<?php echo get_site_url() . "/blog"; ?>" class="btn-learn-more"><img src="<?php bloginfo('stylesheet_directory'); ?>/img/btn-blog.jpg" alt="Will Grant Vision"></a>
							<a href="http://visioniscool.com/" class="btn-visioniscool" target="_blank"><img src="<?php bloginfo('stylesheet_directory'); ?>/img/btn-vision-is-cool.jpg" alt="Vision is Cool"></a>
						</div>
					</div>
				</div>
				<div class="col-lg-4">
					<div class="social">
						<a href="https://www.youtube.com/channel/UCzq2PsKjwl6aLOgEkeeBC1g/" target="_blank"><i class="fab fa-youtube"></i></a>
						<a href="https://www.instagram.com/willgrantvision/" target="_blank"><i class="fab fa-instagram"></i></a>
						<a href="https://www.facebook.com/willgrantvision" target="_blank"><i class="fab fa-facebook-f"></i></a>
					</div>
					<div class="sign-up">
						<p>Sign up with your email address to receive news & updates.</p>
						<?php dynamic_sidebar( 'sign_up' ); ?>
					</div>
				</div>
			</div>
		</div>

	</div><!-- .footer-graphics -->

	<div class="monogram">

	</div><!-- /.monogram -->

</footer><!-- /site-footer -->

<div class="custom-modal get-something-modal">
	<div class="gs-container">
		<div class="gs-content">
			<a class="close-modal"><i class="fas fa-times"></i></a>
			<div class="gs-title">
				<p>TRANSMITTING TO<br>
				. . .</p>
			</div>
			<div class="gs-img">
				<img src="<?php bloginfo('stylesheet_directory'); ?>/img/visionary-stream.png" />
			</div>
			<div class="gs-menu">
				<ul>
					<li><a href="https://visionarystream.com/#the-glasses">glasses</a></li>
					<li><a href="https://visionarystream.com/eye-exams/">eye exams</a></li>
					<li><a href="https://visionarystream.com/exhibits/">exhibits</a></li>
					<li><a href="https://visionarystream.com/exposures/">exposures</a></li>
					<li><a href="https://visionarystream.com/events/">events</a></li>
				</ul>
			</div>
		</div>
	</div>
</div>

<div class="custom-modal visionaire-modal">
	<div class="vs-container">
		<div class="vs-content">
			<a class="close-modal"><i class="fas fa-times"></i></a>
			<div class="vs-width">
				<img src="<?php bloginfo('stylesheet_directory'); ?>/img/visionaire-logo.png" class="visionaire-logo"/>

				<form id="subForm" class="js-cm-form vs-menu" action="https://www.createsend.com/t/subscribeerror?description=" method="post" data-id="A61C50BEC994754B1D79C5819EC1255C2CB9BF97CABC30E0F48DFDAA48879B225E899603E02EC3D08A02E3D7FDEB0B3C6F6FE8F1B86031B748FF65A6CC93073A">

					<div class="option">
						<label for="fieldjukrhjl-0" class="label-container">Partnership
							<input id="fieldjukrhjl-0" name="cm-fo-jukrhjl" type="checkbox" value="3788351" />
							<span class="checkmark"></span>
						</label>
						<a class="option-learn-more" href="#" data-option="partnership">Learn More</a>
					</div>

					<div class="option">
						<label for="fieldjukrhjl-1" class="label-container">Recycle Glasses
							<input id="fieldjukrhjl-1" name="cm-fo-jukrhjl" type="checkbox" value="3788352" />
							<span class="checkmark"></span>
						</label>
						<a class="option-learn-more" href="#" data-option="recycle">Learn More</a>
					</div>

					<div class="option">
						<label for="fieldjukrhjl-2" class="label-container">Recurring Donation
							<input id="fieldjukrhjl-2" name="cm-fo-jukrhjl" type="checkbox" value="3788353" />
							<span class="checkmark"></span>
						</label>
						<a class="option-learn-more" href="#" data-option="recurring">Learn More</a>
					</div>

					<div class="option">
						<label for="fieldjukrhjl-3" class="label-container">Subscription Eye Care
							<input id="fieldjukrhjl-3" name="cm-fo-jukrhjl" type="checkbox" value="3788354" />
							<span class="checkmark"></span>
						</label>
						<a class="option-learn-more" href="#" data-option="subscription">Learn More</a>
					</div>

					<div class="option">
						<label for="fieldjukrhjl-4" class="label-container">Volunteer
							<input id="fieldjukrhjl-4" name="cm-fo-jukrhjl" type="checkbox" value="3788355" />
							<span class="checkmark"></span>
						</label>
						<a class="option-learn-more" href="#" data-option="volunteer">Learn More</a>
					</div>

					<input id="fieldEmail" name="cm-ykjhgk-ykjhgk" type="email" class="js-cm-email-input" required placeholder="E-Mail Address" />

					<button class="js-cm-submit-button submit-become" type="submit">Become A Visionaire</button>

				</form>
			</div>
		</div>
		<div class="learn-more-visionaire">
			<div class="learn-more-content">
				<h2 id="partnership">Partnership</h2>
				<p>Book us for a visionary event<br>
				<em style="font-size: 14px;">and/or</em><br>
				Donate at least $1000 annually<br>
				<em style="font-size: 14px;">and/or</em><br>
				Corporate sponsorship</p>
				<h2 id="recycle">Glasses Recycling Patronage</h2>
				<p>It’s easy</p>
				<p>Get a box of most any size<br>
				<span class="notation" style="font-size: 0.7rem;">(We know you have an Amazon box laying around)</span><br>
				<em class="star" style="font-size: 1.3rem;">*recommend A1 and up*</em></span></p>
				<p>Request decals <span style="font-size: 0.8rem;">(We send you decals for the box)</span></p>
				<p>Repurpose the box into a glasses recycling receptacle</p>
				<p>Fill the box up with glasses and ship it to us</p>
				<p>Commit to at least one box per year</p>
				<h2 id="recurring">Recurring Donation</h2>
				<p>Subscription based giving with monthly donation</p>
				<h2 id="subscription">Subscription Based Eye Care</h2>
				<p>Let us take care of your eyes</p>
				<p>Annual exam & yearly glasses</p>
				<h2 id="volunteer">Volunteer</h2>
				<p>We will contact you to discuss the many ways you can help</p>
				<a href="#" class="close-learn-more">< back</a>
			</div>
		</div>
	</div>
</div>

<div class="modal-fullscreen modal-covid-19">
	<!-- <a class="close-modal-fullscreen"><i class="fas fa-times"></i></a> -->
	<div class="modal-fullscreen-container">
    <a class="close-modal-fullscreen"><i class="fas fa-times"></i></a>
		<div class="covid-images">
			<img src="<?php bloginfo('stylesheet_directory'); ?>/img/stop.jpg" alt="Stop - Enhanced Precautions - You can get COVID-19 through your eyes">
			<img src="<?php bloginfo('stylesheet_directory'); ?>/img/protection.jpg" alt="Eye Protection, Mask, Gloves, Gown">
			<img src="<?php bloginfo('stylesheet_directory'); ?>/img/ask-dr-grant.jpg" alt="Ask Dr. Grant questions?">
		</div>
		<a class="how-see-more trigger-modal-ask-dr-grant" href="#">Learn More</a>
	</div>
</div>

<div class="modal-fullscreen modal-ask-dr-grant">
	<!--<a class="close-modal-fullscreen"><i class="fas fa-times"></i></a>-->
	<div class="modal-fullscreen-container">
    <a class="close-modal-fullscreen"><i class="fas fa-times"></i></a>
		<h1>do you have any questions about your eyes?</h1>
		<img src="<?php bloginfo('stylesheet_directory'); ?>/img/ask-dr-grant.png" alt="Ask Dr. Grant questions?">
		<p>Dr. Grant is a board certified medical doctor and eye surgeon, an ophthalmologist, with speciality training in pediatrics, "lazy eye"?, myopia (nearsightedness), eye alignment and neuromuscular eye disorders.  He is in active practice and routinely diagnoses and manages eye conditions in infants, children, adults and seniors alike.</p>
		<p><strong>Ask Dr. Grant any questions that you may have related to your eyes and get real personalized and professional responses for free. The mission is to give vision by expanding access for all.</strong></p>
		<p><i>*if you have any emergent concerns, please report to the nearest emergency room or urgent care.</i></p>		
	</div>
</div>


<?php wp_footer(); ?>
</body>

</html>