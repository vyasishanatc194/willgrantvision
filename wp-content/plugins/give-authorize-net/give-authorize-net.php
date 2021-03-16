<?php
/**
 * Plugin Name: Give - Authorize.net Gateway
 * Plugin URI:  https://givewp.com/addons/authorize-net-gateway/
 * Description: Give add-on gateway for Authorize.net
 * Version:     1.4.6
 * Author:      GiveWP
 * Author URI:  https://givewp.com/
 * Text Domain: give-authorize
 * Domain Path: /languages
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Constants
if ( ! defined( 'GIVE_AUTHORIZE_VERSION' ) ) {
	define( 'GIVE_AUTHORIZE_VERSION', '1.4.6' );
}
if ( ! defined( 'GIVE_AUTHORIZE_MIN_GIVE_VERSION' ) ) {
	define( 'GIVE_AUTHORIZE_MIN_GIVE_VERSION', '2.2.0' );
}
if ( ! defined( 'GIVE_AUTHORIZE_MIN_PHP_VERSION' ) ) {
	define( 'GIVE_AUTHORIZE_MIN_PHP_VERSION', '5.3' );
}
if ( ! defined( 'GIVE_AUTHORIZE_PLUGIN_FILE' ) ) {
	define( 'GIVE_AUTHORIZE_PLUGIN_FILE', __FILE__ );
}
if ( ! defined( 'GIVE_AUTHORIZE_PLUGIN_DIR' ) ) {
	define( 'GIVE_AUTHORIZE_PLUGIN_DIR', dirname( GIVE_AUTHORIZE_PLUGIN_FILE ) );
}
if ( ! defined( 'GIVE_AUTHORIZE_PLUGIN_URL' ) ) {
	define( 'GIVE_AUTHORIZE_PLUGIN_URL', plugin_dir_url( GIVE_AUTHORIZE_PLUGIN_FILE ) );
}
if ( ! defined( 'GIVE_AUTHORIZE_BASENAME' ) ) {
	define( 'GIVE_AUTHORIZE_BASENAME', plugin_basename( GIVE_AUTHORIZE_PLUGIN_FILE ) );
}

if ( ! class_exists( 'Give_Authorize' ) ) :
	/**
	 * Class Give_Authorize
	 */
	class Give_Authorize {

		/**
		 * @var Give_Authorize The one true Give_Authorize
		 *
		 * @since 1.0
		 */
		private static $instance;

		/**
		 * @var \Give_Authorize_Payments
		 */
		public $payments;

		/**
		 * Notices (array)
		 *
		 * @var array
		 */
		public $notices = array();

		/**
		 * Main Give_Authorize Instance
		 *
		 * Insures that only one instance of Give_Authorize exists in memory at any one
		 * time. Also prevents needing to define globals all over the place.
		 *
		 * @staticvar $instance array
		 *
		 * @return Give_Authorize object
		 */
		public static function get_instance() {
			if ( null === self::$instance ) {
				self::$instance = new self();
				self::$instance->setup();
			}

			return self::$instance;
		}

		/**
		 * Private clone method to prevent cloning of the instance of the
		 * *Singleton* instance.
		 *
		 * @return void
		 */
		private function __clone() {
		}

		/**
		 * Give_Authorize constructor.
		 *
		 * Protected constructor to prevent creating a new instance of the
		 * *Singleton* via the `new` operator from outside of this class.
		 */
		protected function setup() {

			register_activation_hook( GIVE_AUTHORIZE_PLUGIN_FILE, array( $this, 'activation_check' ) );

			add_action( 'admin_init', array( $this, 'check_environment' ) );
			add_action( 'admin_notices', array( $this, 'admin_notices' ), 15 );
			add_action( 'give_init', array( $this, 'init' ) );
			add_action( 'give_loaded', array( $this, 'give_loaded' ), 15 );
		}

		/**
		 * Fire when Give class is loaded
		 *
		 * @since 1.1.2
		 */
		public function give_loaded() {
			if ( ! has_action( 'activate_' . GIVE_PLUGIN_BASENAME, array( $this, 'activation_check' ), 15 ) ) {
				add_action( 'activate_' . GIVE_PLUGIN_BASENAME, array( $this, 'activation_check' ), 15 );
			}
		}

		/**
		 * Init the plugin so environment variables are set.
		 */
		public function init() {
			// Don't hook anything else in the plugin if we're in an incompatible environment.
			if ( ! $this->get_environment_warning() ) {
				return;
			}

			$this->licensing();
			add_filter( 'give_payment_gateways', array( $this, 'register_gateway' ) );
			add_action( 'give_gateway_checkout_label', array( $this, 'customize_payment_label' ), 10, 2 );
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'frontend_scripts' ) );
			load_plugin_textdomain( 'give-authorize', false, dirname( plugin_basename( GIVE_AUTHORIZE_PLUGIN_FILE ) ) . '/languages' );
			$this->includes();

		}

		/**
		 * Allow this class and other classes to add notices.
		 *
		 * @param $slug
		 * @param $class
		 * @param $message
		 */
		public function add_admin_notice( $slug, $class, $message ) {
			$this->notices[ $slug ] = array(
				'class'   => $class,
				'message' => $message,
			);
		}

		/**
		 * Display admin notices.
		 */
		public function admin_notices() {

			$allowed_tags = array(
				'a'      => array(
					'href'  => array(),
					'title' => array(),
					'class' => array(),
					'id'    => array(),
				),
				'br'     => array(),
				'em'     => array(),
				'span'   => array(
					'class' => array(),
				),
				'strong' => array(),
			);

			foreach ( (array) $this->notices as $notice_key => $notice ) {
				echo "<div class='" . esc_attr( $notice['class'] ) . "'><p>";
				echo wp_kses( $notice['message'], $allowed_tags );
				echo '</p></div>';
			}

		}

		/**
		 * The primary sanity check, automatically disable the plugin on activation if it doesn't
		 * meet minimum requirements.
		 */
		public function activation_check() {

			if ( $this->get_environment_warning() && class_exists( 'Give' ) ) {

				// Upgrades.
				if ( file_exists( GIVE_AUTHORIZE_PLUGIN_DIR . '/includes/admin/give-authorize-upgrades.php' ) ) {
					include( GIVE_AUTHORIZE_PLUGIN_DIR . '/includes/admin/give-authorize-upgrades.php' );
				}

				$current_version = get_option( 'give_authorize_version' );

				// Fresh install?
				if ( ! $current_version ) {

					// When new upgrade routines are added, mark them as complete on fresh install.
					$upgrade_routines = array(
						'v13_standardize_authorize_gateway',
					);
					// Mark upgrades complete.
					foreach ( $upgrade_routines as $upgrade ) {
						give_set_upgrade_complete( $upgrade );
					}

				} else {
					// Add Upgraded from option.
					update_option( 'give_authorize_version_upgraded_from', $current_version );
				}

				if ( GIVE_AUTHORIZE_VERSION !== $current_version ) {
					update_option( 'give_authorize_version', GIVE_AUTHORIZE_VERSION );
				}
			}
		}

		/**
		 * Check plugin environment.
		 *
		 * @since  1.0.0
		 * @access public
		 *
		 * @return bool
		 */
		public function check_environment() {
			// Flag to check whether plugin file is loaded or not.
			$is_working = true;

			// Load plugin helper functions.
			if ( ! function_exists( 'is_plugin_active' ) ) {
				require_once ABSPATH . '/wp-admin/includes/plugin.php';
			}

			/* Check to see if Give is activated, if it isn't deactivate and show a banner. */
			// Check for if give plugin activate or not.
			$is_give_active = defined( 'GIVE_PLUGIN_BASENAME' ) ? is_plugin_active( GIVE_PLUGIN_BASENAME ) : false;

			if ( empty( $is_give_active ) ) {
				// Show admin notice.
				$this->add_admin_notice( 'prompt_give_activate', 'error', sprintf( __( '<strong>Activation Error:</strong> You must have the <a href="%s" target="_blank">Give</a> plugin installed and activated for the Authorize.net gateway to activate.', 'give-authorize' ), 'https://givewp.com' ) );
				$is_working = false;
			}

			return $is_working;
		}

		/**
		 * Environment warnings.
		 *
		 * Checks the environment for compatibility problems.
		 * Returns a string with the first incompatibility found or false if the environment has no problems.
		 *
		 * @return bool|mixed|string
		 */
		public function get_environment_warning() {

			// Flag to check whether plugin file is loaded or not.
			$is_working = true;

			// Verify dependency cases.
			if ( defined( 'GIVE_VERSION' ) && version_compare( GIVE_VERSION, GIVE_AUTHORIZE_MIN_GIVE_VERSION, '<' ) ) {

				/* Min. Give. plugin version. */
				// Show admin notice.
				$this->add_admin_notice( 'prompt_give_incompatible', 'error', sprintf( __( '<strong>Activation Error:</strong> You must have the <a href="%s" target="_blank">Give</a> core version %s or above for the Give - Authorize.net gateway add-on to activate.', 'give-authorize' ), 'https://givewp.com', GIVE_AUTHORIZE_MIN_GIVE_VERSION ) );

				$is_working = false;
			}

			if ( ! function_exists( 'curl_init' ) ) {
				$this->add_admin_notice( 'prompt_give_incompatible', 'error', sprintf( __( '<strong>Activation Error:</strong> You must have the <a href="%s" target="_blank">cURL</a> installed for the Give - Authorize.net gateway add-on to activate.', 'give-authorize' ), 'https://givewp.com/documentation/core/requirements/' ) );

				$is_working = false;
			}

			if ( version_compare( phpversion(), GIVE_AUTHORIZE_MIN_PHP_VERSION, '<' ) ) {
				$this->add_admin_notice( 'prompt_give_incompatible', 'error', sprintf( __( '<strong>Activation Error:</strong> You must have the <a href="%s" target="_blank">PHP</a> version %s or above for the Give - Authorize.net gateway add-on to activate.', 'give-authorize' ), 'https://givewp.com/documentation/core/requirements/', GIVE_AUTHORIZE_MIN_PHP_VERSION ) );

				$is_working = false;
			}

			return $is_working;
		}


		/**
		 * Include all files
		 *
		 * @since 1.0.0
		 * @return void
		 */
		private function includes() {

			include_once GIVE_AUTHORIZE_PLUGIN_DIR . '/includes/admin/give-authorize-activation.php';

			if ( ! class_exists( 'Give' ) ) {
				return;
			}

			// Public includes.
			include_once GIVE_AUTHORIZE_PLUGIN_DIR . '/includes/class-authorize-payments.php';
			include_once GIVE_AUTHORIZE_PLUGIN_DIR . '/includes/give-authorize-helpers.php';
			include_once GIVE_AUTHORIZE_PLUGIN_DIR . '/includes/class-authorize-echeck-payments.php';

			// Admin only includes.
			if ( is_admin() ) {
				include_once GIVE_AUTHORIZE_PLUGIN_DIR . '/includes/admin/give-authorize-settings.php';
			}

			// Class Instances.
			$this->payments = new Give_Authorize_Payments();

		}


		/**
		 * Load Admin javascript
		 *
		 * @since  1.3
		 *
		 * @param  $hook
		 *
		 * @return void
		 */
		public function admin_scripts( $hook ) {

			if (
				( isset( $_GET['page'] ) && 'give-settings' === $_GET['page'] )
				&& ( isset( $_GET['section'] ) && 'authorize-net-gateway' === $_GET['section'] )
			) {

				wp_register_style( 'give-authorize-admin-css', GIVE_AUTHORIZE_PLUGIN_URL . 'assets/css/give-authorize-admin.css', false, GIVE_AUTHORIZE_VERSION );
				wp_register_script( 'give-authorize-admin-js', GIVE_AUTHORIZE_PLUGIN_URL . 'assets/js/give-authorize-admin.js', false, GIVE_AUTHORIZE_VERSION );

				wp_enqueue_style( 'give-authorize-admin-css' );
				wp_enqueue_script( 'give-authorize-admin-js' );

			}

		}

		/**
		 * Load Front-end javascript and CSS
		 *
		 * @since  1.3
		 *
		 * @return void
		 */
		public function frontend_scripts() {
			wp_register_style( 'give-authorize-css', GIVE_AUTHORIZE_PLUGIN_URL . 'assets/css/give-authorize.css', false, GIVE_AUTHORIZE_VERSION );
			wp_enqueue_style( 'give-authorize-css' );
		}

		/**
		 * Register Gateway
		 *
		 * Registers the gateway
		 *
		 * @param $gateways
		 *
		 * @return mixed
		 */
		public function register_gateway( $gateways ) {

			// Format: ID => Name
			$gateways['authorize'] = array(
				'admin_label'    => __( 'Authorize.net', 'give-authorize' ),
				'checkout_label' => __( 'Credit Card', 'give-authorize' ),
			);

			/**
			 * Registers a gateway for Authorize.net's
			 * eCheck feature.
			 *
			 */
			$gateways['authorize_echeck'] = array(
				'admin_label'    => __( 'Authorize.net eCheck (ACH)', 'give-authorize' ),
				'checkout_label' => __( 'eCheck (ACH)', 'give-authorize' ),
			);

			return $gateways;
		}

		/**
		 * Customize Payment Label
		 *
		 * @param $label
		 * @param $gateway
		 *
		 * @return string $label
		 */
		public function customize_payment_label( $label, $gateway ) {

			if ( 'authorize' === $gateway ) {
				$label = __( 'Credit Card', 'give-authorize' );
			}

			return $label;
		}

		/**
		 * Authorize.net Licensing
		 */
		function licensing() {
			if ( class_exists( 'Give_License' ) ) {
				new Give_License( GIVE_AUTHORIZE_PLUGIN_FILE, 'Authorize.net Gateway', GIVE_AUTHORIZE_VERSION, 'WordImpress' );
			}
		}

	}

	$GLOBALS['give_authorize'] = Give_Authorize::get_instance();

	/**
	 * Returns class object instance.
	 *
	 * @since 1.3
	 *
	 * @return Give_Authorize bool|object
	 */
	function Give_Authorize() {
		return Give_Authorize::get_instance();
	}

endif; // End if class_exists check.
