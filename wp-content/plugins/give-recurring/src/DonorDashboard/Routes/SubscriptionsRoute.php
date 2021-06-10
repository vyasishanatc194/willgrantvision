<?php

namespace GiveRecurring\DonorDashboard\Routes;

use \WP_REST_Request;
use \Give\DonorDashboards\Tabs\Contracts\Route as RouteAbstract;
use \GiveRecurring\DonorDashboard\Repositories\SubscriptionRepository as SubscriptionRepository;
use Give\Receipt\DonationReceipt;

/**
 * @since 2.10.0
 */
class SubscriptionsRoute extends RouteAbstract {

	/** @var string */
	public function endpoint() {
		return 'recurring-donations/subscriptions';
	}

	public function args() {
		return [];
	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return array
	 *
	 * @since 2.10.0
	 */
	public function handleRequest( WP_REST_Request $request ) {
		return $this->getData();
	}

	/**
	 * @return array
	 *
	 * @since 2.10.0
	 */
	protected function getData() {

		$query = (new SubscriptionRepository())->getByDonorId( give()->donorDashboard->getId() );

		$subscriptions = [];

		foreach ( $query as $subscription ) {
            $subscriptions[] = [
				'id' => $subscription->id,
				'payment' => $this->getPaymentInfo($subscription),
				'receipt' => $this->getReceiptInfo($subscription),
                'form' => $this->getFormInfo($subscription),
				'gateway' => $this->getGatewayInfo($subscription),
				'donor' => $this->getDonorInfo($subscription),
            ];
        }

		return [
			'subscriptions' => $subscriptions,
		];
	}

	/**
	 * Get icon based on icon HTML string
	 *
	 * @param string $iconHtml
	 * @return string
	 * @since 2.10.0
	 */
	protected function getIcon( $iconHtml ) {

		if ( empty( $iconHtml ) ) {
			return '';
		}

		$iconMap = [
			'user',
			'envelope',
			'globe',
			'calendar',
			'building',
		];

		foreach ( $iconMap as $icon ) {
			if ( strpos( $iconHtml, $icon ) !== false ) {
				return $icon;
			}
		}

	}

	/**
	 * Get currency info
	 *
	 * @param Give_Subscription $subscription
	 * @since 2.10.0
	 * @return array Subscription currency info
	 */
	protected function getCurrencyInfo ( $subscription ) {

		$code = give_get_payment_currency_code( $subscription->parent_payment_id );
		$symbol = give_currency_symbol($code, true);
		$formatting = give_get_currency_formatting_settings( $code );

		return [
			'code' => $code,
			'symbol' => $symbol,
			'numberDecimals' => $formatting['number_decimals'],
			'thousandsSeparator' => $formatting['thousands_separator'],
			'currencyPosition' => $formatting['currency_position'],
			'decimalSeparator' => $formatting['decimal_separator']
		];
	}

	/**
	 * Get gateway info
	 *
	 * @param Give_Subscription $subscription
	 * @since 2.10.0
	 * @return array Subscription gateway info
	 */
	protected function getGatewayInfo( $subscription ) {
		return [
			'id' => $subscription->gateway,
			'can_update' => $subscription->can_update_subscription(),
			'can_cancel' => $subscription->can_cancel(),
		];
	}

	/**
	 * Get form info
	 *
	 * @param Give_Subscription $subscription
	 * @since 2.10.0
	 * @return array Subscription form info
	 */
	protected function getFormInfo( $subscription ) {

		$amountsMeta = give_get_meta( $subscription->form_id, '_give_donation_levels', true );
		$amounts = [];
		foreach ( $amountsMeta as $amount ) {
			$raw = $amount['_give_amount'];
			$amounts[] = [
				'raw' => $raw,
				'formatted' => $this->getFormattedAmount($raw, $subscription)
			];
		}

		return [
			'title' => wp_trim_words( get_the_title( $subscription->form_id ), 6, ' [...]' ),
			'id'    => $subscription->form_id,
			'custom_amount' => give_is_setting_enabled( give_get_meta( $subscription->form_id, '_give_custom_amount', true ) ) ? [
				'minimum' => esc_attr( give_maybe_sanitize_amount( give_get_form_minimum_price( $subscription->form_id ) ) ),
				'maximum' => esc_attr( give_maybe_sanitize_amount( give_get_form_maximum_price( $subscription->form_id ) ) ),
			] : false,
			'amounts' => $amounts,
		];
	}

	/**
	 * Get payment info
	 *
	 * @param Give_Subscription Subscription $subscription
	 * @since 2.10.0
	 * @return array Payment info
	 */
	protected function getPaymentInfo( $subscription ) {

		$gateways = give_get_payment_gateways();
		$interval = ! empty( $subscription->frequency ) ? $subscription->frequency : 1;

		return [
			'frequency' 	=> give_recurring_pretty_subscription_frequency( $subscription->period, false, false, $interval ),
			'amount'   		=> [
				'formatted' => $this->getFormattedAmount($subscription->recurring_amount, $subscription),
				'raw' => $subscription->recurring_amount,
			],
			'currency' 		=> $this->getCurrencyInfo( $subscription ),
			'fee'      		=> $this->getFormattedAmount($subscription->recurring_fee_amount, $subscription),
			'total'    		=> $this->getFormattedAmount(($subscription->recurring_amount + $subscription->recurring_fee_amount), $subscription),
			'method'   		=> $gateways[ $subscription->gateway ]['checkout_label'],
			'status'   		=> $this->getFormattedStatus( $subscription->status ),
			'date'			=> ! empty( $subscription->created ) ? date_i18n( get_option( 'date_format' ), strtotime( $subscription->created ) ) : __( 'N/A', 'give-recurring' ),
			'renewalDate'   => ! empty( $subscription->expiration ) ? date_i18n( get_option( 'date_format' ), strtotime( $subscription->expiration ) ) : __( 'N/A', 'give-recurring' ),
			'progress' 		=> get_times_billed_text( $subscription ),
			'mode'			=> (new \Give_Payment($subscription->parent_payment_id))->get_meta( '_give_payment_mode' ),
			'serialCode'    => give_is_setting_enabled( give_get_option( 'sequential-ordering_status', 'disabled' ) ) ? Give()->seq_donation_number->get_serial_code( $subscription->parent_payment_id ) : $subscription->parent_payment_id,
		];
	}

	/**
	 * Get array containing dynamic receipt information
	 *
	 * @param Give_Payment $payment
	 * @return array
	 * @since 2.10.0
	 */
	protected function getReceiptInfo( $subscription ) {

		$receipt = new DonationReceipt( $subscription->parent_payment_id );

		/**
		 * Fire the action for receipt object.
		 *
		 * @since 2.7.0
		 */
		do_action( 'give_new_receipt', $receipt );

		$receiptArr = [];

		$sectionIndex = 0;
		foreach ( $receipt as $section ) {
			// Continue if section does not have line items.
			if ( ! $section->getLineItems() ) {
				continue;
			}

			if ( 'PDFReceipt' === $section->id ) {
				continue;
			}

			// if ( 'Subscription' !== $section->id ) {
			// 	continue;
			// }

			$receiptArr[ $sectionIndex ]['id'] = $section->id;

			if ( $section->label ) {
				$receiptArr[ $sectionIndex ]['label'] = $section->label;
			}

			/* @var LineItem $lineItem */
			foreach ( $section as $lineItem ) {
				// Continue if line item does not have value.
				if ( ! $lineItem->value ) {
					continue;
				}

				// This class is required to highlight total donation amount in receipt.
				$detailRowClass = '';
				if ( DonationReceipt::DONATIONSECTIONID === $section->id ) {
					$detailRowClass = 'totalAmount' === $lineItem->id ? ' total' : '';
				}

				$label = html_entity_decode( wp_strip_all_tags( $lineItem->label ) );
				$value = html_entity_decode( wp_strip_all_tags( $lineItem->value ) );

				if ( strpos($lineItem->value, 'give-donation-status') ) {
					$value = $this->getFormattedSubscriptionStatus($lineItem->value);
				}

				if ( $lineItem->id === 'paymentStatus' ) {
					$value = $this->getFormattedStatus( get_post_status( $subscription->parent_payment_id ) );
				}

				$receiptArr[ $sectionIndex ]['lineItems'][] = [
					'class' => $detailRowClass,
					'icon'  => $this->getIcon( $lineItem->icon ),
					'label' => $label,
					'value' => $value,
				];

			}

			$sectionIndex++;
		}

		return $receiptArr;
	}

	/**
	 * Get formatted subscription status object (used for rendering status correctly in Donor Dashboard)
	 *
	 * @param string $value
	 * @since 2.10.0
	 * @return array Formatted status object (with color and label)
	 */
	protected function getFormattedSubscriptionStatus( $value ) {
		return [
			'label' => html_entity_decode( wp_strip_all_tags( $value ) ),
			'color' => strpos($value, 'status-active') ? '#7ad03a' : '#888',
		];
	}

	/**
	 * Get formatted status object (used for rendering status correctly in Donor Dashboard)
	 *
	 * @param string $status
	 * @since 2.10.0
	 * @return array Formatted status object (with color and label)
	 */
	protected function getFormattedStatus( $status ) {
		$statusMap = [
			'publish' => [
				'color' => '#7AD03A',
				'label' => esc_html__( 'Complete', 'give-recurring' ),
			],
			'active' => [
				'color' => '#7AD03A',
				'label' => esc_html__( 'Active', 'give-recurring' ),
			],
			'cancelled' => [
				'color' => '#888',
				'label' => esc_html__( 'Cancelled', 'give-recurring' ),
			],
		];

		return isset( $statusMap[ $status ] ) ? $statusMap[ $status ] : [
			'color' => '#FFBA00',
			'label' => esc_html__( 'Unknown', 'give-recurring' ),
		];
	}

	/**
	 * Get formatted payment amount
	 *
	 * @param float $amount
	 * @param Give_Payment $payment
	 * @since 2.10.0
	 * @return string Formatted payment amount (with correct decimals and currency symbol)
	 */
	protected function getformattedAmount( $amount, $subscription ) {
		return give_currency_filter(
			give_format_amount(
				$amount,
				[
					'donation_id' => $subscription->parent_payment_id,
				]
			),
			[
				'currency_code'   => give_get_payment_currency_code( $subscription->parent_payment_id ),
				'decode_currency' => true,
				'sanitize'        => false,
			]
		);
	}

	/**
	 * Get donor info
	 *
	 * @param Give_Payment $payment
	 * @since 2.10.0
	 * @return array Donor info
	 */
	protected function getDonorInfo( $subscription ) {
		return (new \Give_Payment($subscription->parent_payment_id))->user_info;
	}
}
