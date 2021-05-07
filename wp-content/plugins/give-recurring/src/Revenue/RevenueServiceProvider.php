<?php
namespace GiveRecurring\Revenue;

use Give\Framework\Migrations\MigrationsRegister;
use Give\Helpers\Hooks;
use Give\ServiceProviders\ServiceProvider;
use GiveRecurring\Activation;
use GiveRecurring\Revenue\Admin\SubscriptionEditPage;
use GiveRecurring\Revenue\Migrations\AddDefaultFundIdToSubscriptionMetadata;
use GiveRecurring\Revenue\Traits\FundAddonTrait;

/**
 * Class RevenueServiceProvider
 * @package GiveRecurring\Revenue
 *
 * @since 1.11.0
 */
class RevenueServiceProvider implements ServiceProvider {
	use FundAddonTrait;

	/**
	 * @inheritdoc
	 */
	public function register() {
		give()->singleton( Activation::class );
	}

	/**
	 * @inheritdoc
	 */
	public function boot() {
		$this->registerMigrations();
		Hooks::addAction( 'give_recurring_record_payment', DonationHandler::class, 'handle', 999, 2 );

		if( $this->isFundAddonActive() ) {
			Hooks::addAction( 'give_subscription_inserted', SubscriptionHandler::class, 'addFundIdToSubscription', 10, 2 );
		}

		if( is_admin() ) {
			$this->backendBoot();
		}
	}

	/**
	 * Boot wp backend.
	 *
	 * @since 1.11.0
	 */
	private function backendBoot(){
		if( $this->isFundAddonActive() && current_user_can('edit_give_payments') ) {
			// Fund addon related functionality
			Hooks::addAction( 'give_recurring_add_subscription_detail', SubscriptionEditPage::class, 'handle', 10, 1 );
			Hooks::addAction( 'give_recurring_update_subscription', SubscriptionEditPage::class, 'updateFundId', 10, 1 );
		}
	}

	/**
	 * Register migrations.
	 *
	 * @since 1.11.0
	 */
	private function registerMigrations(){
		if( $this->isFundAddonActive() ) {
			/* @var MigrationsRegister $migrationRegister */
			$migrationRegister = give( MigrationsRegister::class );
			$migrationRegister->addMigration( AddDefaultFundIdToSubscriptionMetadata::class );
		}
	}
}
