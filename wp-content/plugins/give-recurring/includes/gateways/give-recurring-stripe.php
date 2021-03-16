<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Give_Recurring_Stripe
 */
class Give_Recurring_Stripe extends Give_Recurring_Gateway {

	/**
	 * Stripe API secret key.
	 *
	 * @var string
	 */
	private $secret_key;

	/**
	 * Stripe API public key.
	 *
	 * @var string
	 */
	private $public_key;

	/**
	 * Stripe Gateway Object.
	 *
	 * @var Give_Stripe_Gateway
	 */
	private $stripe_gateway;
	
	/**
	 * Stripe Customer Object.
	 *
	 * @since 1.8.9
	 *
	 * @var Give_Stripe_Customer
	 */
	private $stripe_customer;

	/**
	 * Get Stripe Started.
	 *
	 * @return bool
	 */
	public function init() {

		$this->id = 'stripe';

		if (
			is_plugin_active( GIVE_STRIPE_BASENAME ) &&
			! defined( 'GIVE_STRIPE_VERSION' )
		) {
			add_action( 'admin_notices', array( $this, 'old_api_upgrade_notice' ) );

			// No Stripe SDK. Bounce.
			return false;
		}

		$prefix = 'live_';
		if ( give_is_test_mode() ) {
			$prefix = 'test_';
		}

		$this->secret_key     = trim( give_get_option( $prefix . 'secret_key', '' ) );
		$this->public_key     = trim( give_get_option( $prefix . 'publishable_key', '' ) );
		$this->stripe_gateway = new Give_Stripe_Gateway();

		// Need the Stripe API class from here on.
		if ( ! class_exists( '\Stripe\Stripe' ) ) {
			return false;
		}

		add_action( 'give_pre_refunded_payment', array( $this, 'process_refund' ) );
		add_action( 'give_recurring_cancel_stripe_subscription', array( $this, 'cancel' ), 10, 2 );
		add_action( 'give_stripe_verify_3dsecure_payment', array( $this, 'record_3dsecure_signup' ), 10, 3 );

		// Remove Give's Stripe gateway webhook processing (we handle it here).
		global $give_stripe;
		remove_action( 'init', array( $give_stripe, 'stripe_event_listener' ) );
		remove_action( 'init', 'give_stripe_event_listener' );

		return true;

	}

	/**
	 * Create Payment Profiles.
	 *
	 * Setup customers and plans in Stripe for the sign up.
	 *
	 * @return void
	 */
	public function create_payment_profiles() {

		$source = ! empty( $_POST['give_stripe_source'] ) ? give_clean( $_POST['give_stripe_source'] ) : $this->generate_source_dictionary();
		$email  = $this->purchase_data['user_email'];
		
		// Convert token to source in case of stripe checkout.
		if ( give_is_stripe_checkout_enabled() ) {

			// In case of Stripe Checkout, $source_object is pretended as Token Object.
			$source_object = $this->stripe_gateway->get_token_details( $source );

			// Add source to donation notes and meta.
			give_insert_payment_note( $this->payment_id, 'Stripe Token ID: ' . $source_object->id );
			give_update_payment_meta( $this->payment_id, '_give_stripe_token_id', $source_object->id );
		} else {

			$source_object = $this->stripe_gateway->get_source_details( $source );

			// Add source to donation notes and meta.
			give_insert_payment_note( $this->payment_id, 'Stripe Source ID: ' . $source_object->id );
			give_update_payment_meta( $this->payment_id, '_give_stripe_source_id', $source_object->id );
		}

		$this->stripe_customer = new Give_Stripe_Customer( $email, $source_object->id );
		$stripe_customer      = $this->stripe_customer->customer_data;
		$stripe_customer_id   = $this->stripe_customer->get_id();

		// Add donation note for customer ID.
		if ( ! empty( $stripe_customer_id ) ) {
			give_insert_payment_note( $this->payment_id, 'Stripe Customer ID: ' . $stripe_customer_id );
		}

		// Save Stripe Customer ID into Donor meta.
		$this->stripe_gateway->save_stripe_customer_id( $stripe_customer_id, $this->payment_id );

		// Save customer id to donation.
		give_update_meta( $this->payment_id, '_give_stripe_customer_id', $stripe_customer_id );

		$plan_id = $this->get_or_create_stripe_plan( $this->subscriptions );

		// Add donation note for plan ID.
		if ( ! empty( $plan_id ) ) {
			give_insert_payment_note( $this->payment_id, 'Stripe Plan ID: ' . $plan_id );
		}

		// Save plan id to donation.
		give_update_meta( $this->payment_id, '_give_stripe_plan_id', $plan_id );

		$subscription  = $this->subscribe_customer_to_plan( $stripe_customer, $source_object, $plan_id );

		if ( ! give_is_stripe_checkout_enabled() && $this->stripe_gateway->is_3d_secure_required( $source_object ) ) {

			$source_object = $this->stripe_gateway->create_3d_secure_source( $this->payment_id, $source_object->id );

			// Add temporary data for 3d secure payments.
			give_update_meta( $this->payment_id, '_give_recurring_stripe_subscription_args', $this->subscriptions );
			give_update_meta( $this->payment_id, '_give_recurring_stripe_subscription_is_offsite', $this->offsite );

			$redirect_to = $source_object->redirect->url;

			// Add subscription id to redirect url, if exists.
			if ( $subscription->id ) {
				$redirect_to = add_query_arg( array(
					'subscription_id' => $subscription->id,
				), $redirect_to );
			}

			// Redirect to authorise payment after receiving 3D Secure Source Response.
			wp_redirect( $redirect_to );
			give_die();

		}
	}

