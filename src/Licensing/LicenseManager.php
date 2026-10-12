<?php
declare(strict_types=1);

namespace WPCalibrate\MeasurementPricing\Licensing;

/**
 * Manages licensing settings, persistence, masking, and provider operations.
 */
class LicenseManager {

	/**
	 * Option key for license details.
	 */
	public const OPTION_KEY = 'wpcalibrate_mp_license';

	/**
	 * Current provider.
	 *
	 * @var LicenseProviderInterface
	 */
	private LicenseProviderInterface $provider;

	/**
	 * Constructor.
	 *
	 * @param LicenseProviderInterface|null $provider Optional provider.
	 */
	public function __construct( ?LicenseProviderInterface $provider = null ) {
		$this->provider = $provider ?? new NullLicenseProvider();
	}

	/**
	 * Set a custom licensing provider.
	 *
	 * @param LicenseProviderInterface $provider
	 * @return void
	 */
	public function set_provider( LicenseProviderInterface $provider ): void {
		$this->provider = $provider;
	}

	/**
	 * Get the active license provider.
	 *
	 * @return LicenseProviderInterface
	 */
	public function get_provider(): LicenseProviderInterface {
		return $this->provider;
	}

	/**
	 * Get stored license record.
	 *
	 * @return array{
	 *     key: string,
	 *     status: string,
	 *     expiry: string|null,
	 *     renewal_url: string|null,
	 *     last_checked: int|null
	 * }
	 */
	public function get_license_data(): array {
		$default = array(
			'key'          => '',
			'status'       => 'unconfigured',
			'expiry'       => null,
			'renewal_url'  => null,
			'last_checked' => null,
		);

		$data = get_option( self::OPTION_KEY, $default );
		return is_array( $data ) ? array_merge( $default, $data ) : $default;
	}

	/**
	 * Mask a license key for secure UI presentation.
	 *
	 * @param string $key Plain key.
	 * @return string Masked key (e.g., '••••-••••-••••-1234').
	 */
	public static function mask_key( string $key ): string {
		$key = trim( $key );
		if ( empty( $key ) ) {
			return '';
		}

		$len = strlen( $key );
		if ( $len <= 4 ) {
			return str_repeat( '•', $len );
		}

		$visible_suffix = substr( $key, -4 );
		return str_repeat( '•', max( 0, $len - 4 ) ) . $visible_suffix;
	}

	/**
	 * Save a new license key entered by administrator.
	 *
	 * @param string $key
	 * @return void
	 */
	public function save_key( string $key ): void {
		$sanitized_key = sanitize_text_field( trim( $key ) );
		$current_data  = $this->get_license_data();

		$current_data['key']    = $sanitized_key;
		$current_data['status'] = 'inactive';

		update_option( self::OPTION_KEY, $current_data );
	}

	/**
	 * Attempt activation using the provider.
	 *
	 * @return LicenseResponse
	 */
	public function activate(): LicenseResponse {
		$data = $this->get_license_data();
		$key  = $data['key'];

		if ( empty( $key ) ) {
			return new LicenseResponse(
				false,
				'empty_key',
				null,
				null,
				__( 'Please enter a license key before attempting activation.', 'wpcalibrate-measurement-pricing' )
			);
		}

		$response = $this->provider->activate( $key );

		if ( $response->success ) {
			$data['status']       = $response->status;
			$data['expiry']       = $response->expiry;
			$data['renewal_url']  = $response->renewal_url;
			$data['last_checked'] = time();
			update_option( self::OPTION_KEY, $data );
		}

		return $response;
	}

	/**
	 * Attempt deactivation using the provider.
	 *
	 * @return LicenseResponse
	 */
	public function deactivate(): LicenseResponse {
		$data = $this->get_license_data();
		$key  = $data['key'];

		$response = $this->provider->deactivate( $key );

		$data['status']       = 'inactive';
		$data['last_checked'] = time();
		update_option( self::OPTION_KEY, $data );

		return $response;
	}
}
