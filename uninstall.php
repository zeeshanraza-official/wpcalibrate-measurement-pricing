<?php
/**
 * Uninstall routine for WPCalibrate Measurement Pricing.
 *
 * Runs only when user clicks "Delete" in the WordPress Plugins administration interface.
 * Preserves data by default unless the explicit opt-in setting is enabled.
 *
 * @package WPCalibrate\MeasurementPricing
 */

declare(strict_types=1);

// Abort if uninstall is not invoked by WordPress.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Check if administrator has opted into deleting all plugin data on uninstall.
$settings     = get_option( 'wpcalibrate_mp_settings', array() );
$delete_data  = ! empty( $settings['delete_data_on_uninstall'] );

if ( ! $delete_data ) {
	// Preserve all data by default.
	return;
}

global $wpdb;

// 1. Delete plugin-owned options.
$plugin_options = array(
	'wpcalibrate_mp_version',
	'wpcalibrate_mp_data_version',
	'wpcalibrate_mp_settings',
	'wpcalibrate_mp_license',
);

foreach ( $plugin_options as $option_name ) {
	delete_option( $option_name );
}

// 2. Delete plugin transients.
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
		'_transient_wpcalibrate_mp_%',
		'_transient_timeout_wpcalibrate_mp_%'
	)
);

// 3. Delete product and variation post metadata prefixed with _wpcalibrate_mp_.
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE %s",
		'_wpcalibrate_mp_%'
	)
);

// Note: Historical WooCommerce order-item measurement records are intentionally preserved
// to safeguard historical accounting, refunds, and order audit trail.
