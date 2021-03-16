<?php
/**
 * Give_Authorize_Payments
 *
 * @package     Give
 * @copyright   Copyright (c) 2017, GiveWP
 * @license     https://opensource.org/licenses/gpl-license GNU Public License
 * @since       1.1
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Give_Authorize_Payments
 */
class Give_Authorize_Payments {

	/**
	 * @var string
	 */
	var $live_login_id;

	/**
	 * @var string
	 */
	var $live_transaction_key;

	/**
	 * Whether the webhooks have been setup.
	 *
	 * @var bool
	 */
	var $live_webhooks_setup;

	/**
	 * @var string
	 */
	var $sandbox_login_id;

	/**
	 * @var string
	 */
	var $sandbox_transaction_key;

	/**
	 * Whether the webhooks have been setup.
	 *
	 * @var bool
	 */
	var $sandbox_webhooks_setup;

	/**
	 * Give_Authorize_Payments constructor.
	 */
	public function __construct() {

		require GIVE_AUTHORIZE_PLUGIN_DIR . '/vendor/autoload.php';

		$this->live_login_id        = give_get_option( 'give_api_login' );
		$this->live_transaction_key = give_get_option( 'give_transaction_key' );
		$this->live_webhooks_setup  = give_get_option( 'give_authorize_live_webhooks_setup' );

		$this->sandbox_login_id        = give_get_option( 'give_authorize_sandbox_api_login' );
		$this->sandbox_transaction_key = give_get_option( 'give_authorize_sandbox_transaction_key' );
		$this->sandbox_webhooks_setup  = give_get_option( 'give_authorize_sandbox_webhooks_setup' );

		$this->hooks();
	}


	/**
	 * Setup hooks.
	 */
	public function hooks() {

		add_action( 'wp_ajax_check_authorize_webhooks', array( $this, 'ajax_check_authorize_webhooks' ) );
		add_action( 'wp_ajax_hard_check_authorize_webhooks', array( $this, 'ajax_force_recheck_webhooks' ) );

		add_action( 'parse_request', array( $this, 'authorize_event_listener' ) );

		add_action( 'give_gateway_authorize', array( $this, 'process_payment' ), 10, 1 );

		add_action( 'give_gateway_authorize_echeck', array( $this, 'process_echeck_payment' ), 10, 1 );

		add_action( 'give_authorize_echeck_cc_form', array( $this, 'bank_billing_fields' ), 10, 1 );

		add_action( 'give_authorize_cc_form', array( $this, 'optional_billing_fields' ), 10, 1 );

		add_filter( 'give_payment_details_transaction_id-authorize', array( $this, 'link_transaction_id' ), 10, 2 );

		add_filter( 'give_payment_details_transaction_id-authorize_echeck', array(
			$this,
			'link_transaction_id'
		), 10, 2 );

		add_action( 'give_update_payment_status', array( $this, 'process_admin_refund' ), - 1, 3 );
	}

	/**
	 * Sets up the API Request.
	 *
	 * @return  \JohnConde\Authnet\AuthnetJsonResponse object|bool
	 */
	function setup_api_request() {

		$missing_key_msg       = __( 'There was a problem processing this donation. Please contact the site administrator for assistance.', 'give-authorize' );
		$missing_key_admin_msg = __( 'An Authorize.net transaction was attempted without entering the proper API credentials within the plugin settings.', 'give-authorize' );

		try {
			if ( give_is_test_mode() ) {

				// Keys = mucho necessario.
				if ( empty( $this->sandbox_login_id ) || empty( $this->sandbox_transaction_key ) ) {
					give_record_gateway_error( esc_html__( 'Authorize.net Error', 'give-authorize' ), $missing_key_admin_msg );
					give_set_error( 'give-authorize-missing-keys', $missing_key_msg );
					give_send_back_to_checkout( '?payment-mode=authorize' );

					return false;
				}

				$request = \JohnConde\Authnet\AuthnetApiFactory::getJsonApiHandler( $this->sandbox_login_id, $this->sandbox_transaction_key,
					1 );

			} else {

				// We need keys to continue
				if ( empty( $this->live_login_id ) || empty( $this->live_transaction_key ) ) {
					give_record_gateway_error( esc_html__( 'Authorize.net Error', 'give-authorize' ), $missing_key_admin_msg );
					give_set_error( 'give-authorize-missing-keys', $missing_key_msg );
					give_send_back_to_checkout( '?payment-mode=authorize' );

					return false;
				}

				$request = \JohnConde\Authnet\AuthnetApiFactory::getJsonApiHandler( $this->live_login_id, $this->live_transaction_key,
					0 );

			}

			return $request;

		} catch ( \Exception $e ) {

			give_record_gateway_error( esc_html__( 'Authorize.net Error', 'give-authorize' ), $e->getMessage() );
			give_set_error( 'error_id_here', __( 'Missing API Login or Transaction key. Please enter them in the plugin settings.', 'give-authorize' ) );

			return false;

		}// End try().

	}

