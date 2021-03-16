<?php
/**
 * Give Authorize Settings
 *
 * @package     Give
 * @copyright   Copyright (c) 2019, GiveWP
 * @license     https://opensource.org/licenses/gpl-license GNU Public License
 * @since       1.1
 */


// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Give_Authnet_Settings
 *
 * @since 1.4.6
 */
class Give_Authnet_Settings {

	/**
	 * Singleton instance.
	 *
	 * @access private
	 * @var Give_Authnet_Settings $instance
	 */
	static private $instance;

	/**
	 * @access private
	 * @var string $section_id
	 */
	private $section_id;

	/**
	 * @access private
	 *
	 * @var string $section_label
	 */
	private $section_label;

	/**
	 * Give_Authnet_Settings constructor.
	 */
	private function __construct() {
	}

	/**
	 * get class object.
	 *
	 * @return Give_Authnet_Settings
	 */
	static function get_instance() {
		if ( null === static::$instance ) {
			static::$instance = new static();
		}

		return static::$instance;
	}

	/**
	 * Setup hooks.
	 */
	public function setup_hooks() {

		$this->section_id    = 'authorize-net-gateway';
		$this->section_label = __( 'Authorize.net', 'give-authorize' );

		if ( is_admin() ) {
			// Add settings.
			add_filter( 'give_get_sections_gateways', array( $this, 'register_sections' ) );
			add_filter( 'give_get_settings_gateways', array( $this, 'register_settings' ) );

			add_action( 'give_admin_field_authorize_webhooks', array( $this, 'webhook_field' ), 10, 2 );

		}
	}

	/**
	 * Register sections.
	 *
	 * @acess public
	 *
	 * @param $sections
	 *
	 * @return mixed
	 */
	public function register_sections( $sections ) {
		$sections['authorize-net-gateway']     = __( 'Authorize.net', 'give-authorize' );

		return $sections;
	}

	/**
	 * Register Authorize Main Settings.
	 *
	 * @param $settings
	 *
	 * @return array
	 */
	public function register_settings( $settings ) {

		switch ( give_get_current_setting_section() ) {
			case 'authorize-net-gateway':
				$settings = array(
					array(
						'id'   => 'give_title_authorize',
						'type' => 'title',
					),
					array(
						'name'        => '<strong>' . __( 'Authorize.net Gateway', 'give-authorize' ) . '</strong>',
						'description' => '<hr>',
						'type'        => 'give_title',
						'id'          => 'give_title_authorize_net',
					),
					array(
						'id'          => 'give_api_login',
						'name'        => esc_html__( 'Live API Login ID', 'give-authorize' ),
						'description' => esc_html__( 'Please enter your LIVE authorize.net API login ID.', 'give-authorize' ),
						'type'        => 'api_key',
					),
					array(
						'id'          => 'give_transaction_key',
						'name'        => esc_html__( 'Live Transaction Key', 'give-authorize' ),
						'description' => esc_html__( 'Please enter your LIVE authorize.net transaction key.', 'give-authorize' ),
						'type'        => 'api_key',
					),
					array(
						'id'          => 'give_authorize_sandbox_api_login',
						'name'        => esc_html__( 'Sandbox API Login ID', 'give-authorize' ),
						'description' => __( 'Please enter your SANDBOX authorize.net API login ID for testing purposes.', 'give-authorize' ),
						'type'        => 'api_key',
					),
					array(
						'id'          => 'give_authorize_sandbox_transaction_key',
						'name'        => esc_html__( 'Sandbox Transaction Key', 'give-authorize' ),
						'description' => __( 'Please enter your SANDBOX authorize.net transaction key for testing purposes.', 'give-authorize' ),
						'type'        => 'api_key',
					),
					array(
						'id'          => 'give_authorize_webhooks',
						'name'        => esc_html__( 'Webhooks', 'give-authorize' ),
						'description' => __( 'Please enable webhooks for both live and test modes.', 'give-authorize' ),
						'type'        => 'authorize_webhooks',
					),
					array(
						'id'          => 'give_authorize_merchant_descriptor',
						'name'        => esc_html__( 'Billing Descriptor', 'give-authorize' ),
						'description' => __( 'Max 25 characters. The billing descriptor is the way a organization\'s name appears on a credit card statement and is set up when the merchant account is established. It is used by the credit card customer to identify who a payment was made to on a particular transaction.', 'give-authorize' ),
						'attributes'  => array(
							'maxlength'   => '25',
							'placeholder' => substr( get_bloginfo( 'name' ), 0, 24 ),
						),
						'type'        => 'text',
					),
					array(
						'name'        => esc_html__( 'Collect Billing Details', 'give-authorize' ),
						'description' => sprintf( esc_html__( 'This option will enable the billing details section for Authorize which requires the donor\'s address to complete the donation. These fields are not required by Authorize.net to process the transaction, but you may have the need to collect the data.', 'give-authorize' ) ),
						'id'          => 'authorize_collect_billing',
						'type'        => 'checkbox',
					),
					array(
						'name'  => __( 'Authorize.net Gateway Documentation', 'give-authorize' ),
						'id'    => 'display_settings_docs_link',
						'url'   => esc_url( 'http://docs.givewp.com/addon-authorize' ),
						'title' => __( 'Authorize.net Gateway Documentation', 'give-authorize' ),
						'type'  => 'give_docs_link',
					),
					array(
						'id'   => 'give_title_authorize',
						'type' => 'sectionend',
					),
				);

				break;
		}// End switch().

		return $settings;
	}

