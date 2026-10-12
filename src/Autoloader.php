<?php
declare(strict_types=1);

namespace WPCalibrate\MeasurementPricing;

/**
 * Lightweight PSR-4 autoloader for WPCalibrate Measurement Pricing.
 *
 * Ensures zero external autoloader dependency in production runtime.
 */
class Autoloader {

	/**
	 * Namespace prefix to match.
	 *
	 * @var string
	 */
	private const NAMESPACE_PREFIX = 'WPCalibrate\\MeasurementPricing\\';

	/**
	 * Base directory for the namespace.
	 *
	 * @var string
	 */
	private string $base_dir;

	/**
	 * Constructor.
	 *
	 * @param string $base_dir Base directory path for classes (typically plugin src/ directory).
	 */
	public function __construct( string $base_dir ) {
		$this->base_dir = rtrim( $base_dir, '/\\' ) . DIRECTORY_SEPARATOR;
	}

	/**
	 * Register the autoloader with SPL.
	 *
	 * @param string $base_dir Base directory path.
	 * @return void
	 */
	public static function register( string $base_dir ): void {
		$loader = new self( $base_dir );
		spl_autoload_register( array( $loader, 'load_class' ) );
	}

	/**
	 * Load the requested class if it belongs to our namespace.
	 *
	 * @param string $class Fully qualified class name.
	 * @return bool True if loaded, false otherwise.
	 */
	public function load_class( string $class ): bool {
		$len = strlen( self::NAMESPACE_PREFIX );
		if ( strncmp( self::NAMESPACE_PREFIX, $class, $len ) !== 0 ) {
			return false;
		}

		$relative_class = substr( $class, $len );
		$file           = $this->base_dir . str_replace( '\\', DIRECTORY_SEPARATOR, $relative_class ) . '.php';

		if ( file_exists( $file ) ) {
			require_once $file;
			return true;
		}

		return false;
	}
}
