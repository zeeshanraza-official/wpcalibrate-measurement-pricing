<?php
declare(strict_types=1);

namespace WPCalibrate\MeasurementPricing\Conversion;

use InvalidArgumentException;
use WPCalibrate\MeasurementPricing\Measurement\Unit;
use WPCalibrate\MeasurementPricing\Measurement\UnitRegistry;

/**
 * Service for deterministic unit conversions using high-precision arithmetic.
 */
class ConversionService {

	/**
	 * Calculation scale for BCMath operations.
	 */
	public const CALCULATION_SCALE = 12;

	/**
	 * Convert an amount from one unit to another.
	 *
	 * @param float|string $amount Value to convert.
	 * @param string|Unit  $from_unit Source unit or unit identifier.
	 * @param string|Unit  $to_unit Target unit or unit identifier.
	 * @return float Converted amount.
	 *
	 * @throws InvalidArgumentException When a unit is unknown or dimensions are incompatible.
	 */
	public static function convert( float|string $amount, string|Unit $from_unit, string|Unit $to_unit ): float {
		$source = is_string( $from_unit ) ? UnitRegistry::get( $from_unit ) : $from_unit;
		$target = is_string( $to_unit ) ? UnitRegistry::get( $to_unit ) : $to_unit;

		if ( ! $source instanceof Unit ) {
			throw new InvalidArgumentException( sprintf( 'Unknown source unit: %s', is_string( $from_unit ) ? $from_unit : 'invalid' ) );
		}

		if ( ! $target instanceof Unit ) {
			throw new InvalidArgumentException( sprintf( 'Unknown target unit: %s', is_string( $to_unit ) ? $to_unit : 'invalid' ) );
		}

		// Strictly forbid cross-dimensional conversions.
		if ( $source->get_dimension() !== $target->get_dimension() ) {
			throw new InvalidArgumentException(
				sprintf(
					'Cannot convert incompatible dimensions: "%s" (%s) to "%s" (%s).',
					$source->get_id(),
					$source->get_dimension(),
					$target->get_id(),
					$target->get_dimension()
				)
			);
		}

		// If same unit, return direct value as float.
		if ( $source->get_id() === $target->get_id() ) {
			return (float) $amount;
		}

		$amount_str = self::format_amount_string( $amount );

		if ( function_exists( 'bcmul' ) && function_exists( 'bcdiv' ) ) {
			// Convert source value to canonical base value.
			$canonical_value = bcmul( $amount_str, $source->get_to_canonical_factor(), self::CALCULATION_SCALE );
			// Convert canonical value to target unit.
			$converted_value = bcdiv( $canonical_value, $target->get_to_canonical_factor(), self::CALCULATION_SCALE );
			return (float) $converted_value;
		}

		// Fallback float calculation.
		$canonical = (float) $amount * (float) $source->get_to_canonical_factor();
		return $canonical / (float) $target->get_to_canonical_factor();
	}

	/**
	 * Convert to a precise string representation.
	 *
	 * @param float|string $amount Value to convert.
	 * @param string|Unit  $from_unit Source unit or identifier.
	 * @param string|Unit  $to_unit Target unit or identifier.
	 * @param int          $scale Output decimal scale.
	 * @return string Converted amount as numeric string.
	 */
	public static function convert_to_string( float|string $amount, string|Unit $from_unit, string|Unit $to_unit, int $scale = 6 ): string {
		$converted = self::convert( $amount, $from_unit, $to_unit );
		return number_format( $converted, $scale, '.', '' );
	}

	/**
	 * Format an amount to a clean numeric string for BCMath.
	 *
	 * @param float|string $amount
	 * @return string
	 */
	private static function format_amount_string( float|string $amount ): string {
		if ( is_string( $amount ) ) {
			$amount = trim( $amount );
			if ( is_numeric( $amount ) ) {
				return $amount;
			}
		}
		return sprintf( '%.12F', (float) $amount );
	}
}