	/**
	 * Webhook field.
	 *
	 * @since 1.3
	 *
	 * @param $value
	 * @param $option_value
	 */
	function webhook_field( $value, $option_value ) { ?>

		<tr valign="top" <?php echo ! empty( $value['wrapper_class'] ) ? 'class="' . $value['wrapper_class'] . '"' : '' ?>>
			<th scope="row" class="titledesc">
				<label for=""> <?php _e( 'Authorize.net Webhooks', 'give-authorize' ) ?></label>
			</th>

			<td class="give-forminp give-forminp-api_key">

			<span class="give-authorize-checking-status"><span
						class="give-authorize-loading-icon"></span><?php esc_html_e( 'Checking webhooks status...', 'give-authorize' ); ?></span>

				<div class="give-authorize-live-webhook-statuses">
				<span class="give-authorize-webhook-message give-authorize-webhook-success give-authorize-webhook-live-success"><span
							class="dashicons dashicons-yes"></span><?php esc_html_e( 'Live Webhooks Setup', 'give-authorize' ); ?></span>

					<span class="give-authorize-webhook-message give-authorize-webhook-no-keys give-authorize-webhook-no-live-keys"><span
								class="dashicons dashicons-info"></span><?php esc_html_e( 'No Live API Keys Detected', 'give-authorize' ); ?></span>

					<span class="give-authorize-webhook-message give-authorize-webhook-issue give-authorize-webhook-live-issue"><span
								class="dashicons dashicons-no-alt"></span><?php esc_html_e( 'There was a problem setting up the live webhooks.', 'give-authorize' ); ?></span>
				</div>


				<div class="give-authorize-sandbox-webhook-statuses">
				<span class="give-authorize-webhook-message give-authorize-webhook-success give-authorize-webhook-sandbox-success"><span
							class="dashicons dashicons-yes"></span><?php esc_html_e( 'Sandbox Webhooks Setup', 'give-authorize' ); ?></span>

					<span class="give-authorize-webhook-message give-authorize-webhook-no-keys give-authorize-webhook-no-sandbox-keys"><span
								class="dashicons dashicons-info"></span><?php esc_html_e( 'No Sandbox API Keys Detected', 'give-authorize' ); ?></span>

					<span class="give-authorize-webhook-message give-authorize-webhook-issue give-authorize-webhook-sandbox-issue"><span
								class="dashicons dashicons-no-alt"></span><?php printf( __( 'There was a problem setting up the sandbox webhooks. Please check the <a href="%s">logs</a> for additional details.', 'give-authorize' ), admin_url( '/edit.php?post_type=give_forms&page=give-tools&tab=logs&section=gateway_errors' ) ); ?></span>
				</div>

				<div class="give-authorize-webhook-check-wrap">
					<button class="button button-small give-authorize-check-webhooks"><?php esc_html_e( 'Check again', 'give-authorize' ) ?></button>
				</div>

				<p class="give-field-description"><?php esc_html_e( 'Authorize.net webhooks are important to setup so Give can communicate properly with the payment gateway. Please enter your API keys above and the plugin will automatically enable the webhooks within the gateway. It is not required to have the sandbox webhooks setup unless you are testing. Note: webhooks cannot be setup on localhost or websites in maintenance mode.', 'give-authorize' ); ?></p>

			</td>
		</tr>


	<?php }

}

Give_Authnet_Settings::get_instance()->setup_hooks();


/**
 * Load Transaction-specific admin javascript.
 *
 * Allows the user to refund non-recurring donations.
 *
 * @since  1.0
 *
 * @param int $payment_id
 */
function give_authorize_admin_payment_js( $payment_id = 0 ) {

	// Sanity check: Only authorize.net payments.
	if ( 'authorize' !== give_get_payment_gateway( $payment_id ) ) {
		return;
	}

	$trans_id = give_get_payment_transaction_id( $payment_id );

	// Must have authorize.net transaction ID.
	if ( intval( $payment_id ) === intval( $trans_id ) ) {
		return;
	}

	?>
	<script type="text/javascript">
		jQuery( document ).ready( function( $ ) {

			$( 'select[name=give-payment-status]' ).change( function() {

				if ( 'refunded' === $( this ).val() ) {

					$( this ).parent().parent().append( '<p class="give-authorize-refund"><input type="checkbox" id="give_refund_in_authorize" name="give_refund_in_authorize" value="1"/><label for="give_refund_in_authorize"><?php esc_html_e( 'Refund Charge in Authorize.net?', 'give-authorize' ); ?></label></p>' );

				} else {
					$( '.give-authorize-refund' ).remove();
				}

			} );
		} );
	</script>
	<?php

}

add_action( 'give_view_donation_details_before', 'give_authorize_admin_payment_js', 100 );
