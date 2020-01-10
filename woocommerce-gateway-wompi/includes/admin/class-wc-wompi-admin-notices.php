<?php
defined( 'ABSPATH' ) || exit;

/**
 * Class that represents admin notices.
 */
class WC_Wompi_Admin_Notices {
	/**
	 * Notices (array)
	 */
	public $notices = array();

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );
	}

	/**
	 * Allow this class and other classes to add slug keyed notices (to avoid duplication).
	 */
	public function add_admin_notice( $slug, $class, $message, $dismissible = false ) {
		$this->notices[ $slug ] = array(
			'class'       => $class,
			'message'     => $message,
			'dismissible' => $dismissible,
		);
	}

	/**
	 * Display any notices we've collected thus far.
	 */
	public function admin_notices() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$this->wompi_check_environment();

		foreach ( (array) $this->notices as $notice_key => $notice ) {			
			echo '<div class="' . esc_attr( $notice['class'] ) . '"><p>' . wp_kses( $notice['message'], array( 'a' => array( 'href' => array(), 'target' => array() ) ) ) . '</p></div>';			 
		}
	}

	/**
	 * The backup sanity check, in case the plugin is activated in a weird way,
	 * or the environment changes after activation. Also handles upgrade routines.
	 */
	public function wompi_check_environment() {
		$options            = WC_Wompi::$settings;
		$testmode           = ( isset( $options['testmode'] ) && 'yes' === $options['testmode'] ) ? true : false;
		$test_pub_key       = isset( $options['test_public_key'] ) ? $options['test_public_key'] : '';
		$test_secret_key    = isset( $options['test_private_key'] ) ? $options['test_private_key'] : '';
		$live_pub_key       = isset( $options['public_key'] ) ? $options['public_key'] : '';
		$live_secret_key    = isset( $options['private_key'] ) ? $options['private_key'] : '';

		if ( isset( $options['enabled'] ) && 'yes' === $options['enabled'] ) {

            $secret = WC_Wompi_API::instance()->get_private_key();

            if ( empty( $secret ) && ! ( isset( $_GET['page'], $_GET['section'] ) && 'wc-settings' === $_GET['page'] && 'wompi' === $_GET['section'] ) ) {
                $setting_link = $this->get_setting_link();
                $this->add_admin_notice( 'keys', 'notice notice-warning', sprintf( __( 'Wompi is almost ready. To get started, <a href="%s">set your Wompi account keys</a>.', 'woocommerce-gateway-wompi' ), $setting_link ) );
            }

            // Check if keys are entered properly per live/test mode.
            if ( $testmode ) {
                if (
                    empty( $test_pub_key ) ||
                    empty( $test_secret_key ) ) {
                    $setting_link = $this->get_setting_link();
                    $this->add_admin_notice( 'keys', 'notice notice-error', sprintf( __( 'Wompi is in test mode however your test keys may not be valid. Please go to your settings and, <a href="%s">set your Wompi account keys</a>.', 'woocommerce-gateway-wompi' ), $setting_link ) );
                }
            } else {
                if (
                    empty( $live_pub_key ) ||
                    empty( $live_secret_key ) ) {
                    $setting_link = $this->get_setting_link();
                    $this->add_admin_notice( 'keys', 'notice notice-error', sprintf( __( 'Wompi is in live mode however your live keys may not be valid. Please go to your settings and, <a href="%s">set your Wompi account keys</a>.', 'woocommerce-gateway-wompi' ), $setting_link ) );
                }
            }

            // Supported currency notice
			$gateway = new WC_Gateway_Wompi();																									
			$supported_currency = $gateway->get_supported_currency();
			if ( ! in_array( get_woocommerce_currency(), $supported_currency ) ) {
                $this->add_admin_notice( $gateway->method_title, 'notice notice-error', sprintf( __( '%1$s is enabled - it requires store currency to be set to %2$s', 'woocommerce-gateway-wompi' ), 'WC_Gateway_Wompi', implode( ', ', $supported_currency ) ) );
			}
		}
	}

	/**
	 * Get setting link.
	 */
	public function get_setting_link() {
		$use_id_as_section = function_exists( 'WC' ) ? version_compare( WC()->version, '2.6', '>=' ) : false;

		$section_slug = $use_id_as_section ? 'wompi' : strtolower( 'WC_Gateway_Wompi' );

		return admin_url( 'admin.php?page=wc-settings&tab=checkout&section=' . $section_slug );
	}
}

new WC_Wompi_Admin_Notices();