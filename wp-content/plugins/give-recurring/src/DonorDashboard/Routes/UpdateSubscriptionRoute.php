<?php

namespace GiveRecurring\DonorDashboard\Routes;

use \WP_REST_Request;
use \Give_Subscription as Subscription;
use \Give_Recurring_Subscriber as Subscriber;
use \Give\DonorDashboards\Tabs\Contracts\Route as RouteAbstract;
use \GiveRecurring\DonorDashboard\Repositories\SubscriptionRepository as SubscriptionRepository;

/**
 * @since 2.10.0
 */
class UpdateSubscriptionRoute extends RouteAbstract {

	/** @var string */
	public function endpoint() {
		return 'recurring-donations/subscription/update';
	}

	public function args() {
		return [
            'id'   => [
				'type'     => 'int',
				'required' => true,
			],
			'payment_method' => [
				'type'	=> 'array',
				'required' => false,
				'sanitize_callback' => [ $this, 'sanitizeArray' ],
			],
			'amount' => [
				'type' => 'int',
				'required' => false,
			]
        ];
	}

	public function sanitizeArray ( $arr ) {
		$sanitizedArr = [];
		if ( $arr ) {
			foreach ( $arr as $key => $value ) {
				$sanitizedArr[$key] = sanitize_text_field( $value );
			}
		}
		return $sanitizedArr;
	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return array
	 *
	 * @since 2.10.0
	 */
	public function handleRequest( WP_REST_Request $request ) {

		// Gather parameters from the request
		$subscription = new Subscription( $request->get_param( 'id' ) );
		$paymentMethod = $request->get_param( 'payment_method' );
		$amount = $request->get_param( 'amount' );

		$gateway = give_recurring_get_gateway_from_subscription( $subscription );

		// Update with gateway
		if ( $gateway && $gateway->can_update( false, $subscription ) ) {

			$subscriber = new Subscriber( $subscription->donor_id );

			if ( !empty($payment_method) ) {
				$data = [];
				foreach ( $paymentMethod as $key => $value ) {
					$data[$key] = $value;
				}
				$gateway->update_payment_method($subscriber, $subscription, $data);
			}

			if ( !empty($amount) ) {
				$data = [
					'give-amount' => $amount,
					'subscription_id' => $subscription->id,
				];
				$gateway->update_subscription($subscriber, $subscription, $data);
			}
		}

		// Update with GiveWP
		if ( !empty($amount) ) {
			$args = [
				'recurring_amount' => $amount,
			];
			$subscription->update( $args );
		}

	}
}