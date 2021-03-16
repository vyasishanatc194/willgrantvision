<?php
/**
 * Give - Authorize.net Helper Functions.
 */


/**
 * Admin notices for Authorize.net
 *
 * @since 1.3
 */
function give_authorize_admin_notices() {

	$payments                  = Give_Authorize()->payments;
	$webhook_sig_error_live    = give_get_option( 'give_authorize_live_webhooks_no_signature_key' );
	$live_api_keys             = ( ! empty( $payments->live_login_id ) && ! empty( $payments->live_transaction_key ) ) ? true : false;
	$webhook_sig_error_sandbox = give_get_option( 'give_authorize_sandbox_webhooks_no_signature_key' );
	$sanbox_api_keys           = ( ! empty( $payments->sandbox_login_id ) && ! empty( $payments->sandbox_transaction_key ) ) ? true : false;

	$notice_args = array(
		'id'          => 'give-authorize-missing-sig',
		'type'        => 'error',
		'description' => '',
		'dismissible' => false,
	);

	if ( $webhook_sig_error_live && ! give_is_test_mode() && $live_api_keys ) {
		$notice_args['description'] = sprintf(
			__( 'Give is having trouble creating the necessary Authorize.net Live webhooks that are necessary to communicate with the API. Please generate a signature key within the Authorize.net merchant interface to resolve the issue. <a href="%s" target="_blank">Click here for instructions &raquo;</a>', 'give-authorize' ),
			'http://docs.givewp.com/authorizenet-signature-key'
		);

		Give()->notices->register_notice( $notice_args );

	} elseif ( $webhook_sig_error_sandbox && give_is_test_mode() && $sanbox_api_keys ) {
		$notice_args['description'] = sprintf(
			__( 'Give is having trouble creating the necessary Authorize.net Sandbox webhooks that are necessary to communicate with the API. Please generate a signature key within the Authorize.net merchant interface to resolve the issue. <a href="%s" target="_blank">Click here for instructions &raquo;</a>', 'give-authorize' ),
			'http://docs.givewp.com/authorizenet-signature-key'
		);

		Give()->notices->register_notice( $notice_args );
	}

}

add_action( 'admin_init', 'give_authorize_admin_notices' );