	/**
	 * AJAX check webhooks.
	 */
	function ajax_check_authorize_webhooks() {

		$data = array(
			'live_webhooks_setup'    => false,
			'sandbox_webhooks_setup' => false,
		);

		// Are LIVE webhooks already setup?
		if ( empty( $this->live_login_id ) || empty( $this->live_transaction_key ) ) {
			// No API keys.
			$data['live_webhooks_setup'] = 'unconfigured';
		} elseif ( give_is_test_mode() && $this->live_webhooks_setup ) {
			// Already configured.
			$data['live_webhooks_setup'] = true;
		} elseif ( ! empty( $this->live_login_id ) && ! empty( $this->live_transaction_key ) ) {
			// We have API keys, and webhooks are not setup. So set them up.
			$data['live_webhooks_setup'] = $this->setup_webhooks( $this->live_login_id, $this->live_transaction_key, 0 );
		}

		// Are SANDBOX webhooks already setup?
		if ( empty( $this->sandbox_login_id ) || empty( $this->sandbox_transaction_key ) ) {
			// No API keys.
			$data['sandbox_webhooks_setup'] = 'unconfigured';
		} elseif ( give_is_test_mode() && $this->sandbox_webhooks_setup ) {
			// Already configured.
			$data['sandbox_webhooks_setup'] = true;
		} elseif ( ! empty( $this->sandbox_login_id ) && ! empty( $this->sandbox_transaction_key ) ) {
			// We have API keys, and webhooks are not setup. So set them up.
			$data['sandbox_webhooks_setup'] = $this->setup_webhooks( $this->sandbox_login_id, $this->sandbox_transaction_key, 1 );
		}

		wp_send_json_success( $data );

		wp_die();

	}

	/**
	 * Force recheck of webhooks.
	 *
	 * @since 1.3
	 */
	public function ajax_force_recheck_webhooks() {

		give_delete_option( 'give_authorize_live_webhooks_setup' );
		give_delete_option( 'give_authorize_sandbox_webhooks_setup' );

		$this->ajax_check_authorize_webhooks();
	}

