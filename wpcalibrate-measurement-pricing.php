<?php
/**
 * Plugin Name:       WPCalibrate Measurement Pricing for WooCommerce
 * Plugin URI:        https://wpcalibrate.com/plugins/measurement-pricing/
 * Description:       Sell WooCommerce products by length, area, volume, weight, or custom measurements with authoritative server-side pricing, live customer calculations, and tiered pricing.
 * Version:           1.0.3
 * Requires at least: 6.5
 * Requires PHP:      8.2
 * Author:            WPCalibrate
 * Author URI:        https://wpcalibrate.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wpcalibrate-measurement-pricing
 * Domain Path:       /languages
 * Requires Plugins:  woocommerce
 * WC requires at least: 8.5
 * WC tested up to:   11.2.1
 *
 * @package WPCalibrate\MeasurementPricing
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Core plugin constants.
define( 'WPCALIBRATE_MP_VERSION', '1.0.3' );
define( 'WPCALIBRATE_MP_DATA_VERSION', '1.0.3' );
define( 'WPCALIBRATE_MP_FILE', __FILE__ );
define( 'WPCALIBRATE_MP_PATH', plugin_dir_path( __FILE__ ) );
define( 'WPCALIBRATE_MP_URL', plugin_dir_url( __FILE__ ) );
define( 'WPCALIBRATE_MP_BASENAME', plugin_basename( __FILE__ ) );

// Autoloader.
require_once WPCALIBRATE_MP_PATH . 'src/Autoloader.php';
\WPCalibrate\MeasurementPricing\Autoloader::register( WPCALIBRATE_MP_PATH . 'src' );

// High-Performance Order Storage (HPOS) and Blocks compatibility declaration.
add_action(
	'before_woocommerce_init',
	static function (): void {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', WPCALIBRATE_MP_FILE, true );
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', WPCALIBRATE_MP_FILE, true );
		}
	}
);

// Activation and deactivation hooks.
register_activation_hook(
	__FILE__,
	static function (): void {
		\WPCalibrate\MeasurementPricing\Activation\Activator::activate();
	}
);

register_deactivation_hook(
	__FILE__,
	static function (): void {
		\WPCalibrate\MeasurementPricing\Activation\Deactivator::deactivate();
	}
);

/**
 * Bootstrap plugin on plugins_loaded.
 */
add_action(
	'plugins_loaded',
	static function (): void {
		// Verify environment and dependencies.
		$status = \WPCalibrate\MeasurementPricing\WooCommerce\DependencyChecker::check();

		if ( ! $status['passed'] ) {
			// Register admin notice for authorized administrators.
			add_action(
				'admin_notices',
				static function () use ( $status ): void {
					if ( ! current_user_can( 'activate_plugins' ) && ! current_user_can( 'manage_woocommerce' ) ) {
						return;
					}
					foreach ( $status['errors'] as $error ) {
						echo '<div class="notice notice-error is-dismissible"><p>' . esc_html( $error ) . '</p></div>';
					}
				}
			);
			return;
		}

		// Load text domain.
		load_plugin_textdomain(
			'wpcalibrate-measurement-pricing',
			false,
			dirname( WPCALIBRATE_MP_BASENAME ) . '/languages'
		);

		// Initialize core plugin singleton.
		\WPCalibrate\MeasurementPricing\Plugin::get_instance()->init();
	},
	20 // Hook with priority 20 to ensure WooCommerce is fully initialized.
);
