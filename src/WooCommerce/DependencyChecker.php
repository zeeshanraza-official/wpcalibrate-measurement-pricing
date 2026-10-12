<?php
declare(strict_types=1);

namespace WPCalibrate\MeasurementPricing\WooCommerce;

/**
 * Handles environment and dependency verification for the plugin.
 */
class DependencyChecker {

	/**
	 * Minimum supported PHP version.
	 */
	public const MIN_PHP_VERSION = '8.2.0';

	/**
	 * Minimum supported WordPress version.
	 */
	public const MIN_WP_VERSION = '6.5.0';

	/**
	 * Minimum supported WooCommerce version.
	 */
	public const MIN_WC_VERSION = '8.5.0';

	/**
	 * Check if PHP meets the minimum requirements.
	 *
	 * @return bool
	 */
	public static function is_php_compatible(): bool {
		return version_compare( PHP_VERSION, self::MIN_PHP_VERSION, '>=' );
	}

	/**
	 * Check if WordPress meets the minimum requirements.
	 *
	 * @return bool
	 */
	public static function is_wp_compatible(): bool {
		global $wp_version;
		if ( empty( $wp_version ) ) {
			return true;
		}
		return version_compare( $wp_version, self::MIN_WP_VERSION, '>=' );
	}

	/**
	 * Check if WooCommerce is installed and active.
	 *
	 * @return bool
	 */
	public static function is_woocommerce_active(): bool {
		if ( class_exists( 'WooCommerce' ) ) {
			return true;
		}

		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		return in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins', array() ) ), true )
			|| ( is_multisite() && array_key_exists( 'woocommerce/woocommerce.php', get_site_option( 'active_sitewide_plugins', array() ) ) );
	}

	/**
	 * Check if WooCommerce version meets the minimum requirement.
	 *
	 * @return bool
	 */
	public static function is_wc_version_compatible(): bool {
		if ( ! defined( 'WC_VERSION' ) ) {
			return false;
		}
		return version_compare( WC_VERSION, self::MIN_WC_VERSION, '>=' );
	}

	/**
	 * Run all checks.
	 *
	 * @return array{passed: bool, errors: list<string>}
	 */
	public static function check(): array {
		$errors = array();

		if ( ! self::is_php_compatible() ) {
			$errors[] = sprintf(
				/* translators: 1: Current PHP version, 2: Required PHP version */
				__( 'WPCalibrate Measurement Pricing requires PHP %2$s or higher. Your server is running PHP %1$s.', 'wpcalibrate-measurement-pricing' ),
				esc_html( PHP_VERSION ),
				self::MIN_PHP_VERSION
			);
		}

		if ( ! self::is_woocommerce_active() ) {
			$errors[] = __( 'WPCalibrate Measurement Pricing requires WooCommerce to be installed and active.', 'wpcalibrate-measurement-pricing' );
		} elseif ( ! self::is_wc_version_compatible() ) {
			$errors[] = sprintf(
				/* translators: 1: Current WC version, 2: Required WC version */
				__( 'WPCalibrate Measurement Pricing requires WooCommerce %2$s or higher. Your site is running WooCommerce %1$s.', 'wpcalibrate-measurement-pricing' ),
				defined( 'WC_VERSION' ) ? esc_html( WC_VERSION ) : 'unknown',
				self::MIN_WC_VERSION
			);
		}

		return array(
			'passed' => empty( $errors ),
			'errors' => $errors,
		);
	}
}
