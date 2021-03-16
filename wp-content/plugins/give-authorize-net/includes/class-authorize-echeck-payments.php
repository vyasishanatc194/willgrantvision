<?php

/**
 * Class Give_Authorize_Echeck_Payments
 */
class Give_Authorize_Echeck_Payments {

	/**
	 * List of account types
	 *
	 * @var array
	 */
	public static $account_types = '';


	/**
	 * Initializes the variables.
	 */
	public static function init() {
		add_filter( 'give_donation_data_before_gateway', 'Give_Authorize_Echeck_Payments::add_bank_details_to_donation_data' );

		/**
		 * Filter to add another account type.
		 */
		self::$account_types = apply_filters( 'give_add_echeck_account_type', array(
			'checking'         => __( 'Checking', 'give-authorize' ),
			'savings'          => __( 'Savings', 'give-authorize' ),
			'businessChecking' => __( 'Business Checking', 'give-authorize' ),
		) );
	}


	/**
	 * Generates select dropdown list for bank details.
	 *
	 * @param string $select_type account|echeck.
	 * @param string $name        name attribute of the select field.
	 *
	 * @return void.
	 */
	public static function select_fields( $select_type, $name ) {
		$select_array = array();

		switch ( $select_type ) {

			case 'account':
				$select_array = self::$account_types;
				break;

			default:
				break;
		}

		if( ! empty( $select_array ) ) {
			printf( '<select name="%s">', esc_attr( $name ) );

			foreach ( $select_array as $attr => $value ) {
				printf( '<option value="%1$s">%2$s</option>', esc_attr( $attr ), esc_html( $value ) );
			}

			printf( '</select>' );
		}
	}


	/**
	 * Adds bank details to donation data before sending it to the processing.
	 *
	 * @param array $donation_data Array of donation data.
	 *
	 * @return array
	 */
	public static function add_bank_details_to_donation_data( $donation_data ) {
		$gateway = give_clean( $_POST['give-gateway'] );

		if ( 'authorize_echeck' === $gateway ) {
			$donation_data['bank_details'] = array(
				'account-number'  => give_clean( $_POST['account-number'] ),
				'routing-number'  => give_clean( $_POST['routing-number'] ),
				'name-on-account' => give_clean( $_POST['name-on-account'] ),
				'account-type'    => give_clean( $_POST['account-type'] ),
			);
		}

		return $donation_data;
	}
}

Give_Authorize_Echeck_Payments::init();
