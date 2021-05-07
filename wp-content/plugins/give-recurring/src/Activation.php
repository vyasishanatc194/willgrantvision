<?php
namespace GiveRecurring;

use GiveFunds\FundsServiceProvider;
use GiveRecurring\Revenue\Repositories\Subscription;
use GiveRecurring\Revenue\Traits\FundAddonTrait;

/**
 * Class responsible for registering and handling add-on activation hooks.
 *
 * @package     GiveRecurring
 * @copyright   Copyright (c) 2020, GiveWP
 */
class Activation {
	/**
	 * Activate add-on action hook.
	 *
	 * @since 1.11.0
	 * @return void
	 */
	public static function activateAddon() {
		if( class_exists( FundsServiceProvider::class ) ) {
			/* @var Subscription $subscriptionRepository */
			$subscriptionRepository = give( Subscription::class );
			$subscriptionRepository->setAllToDefaultFundId();
		}
	}

	/**
	 * Deactivate add-on action hook.
	 *
	 * @since 1.11.0
	 * @return void
	 */
	public static function deactivateAddon() {}

	/**
	 * Uninstall add-on action hook.
	 *
	 * @since 1.11.0
	 * @return void
	 */
	public static function uninstallAddon() {}
}