	/**
	 * Subscribes a Stripe Customer to a plan.
	 *
	 * @param  \Stripe\Customer      $stripe_customer Stripe Customer Object.
	 * @param  string|\Stripe\Source $source          Stripe Source ID/Object.
	 * @param  string                $plan_id         Stripe Plan ID.
	 *
	 * @return bool|\Stripe\Subscription
	 */
	public function subscribe_customer_to_plan( $stripe_customer, $source, $plan_id ) {

		if ( $stripe_customer instanceof \Stripe\Customer ) {

			try {

				$default_source_id = $this->stripe_customer->is_card_exists
					? $this->stripe_customer->customer_data->default_source
					: $source->id;

				// Get metadata.
				$metadata = give_recurring_get_metadata( $this->purchase_data, $this->payment_id );

				$args = array(
					'customer' => $stripe_customer->id,
					'items'    => array(
						array(
							'plan' => $plan_id,
						),
					),
					'metadata' => $metadata,
				);

				if ( ! give_is_stripe_checkout_enabled() && $this->stripe_gateway->is_3d_secure_required( $source ) ) {

					// Create subscription with trail period if the payment is 3d secure.
					$renewal_date = date( 'c', strtotime( date( 'Y-m-d H:i:s' ) . ' +' . $this->subscriptions['frequency'] . ' ' . $this->subscriptions['period'] ) );
					$args['trial_end'] = strtotime( $renewal_date );
				} elseif ( ! give_is_stripe_checkout_enabled() ) {
					$args['default_source'] = $default_source_id;
				}

				$charge_options = array();

				// Stripe connected?
				if (
					function_exists( 'give_is_stripe_connected' )
					&& give_is_stripe_connected()
				) {
					$charge_options['stripe_account'] = give_get_option( 'give_stripe_user_id' );
				}
				$subscription                      = \Stripe\Subscription::create( $args, $charge_options );
				$this->subscriptions['profile_id'] = $subscription->id;

				return $subscription;

			} catch ( \Stripe\Error\Base $e ) {

				// There was an issue subscribing the Stripe customer to a plan.
				Give_Stripe_Logger::log_error( $e, $this->id );

			} catch ( Exception $e ) {

				// Something went wrong outside of Stripe.
				give_record_gateway_error(
					__( 'Stripe Error', 'give-recurring' ),
					sprintf(
						/* translators: %s Exception Message. */
						__( 'An error while subscribing a customer to a plan. Details: %s', 'give-recurring' ),
						$e->getMessage()
					)
				);
				give_set_error( 'Stripe Error', __( 'An error occurred while processing the donation. Please try again.', 'give-recurring' ) );
				give_send_back_to_checkout( '?payment-mode=stripe' );

			} // End try().
		} // End if().

		return false;
	}

	/**
	 * Process Stripe web hooks.
	 *
	 * Processes web hooks from the payment processor.
	 *
	 * @access public
	 * @since  1.0
	 *
	 * @return void
	 */
	public function process_webhooks() {

		// set webhook URL to: home_url( 'index.php?give-listener=' . $this->id );.
		if ( empty( $_GET['give-listener'] ) || $this->id !== $_GET['give-listener'] ) {
			return;
		}

		// Retrieve the request's body and parse it as JSON.
		$body       = @file_get_contents( 'php://input' );
		$event_json = json_decode( $body );

		// Process Stripe Event, if donation id exists.
		$result = $this->process_stripe_event( $event_json );

		if ( false === $result ) {
			$message = __( 'Something went wrong with processing the payment gateway event.', 'give-recurring' );
		} else {
			$message = sprintf(
				/* translators: 1. Processing result. */
				__( 'Processed event: %s', 'give-recurring' ),
				$result
			);
		}

		give_stripe_record_log(
			__( 'Stripe - Webhook Received', 'give-recurring' ),
			sprintf(
				/* translators: 1. Event ID 2. Event Type 3. Message */
				__( 'Webhook received with ID %1$s and TYPE %2$s which processed and returned a message %3$s.', 'give-recurring' ),
				$event_json->id,
				$event_json->type,
				$message
			)
		);

		status_header( 200 );
		exit( $message );
	}

	/**
	 * Process a Stripe Event.
	 *
	 * @param /Stripe/Event $event Stripe Event Object received via webhooks.
	 *
	 * @return bool|object
	 */
	public function process_stripe_event( $event ) {

		if ( empty( $event->type ) ) {
			return false;
		}

		switch ( $event->type ) {
			case 'invoice.payment_succeeded':
				$this->process_invoice_payment_succeeded_event( $event );
				break;
			case 'customer.subscription.deleted':
				$this->process_customer_subscription_deleted( $event );
				break;
			case 'charge.refunded':
				$this->process_charge_refunded_event( $event );
				break;
		}

		do_action( 'give_recurring_stripe_event_' . $event->type, $event );

		return $event->type;

	}

	/**
	 * Processes invoice.payment_succeeded event.
	 *
	 * @param \Stripe\Event $stripe_event Stripe Event received via webhooks.
	 *
	 * @return bool|Give_Subscription
	 */
	public function process_invoice_payment_succeeded_event( $stripe_event ) {

		// Bail out, if incorrect event type received.
		if ( 'invoice.payment_succeeded' !== $stripe_event->type ) {
			return false;
		}

		$invoice = $stripe_event->data->object;

		// Make sure we have an invoice object.
		if ( 'invoice' !== $invoice->object ) {
			return false;
		}

		$subscription_profile_id = $invoice->subscription;
		$subscription            = new Give_Subscription( $subscription_profile_id, true );
		
		// Check for subscription ID.
		if ( 0 === $subscription->id ) {
			return false;
		}

		$total_payments = intval( $subscription->get_total_payments() );
		$bill_times     = intval( $subscription->bill_times );

		if ( $this->can_cancel( false, $subscription ) ) {

			// If subscription is ongoing or bill_times is less than total payments.
			if ( 0 === $bill_times || $total_payments < $bill_times ) {

				// We have a new invoice payment for a subscription.
				$amount         = $this->cents_to_dollars( $invoice->total );
				$transaction_id = $invoice->charge;

				// Look to see if we have set the transaction ID on the parent payment yet.
				if ( ! $subscription->get_transaction_id() ) {
					// This is the initial transaction payment aka first subscription payment.
					$subscription->set_transaction_id( $transaction_id );

				} else {

					$donation_id = give_get_purchase_id_by_transaction_id( $transaction_id );

					// Check if donation id empty that means renewal donation not made so please create it.
					if ( empty( $donation_id ) ) {

						$args = array(
							'amount'         => $amount,
							'transaction_id' => $transaction_id,
							'post_date'      => date_i18n( 'Y-m-d H:i:s', $invoice->created ),
						);
						// We have a renewal.
						$subscription->add_payment( $args );
						$subscription->renew();
					}

					// Check if this subscription is complete.
					$this->is_subscription_completed( $subscription, $total_payments, $bill_times );

				}
			} else {

				$this->is_subscription_completed( $subscription, $total_payments, $bill_times );
			}

			return $subscription;

		} // End if().

		return false;

	}

