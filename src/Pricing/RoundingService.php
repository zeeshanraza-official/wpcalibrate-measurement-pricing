<?php
declare(strict_types=1);

namespace WPCalibrate\MeasurementPricing\Pricing;

use InvalidArgumentException;

/**
 * Service for deterministic measurement rounding and step/increment alignment.
 */
class RoundingService {

	public const MODE_NEAREST = 'nearest';
	public const MODE_UP      = 'up';
	public const MODE_DOWN    = 'down';

	/**
	 * Round a measurement to the nearest, up, or down increment.
	 *
	 * @param float  $value Measurement value.
	 * @param float  $increment Step/increment (e.g. 0.25, 0.5, 1.0, 0.01).
	 * @param string $mode Rounding mode ('nearest', 'up', 'down').
	 * @param int    $precision Decimal precision to round output.
	 * @return float
	 *
	 * @throws InvalidArgumentException When increment is negative or zero.
	 */
	public static function round_to_increment(
		float $value,
		float $increment = 0.0,
		string $mode = self::MODE_NEAREST,
		int $precision = 4
	): float {
		if ( $value <= 0 ) {
			return 0.0;
		}

		if ( $increment <= 0.0 ) {
			// No increment specified, simply round to configured precision.
			return round( $value, $precision );
		}

		// Calculate number of increments: value / increment.
		$ratio = $value / $increment;

		// Use an epsilon to prevent floating-point representation quirks (e.g. 2.9999999999999996).
		$epsilon = 1e-9;

		$rounded_ratio = match ( strtolower( $mode ) ) {
			self::MODE_UP   => ceil( $ratio - $epsilon ),
			self::MODE_DOWN => floor( $ratio + $epsilon ),
			default         => round( $ratio, 0, PHP_ROUND_HALF_UP ),
		};

		$result = $rounded_ratio * $increment;

		return round( $result, $precision );
	}
}
