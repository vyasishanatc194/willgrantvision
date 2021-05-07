<?php

namespace GiveRecurring\DonorDashboard\Routes;

use \WP_REST_Request;
use \Give_Subscription as Subscription;
use \Give\DonorDashboards\Tabs\Contracts\Route as RouteAbstract;

/**
 * @since 2.10.0
 */
class CancelSubscriptionRoute extends RouteAbstract {

	/** @var string */
	public function endpoint() {
		return 'recurring-donations/subscription/cancel';
	}

	public function args() {
		return [
			'id'   => [
				'type'     => 'int',
				'required' => true,
			],
		];
	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return array
	 *
	 * @since 2.10.0
	 */
	public function handleRequest( WP_REST_Request $request ) {

		$subscription = new Subscription( $request->get_param( 'id' ) );
		$gateway = give_recurring_get_gateway_from_subscription( $subscription );

		// Cancel the subscription with the gateway
		if ( $gateway ) {
			$gateway->cancel( $subscription, true );
		}

		// Cancel the subscription with GiveWP
		$subscription->cancel();
		
	}
}
