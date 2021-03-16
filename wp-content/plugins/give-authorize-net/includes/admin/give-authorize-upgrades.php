<?php
/**
 * Authorize.net Upgrades
 *
 * @package     Give
 * @copyright   Copyright (c) 2017, GiveWP
 * @license     https://opensource.org/licenses/gpl-license GNU Public License
 * @since       1.2
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Display Upgrade Notices
 *
 * @since 1.0
 * @since 1.3 Update new update process code.
 *
 * @param Give_Updates $give_updates
 *
 * @return void
 */
function give_authorize_show_upgrade_notices( $give_updates ) {

	// v1.8.12 Upgrades
	$give_updates->register(
		array(
			'id'       => 'v13_standardize_authorize_gateway',
			'version'  => '1.8.16',
			'callback' => 'give_v13_standardize_authorize_gateway',
		)
	);
}

add_action( 'give_register_updates', 'give_authorize_show_upgrade_notices' );


/**
 * Standardizes the gateway ID to be 'authorize'.
 *
 * @since      1.3
 */
function give_v13_standardize_authorize_gateway() {

	/* @var Give_Updates $give_updates */
	$give_updates = Give_Updates::get_instance();

	// Query payments.
	$payments = new WP_Query( array(
			'paged'          => $give_updates->step,
			'post_status'    => 'any',
			'order'          => 'ASC',
			'post_type'      => 'give_payment',
			'meta_key'       => '_give_payment_gateway',
			'meta_value'     => 'authorizenet',
			'fields'         => 'ids',
			'posts_per_page' => 20,
		)
	);

	if ( $payments->have_posts() ) {

		$give_updates->set_percentage( $payments->found_posts, ( $give_updates->step * 20 ) );

		while ( $payments->have_posts() ) {

			$payments->the_post();
			$payment_id = get_the_ID();

			// Set this payments
			give_update_payment_meta( $payment_id, '_give_payment_gateway', 'authorize' );
		}

	} else {
		// The Update Ran.
		give_set_upgrade_complete( 'v13_standardize_authorize_gateway' );
	}

}

/**
 * Auto setup webhooks for upgrading customers.
 *
 * @since 1.3
 *
 * @return bool
 */
function give_authorize_auto_setup_webhooks() {

	if ( ! class_exists( 'Give' ) || ! function_exists( 'give_has_upgrade_completed' ) ) {
		return false;
	}

	$checked = give_has_upgrade_completed( 'give_authorize_v13_webhook_autosetup' );

	// Don't recheck.
	if ( $checked ) {
		return;
	}

	if ( ! empty( Give_Authorize()->payments->live_login_id ) && ! empty( Give_Authorize()->payments->live_transaction_key ) ) {
		// We have API keys, and webhooks are not setup. So set them up.
		Give_Authorize()->payments->setup_webhooks( Give_Authorize()->payments->live_login_id, Give_Authorize()->payments->live_transaction_key, 0 );
	}

	if ( ! empty( Give_Authorize()->payments->sandbox_login_id ) && ! empty( Give_Authorize()->payments->sandbox_transaction_key ) ) {
		// We have API keys, and webhooks are not setup. So set them up.
		Give_Authorize()->payments->setup_webhooks( Give_Authorize()->payments->sandbox_login_id, Give_Authorize()->payments->sandbox_transaction_key, 1 );
	}

	give_set_upgrade_complete( 'give_authorize_v13_webhook_autosetup' );

}

add_action( 'admin_init', 'give_authorize_auto_setup_webhooks' );
