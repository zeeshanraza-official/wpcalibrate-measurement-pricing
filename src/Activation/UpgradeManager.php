<?php
declare(strict_types=1);

namespace WPCalibrate\MeasurementPricing\Activation;

use WPCalibrate\MeasurementPricing\WooCommerce\Logger;

/**
 * Handles idempotent versioned schema and data upgrades.
 */
class UpgradeManager {

	/**
	 * Run upgrade routines if current data version is older than target.
	 *
	 * @return void
	 */
	public static function run_upgrades(): void {
		$installed_version = get_option( 'wpcalibrate_mp_data_version', '0.0.0' );

		if ( version_compare( $installed_version, WPCALIBRATE_MP_DATA_VERSION, '<' ) ) {
			self::upgrade( $installed_version );
			update_option( 'wpcalibrate_mp_data_version', WPCALIBRATE_MP_DATA_VERSION );
		}
	}

	/**
	 * Execute specific incremental migrations.
	 *
	 * @param string $from_version Prior installed version.
	 * @return void
	 */
	private static function upgrade( string $from_version ): void {
		// Migration for 1.0.0 baseline.
		if ( version_compare( $from_version, '1.0.0', '<' ) ) {
			self::migrate_to_100();
		}
	}

	/**
	 * Initial 1.0.0 migration.
	 * Ensures default options are fully populated without overriding existing settings.
	 *
	 * @return void
	 */
	private static function migrate_to_100(): void {
		$existing = get_option( 'wpcalibrate_mp_settings', array() );
		$defaults = Activator::get_default_settings();

		if ( ! is_array( $existing ) ) {
			$existing = array();
		}

		$merged = array_merge( $defaults, $existing );
		update_option( 'wpcalibrate_mp_settings', $merged );

		Logger::log( 'info', 'UpgradeManager: Migrated to schema version 1.0.0 successfully.' );
	}
}
