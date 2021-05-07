<?php
namespace GiveRecurring\Revenue\Traits;

use GiveFunds\FundsServiceProvider;

/**
 * Class HasFundAddon
 * @package GiveRecurring\Revenue\Traits
 *
 * @since 1.11.0
 */
trait FundAddonTrait {
	/**
	 * check whether or not fund addon active.
	 *
	 * @since 1.11.0
	 *
	 * @return bool
	 */
	public function isFundAddonActive(){
		return class_exists( FundsServiceProvider::class );
	}
}
