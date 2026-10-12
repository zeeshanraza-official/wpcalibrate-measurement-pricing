<?php
declare(strict_types=1);

namespace WPCalibrate\MeasurementPricing\Licensing;

/**
 * Null license provider used when no remote licensing provider or API is configured.
 *
 * Explicitly communicates unconfigured state without faking activation or making unauthorized calls.
 */
class NullLicenseProvider implements LicenseProviderInterface {

	/**
	 * Provider display name.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'Standard WPCalibrate Provider (Unconfigured)';
	}

	/**
	 * Indicates whether provider API credentials are configured.
	 *
	 * @return bool
	 */
	public function is_configured(): bool {
		return false;
	}

	/**
	 * Attempt activation.
	 *
	 * @param string $license_key
	 * @return LicenseResponse
	 */
	public function activate( string $license_key ): LicenseResponse {
		return new LicenseResponse(
			false,
			'unconfigured',
			null,
			null,
			__( 'Licensing provider is not configured. No remote licensing API has been connected.', 'wpcalibrate-measurement-pricing' )
		);
	}

	/**
	 * Attempt deactivation.
	 *
	 * @param string $license_key
	 * @return LicenseResponse
	 */
	public function deactivate( string $license_key ): LicenseResponse {
		return new LicenseResponse(
			false,
			'unconfigured',
			null,
			null,
			__( 'Licensing provider is not configured.', 'wpcalibrate-measurement-pricing' )
		);
	}

	/**
	 * Check license status.
	 *
	 * @param string $license_key
	 * @return LicenseResponse
	 */
	public function check_status( string $license_key ): LicenseResponse {
		return new LicenseResponse(
			false,
			'unconfigured',
			null,
			null,
			__( 'Licensing provider is not configured.', 'wpcalibrate-measurement-pricing' )
		);
	}
}
