/**
 * Give - Authorize Gateway Add-on ADMIN JS
 */

jQuery.noConflict();
var ajaxurl;
(function( $ ) {


	//On DOM Ready
	$( function() {
		check_authorize_webhooks();

		$( '.give-authorize-check-webhooks' ).on( 'click', function( e ) {
			e.preventDefault();
			// Show loading.
			$( '.give-authorize-checking-status' ).show();
			// Hide messages and button.
			$( this ).parent().hide();
			$( '.give-authorize-webhook-message' ).hide();

			var data = {
				'action': 'hard_check_authorize_webhooks',
			};

			jQuery.post( ajaxurl, data, function( response ) {
				// Check again.
				check_authorize_webhooks();
			} );


		} );

	} );

	/**
	 * Check authorize webhooks.
	 *
	 * AJAX to see if webhooks are already setup
	 *
	 * @since 1.3
	 */
	function check_authorize_webhooks() {

		var data = {
			'action': 'check_authorize_webhooks',
		};

		jQuery.post( ajaxurl, data, function( response ) {
			handle_webhook_response( response );
		} );
	}

	/**
	 * Handle the AJAX response.
	 *
	 * Displays certain DOM elements depending on the response from the server.
	 *
	 * @param response
	 */
	function handle_webhook_response( response ) {

		// For easy debugging.
		console.log( response.data );

		$( '.give-authorize-checking-status' ).hide();
		$( '.give-authorize-webhook-check-wrap' ).show();

		// LIVE no keys.
		if ( 'unconfigured' === response.data.live_webhooks_setup ) {
			$( '.give-authorize-webhook-no-live-keys' ).show();
		}

		// LIVE.
		switch ( response.data.live_webhooks_setup ) {
			case true :
				$( '.give-authorize-webhook-live-success' ).show();
				break;
			case 'error' :
				$( '.give-authorize-webhook-live-issue' ).show();
				break;
			case 'unconfigured':
				$( '.give-authorize-webhook-no-live-keys' ).show();
				break;
		}

		// Sandbox.
		switch ( response.data.sandbox_webhooks_setup ) {
			case true :
				$( '.give-authorize-webhook-sandbox-success' ).show();
				break;
			case 'error' :
				$( '.give-authorize-webhook-sandbox-issue' ).show();
				break;
			case 'unconfigured':
				$( '.give-authorize-webhook-no-sandbox-keys' ).show();
				break;
		}

		$( '.give-authorize-check-webhooks' ).show();
	}

})( jQuery );