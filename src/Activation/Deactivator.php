<?php
declare(strict_types=1);

namespace WPCalibrate\MeasurementPricing\Activation;

/**
 * Handles plugin deactivation routines.
 */
class Deactivator {

	/**
	 * Run deactivation tasks.
	 *
	 * @return void
	 */
	public static function deactivate(): void {
		// Clear transients.
		delete_transient( 'wpcalibrate_mp_units_cache' );
		delete_transient( 'wpcalibrate_mp_admin_notice' );

		// Clear any plugin-scheduled cron actions if registered in future.
		wp_clear_scheduled_hook( 'wpcalibrate_mp_scheduled_cleanup' );

		// Flush rewrite rules if needed.
		// Note: We deliberately preserve product metadata, global options, and order history.
	}
}
