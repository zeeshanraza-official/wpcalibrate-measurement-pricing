<?php
declare(strict_types=1);

namespace WPCalibrate\MeasurementPricing\Licensing;

/**
 * Interface for third-party or custom WPCalibrate licensing providers.
 */
interface LicenseProviderInterface {

	/**
	 * Get the display name of this provider.
	 *
	 * @return string
	 */
	public function get_name(): string;

	/**
	 * Whether this provider is configured with necessary API endpoints/credentials.
	 *
	 * @return bool
	 */
	public function is_configured(): bool;

	/**
	 * Activate a license key with the provider.
	 *
	 * @param string $license_key Masked or raw key.
	 * @return LicenseResponse
	 */
	public function activate( string $license_key ): LicenseResponse;

	/**
	 * Deactivate a license key with the provider.
	 *
	 * @param string $license_key Masked or raw key.
	 * @return LicenseResponse
	 */
	public function deactivate( string $license_key ): LicenseResponse;

	/**
	 * Check status of a license key.
	 *
	 * @param string $license_key Masked or raw key.
	 * @return LicenseResponse
	 */
	public function check_status( string $license_key ): LicenseResponse;
}