	/**
	 * Process customer.subscription.deleted event posted to webhooks.
	 *
	 * @param  \Stripe\Event $stripe_event
	 *
	 * @return bool
	 */
	public function process_customer_subscription_deleted( $stripe_event ) {

		if ( $stripe_event instanceof \Stripe\Event ) {

			// Sanity Check
			if ( 'customer.subscription.deleted' !== $stripe_event->type ) {
				return false;
			}

			$subscription = $stripe_event->data->object;

			if ( 'subscription' === $subscription->object ) {

				$profile_id   = $subscription->id;
				$subscription = new Give_Subscription( $profile_id, true );

				// Sanity Check: Don't cancel already completed subscriptions or empty subscription objects
				if ( empty( $subscription ) || 'completed' === $subscription->status ) {

					return false;

				} else if ( 'cancelled' !== $subscription->status ) {

					// Cancel the sub
					$subscription->cancel();

					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Process charge.refunded \Stripe\Event
	 *
	 * @param  \Stripe\Event $stripe_event
	 *
	 * @return bool
	 */
	public function process_charge_refunded_event( $stripe_event ) {

		global $wpdb;

		if ( $stripe_event instanceof \Stripe\Event ) {

			if ( 'charge.refunded' != $stripe_event->type ) {
				return false;
			}

			$charge = $stripe_event->data->object;

			if ( 'charge' == $charge->object && $charge->refunded ) {
				$donation_meta_table_name = Give()->payment_meta->table_name;
				$donation_id_col_name     = Give()->payment_meta->get_meta_type() . '_id';

				$payment_id = $wpdb->get_var( $wpdb->prepare(
					"
						SELECT {$donation_id_col_name}
						FROM {$donation_meta_table_name}
						WHERE meta_key = '_give_payment_transaction_id'
						AND meta_value = %s LIMIT 1", $charge->id
				) );

				if ( $payment_id ) {

					give_update_payment_status( $payment_id, 'refunded' );
					give_insert_payment_note( $payment_id, __( 'Charge refunded in Stripe.', 'give-recurring' ) );

					return true;
				}
			}
		}

		return false;
	}


	/**
	 * Refund subscription charges and cancels the subscription if the parent donation triggered when refunding in wp-admin donation details.
	 *
	 * @access      public
	 * @since       1.1
	 *
	 * @param $payment Give_Payment
	 *
	 * @return      void
	 */
	public function process_refund( $payment ) {

		if ( empty( $_POST['give_refund_in_stripe'] ) ) {
			return;
		}
		$statuses = array( 'give_subscription', 'publish' );

		if ( ! in_array( $payment->old_status, $statuses ) ) {
			return;
		}

		if ( 'stripe' !== $payment->gateway ) {
			return;
		}

		switch ( $payment->old_status ) {

			case 'give_subscription' :

				// Refund renewal payment
				if ( empty( $payment->transaction_id ) || $payment->transaction_id == $payment->ID ) {

					// No valid charge ID
					return;
				}

				try {

					$refund = \Stripe\Refund::create( array(
						'charge' => $payment->transaction_id,
					) );

					$payment->add_note( sprintf( __( 'Charge %1$s refunded in Stripe. Refund ID: %1$s', 'give-recurring' ), $payment->transaction_id, $refund->id ) );

				} catch ( Exception $e ) {

					// some sort of other error
					$body = $e->getJsonBody();
					$err  = $body['error'];

					if ( isset( $err['message'] ) ) {
						$error = $err['message'];
					} else {
						$error = __( 'Something went wrong while refunding the charge in Stripe.', 'give-recurring' );
					}

					wp_die( $error, __( 'Error', 'give-recurring' ), array(
						'response' => 400,
					) );

				}

				break;

			case 'publish' :

				// Refund & cancel initial subscription donation.
				$db   = new Give_Subscriptions_DB();
				$subs = $db->get_subscriptions( array(
					'parent_payment_id' => $payment->ID,
					'number'            => 100,
				) );

				if ( empty( $subs ) ) {
					return;
				}

				foreach ( $subs as $subscription ) {

					try {

						$refund = \Stripe\Refund::create( array(
							'charge' => $subscription->transaction_id,
						) );

						$payment->add_note( sprintf( __( 'Charge %s refunded in Stripe.', 'give-recurring' ), $subscription->transaction_id ) );
						$payment->add_note( sprintf( __( 'Charge %1$s refunded in Stripe. Refund ID: %1$s', 'give-recurring' ), $subscription->transaction_id, $refund->id ) );

					} catch ( Exception $e ) {

						// some sort of other error
						$body = $e->getJsonBody();
						$err  = $body['error'];

						if ( isset( $err['message'] ) ) {
							$error = $err['message'];
						} else {
							$error = __( 'Something went wrong while refunding the charge in Stripe.', 'give-recurring' );
						}

						$payment->add_note( sprintf( __( 'Charge %1$s could not be refunded in Stripe. Error: %1$s', 'give-recurring' ), $subscription->transaction_id, $error ) );

					}

					// Cancel subscription.
					$this->cancel( $subscription, false );
					$subscription->cancel();
					$payment->add_note( sprintf( __( 'Subscription %d cancelled.', 'give-recurring' ), $subscription->id ) );

				}

				break;

		}// End switch().

	}

	/**
	 * Gets a stripe plan if it exists otherwise creates a new one.
	 *
	 * @param  array  $subscription The subscription array set at process_checkout before creating payment profiles.
	 * @param  string $return       if value 'id' is passed it returns plan ID instead of Stripe_Plan.
	 *
	 * @return string|\Stripe\Plan
	 */
	public function get_or_create_stripe_plan( $subscription, $return = 'id' ) {

		$stripe_plan_name = give_recurring_generate_subscription_name( $subscription['form_id'], $subscription['price_id'] );
		$stripe_plan_id   = $this->generate_stripe_plan_id( $stripe_plan_name, give_maybe_sanitize_amount( $subscription['recurring_amount'] ), $subscription['period'], $subscription['frequency'] );

		try {
			// Check if the plan exists already.
			$stripe_plan = \Stripe\Plan::retrieve( $stripe_plan_id );

		} catch ( Exception $e ) {

			// The plan does not exist, please create a new plan.
			$args = array(
				'amount'         => $this->dollars_to_cents( $subscription['recurring_amount'] ),
				'interval'       => $subscription['period'],
				'interval_count' => $subscription['frequency'],
				'currency'       => give_get_currency(),
				'id'             => $stripe_plan_id,
			);

			// Create a Subscription Product Object and Pass plan parameters as per the latest version of stripe api.
			$args['product'] = \Stripe\Product::create( array(
				'name'                 => $stripe_plan_name,
				'statement_descriptor' => give_get_stripe_statement_descriptor( $subscription ),
				'type'                 => 'service',
			) );

			$stripe_plan = $this->create_stripe_plan( $args );

		}

		if ( 'id' == $return ) {
			return $stripe_plan->id;
		} else {
			return $stripe_plan;
		}

	}

	/**
	 * Creates a Stripe Plan using the API.
	 *
	 * @param  array $args
	 *
	 * @return bool|\Stripe\Plan
	 */
	private function create_stripe_plan( $args = array() ) {

		$stripe_plan = false;

		try {

			$stripe_plan = \Stripe\Plan::create( $args );

		} catch ( \Stripe\Error\Base $e ) {

			// There was an issue creating the Stripe plan.
			Give_Stripe_Logger::log_error( $e, $this->id );

		} catch ( Exception $e ) {

			// Something went wrong outside of Stripe.
			give_record_gateway_error( __( 'Stripe Error', 'give-recurring' ), sprintf( __( 'The Stripe Gateway returned an error while creating a plan. Details: %s', 'give-recurring' ), $e->getMessage() ) );
			give_set_error( 'Stripe Error', __( 'An error occurred while processing the donation. Please try again.', 'give-recurring' ) );
			give_send_back_to_checkout( '?payment-mode=stripe' );

		}

		return $stripe_plan;
	}

	/**
	 * Generates source dictionary, used for testing purpose only.
	 *
	 * @param  array $card_info
	 *
	 * @return array
	 */
	public function generate_source_dictionary( $card_info = array() ) {

		if ( empty( $card_info ) ) {
			$card_info = $this->purchase_data['card_info'];
		}

		$card_info = array_map( 'trim', $card_info );
		$card_info = array_map( 'strip_tags', $card_info );

		return array(
			'object'    => 'card',
			'exp_month' => $card_info['card_exp_month'],
			'exp_year'  => $card_info['card_exp_year'],
			'number'    => $card_info['card_number'],
			'cvc'       => $card_info['card_cvc'],
			'name'      => $card_info['card_name'],
		);
	}


	/**
	 * Initial field validation before ever creating profiles or donors.
	 *
	 * Note: Please don't use this function. This function is for internal purposes only and can be removed
	 * anytime without notice.
	 *
	 * @access      public
	 * @since       1.0
	 *
	 * @param array $valid_data List of valid data.
	 * @param array $post_data  List of posted variables.
	 *
	 * @return      void
	 */
	public function validate_fields( $valid_data, $post_data ) {

		if (
			isset( $post_data['card_name'] ) &&
			empty( $post_data['card_name'] ) &&
			! isset( $post_data['is_payment_request'] )
		) {
			give_set_error( 'no_card_name', __( 'Please enter a name for the credit card.', 'give-recurring' ) );
		}

		if ( ! class_exists( '\Stripe\Stripe' ) ) {

			give_set_error( 'give_recurring_stripe_missing', __( 'The Stripe Gateway does not appear to be activated.', 'give-recurring' ) );
		}

		if ( empty( $this->public_key ) ) {

			give_set_error( 'give_recurring_stripe_public_missing', __( 'The Stripe publishable key must be entered in settings.', 'give-recurring' ) );
		}

		if ( empty( $this->secret_key ) ) {
			give_set_error( 'give_recurring_stripe_public_missing', __( 'The Stripe secret key must be entered in settings.', 'give-recurring' ) );
		}

	}

	/**
	 * Is Subscription Completed?
	 *
	 * After a sub renewal comes in from Stripe we check to see if total_payments
	 * is greater than or equal to bill_times; if it is, we cancel the stripe sub for the customer.
	 *
	 * @param Give_Subscription $subscription   Subscription object created for Give.
	 * @param int               $total_payments Total payment count.
	 * @param int               $bill_times     Total billed count.
	 *
	 * @return bool
	 */
	public function is_subscription_completed( $subscription, $total_payments, $bill_times ) {

		if ( $total_payments >= $bill_times && $bill_times != 0 ) {
			// Cancel subscription in stripe if the subscription has run its course.
			$this->cancel( $subscription, false );
			// Complete the subscription w/ the Give_Subscriptions class.
			$subscription->complete();
			return true;
		} else {
			return false;
		}

	}


	/**
	 * Can Cancel.
	 *
	 * @param $ret
	 * @param $subscription
	 *
	 * @return bool
	 */
	public function can_cancel( $ret, $subscription ) {

		$supported_gateways = array( 'stripe', 'stripe_ach' );

		if (
			in_array( $subscription->gateway, $supported_gateways ) &&
			! empty( $subscription->profile_id ) &&
			'active' === $subscription->status
		) {
			$ret = true;
		}

		return $ret;
	}

	/**
	 * Can update subscription CC details.
	 *
	 * @since 1.7
	 *
	 * @param bool   $ret
	 * @param object $subscription
	 *
	 * @return bool
	 */
	public function can_update( $ret, $subscription ) {

		if (
			'stripe' === $subscription->gateway
			&& ! empty( $subscription->profile_id )
			&& in_array( $subscription->status, array(
				'active',
				'failing',
			), true )
			&& ! give_is_setting_enabled( give_get_option( 'stripe_checkout_enabled' ) )
		) {
			return true;
		}

		return $ret;
	}

	/**
	 * Can update subscription details.
	 *
	 * @since 1.8
	 *
	 * @param bool   $ret
	 * @param object $subscription
	 *
	 * @return bool
	 */
	public function can_update_subscription( $ret, $subscription ) {

		if (
			'stripe' === $subscription->gateway
			&& ! empty( $subscription->profile_id )
			&& in_array( $subscription->status, array(
				'active',
			), true )
		) {
			return true;
		}

		return $ret;
	}

	/**
	 * Can Sync.
	 *
	 * @param $ret
	 * @param $subscription
	 *
	 * @return bool
	 */
	public function can_sync( $ret, $subscription ) {

		if (
			$subscription->gateway === $this->id
			&& ! empty( $subscription->profile_id )
		) {
			$ret = true;
		}

		return $ret;
	}

	/**
	 * Cancels a Stripe Subscription.
	 *
	 * @param  Give_Subscription $subscription Subscription Object.
	 * @param  bool              $now          If false, cancels subscription at end of period,
	 *                                         and If true, cancels immediately. Default true.
	 *
	 * @return bool
	 */
	public function cancel( $subscription, $now = true ) {

		try {

			// Proceed now as Stripe customer id exists.
			$stripe_sub = \Stripe\Subscription::retrieve( $subscription->profile_id );

			if ( $now ) {

				// Cancel Subscription immediately from stripe.
				$stripe_sub->cancel();
			} else {

				// Cancel Subscription after period end from stripe.
				$stripe_sub->cancel_at_period_end = true;
				$stripe_sub->save();
			}

			return true;

		} catch ( \Stripe\Error\Base $e ) {

			// There was an issue cancelling the subscription w/ Stripe.
			give_record_gateway_error(
				__( 'Stripe Error', 'give-recurring' ),
				sprintf(
					/* translators: 1. Error Message. */
					__( 'The Stripe Gateway returned an error while cancelling a subscription. Details: %s', 'give-recurring' ),
					$e->getMessage()
				)
			);
			give_set_error( 'Stripe Error', __( 'An error occurred while cancelling the donation. Please try again.', 'give-recurring' ) );

			return false;

		} catch ( Exception $e ) {

			// Something went wrong outside of Stripe.
			give_record_gateway_error(
				__( 'Stripe Error', 'give-recurring' ),
				sprintf(
					/* translators: 1. Error Message. */
					__( 'The Stripe Gateway returned an error while cancelling a subscription. Details: %s', 'give-recurring' ),
					$e->getMessage()
				)
			);
			give_set_error( 'Stripe Error', __( 'An error occurred while cancelling the donation. Please try again.', 'give-recurring' ) );

			return false;

		} // End try().

	}

	/**
	 * Stripe Recurring Customer ID.
	 *
	 * The Give Stripe gateway stores it's own customer_id so this method first checks for that, if it exists.
	 * If it does it will return that value. If it does not it will return the recurring gateway value.
	 *
	 * @param string $user_email Donor Email.
	 *
	 * @return string The donor's Stripe customer ID.
	 */
	public function get_stripe_recurring_customer_id( $user_email ) {

		// First check user meta to see if they have made a previous donation
		// w/ Stripe via non-recurring donation so we don't create a duplicate Stripe customer for recurring.
		$customer_id = give_stripe_get_customer_id( $user_email );

		// If no data found check the subscribers profile to see if there's a recurring ID already.
		if ( empty( $customer_id ) ) {

			$subscriber = new Give_Recurring_Subscriber( $user_email );

			$customer_id = $subscriber->get_recurring_donor_id( $this->id );
		}

		return $customer_id;

	}

	/**
	 * Generates a plan ID to be used with Stripe.
	 *
	 * @param  string $subscription_name Name of the subscription generated from
	 *                                   give_recurring_generate_subscription_name.
	 * @param  string $recurring_amount  Recurring amount specified in the form.
	 * @param  string $period            Can be either 'day', 'week', 'month' or 'year'. Set from form.
	 * @param  int    $frequency         Can be either 1,2,..6 Set from form.
	 *
	 * @return string
	 */
	public function generate_stripe_plan_id( $subscription_name, $recurring_amount, $period, $frequency ) {
		$subscription_name = sanitize_title( $subscription_name );

		return sanitize_key( $subscription_name . '_' . $recurring_amount . '_' . $period . '_' . $frequency );
	}

	/**
	 * Converts Cents to Dollars
	 *
	 * @param  string $cents
	 *
	 * @return string
	 */
	public function cents_to_dollars( $cents ) {
		return ( $cents / 100 );
	}

	/**
	 * Converts Dollars to Cents
	 *
	 * @param  string $dollars
	 *
	 * @return string
	 */
	public function dollars_to_cents( $dollars ) {
		return round( $dollars, give_currency_decimal_filter() ) * 100;
	}


	/**
	 * Upgrade notice.
	 *
	 * Tells the admin that they need to upgrade the Stripe gateway.
	 *
	 * @since 1.2
	 */
	public function old_api_upgrade_notice() {
		$message = sprintf( __( '<strong>Attention:</strong> The Recurring Donations plugin requires the latest version of the Stripe gateway add-on to process donations properly. Please update to the latest version of Stripe to resolve this issue. If your license is active you should see the update available in WordPress. Otherwise, you can access the latest version by <a href="%1$s" target="_blank">logging into your account</a> and visiting <a href="%1$s" target="_blank">your downloads</a> page on the Give website.', 'give-recurring' ), 'https://givewp.com/wp-login.php', 'https://givewp.com/my-account/#tab_downloads' );
		if ( class_exists( 'Give_Notices' ) ) {
			Give()->notices->register_notice( array(
				'id'          => 'give-activation-error',
				'type'        => 'error',
				'description' => $message,
				'show'        => true,
			) );
		} else {
			$class = 'notice notice-error';
			printf( '<div class="%1$s"><p>%2$s</p></div>', $class, $message );
		}
	}


	/**
	 * Get Stripe Subscription.
	 *
	 * @param $stripe_subscription_id
	 *
	 * @return mixed
	 */
	public function get_stripe_subscription( $stripe_subscription_id ) {

		$stripe_subscription = \Stripe\Subscription::retrieve( $stripe_subscription_id );

		return $stripe_subscription;

	}

	/**
	 * Get gateway subscription.
	 *
	 * @param $subscription
	 *
	 * @return bool|mixed
	 */
	public function get_gateway_subscription( $subscription ) {

		if ( $subscription instanceof Give_Subscription ) {

			$stripe_subscription_id = $subscription->profile_id;

			$stripe_subscription = $this->get_stripe_subscription( $stripe_subscription_id );

			return $stripe_subscription;
		}

		return false;
	}

	/**
	 * Get subscription details.
	 *
	 * @param Give_Subscription $subscription
	 *
	 * @return array|bool
	 */
	public function get_subscription_details( $subscription ) {

		$stripe_subscription = $this->get_gateway_subscription( $subscription );
		if ( false !== $stripe_subscription ) {

			$subscription_details = array(
				'status'         => $stripe_subscription->status,
				'created'        => $stripe_subscription->created,
				'billing_period' => $stripe_subscription->plan->interval,
				'frequency'      => $stripe_subscription->plan->interval_count,
			);

			return $subscription_details;
		}

		return false;
	}

	/**
	 * Get transactions.
	 *
	 * @param  Give_Subscription $subscription
	 * @param string             $date
	 *
	 * @return array
	 */
	public function get_gateway_transactions( $subscription, $date = '' ) {

		$subscription_invoices = $this->get_invoices_for_give_subscription( $subscription, $date = '' );
		$transactions          = array();

		foreach ( $subscription_invoices as $invoice ) {

			$transactions[] = array(
				'amount'         => $this->cents_to_dollars( $invoice->amount_due ),
				'date'           => $invoice->created,
				'transaction_id' => $invoice->charge,
			);
		}

		return $transactions;
	}

	/**
	 * Get invoices for a Give subscription.
	 *
	 * @param Give_Subscription $subscription
	 * @param string            $date
	 *
	 * @return array
	 */
	private function get_invoices_for_give_subscription( $subscription, $date = '' ) {
		$subscription_invoices = array();

		if ( $subscription instanceof Give_Subscription ) {

			$stripe_subscription_id = $subscription->profile_id;
			$stripe_customer_id     = $this->get_stripe_recurring_customer_id( $subscription->donor->email );
			$subscription_invoices  = $this->get_invoices_for_subscription( $stripe_customer_id, $stripe_subscription_id, $date );
		}

		return $subscription_invoices;
	}

	/**
	 * Get invoices for subscription.
	 *
	 * @param $stripe_customer_id
	 * @param $stripe_subscription_id
	 * @param $date
	 *
	 * @return array
	 */
	public function get_invoices_for_subscription( $stripe_customer_id, $stripe_subscription_id, $date ) {
		$subscription_invoices = array();
		$invoices              = $this->get_invoices_for_customer( $stripe_customer_id, $date );

		foreach ( $invoices as $invoice ) {
			if ( $invoice->subscription == $stripe_subscription_id ) {
				$subscription_invoices[] = $invoice;
			}
		}

		return $subscription_invoices;
	}

	/**
	 * Get invoices for Stripe customer.
	 *
	 * @param string $stripe_customer_id
	 * @param string $date
	 *
	 * @return array|bool
	 */
	private function get_invoices_for_customer( $stripe_customer_id = '', $date = '' ) {
		$args     = array(
			'limit' => 100,
		);
		$has_more = true;
		$invoices = array();

		if ( ! empty( $date ) ) {
			$date_timestamp = strtotime( $date );
			$args['date']   = array(
				'gte' => $date_timestamp,
			);
		}

		if ( ! empty( $stripe_customer_id ) ) {
			$args['customer'] = $stripe_customer_id;
		}

		while ( $has_more ) {
			try {
				$collection             = \Stripe\Invoice::all( $args );
				$invoices               = array_merge( $invoices, $collection->data );
				$has_more               = $collection->has_more;
				$last_obj               = end( $invoices );
				$args['starting_after'] = $last_obj->id;

			} catch ( \Stripe\Error\Base $e ) {

				Give_Stripe_Logger::log_error( $e, $this->id );

				return false;

			} catch ( Exception $e ) {

				// Something went wrong outside of Stripe.
				give_record_gateway_error( __( 'Stripe Error', 'give-recurring' ), sprintf( __( 'The Stripe Gateway returned an error while getting invoices a Stripe customer. Details: %s', 'give-recurring' ), $e->getMessage() ) );

				return false;

			}
		}

		return $invoices;
	}


	/**
	 * Link the recurring profile in Stripe.
	 *
	 * @since  1.4
	 *
	 * @param  string $profile_id   The recurring profile id.
	 * @param  object $subscription The Subscription object.
	 *
	 * @return string               The link to return or just the profile id.
	 */
	public function link_profile_id( $profile_id, $subscription ) {

		if ( ! empty( $profile_id ) ) {
			$payment    = new Give_Payment( $subscription->parent_payment_id );
			$html       = '<a href="%s" target="_blank">' . $profile_id . '</a>';
			$base_url   = 'live' === $payment->mode ? 'https://dashboard.stripe.com/' : 'https://dashboard.stripe.com/test/';
			$link       = esc_url( $base_url . 'subscriptions/' . $profile_id );
			$profile_id = sprintf( $html, $link );
		}

		return $profile_id;

	}

	/**
	 * Outputs the payment method update form
	 *
	 * @since  1.7
	 *
	 * @param  Give_Subscription $subscription The subscription object
	 *
	 * @return void
	 */
	public function update_payment_method_form( $subscription ) {

		if ( $subscription->gateway !== $this->id ) {
			return;
		}

		// give_stripe_credit_card_form() only shows when Stripe Checkout is enabled so we fake it
		add_filter( 'give_get_option_stripe_checkout', '__return_false' );

		// Remove Billing address fields.
		if ( has_action( 'give_after_cc_fields', 'give_default_cc_address_fields' ) ) {
			remove_action( 'give_after_cc_fields', 'give_default_cc_address_fields', 10 );
		}

		$form_id           = ! empty( $subscription->form_id ) ? absint( $subscription->form_id ) : 0;
		$args['id_prefix'] = "$form_id-1";
		give_stripe_credit_card_form( $form_id, $args, $echo = true );

	}

	/**
	 * Process the update payment form
	 *
	 * @since  1.7
	 *
	 * @param  Give_Recurring_Subscriber $subscriber   Give_Recurring_Subscriber
	 * @param  Give_Subscription         $subscription Give_Subscription
	 *
	 * @return void
	 */
	public function update_payment_method( $subscriber, $subscription ) {
		
		// Check for any existing errors.
		$errors = give_get_errors();
		
		if ( empty( $errors ) ) {

			$source_id   = ! empty( $_POST['give_stripe_source'] ) ? give_clean( $_POST['give_stripe_source'] ) : 0;
			$customer_id = Give()->donor_meta->get_meta( $subscriber->id, give_stripe_get_customer_key(), true );
			
			// We were unable to retrieve the customer ID from meta so let's pull it from the API
			try {
				
				$stripe_subscription = \Stripe\Subscription::retrieve( $subscription->profile_id );
				
			} catch ( Exception $e ) {
				
				give_set_error( 'give_recurring_stripe_error', $e->getMessage() );
				return;
			}
			
			// If customer id doesn't exist, take the customer id from subscription.
			if ( empty( $customer_id ) ) {
				$customer_id = $stripe_subscription->customer;
			}
			
			try {
				
				$stripe_customer = \Stripe\Customer::retrieve( $customer_id );
			} catch ( Exception $e ) {
				
				give_set_error( 'give-recurring-stripe-customer-retrieval-error', $e->getMessage() );
				return;
			}

			// No errors in stripe, continue on through processing
			try {

				if ( $source_id ) {
					$card               = $stripe_customer->sources->create( array( 'source' => $source_id ) );
					$stripe_customer->default_source = $card->id;
					$stripe_subscription->default_source = $card->id;
				} else if ( isset( $_POST['give_stripe_existing_card'] ) ) {
					$stripe_customer->default_source = give_clean( $_POST['give_stripe_existing_card'] );
					$stripe_subscription->default_source = give_clean( $_POST['give_stripe_existing_card'] );
				}
				
				// Save the updated subscription details.
				$stripe_subscription->save();
				
				// Save the updated customer details.
				$stripe_customer->save();

			} catch ( \Stripe\Error\Card $e ) {

				$body = $e->getJsonBody();
				$err  = $body['error'];

				if ( isset( $err['message'] ) ) {
					give_set_error( 'payment_error', $err['message'] );
				} else {
					give_set_error( 'payment_error', __( 'There was an error processing your payment, please ensure you have entered your card number correctly.', 'give-recurring' ) );
				}

			} catch ( \Stripe\Error\ApiConnection $e ) {

				$body = $e->getJsonBody();
				$err  = $body['error'];

				if ( isset( $err['message'] ) ) {
					give_set_error( 'payment_error', $err['message'] );
				} else {
					give_set_error( 'payment_error', __( 'There was an error processing your payment (Stripe\'s API is down), please try again', 'give-recurring' ) );
				}

			} catch ( \Stripe\Error\InvalidRequest $e ) {

				$body = $e->getJsonBody();
				$err  = $body['error'];

				// Bad Request of some sort. Maybe Christoff was here ;)
				if ( isset( $err['message'] ) ) {
					give_set_error( 'request_error', $err['message'] );
				} else {
					give_set_error( 'request_error', __( 'The Stripe API request was invalid, please try again', 'give-recurring' ) );
				}

			} catch ( \Stripe\Error\Api $e ) {

				$body = $e->getJsonBody();
				$err  = $body['error'];

				if ( isset( $err['message'] ) ) {
					give_set_error( 'request_error', $err['message'] );
				} else {
					give_set_error( 'request_error', __( 'The Stripe API request was invalid, please try again', 'give-recurring' ) );
				}

			} catch ( \Stripe\Error\Authentication $e ) {

				$body = $e->getJsonBody();
				$err  = $body['error'];

				// Authentication error. Stripe keys in settings are bad.
				if ( isset( $err['message'] ) ) {
					give_set_error( 'request_error', $err['message'] );
				} else {
					give_set_error( 'api_error', __( 'The API keys entered in settings are incorrect', 'give-recurring' ) );
				}

			} catch ( Exception $e ) {
				give_set_error( 'update_error', __( 'There was an error with this payment method. Please try with another card.', 'give-recurring' ) );
			}

		}

	}

	/**
	 * Process the update payment form.
	 *
	 * @since  1.8
	 *
	 * @param  Give_Recurring_Subscriber $subscriber   Give_Recurring_Subscriber
	 * @param  Give_Subscription         $subscription Give_Subscription
	 *
	 * @return void
	 */
	public function update_subscription( $subscriber, $subscription ) {
		// Sanitize the values submitted with donation form.
		$post_data = give_clean( $_POST ); // WPCS: input var ok, sanitization ok, CSRF ok.

		// Get update renewal amount.
		$renewal_amount           = isset( $post_data['give-amount'] ) ? give_maybe_sanitize_amount( $post_data['give-amount'] ) : 0;
		$current_recurring_amount = give_maybe_sanitize_amount( $subscription->recurring_amount );
		$check_amount             = number_format( $renewal_amount, 0 );

		// Set error if renewal amount not valid.
		if (
			empty( $check_amount ) ||
			$renewal_amount === $current_recurring_amount
		) {
			give_set_error( 'give_recurring_invalid_subscription_amount', __( 'Please enter the valid subscription amount.', 'give-recurring' ) );
		}

		// Is errors?
		$errors = give_get_errors();

		if ( empty( $errors ) ) {
			$this->update_subscription_plan( $subscription, $renewal_amount );
		}
	}

	/**
	 * Update Stripe Subscription plan.
	 *
	 * @since 1.8
	 *
	 * @param \Give_Subscription $subscription
	 * @param int                $renewal_amount
	 */
	private function update_subscription_plan( $subscription, $renewal_amount ) {
		$stripe_plan_name = give_recurring_generate_subscription_name( $subscription->form_id, $subscription->price_id );
		$stripe_plan_id   = $this->generate_stripe_plan_id( $stripe_plan_name, $renewal_amount, $subscription->period, $subscription->frequency );

		try {

			// The plan does not exist, please create a new plan.
			$args = array(
				'amount'         => $this->dollars_to_cents( $renewal_amount ),
				'interval'       => $subscription->period,
				'interval_count' => $subscription->frequency,
				'currency'       => give_get_currency(),
				'id'             => $stripe_plan_id,
			);

			// Create a Subscription Product Object and Pass plan parameters as per the latest version of stripe api.
			$args['product'] = \Stripe\Product::create( array(
				'name'                 => $stripe_plan_name,
				'statement_descriptor' => give_get_stripe_statement_descriptor( $subscription ),
				'type'                 => 'service',
			) );

			$stripe_plan = false;

			try {

				$stripe_plan = \Stripe\Plan::create( $args );

			} catch ( \Stripe\Error\Base $e ) {

				$body = $e->getJsonBody();
				$err  = $body['error'];

				if ( isset( $err['message'] ) ) {
					give_set_error( 'stripe_error', $err['message'] );
				} else {
					give_set_error( 'stripe_error', __( 'There was an issue creating the Stripe plan.', 'give-recurring' ) );
				}

			} catch ( Exception $e ) {

				// Something went wrong outside of Stripe.
				give_set_error( 'Stripe Error', __( 'An error occurred while processing the donation. Please try again.', 'give-recurring' ) );
			}

			if ( isset( $stripe_plan ) && is_object( $stripe_plan ) ) {
				// get stripe subscription.
				$stripe_subscription = \Stripe\Subscription::retrieve( $subscription->profile_id );

				if (
					isset( $stripe_subscription->items->data[0]->id )
					&& isset( $stripe_plan->id )
				) {
					$stripe_subscription->update( $subscription->profile_id, array(
							'items'   => array(
								array(
									'id'   => $stripe_subscription->items->data[0]->id,
									'plan' => $stripe_plan->id
								)
							),
							'prorate' => false,
						)
					);

					$stripe_subscription->save();
				} else {
					give_set_error( 'give_recurring_stripe_subscription_update', __( 'Problem in Stripe subscription update.', 'give-recurring' ) );
				}
			}
		} catch ( Exception $e ) {
			give_set_error( 'give_recurring_update_subscription_amount', __( 'Problem in update subscription amount.', 'give-recurring' ) );
		}
	}

	/**
	 * This function will record subscriptions processed using Stripe 3D secure payments.
	 *
	 * @todo   add post payment profile action hook if required in future.
	 *
	 * @param int            $donation_id Donation ID.
	 * @param \Stripe\Charge $charge      Stripe Charge Object.
	 * @param string         $customer_id Stripe Customer ID.
	 *
	 * @since  2.1
	 * @access public
	 */
	public function record_3dsecure_signup( $donation_id, $charge, $customer_id ) {

		// Proceed only, if donation is recurring.
		if ( give_get_meta( $donation_id, '_give_is_donation_recurring', true ) ) {

			// Set subscription_payment.
			give_update_meta( $donation_id, '_give_subscription_payment', true );

			// Retrieve temporary data for 3d secure payments.
			$subscription_args = give_get_payment_meta( $donation_id, '_give_recurring_stripe_subscription_args', true );
			$offsite           = give_get_payment_meta( $donation_id, '_give_recurring_stripe_subscription_is_offsite', true );

			// Now create the subscription record.
			$subscriber = new Give_Recurring_Subscriber( $customer_id );

			if ( isset( $subscription_args['status'] ) ) {
				$status = $subscription_args['status'];
			} else {
				$status = $offsite ? 'pending' : 'active';
			}

			// Set Subscription frequency.
			$frequency = ! empty( $subscription_args['frequency'] ) ? intval( $subscription_args['frequency'] ) : 1;

			$args = array(
				'form_id'           => give_get_payment_form_id( $donation_id ),
				'parent_payment_id' => $donation_id,
				'status'            => $status,
				'period'            => $subscription_args['period'],
				'frequency'         => $frequency,
				'initial_amount'    => $subscription_args['initial_amount'],
				'recurring_amount'  => $subscription_args['recurring_amount'],
				'bill_times'        => $subscription_args['bill_times'],
				'expiration'        => $subscriber->get_new_expiration( $subscription_args['id'], $subscription_args['price_id'], $frequency ),
				'profile_id'        => $subscription_args['profile_id'],
				'transaction_id'    => $subscription_args['transaction_id'],
			);

			// Support user_id if it is present is purchase_data.
			if ( isset( $this->purchase_data['user_info']['id'] ) ) {
				$args['user_id'] = '';
			}

			$subscriber->add_subscription( $args );

			if ( ! $offsite ) {
				// Offsite payments get verified via a webhook so are completed in webhooks().
				give_update_payment_status( $donation_id, 'publish' );
			}

			// Delete temporary data required for successful 3d secure payments.
			give_delete_meta( $donation_id, '_give_recurring_stripe_subscription_args', true );
			give_delete_meta( $donation_id, '_give_recurring_stripe_subscription_is_offsite', true );

		}
	}
}

new Give_Recurring_Stripe();
