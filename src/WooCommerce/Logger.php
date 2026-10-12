<?php
declare(strict_types=1);

namespace WPCalibrate\MeasurementPricing\WooCommerce;

/**
 * Wrapper for WooCommerce logging facilities.
 */
class Logger {

	/**
	 * Log source identifier.
	 */
	public const SOURCE = 'wpcalibrate-measurement-pricing';

	/**
	 * Log a message to WooCommerce logs.
	 *
	 * @param string               $level Log level: 'emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug'.
	 * @param string               $message Message to log.
	 * @param array<string, mixed> $context Additional contextual data (secrets will be masked).
	 * @return void
	 */
	public static function log( string $level, string $message, array $context = array() ): void {
		// Only log if WC is available and logging function exists.
		if ( ! function_exists( 'wc_get_logger' ) ) {
			return;
		}

		// Check if debug logging is enabled or if level is error/warning.
		$settings = get_option( 'wpcalibrate_mp_settings', array() );
		$logging_enabled = ! empty( $settings['enable_logging'] ) && 'yes' === $settings['enable_logging'];

		if ( ! $logging_enabled && in_array( $level, array( 'debug', 'info', 'notice' ), true ) ) {
			return;
		}

		$sanitized_context = self::sanitize_context( $context );
		$logger            = wc_get_logger();

		$logger->log(
			$level,
			$message,
			array_merge(
				array( 'source' => self::SOURCE ),
				$sanitized_context
			)
		);
	}

	/**
	 * Mask sensitive keys in context before logging.
	 *
	 * @param array<string, mixed> $context Raw context data.
	 * @return array<string, mixed> Sanitized context data.
	 */
	private static function sanitize_context( array $context ): array {
		$sensitive_keys = array( 'license_key', 'license', 'key', 'secret', 'password', 'token', 'authorization' );
		$sanitized      = array();

		foreach ( $context as $key => $value ) {
			if ( is_string( $key ) && in_array( strtolower( $key ), $sensitive_keys, true ) ) {
				$sanitized[ $key ] = '***REDACTED***';
			} elseif ( is_array( $value ) ) {
				$sanitized[ $key ] = self::sanitize_context( $value );
			} else {
				$sanitized[ $key ] = $value;
			}
		}

		return $sanitized;
	}
}
