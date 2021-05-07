<?php
namespace GiveRecurring\Revenue;

use GiveFunds\Repositories\Revenue;

/**
 * Class SubscriptionHandler
 * @package GiveRecurring\Revenue
 *
 * @since 1.11.0
 */
class SubscriptionHandler {
	/**
	 * Add fund id to subscription.
	 *
	 * @param  int  $subscriptionId
	 * @param array $subscriptionData
	 *
	 * @since 1.11.0
	 */
	public function addFundIdToSubscription( $subscriptionId, $subscriptionData ) {
		/* @var Revenue $revenueRepository */
		$revenueRepository = give( Revenue::class );
		$fundId            = $revenueRepository->getDonationFundId( $subscriptionData['parent_payment_id'] );

		give_recurring()->subscription_meta->update_meta( $subscriptionId, 'fund_id', $fundId );
	}
}
