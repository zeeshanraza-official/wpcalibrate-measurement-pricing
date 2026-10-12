<?php
declare(strict_types=1);

namespace WPCalibrate\MeasurementPricing\Activation;

/**
 * Handles plugin activation routines.
 */
class Activator {

	/**
	 * Run activation tasks.
	 *
	 * @return void
	 */
	public static function activate(): void {
		// Store plugin version if not set.
		if ( false === get_option( 'wpcalibrate_mp_version' ) ) {
			add_option( 'wpcalibrate_mp_version', WPCALIBRATE_MP_VERSION );
		} else {
			update_option( 'wpcalibrate_mp_version', WPCALIBRATE_MP_VERSION );
		}

		// Store data schema version.
		if ( false === get_option( 'wpcalibrate_mp_data_version' ) ) {
			add_option( 'wpcalibrate_mp_data_version', WPCALIBRATE_MP_DATA_VERSION );
		}

		// Initialize default global settings if missing.
		$existing_settings = get_option( 'wpcalibrate_mp_settings', false );
		if ( false === $existing_settings ) {
			add_option( 'wpcalibrate_mp_settings', self::get_default_settings() );
		}

		// Run upgrade manager for any pending schema migrations.
		UpgradeManager::run_upgrades();
	}

	/**
	 * Get default settings for new installations.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_default_settings(): array {
		return array(
			// General settings.
			'enable_plugin'              => 'yes',
			'delete_data_on_uninstall'   => 'no',
			'enable_logging'             => 'no',

			// Display settings.
			'price_display_suffix'       => 'yes', // e.g. "$10.00 / m"
			'show_measurement_in_cart'   => 'yes',
			'show_measurement_in_orders' => 'yes',
			'live_calculator_debounce'   => 150, // ms

			// Calculation settings.
			'default_rounding_mode'      => 'nearest', // nearest, up, down
			'default_waste_percentage'   => 0,
			'strict_boundary_validation' => 'yes',

			// Compatibility settings.
			'enable_blocks_support'      => 'yes',
		);
	}
}
