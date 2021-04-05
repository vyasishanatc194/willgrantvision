jQuery.cookie = function(name, value, options) {
    if (typeof value != 'undefined') { // name and value given, set cookie
        options = options || {};
        if (value === null) {
            value = '';
            options.expires = -1;
        }
        var expires = '';
        if (options.expires && (typeof options.expires == 'number' || options.expires.toUTCString)) {
            var date;
            if (typeof options.expires == 'number') {
                date = new Date();
                date.setTime(date.getTime() + (options.expires * 24 * 60 * 60 * 1000));
            } else {
                date = options.expires;
            }
            expires = '; expires=' + date.toUTCString(); // use expires attribute, max-age is not supported by IE
        }
        // CAUTION: Needed to parenthesize options.path and options.domain
        // in the following expressions, otherwise they evaluate to undefined
        // in the packed version for some reason...
        var path = options.path ? '; path=' + (options.path) : '';
        var domain = options.domain ? '; domain=' + (options.domain) : '';
        var secure = options.secure ? '; secure' : '';
        document.cookie = [name, '=', encodeURIComponent(value), expires, path, domain, secure].join('');
    } else { // only name given, get cookie
        var cookieValue = null;
        if (document.cookie && document.cookie != '') {
            var cookies = document.cookie.split(';');
            for (var i = 0; i < cookies.length; i++) {
                var cookie = jQuery.trim(cookies[i]);
                // Does this cookie string begin with the name we want?
                if (cookie.substring(0, name.length + 1) == (name + '=')) {
                    cookieValue = decodeURIComponent(cookie.substring(name.length + 1));
                    break;
                }
            }
        }
        return cookieValue;
    }
};

