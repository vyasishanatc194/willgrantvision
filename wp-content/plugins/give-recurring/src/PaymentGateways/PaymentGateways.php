<?php
namespace GiveRecurring\PaymentGateways;

use Give\Helpers\Hooks;
use Give\PaymentGateways\PayPalCommerce\Webhooks\WebhookRegister;
use Give\ServiceProviders\ServiceProvider;
use Give_Subscription;
use GiveRecurring\Infrastructure\View;
use GiveRecurring\PaymentGateways\PayPalCommerce\AjaxRequestHandler;
use GiveRecurring\PaymentGateways\PayPalCommerce\HttpHeader;
use GiveRecurring\PaymentGateways\PayPalCommerce\PayPalCommerce;
use GiveRecurring\PaymentGateways\PayPalCommerce\SubscriptionProcessor;
use Give\PaymentGateways\PayPalCommerce\PayPalCommerce as GivePayPalCommerce;
use GiveRecurring\PaymentGateways\PayPalCommerce\WebHooks\Listeners\BillingSubscriptionActivated;
use GiveRecurring\PaymentGateways\PayPalCommerce\WebHooks\Listeners\BillingSubscriptionCancelled;
use GiveRecurring\PaymentGateways\PayPalCommerce\WebHooks\Listeners\BillingSubscriptionExpired;
use GiveRecurring\PaymentGateways\PayPalCommerce\WebHooks\Listeners\BillingSubscriptionPaymentFailed;
use GiveRecurring\PaymentGateways\PayPalCommerce\WebHooks\Listeners\BillingSubscriptionSuspended;
use GiveRecurring\PaymentGateways\PayPalCommerce\WebHooks\Listeners\BillingSubscriptionUpdated;
use GiveRecurring\PaymentGateways\PayPalCommerce\WebHooks\Listeners\PaymentSaleCompleted;
use GiveRecurring\PaymentGateways\PayPalCommerce\WebHooks\Listeners\PaymentSaleRefunded;

class PaymentGateways implements ServiceProvider {
	/**
	 * @var array
	 */
	private $webhookListeners = [
		PaymentSaleCompleted::WEBHOOK_ID => PaymentSaleCompleted::class,
		PaymentSaleRefunded::WEBHOOK_ID => PaymentSaleRefunded::class,
		BillingSubscriptionExpired::WEBHOOK_ID => BillingSubscriptionExpired::class,
		BillingSubscriptionCancelled::WEBHOOK_ID => BillingSubscriptionCancelled::class,
		BillingSubscriptionActivated::WEBHOOK_ID => BillingSubscriptionActivated::class,
		BillingSubscriptionPaymentFailed::WEBHOOK_ID => BillingSubscriptionPaymentFailed::class,
		BillingSubscriptionSuspended::WEBHOOK_ID => BillingSubscriptionSuspended::class,
		BillingSubscriptionUpdated::WEBHOOK_ID => BillingSubscriptionUpdated::class
	];

	/**
	 * @inheritDoc
	 */
	public function register() {
		give()->bind('PAYPAL_COMMERCE_SUBSCRIPTION_ATTRIBUTION_ID', static function() {
			return 'GiveWP_SP_Migration';
		}); // storage

		// Load recurring gateway class.
		require_once GIVE_RECURRING_PLUGIN_DIR . 'includes/gateways/give-recurring-gateway.php';

		$this->registerPayPalCommerceClasses();
	}

	/**
	 * @inheritDoc
	 */
	public function boot() {
		$gatewayId = GivePayPalCommerce::GATEWAY_ID;

		give()->singleton( PayPalCommerce::class );

		// Initialize class.
		give(PayPalCommerce::class);

		Hooks::addFilter( 'give_recurring_available_gateways', __CLASS__, 'registerRecurringPaymentGateway', 10, 1 );
		Hooks::addAction( 'template_redirect', SubscriptionProcessor::class, 'setSubscriptionFailed', 10, 1 );
		Hooks::addAction( 'give_subscription_deleted', SubscriptionProcessor::class, 'handleSubscriptionDeletion', 11, 3 );
		Hooks::addAction( 'give_subscription_updated', SubscriptionProcessor::class, 'handleSubscriptionStatusChange', 11, 3 );
		Hooks::addAction( 'wp_ajax_give_paypal_commerce_create_plan_id', AjaxRequestHandler::class, 'createPlanId', 10 );
		Hooks::addAction( 'wp_ajax_nopriv_give_paypal_commerce_create_plan_id', AjaxRequestHandler::class, 'createPlanId', 10 );
		Hooks::addAction( 'give_recurring_add_subscription_detail', __CLASS__, 'addSubscriptionStatusOptInField' );
		Hooks::addFilter( "give_recurring_gateway_statues_for_optin_{$gatewayId}", PayPalCommerce::class, 'getSubscriptionStatuesForOptIn' );

		// Register a bunch of events
		give(WebhookRegister::class )->registerEventHandlers($this->webhookListeners);
	}

	/**
	 * Register PayPal Commerce related classes.
	 *
	 * @since 1.11.0
	 */
	private function registerPayPalCommerceClasses(){
		give()->singleton( SubscriptionProcessor::class);
		give()->singleton( HttpHeader::class);
	}

	/**
	 * Register payment gateway as recurring payment gateway.
	 *
	 * @param $availableGateway
	 *
	 * @since 1.11.0
	 *
	 * @return array
	 */
	public function registerRecurringPaymentGateway( $availableGateway ){
		$availableGateway[GivePayPalCommerce::GATEWAY_ID] = PayPalCommerce::class;

		return $availableGateway;
	}

	/**
	 * Render subscription status opt-in field.
	 *
	 * @since 1.11.0
	 */
	public function addSubscriptionStatusOptInField(){
		View::load( 'admin/subscription-status-optin-field', [
			'subscription' => new Give_Subscription( absint( $_GET['id'] ) )
		], true );
	}
}
