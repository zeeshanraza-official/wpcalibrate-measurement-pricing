<?php
declare(strict_types=1);

namespace WPCalibrate\MeasurementPricing\Licensing;

/**
 * Value object representing a response from a license provider.
 */
class LicenseResponse {

	/**
	 * Constructor.
	 *
	 * @param bool        $success Whether the operation succeeded.
	 * @param string      $status Status code (e.g., 'active', 'inactive', 'expired', 'unconfigured', 'invalid').
	 * @param string|null $expiry Expiration date string or null.
	 * @param string|null $renewal_url Renewal URL or null.
	 * @param string      $message User-facing message.
	 */
	public function __construct(
		public readonly bool $success,
		public readonly string $status,
		public readonly ?string $expiry = null,
		public readonly ?string $renewal_url = null,
		public readonly string $message = ''
	) {}
}