jQuery(function($) {

    // Home Hero

    var step = 1;

    $(".blurry").addClass("intro");

    $(".btn-hero").on("click", function(e) {
        e.preventDefault();

        if( step === 1 ) {
            $(".image-two").addClass("show");
            $(".blurry").hide();
            $(".better").removeClass("display-none");
            $(".btn-hero").removeClass("intro");
            setTimeout(function(){
                $(".better").addClass("show");
            },500);
            setTimeout(function(){
                $(".btn-hero").addClass("animate");
                $(".image-one").addClass("got-vision");
            },1000);
            step++;
        } else if ( step === 2 ) {
            $(".image-two").removeClass("show");
            $(".better").removeClass("show").hide();
            $(".btn-hero").hide();
            $(".we-deliver-exams").addClass("display");
            $(".hero-got-vision").removeClass("display-none");
            $(".home-hero .content").addClass("flex-end");
            setTimeout(function(){
                $(".hero-got-vision").addClass("show");
            },40);
            if($(this).hasClass("btn-hero-no")) {
                $(".you-need-exam").addClass("show");
            }
            setTimeout(function(){
                $(".btn-hero").addClass("display-none");
                $(".hero-got-vision").addClass("show");
                setTimeout(function(){
                    $(".we-deliver-exams").addClass("opacity");
                },40);
            },500);
        }
    });

    // $33 active by default
    $(".give-btn-level-2").addClass("active");

    $(".give-total-wrap").insertAfter("#give-donation-level-button-wrap");

    // Donation Button
    var btnDonation = $(".give-btn");

    $(document).on("give_donation_value_updated", function(){
        var priceID = $("input[name='give-price-id']").val();
        $(".give-total-wrap").removeClass("visible");

        switch (priceID) {
            case '0':
                btnDonation.removeClass("active");
                $(".give-btn-level-0").addClass("active");
                break;
            case '1':
                btnDonation.removeClass("active");
                $(".give-btn-level-1").addClass("active");
                break;
            case '2':
                btnDonation.removeClass("active");
                $(".give-btn-level-2").addClass("active");
                break;
            case '3':
                btnDonation.removeClass("active");
                $(".give-btn-level-3").addClass("active");
                break;
            case '4':
                btnDonation.removeClass("active");
                $(".give-btn-level-4").addClass("active");
                break;
            default:
            console.log('Custom');
        }

    });

    // Custom donation amount

    $("#give-amount").on("focus", function(){
        btnDonation.removeClass("active");
        $(".give-btn-level-custom").addClass("active");
        $(".give-total-wrap").addClass("visible");
    });

    // Unmute button

    $(".btn-unmute").on("click tap", function(e) {
        e.preventDefault();
        $(this).addClass("opacity");
        $(".video-player").prop("muted",false);
    });

    // Play Button

    $(".btn-play").on("click tap", function(e) {
        e.preventDefault();
        $(".video-player").get(0).play();
        $(".video-player").prop("controls",true);
        $(".play-wrapper").hide();
    });

    // Back button

    $(".btn-back").on("click",function(e){
        e.preventDefault();
        window.history.back();
    });
    
    // Why page

    $(".btn-why").mouseover(function() {
        console.log("mouse $$$");
        $(this).addClass("active");
    });

    $(".btn-why").mouseout(function() {
        console.log("mouse out");
        $(this).removeClass("active");
    });

    // Get something modal

    $(".btn-get-something").on("click", function(e) {
        e.preventDefault();
        $(".get-something-modal").addClass("active");
        $("body").addClass("no-scroll");
        setTimeout(function() {
            location.href = 'https://visionarystream.com';
        }, 3000);
    });

    $(".bv-trigger").on("click", function(e) {
        e.preventDefault();
        $(".visionaire-modal").addClass("active");
        $("body").addClass("no-scroll");
    });

    $(".close-modal").on("click", function() {
        $(".custom-modal").removeClass("active");
        $("body").removeClass("no-scroll");
    });

    // Learn More

    const learnMore = $(".learn-more-visionaire");

    $(".option-learn-more").on("click tap", function(e){
        e.preventDefault();
        var option = "#" + $(this).attr("data-option");
        var modalTop = $(".learn-more-visionaire").offset().top;
        var scrollTop = $(option).offset().top;
        console.log(scrollTop - modalTop);
        $(".learn-more-visionaire").scrollTop(scrollTop - modalTop);
        $("body").addClass("no-scroll");
        learnMore.addClass("top opacity");
        $(".vs-content").addClass("learning-more");
    });

    $(".close-learn-more").on("click tap", function(e){
        e.preventDefault();
        learnMore.removeClass("opacity");
        setTimeout(function(){
            learnMore.removeClass("top");
        },500);
        $(".vs-content").removeClass("learning-more");
    });

    // Van animation

    $(".how-section-2").prepend($(".van-wrapper"));

    var van = $(".van");
    var $window = $(window);

    function check_if_in_view() {
        var window_height = $window.height();
        var window_top_position = $window.scrollTop();
        var window_bottom_position = (window_top_position + window_height);
      
        $.each(van, function() {
            var $element = $(this);
            var element_height = $element.outerHeight();
            var element_top_position = $element.offset().top;
            var element_bottom_position = (element_top_position + element_height);
            var margin = element_top_position - window_top_position - 300;

            console.log("-----------------------------------");
            console.log("element top: " + element_top_position);
            console.log("element bottom: " + element_bottom_position);
            console.log("window top: " + window_top_position);
            console.log("window bottom: " + window_bottom_position);

            console.log("difference " + ( element_top_position - window_top_position - 300) );
            console.log("-----------------------------------");
      
            //check to see if this current container is within viewport
            if ((element_bottom_position >= window_top_position) && (element_top_position <= window_bottom_position) && margin >= 0) {
                $element.addClass('in-view');
                $element.css("margin-left", margin + "px");
            }
            else {
                $element.removeClass('in-view');
                $element.css("margin-left", "0px");
            }
        });
    }

    // See more video

    $(".video-player").on("ended", function() {
        $(".video-more").addClass("display");
        setTimeout(function(){
            $(".video-more").addClass("opacity");
        },40);

    });

    $(window).on("scroll resize", function(){

        check_if_in_view();

        var helpUs = $('.help-us-wrapper');
        
        // This is then function used to detect if the element is scrolled into view
        function elementScrolled(elem) {
            var docViewTop = $(window).scrollTop();
            var docViewBottom = docViewTop + $(window).height();
            var elemTop = $(elem).offset().top;
            return ((elemTop <= ( docViewBottom - 100 )) && (elemTop >= docViewTop));
        }
         
        // This is where we use the function to detect if ".box2" is scrolled into view, and when it is add the class ".animated" to the <p> child element
        if(elementScrolled('.help-us-wrapper')) {
            var f = function () {
                helpUs.addClass('animated');
                setTimeout(f, 400);
            };
            f();
        }
    });

    $(".dialogflow-widget-init").on('click', function(e) {
        $(".dialogflow-widget-fake").remove();
        $(".dialogflow-widget-wrapper").css('display', 'flex');

        e.preventDefault();
        e.stopPropagation();
        return false;
    });

    // Fullscreen Modals
    
	if ($.cookie('covid-19') < 1 ) {
        window.addEventListener('load', function() {
            setTimeout(function(){
                $("body").addClass("no-scroll");
				//$(".modal-covid-19").addClass("active");
                $(".modal-ask-dr-grant").addClass("active"); // changed on 04/02/2021
            },1000);
        });
		$.cookie('covid-19', 1, { expires: 1 });
	}

    $(".close-modal-fullscreen").on("click", function(e) {
        e.preventDefault();
        $(this).parent().parent().removeClass("active");
        $("body").removeClass("no-scroll");
    });

    $(".modal-covid-19 .trigger-modal-ask-dr-grant").on("click",function(e) {
        e.preventDefault();
        $(".modal-covid-19").removeClass("active");
        $(".modal-ask-dr-grant").addClass("active");
    });
	
	// open chat from popup
    $(".covid-images img:nth-child(3)").addClass('dr-grant');
	$(".modal-ask-dr-grant div.modal-fullscreen-container img").addClass('dr-grant');
	$(".modal-covid-19").attr('id', 'pop1');
	$(".modal-ask-dr-grant").attr('id', 'pop2');
	$(".dr-grant").on("click", function(){
	  $(".modal-covid-19 a").click();
	  $(".modal-ask-dr-grant a").click();
	  $("#lhnHocButton").click();
	});
	
	// Get the modal
	var pop1 = document.getElementById('pop1');
	var pop2 = document.getElementById('pop2');

	// When the user clicks anywhere outside of the modal, close it
	window.onclick = function(event) {
	  if (event.target == pop1) {
		$(pop1).removeClass("active");
                $("body").removeClass("no-scroll");
	  }
	  if (event.target == pop2) {
		$(pop2).removeClass("active");
                $("body").removeClass("no-scroll");
	  }
	}

});