=== Give - Authorize.net Gateway ===
Contributors: givewp
Tags: donations, donation, ecommerce, e-commerce, fundraising, fundraiser, authorize.net, gateway
Requires at least: 4.8
Tested up to: 5.1
Stable tag: 1.4.6
License: GPLv3
License URI: https://opensource.org/licenses/GPL-3.0

Authorize.net Gateway Add-on for Give.

== Description ==

This plugin requires the Give plugin activated to function properly. When activated, it adds a payment gateway for Authorize.net.

== Installation ==

= Minimum Requirements =

* WordPress 4.8 or greater
* PHP version 5.3 or greater
* MySQL version 5.0 or greater
* Some payment gateways require fsockopen support (for IPN access)

= Automatic installation =

Automatic installation is the easiest option as WordPress handles the file transfers itself and you don't need to leave your web browser. To do an automatic install of Give, log in to your WordPress dashboard, navigate to the Plugins menu and click Add New.

In the search field type "Give" and click Search Plugins. Once you have found the plugin you can view details about it such as the the point release, rating and description. Most importantly of course, you can install it by simply clicking "Install Now".

= Manual installation =

The manual installation method involves downloading our donation plugin and uploading it to your server via your favorite FTP application. The WordPress codex contains [instructions on how to do this here](http://codex.wordpress.org/Managing_Plugins#Manual_Plugin_Installation).

= Updating =

Automatic updates should work like a charm; as always though, ensure you backup your site just in case.

== Changelog ==

= 1.4.6: March 21st, 2019 =
* Refactor: Adjusted how settings are output in preparation for Give Core 2.5.0 which drops backwards compability for old CMB2 style of registering settings.

= 1.4.5: January 10th, 2019 =
* Fix: Resolved compatibility issue with PHP 5.4 within Authnet's error response code.

= 1.4.4: January 3rd, 2019 =
* Fix: Reworked how credit card declines and other transaction errors are processed so that they display properly and do not incorrect create a pending payment within the system.

= 1.4.3: September 6th, 2018 =
* New: Added helpful tooltips to the eCheck donation fields.
* New: The plugin now passes the donor's email address to Authorize.net for more complete records at the gateway.
* New: "Held" payments and payments flagged by Authorize.net's Fraud Filter are now marked as pending and a note is added to the donation in the admin panel. When approved at the gateway by an admin, the payments are automatically marked complete via Authorize.net's webhook system.
* New: If a specific credit card is not accepted by the Authorize.net merchant account then a helpful error message will display to the donor prompting them to retry their donation with another credit card type.

= 1.4.2: May 21st, 2018 =
* Fix: Properly send the country code rather than incorrectly sending the state code twice.

= 1.4.1: May 10th, 2018 =
* Fix: Refreshing in the gateway's admin screen would create multiple webhooks within Authorize.net unnecessarily. This could lead to many webhook endpoints being created unnecessarily in the gateway. Please update to this version and remove duplicate webhook entries within your Authorize.net dashboards.

= 1.4: May 2nd, 2018 =
* New: The Authorize.net eCheck payment method is now here! Now your donors can give using their checking/banking information with lower fees.
* Fix: PHP notices when activating gateway without Give Core active.
* Fix: Removed global nag about sandbox secret key if not in test mode.

= 1.3.3: January 16th, 2018 =
* Fix: Resolved CVV validation issue and now the plugin properly verifies transaction approved to prevent issue where declined transactions would go through incorrectly.

= 1.3.2: November 29th, 2017 =
* Fix: Revamped notice for "Signature Key" that displays so that it should now only show depending on whether API keys are entered and if in test mode or not.

= 1.3.1: November 21st, 2017 =
* Fix: Resolved issue with webhook admin "Signature Key" notice not removing even after successful setup.

= 1.3: November 20th, 2017 =
* New: Support for Authorize.net's webhooks is now built in. No more Silent Post URLs. The add-on will automatically attempt to create the webhooks upon upgrading to version 1.3 and if there is an issue errors will be logged and the admin notified.
* New: You can now process refunds directly in Give for Authorize.net transactions.
* New: Customize the Merchant Descriptor within the plugin's settings. This is the details that display within banking transaction details.
* Fix: An upgrade routine will resolve differences with the payment gateway's title to correct donation income reports discrepancies.
* Fix: Prevented a whitescreen from happening if a donation is attempted without properly adding API keys within the plugin's settings.

= 1.2.3 =
* New: Improvements to plugin activation checks and general code optimization.
* Fix: When a custom donation was given the gateway would incorrectly assign it as a donation level within the receipt despite the correct custom amount being processed.

= 1.2.3 =
* Fix: Verify with Authorize.net via API response that the account is properly configured in order to accept a donation. Resolves "Transactions of this market type cannot be processed on this system." error messages.
* Fix: Plugin is now compatible with the unofficial Authorize.net WooCommerce Add-on: https://github.com/impress-org/give-authorize-gateway/issues/40

= 1.2.1 =
* Fix: License field not displaying due to action being invoked at an improper time - https://github.com/impress-org/give-authorize-gateway/issues/21
* Update: Removed test & doc directories from the Authorize.net SDK for a lighter plugin footprint - https://github.com/impress-org/give-authorize-gateway/issues/19

= 1.2 =
* New: Added the ability to disable "Billing Details" fieldset for Authorize.net to optimize donations forms with the least amount of fields possible - https://github.com/impress-org/give-authorize-gateway/issues/16
* Fix: Set the frontend "Payment Method" value for [donation_receipt] to "Credit Card" rather than "authorizenet" - https://github.com/impress-org/give-authorize-gateway/issues/15

= 1.1 =
* New: Class structure introduced for better organization and code quality
* New: Updated API to latest SDK
* New: Separate sandbox credential settings for easier testing
* New: Plugin activation banner with links to support and docs
* Fix: Bug with connecting to Authorizenet for some users

= 1.0 =
* Initial plugin release. Yippee!

