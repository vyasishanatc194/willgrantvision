<?php
/**
 * Give Authorize.net Gateway Activation
 *
 * @package     Give
 * @copyright   Copyright (c) 2017, GiveWP
 * @license     https://opensource.org/licenses/gpl-license GNU Public License
 * @since       1.2
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Give Authorize.net Activation Banner
 *
 * Includes and initializes Give activation banner class.
 *
 * @since 1.1
 */
function give_authorize_activation_banner() {

	if(! class_exists('Give')) {
		return false;
	}

	// Check for activation banner inclusion.
	if ( ! class_exists( 'Give_Addon_Activation_Banner' )
	     && file_exists( GIVE_PLUGIN_DIR . 'includes/admin/class-addon-activation-banner.php' )
	) {

		include GIVE_PLUGIN_DIR . 'includes/admin/class-addon-activation-banner.php';
	}

	// Initialize activation welcome banner.
	if ( class_exists( 'Give_Addon_Activation_Banner' ) ) {

		$args = array(
			'file'              => GIVE_AUTHORIZE_PLUGIN_FILE,
			'name'              => __( 'Authorize.net Gateway', 'give-authorize' ),
			'version'           => GIVE_AUTHORIZE_VERSION,
			'settings_url'      => admin_url( 'edit.php?post_type=give_forms&page=give-settings&tab=gateways&section=authorize-net-gateway' ),
			'documentation_url' => 'http://docs.givewp.com/addon-authorize',
			'support_url'       => 'https://givewp.com/support/',
			'testing'           => false //Never leave as true!
		);

		new Give_Addon_Activation_Banner( $args );

	}

	return false;

}

add_action( 'admin_init', 'give_authorize_activation_banner' );

/**
 * Plugins row action links
 *
 * @since 1.3
 *
 * @param array $actions An array of plugin action links.
 *
 * @return array An array of updated action links.
 */
function give_authorize_plugin_action_links( $actions ) {
	$new_actions = array(
		'settings' => sprintf(
			'<a href="%1$s">%2$s</a>',
			admin_url( 'edit.php?post_type=give_forms&page=give-settings&tab=gateways&section=authorize-net-gateway' ),
			esc_html__( 'Settings', 'give-authorize' )
		),
	);

	return array_merge( $new_actions, $actions );
}

add_filter( 'plugin_action_links_' . GIVE_AUTHORIZE_BASENAME, 'give_authorize_plugin_action_links' );


/**
 * Plugin row meta links
 *
 * @since 1.3
 *
 * @param array  $plugin_meta An array of the plugin's metadata.
 * @param string $plugin_file Path to the plugin file, relative to the plugins directory.
 *
 * @return array
 */
function give_authorize_plugin_row_meta( $plugin_meta, $plugin_file ) {

	if ( $plugin_file != GIVE_AUTHORIZE_BASENAME ) {
		return $plugin_meta;
	}

	$new_meta_links = array(
		sprintf(
			'<a href="%1$s" target="_blank">%2$s</a>',
			esc_url( add_query_arg( array(
					'utm_source'   => 'plugins-page',
					'utm_medium'   => 'plugin-row',
					'utm_campaign' => 'admin',
				), 'http://docs.givewp.com/addon-authorize' )
			),
			esc_html__( 'Documentation', 'give-authorize' )
		),
		sprintf(
			'<a href="%1$s" target="_blank">%2$s</a>',
			esc_url( add_query_arg( array(
					'utm_source'   => 'plugins-page',
					'utm_medium'   => 'plugin-row',
					'utm_campaign' => 'admin',
				), 'https://givewp.com/addons/' )
			),
			esc_html__( 'Add-ons', 'give-authorize' )
		),
	);

	return array_merge( $plugin_meta, $new_meta_links );
}

add_filter( 'plugin_row_meta', 'give_authorize_plugin_row_meta', 10, 2 );