	/**
	 * Setup webhooks with Authorize.net and website.
	 *
	 * @see   http://www.johnconde.net/blog/handling-authorize-net-webhooks-with-php/
	 *
	 * @since 1.3
	 *
	 * @param string $login_id
	 * @param string $transaction_key
	 * @param int    $server 0 = production, 1 = sandbox/dev
	 *
	 * @return bool|string
	 */
	function setup_webhooks( $login_id, $transaction_key, $server ) {

		$listener_url = home_url( '/give-authorize-webhook-listener/' );

		try {

			$request = \JohnConde\Authnet\AuthnetApiFactory::getWebhooksHandler( $login_id, $transaction_key, $server );

			// Get AuthnetWebhooksResponse object.
			$webhooks_response = $request->getWebhooks();

			// Get a list of webhooks from the AuthnetWebhooksResponse object.
			$webhooks_check = $webhooks_response->getWebhooks();

			// Check if webhooks have already been configured for this URL.
			foreach ( $webhooks_check as $webhook ) {

				// Already configured. Bounce out.
				if ( $webhook->getUrl() === $listener_url ) {
					// Successful webhook setup.
					if ( 0 === $server ) {
						give_update_option( 'give_authorize_live_webhooks_setup', true );
						give_update_option( 'give_authorize_live_webhooks_id', $webhook->getWebhooksId() );
						give_delete_option( 'give_authorize_live_webhooks_no_signature_key' );
					} elseif ( 1 === $server ) {
						give_update_option( 'give_authorize_sandbox_webhooks_setup', true );
						give_update_option( 'give_authorize_sandbox_webhooks_id', $webhook->getWebhooksId() );
						give_delete_option( 'give_authorize_sandbox_webhooks_no_signature_key' );
					}

					return true;
				}
			}

			// Ok, now create the webhooks for this URL.
			$request2 = \JohnConde\Authnet\AuthnetApiFactory::getWebhooksHandler( $login_id, $transaction_key, $server );

			$response = $request2->createWebhooks( array(
				'net.authorize.customer.created',
				'net.authorize.customer.deleted',
				'net.authorize.customer.updated',
				'net.authorize.customer.paymentProfile.created',
				'net.authorize.customer.paymentProfile.deleted',
				'net.authorize.customer.paymentProfile.updated',
				'net.authorize.customer.subscription.cancelled',
				'net.authorize.customer.subscription.created',
				'net.authorize.customer.subscription.expiring',
				'net.authorize.customer.subscription.suspended',
				'net.authorize.customer.subscription.terminated',
				'net.authorize.customer.subscription.updated',
				'net.authorize.payment.authcapture.created',
				'net.authorize.payment.authorization.created',
				'net.authorize.payment.capture.created',
				'net.authorize.payment.fraud.approved',
				'net.authorize.payment.fraud.declined',
				'net.authorize.payment.fraud.held',
				'net.authorize.payment.priorAuthCapture.created',
				'net.authorize.payment.refund.created',
				'net.authorize.payment.void.created',
			), $listener_url, 'active' );

			if ( 'active' === $response->getStatus() ) {

				// Successful webhook setup.
				if ( 0 === $server ) {
					give_update_option( 'give_authorize_live_webhooks_setup', true );
					give_update_option( 'give_authorize_live_webhooks_id', $response->getWebhooksId() );
					give_delete_option( 'give_authorize_live_webhooks_no_signature_key' );
				} elseif ( 1 === $server ) {
					give_update_option( 'give_authorize_sandbox_webhooks_setup', true );
					give_update_option( 'give_authorize_sandbox_webhooks_id', $response->getWebhooksId() );
					give_delete_option( 'give_authorize_sandbox_webhooks_no_signature_key' );
				}
			}

			return true;

		} catch ( \Exception $e ) {

			give_record_gateway_error( esc_html__( 'Authorize.net Error', 'give-authorize' ), $e->getMessage() );

			// Common error is not having the signature key setup. Flag notice and only show for live sites.
			if ( strpos( $e->getMessage(), 'Please generate a signature key' ) !== false ) {

				// Live and sandbox.
				if ( 0 === $server ) {
					give_update_option( 'give_authorize_live_webhooks_no_signature_key', true );
				} else {
					give_update_option( 'give_authorize_sandbox_webhooks_no_signature_key', true );
				}
			}

			return 'error';
		} // End try().
	}

	/**
	 * Listen for Authorize.net webhook events.
	 *
	 * @access      public
	 * @since       1.3
	 *
	 * @param  WP $query
	 *
	 * @return      WP
	 */
	public function authorize_event_listener( $query ) {

		// Must be a GiveWP Authorize.net listener to proceed.
		if ( 'give-authorize-webhook-listener' !== $query->request ) {
			return $query;
		}

		// Retrieve the request's body and parse it as JSON.
		$body = @file_get_contents( 'php://input' );
		// Decode JSON into object.
		$event_json = json_decode( $body );

		// Process the webhooks.
		$this->process_webhooks( $event_json );

	}

