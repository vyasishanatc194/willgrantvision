<?php
namespace GiveRecurring\Revenue\Migrations;

use Give\Framework\Migrations\Contracts\Migration;
use GiveRecurring\Revenue\Repositories\Subscription;

/**
 * Class AddDefaultFundIdToSubscriptionMetadata
 * @package GiveRecurring\Migrations
 *
 * @since 1.11.0
 */
class AddDefaultFundIdToSubscriptionMetadata extends Migration {
	/**
	 * @inheritdoc
	 */
	public function run() {
		/* @var Subscription $subscriptionRepository */
		$subscriptionRepository = give( Subscription::class );

		$subscriptionRepository->setAllToDefaultFundId();
	}

	/**
	 * @inheritdoc
	 */
	public static function id() {
		return 'add-default-fund-id-to-subscription-metadata';
	}

	/**
	 * @inheritdoc
	 */
	public static function title() {
		return 'Add default Fund ID to subscriptions metadata';
	}

	/**
	 * @inheritdoc
	 */
	public static function source() {
		return GIVE_RECURRING_ADDON_NAME;
	}

	/**
	 * @inheritdoc
	 */
	public static function timestamp() {
		return strtotime( '2020-10-1' );
	}
}
