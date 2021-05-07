<?php
namespace GiveRecurring\Revenue;

use Give\Revenue\DonationHandler as GiveDonationHandler;
use Give_Payment;
use Give_Subscription;
use GiveFunds\Repositories\Funds;
use Give\Revenue\Repositories\Revenue;
use GiveRecurring\Revenue\Traits\FundAddonTrait;


/**
 * Class DonationHandler
 * @package GiveRecurring\Revenue
 *
 * @since 1.11.0
 */
class DonationHandler {
	use FundAddonTrait;

	/**
	 * Handle new renewal
	 *
	 * @param Give_Payment $donation
	 * @param int $parentDonation
	 */
	public function handle( $donation, $parentDonation ){
		/* @var Revenue $revenueRepository */
		$revenueRepository = give( Revenue::class );
		$subscription = new Give_Subscription( $parentDonation );
		$revenueData = $this->getData( $donation->ID, $subscription );
		$revenueRepository->insert( $revenueData );
	}

	/**
	 * Get revenue data.
	 *
	 * @since 1.11.0
	 *
	 * @param int $donationId
	 * @param Give_Subscription $subscription
	 *
	 * @return array
	 */
	private function getData( $donationId, $subscription ) {
		/* @var GiveDonationHandler $giveWPCoreDonationHandler */
		$giveWPCoreDonationHandler = give( GiveDonationHandler::class );
		$revenueData = $giveWPCoreDonationHandler->getData( $donationId );

		if( $this->isFundAddonActive() ){
			/* @var Funds $fundRepository */
			$fundRepository = give( Funds::class );

			$revenueData['fund_id'] = give_recurring()->subscription_meta->get_meta( $subscription->id, 'fund_id', true );
			$revenueData['fund_id'] = $revenueData['fund_id'] ?: $fundRepository->getDefaultFundId();
		}

		return $revenueData;
	}
}