	/**
	 * Process Stripe Webhooks.
	 *
	 * @since 1.3
	 *
	 * @param $event_json
	 */
	public function process_webhooks( $event_json ) {

		// Next, proceed with additional webhooks.
		if ( isset( $event_json->eventType ) ) {

			status_header( 200 );

			$transaction_id = $event_json->payload->id;

			if ( isset( $transaction_id ) && ! empty( $transaction_id ) ) {
				global $wpdb;
				$donation_meta_table  = Give()->payment_meta->table_name;
				$donation_id_col_name = Give()->payment_meta->get_meta_type() . '_id';

				$payment_id = $wpdb->get_var( $wpdb->prepare( "
					SELECT {$donation_id_col_name} 
					FROM {$donation_meta_table} 
					WHERE meta_key = '_give_payment_transaction_id' 
					AND meta_value = %s LIMIT 1", $transaction_id ) );
			}

			// Refunds and voids both receive refunds in Give.
			if (
				'net.authorize.payment.void.created' === $event_json->eventType
				|| 'net.authorize.payment.refund.created' === $event_json->eventType
			) {
				if ( isset( $payment_id ) ) {
					give_update_payment_status( $payment_id, 'refunded' );
					give_insert_payment_note( $payment_id, esc_html__( 'Charge refunded or voided in Authorize.net.', 'give-authorize' ) );
				}
			}

			// Update donation status to `Complete` when approved status.
			if ( 'net.authorize.payment.fraud.approved' === $event_json->eventType ) {
				if ( isset( $payment_id ) ) {
					give_update_payment_status( $payment_id, 'publish' );
					give_insert_payment_note( $payment_id, esc_html__( 'Authorize.net transaction approved by the fraud filter.', 'give-authorize' ) );
				}
			}

			do_action( 'give_authorize_event_' . $event_json->eventType, $event_json );

			die( '1' ); // Completed successfully

		} else {
			status_header( 500 );
			// Something went wrong outside of Stripe.
			give_record_gateway_error( esc_html__( 'Authorize.net Error', 'give-authorize' ), sprintf( esc_html__( 'An error occurred while processing a webhook.', 'give-authorize' ) ) );
			die( '-1' ); // Failed
		}// End if().
	}

	/**
	 * Process an authorize.net payment using AIM
	 *
	 * @see   : http://developer.authorize.net/api/reference/
	 *
	 * @since 1.3
	 *
	 * @param $donation_data
	 *
	 * @return bool
	 */
	public function process_payment( $donation_data ) {

		$request = $this->setup_api_request();

		if ( ! $request ) {
			return false;
		}

		$args = apply_filters( 'give_authorize_payment_args', array(
			'refId'              => substr( $donation_data['purchase_key'], 0, 19 ),
			'transactionRequest' => array(
				'transactionType'     => 'authCaptureTransaction',
				'amount'              => $donation_data['price'],
				'payment'             => array(
					'creditCard' => array(
						'cardNumber'     => sanitize_text_field( $donation_data['card_info']['card_number'] ),
						'expirationDate' => sanitize_text_field( $donation_data['card_info']['card_exp_month'] . $donation_data['card_info']['card_exp_year'] ),
						'cardCode'       => sanitize_text_field( $donation_data['card_info']['card_cvc'] ),
					),
				),
				'order'               => array(
					'invoiceNumber' => substr( $donation_data['purchase_key'], 0, 19 ),
					'description'   => apply_filters( 'give_authorize_one_time_payment_description', give_payment_gateway_donation_summary( $donation_data, false ), $donation_data ),
				),
				'customer'            => array(
					'email' => $donation_data['user_email'],
				),
				'billTo'              => array(
					'firstName' => $donation_data['user_info']['first_name'],
					'lastName'  => $donation_data['user_info']['last_name'],
					'address'   => $donation_data['card_info']['card_address'] . ' ' . $donation_data['card_info']['card_address_2'],
					'city'      => $donation_data['card_info']['card_city'],
					'state'     => $donation_data['card_info']['card_state'],
					'zip'       => $donation_data['card_info']['card_zip'],
					'country'   => $donation_data['card_info']['card_country'],
				),
				'customerIP'          => give_get_ip(),
				'transactionSettings' => array(
					'setting' => array(
						0 => array(
							'settingName'  => 'allowPartialAuth',
							'settingValue' => 'false',
						),
						1 => array(
							'settingName'  => 'duplicateWindow',
							'settingValue' => '0',
						),
						2 => array(
							'settingName'  => 'emailCustomer',
							'settingValue' => 'false',
						),
						3 => array(
							'settingName'  => 'recurringBilling',
							'settingValue' => 'false',
						),
						4 => array(
							'settingName'  => 'testRequest',
							'settingValue' => 'false',
						),
					),
				),
			),
		), $donation_data );

		try {

			/* @var JohnConde\Authnet\AuthnetJsonResponse $response */
			$response      = $request->createTransactionRequest( $args );
			$error_message = '';

			$payment_data = array(
				'price'           => $donation_data['price'],
				'give_form_title' => $donation_data['post_data']['give-form-title'],
				'give_form_id'    => intval( $donation_data['post_data']['give-form-id'] ),
				'give_price_id'   => isset( $donation_data['post_data']['give-price-id'] ) ? $donation_data['post_data']['give-price-id'] : '',
				'date'            => $donation_data['date'],
				'user_email'      => $donation_data['user_email'],
				'purchase_key'    => $donation_data['purchase_key'],
				'currency'        => give_get_currency(),
				'user_info'       => $donation_data['user_info'],
				'status'          => 'pending',
				'gateway'         => 'authorize',
			);


			// Flags for fraud conditional check below.
			$authnet_fraud_error_codes = array( '252', '253' );

			// Must be approved.
			if ( $response->isSuccessful() && $response->isApproved() ) {

				// Insert payment.
				$payment_id = give_insert_payment( $payment_data );
				give_update_payment_status( $payment_id, 'publish' );
				give_set_payment_transaction_id( $payment_id, $response->transactionResponse->transId );
				give_send_to_success_page();

			} elseif ( isset( $response->transactionResponse->messages[0]->code ) && in_array( $response->transactionResponse->messages[0]->code, $authnet_fraud_error_codes ) ) {

				// Not approved through fraud screening. An error with the payment.
				$payment_id = give_insert_payment( $payment_data );
				give_set_payment_transaction_id( $payment_id, $response->transactionResponse->transId );
				give_insert_payment_note( $payment_id, __( 'Authorize.net transaction flagged this donation through the fraud filter. Please approve or void this transaction within your Authorize.net merchant dashboard.', 'give-authorize' ) );
				give_send_to_success_page();

			} else {

				$error_code = $response->getErrorCode();
				$error_text = $response->getErrorText();

				if ( ! empty( $error_code) && '17' === $error_code ) {
					$error_message = __( 'The donation could not be charged because the type of credit card used is not accepted. Please try again with a supported card type.', 'give-authorize' );
				} else {
					// Not approved. An error with the payment.
					$error_message = ! empty( $error_text ) ? $error_text : __( 'The transaction has been declined.', 'give-authorize' );
					$error_message = sprintf( __( 'The donation could not be charged. Please try again. Reason: %s', 'give-authorize' ), $error_message );
				}
			}

			/**
			 * Customize Authorize error message.
			 *
			 * @since 1.4.3
			 *
			 * @param string $error_message Error message.
			 * @param object $response      Response of Authorize.net Payment request.
			 *
			 * @return string $error_message Error message.
			 */
			$error_message = apply_filters( 'give_authorize_error_message', $error_message, $response );
			give_set_error( 'authorize_request_error', $error_message );

			give_record_gateway_error( 'Authorize.net Error', $error_message );
			give_send_back_to_checkout( '?payment-mode=' . $donation_data['post_data']['give-gateway'] );

		} catch ( Exception $e ) {

			give_set_error( 'authorize_request_error', __( 'The donation could not be charged. Please try again.', 'give-authorize' ) );
			give_record_gateway_error( 'Authorize.net Error', $e->getMessage() );
			give_send_back_to_checkout( '?payment-mode=' . $donation_data['post_data']['give-gateway'] );

		}// End try().

	}


	/**
	 * Adds form fields necessary for eCheck payment
	 *
	 * @param number $form_id Form ID.
	 *
	 * @return void
	 */
	public function bank_billing_fields( $form_id ) {

		if ( ! give_is_setting_enabled( give_get_option( 'authorize_collect_billing' ) ) ) {
			remove_action( 'give_after_cc_fields', 'give_default_cc_address_fields' );
		}

		?>

		<fieldset id="give_checkout_bank_details">
			<legend>
				<?php
				/**
				 * Filter the legend title.
				 */
				echo apply_filters( 'give_checkout_bank_details', __( 'Bank Details', 'give-authorize' ) );
				?>
			</legend>

			<div class="give-check-sample">
				<img src="<?php echo GIVE_AUTHORIZE_PLUGIN_URL . '/assets/images/check-sample.png'; ?>">
			</div>

			<div class="ga-column-wrap">

				<!-- Routing Number input field -->
				<p>
					<label>
						<?php esc_html_e( 'Routing Number', 'give-authorize' ); ?>
						<?php echo Give()->tooltips->render_help( __( 'The bank\'s routing number.', 'give-authorize' ) ); ?>
					</label>
					<input type="text" name="routing-number"
						   placeholder="<?php echo esc_attr( 'Routing Number', 'give-authorize' ); ?>">
				</p>

				<!-- Account Number input field -->
				<p>
					<label>
						<?php esc_html_e( 'Account Number', 'give-authorize' ); ?>
						<?php echo Give()->tooltips->render_help( __( 'The bank account number.', 'give-authorize' ) ); ?>
					</label>
					<input type="text" name="account-number"
						   placeholder="<?php echo esc_attr( 'Account Number', 'give-authorize' ); ?>">
				</p>

			</div>


			<div class="ga-column-wrap">
				<!-- Name on the Account input field -->
				<p>
					<label>
						<?php esc_html_e( 'Name on Account', 'give-authorize' ); ?>
						<?php echo Give()->tooltips->render_help( __( 'The name of the person who holds the bank account.', 'give-authorize' ) ); ?>
					</label>
					<input type="text" name="name-on-account"
						   placeholder="<?php echo esc_attr( 'Name on Account', 'give-authorize' ); ?>">
				</p>


				<!-- Account Types select fields -->
				<p>
					<label>
						<?php esc_html_e( 'Account Type', 'give-authorize' ); ?>
						<?php echo Give()->tooltips->render_help( __( 'The type of bank account.', 'give-authorize' ) ); ?>
					</label>
					<?php Give_Authorize_Echeck_Payments::select_fields( 'account', 'account-type' ); ?>
				</p>
			</div>
		</fieldset>

		<?php

		do_action( 'give_after_cc_fields', $form_id );
	}


	/**
	 * Process an authorize.net eCheck payment.
	 *
	 * @see   : http://developer.authorize.net/api/reference/
	 *
	 * @param array $donation_data Donation data.
	 *
	 * @return bool
	 */
	public function process_echeck_payment( $donation_data ) {
		$request = $this->setup_api_request();

		if ( ! $request ) {
			return false;
		}

		$payment_data = array(
			'price'           => $donation_data['price'],
			'give_form_title' => $donation_data['post_data']['give-form-title'],
			'give_form_id'    => intval( $donation_data['post_data']['give-form-id'] ),
			'give_price_id'   => isset( $donation_data['post_data']['give-price-id'] ) ? $donation_data['post_data']['give-price-id'] : '',
			'date'            => $donation_data['date'],
			'user_email'      => $donation_data['user_email'],
			'purchase_key'    => $donation_data['purchase_key'],
			'currency'        => give_get_currency(),
			'user_info'       => $donation_data['user_info'],
			'status'          => 'pending',
			'gateway'         => 'authorize_echeck',
		);

		$payment_id = give_insert_payment( $payment_data );

		/**
		 * Filter the arguments that is required for eCheck's
		 * createTransactionRequest() method.
		 */
		$args = apply_filters( 'give_authorize_echeck_payment_args', array(
			'refId'              => substr( $donation_data['purchase_key'], 0, 19 ),
			'transactionRequest' => array(
				'transactionType'     => 'authCaptureTransaction',
				'amount'              => $donation_data['price'],
				'payment'             => array(
					'bankAccount' => array(
						'accountType'   => $donation_data['bank_details']['account-type'],
						'routingNumber' => $donation_data['bank_details']['routing-number'],
						'accountNumber' => $donation_data['bank_details']['account-number'],
						'nameOnAccount' => $donation_data['bank_details']['name-on-account'],
					),
				),
				'order'               => array(
					'invoiceNumber' => substr( $donation_data['purchase_key'], 0, 19 ),

					/**
					 * Filter eCheck's the one-time payment description.
					 */
					'description'   => apply_filters( 'give_authorize_echeck_one_time_payment_description', give_payment_gateway_donation_summary( $donation_data, false ), $donation_data ),
				),
				'customer'            => array(
					'email' => $donation_data['user_email'],
				),
				'billTo'              => array(
					'firstName' => $donation_data['user_info']['first_name'],
					'lastName'  => $donation_data['user_info']['last_name'],
				),
				'customerIP'          => give_get_ip(),
				'transactionSettings' => array(
					'setting' => array(
						0 => array(
							'settingName'  => 'allowPartialAuth',
							'settingValue' => false,
						),
						1 => array(
							'settingName'  => 'duplicateWindow',
							'settingValue' => '0',
						),
						2 => array(
							'settingName'  => 'emailCustomer',
							'settingValue' => false,
						),
						3 => array(
							'settingName'  => 'recurringBilling',
							'settingValue' => false,
						),
						4 => array(
							'settingName'  => 'testRequest',
							'settingValue' => false,
						),
					),
				),
			),
		), $donation_data );

		try {
			$response      = $request->createTransactionRequest( $args );
			$error_message = '';

			// Must be approved.
			if ( $response->isSuccessful() && $response->isApproved() ) {

				// Insert payment.
				if ( $payment_id ) {
					give_update_payment_status( $payment_id, 'publish' );
					give_set_payment_transaction_id( $payment_id, $response->transactionResponse->transId );
					give_send_to_success_page();
				}

			} else {
				// Not approved. An error with the payment.
				$error_message = isset( $response->transactionResponse->errors[0]->errorText ) ? $response->transactionResponse->errors[0]->errorText : __( 'The transaction has been declined.', 'give-authorize' );
				$error_message = sprintf( __( 'The donation could not be charged. Please try again. Reason: %s', 'give-authorize' ), $error_message );

				if ( isset( $payment_id ) ) {
					give_set_payment_transaction_id( $payment_id, $response->transactionResponse->transId );
					give_insert_payment_note( $payment_id, $error_message );
				}

				give_update_payment_status( $payment_id, 'failed' );
			}

			/**
			 * Customize Authorize eCheck error message.
			 *
			 * @since 1.4.3
			 *
			 * @param string $error_message Error message.
			 * @param object $response      Response of Authorize.net Payment request.
			 *
			 * @return string $error_message Error message.
			 */
			$error_message = apply_filters( 'give_authorize_echeck_error_message', $error_message, $response );

			give_set_error( 'authorize_request_error', $error_message );
			give_record_gateway_error( 'Authorize.net eCheck (ACH) Error', $error_message );
			give_send_back_to_checkout( '?payment-mode=' . $donation_data['post_data']['give-gateway'] );

		} catch ( Exception $e ) {

			give_set_error( 'authorize_request_error', __( 'The donation could not be charged. Please try again.', 'give-authorize' ) );
			give_record_gateway_error( 'Authorize.net eCheck (ACH) Error', $e->getMessage() );
			give_update_payment_status( $payment_id, 'failed' );
			give_send_back_to_checkout( '?payment-mode=' . $donation_data['post_data']['give-gateway'] );

		}// End try().
	}


	/**
	 * Optional Billing Fields
	 *
	 * @since 1.2
	 *
	 * @param $form_id
	 *
	 * @return void
	 */
	public function optional_billing_fields( $form_id ) {

		// Remove Address Fields if user has option enabled.
		if ( ! give_get_option( 'authorize_collect_billing' ) ) {
			remove_action( 'give_after_cc_fields', 'give_default_cc_address_fields' );
		}

		// Ensure CC field is in place properly.
		do_action( 'give_cc_form', $form_id );

	}

	/**
	 * Link transaction ID.
	 *
	 * Links the transaction ID in the donation details the ID to the authnet trans search with a tooltip.
	 *
	 * @since 1.3
	 *
	 * @param $transaction_id
	 * @param $payment_id
	 *
	 * @return string
	 */
	public function link_transaction_id( $transaction_id, $payment_id ) {

		if ( intval( $transaction_id ) === intval( $payment_id ) ) {
			return $transaction_id;
		}

		if ( ! empty( $transaction_id ) ) {

			$payment = new Give_Payment( $payment_id );
			$html    = '<a href="%s" target="_blank">' . $transaction_id . '</a> <span class="dashicons dashicons-editor-help" 
data-tooltip="' . __( 'This is the Authorize.net transction ID. Click the link to view more details at the gateway.', 'give-authorize' ) . '"></span>';

			// Set appropriate URL.
			$base_url = 'live' === $payment->mode ? 'https://authorize.net/ui/themes/sandbox/merch.aspx?page=search' : 'https://sandbox.authorize.net/ui/themes/sandbox/merch.aspx?page=search';
			$link     = esc_url( $base_url );

			// Setup html.
			$transaction_id = sprintf( $html, $link );
		}

		return $transaction_id;

	}

	/**
	 * Process and admin refund in Authorize.net
	 *
	 * Refunds occur when a payment is marked as refunded and the "Refund in Authorize.net" checkbox is checked.
	 *
	 * @since 1.3
	 *
	 * @param $payment_id
	 * @param $new_status
	 * @param $old_status
	 *
	 * @return      void
	 */
	public function process_admin_refund( $payment_id, $new_status, $old_status ) {

		// Only move forward if refund requested.
		if ( empty( $_POST['give_refund_in_authorize'] ) ) {
			return;
		}

		// Verify statuses.
		$should_process_refund = 'publish' !== $old_status ? false : true;
		$should_process_refund = apply_filters( 'give_authorize_should_process_refund', $should_process_refund, $payment_id, $new_status, $old_status );

		// Sanity check: check var from above
		if ( false === $should_process_refund ) {
			return;
		}

		// Sanity check, must be refunded status.
		if ( 'refunded' !== $new_status ) {
			return;
		}

		// Must have the Authorize.net Transaction ID,
		// it can't be the same as the payment_id.
		$auth_transid = give_get_payment_transaction_id( $payment_id );

		if ( intval( $auth_transid ) === intval( $payment_id ) ) {
			give_insert_payment_note( $payment_id, sprintf( esc_html__( 'Non-valid Authorize.net transaction ID found on this payment: %s The refund could not be processed at the gateway.', 'give-authorize' ), $auth_transid ) );

			return;
		}

		$request             = $this->setup_api_request();
		$transaction_details = '';

		// First get the last 4 digits of the CC card from Authorize.net API
		try {
			$transaction_details = $request->getTransactionDetailsRequest( array(
				'transId' => $auth_transid,
			) );
		} catch ( Exception $e ) {
			give_set_error( 'authorize_refund_error', __( 'The donation could not be refunded. Please try again.', 'give-authorize' ) );
			give_record_gateway_error( 'Authorize.net Error', $e->getMessage() );
		}

		// Need payment details from Auth.net to continue
		if ( ! isset( $transaction_details->transaction->payment->creditCard->cardNumber ) ) {
			return;
		}

		try {

			$args = apply_filters( 'give_authorize_donation_admin_refund_args', array(
					'refId'              => $payment_id,
					'transactionRequest' => array(
						'transactionType' => 'refundTransaction',
						'amount'          => give_donation_amount( $payment_id ),
						'payment'         => array(
							'creditCard' => array(
								'cardNumber'     => $transaction_details->transaction->payment->creditCard->cardNumber,
								'expirationDate' => $transaction_details->transaction->payment->creditCard->expirationDate,
							),
						),
						'refTransId'      => give_get_payment_transaction_id( $payment_id ),
					),
				)
			);

			$response = $request->createTransactionRequest( $args );

			// Charge was refunded.
			if ( $response->isSuccessful() ) {

				give_insert_payment_note( $payment_id, sprintf( esc_html__( 'Charge refunded in Authorize.net: %s', 'give-authorize' ), $response->transactionResponse->transId ) );

			} elseif ( $response->isError() ) {

				give_record_gateway_error( 'Authorize.net Error', json_encode( $response->transactionResponse->errors ) );

				// If there was an issue with the refund, display a notice to admins.
				wp_die( sprintf( __( 'The donation could not be refunded at the Authorize.net gateway. This can happen if the transaction has already been refunded, past the 120 day refund window, has been voided, or has yet to be settled. Please check the <a href="%s">error logs</a> for more information.', 'give-authorize' ), admin_url( 'edit.php?post_type=give_forms&page=give-tools&tab=logs&section=gateway_errors' ) ), 'Authorize.net Error' );

			}
		} catch ( Exception $e ) {

			wp_die( sprintf( __( 'There was an error communicating with the Authorize.net gateway. Please check the <a href="%s">error logs</a> for more information.', 'give-authorize' ), admin_url( 'edit.php?post_type=give_forms&page=give-tools&tab=logs&section=gateway_errors' ) ), 'Authorize.net Error' );
			give_record_gateway_error( 'Authorize.net Error', $e->getMessage() );

		}// End try().

		do_action( 'give_authorize_donation_admin_refunded', $payment_id );
	}

}
