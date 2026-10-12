<?php
declare(strict_types=1);

namespace WPCalibrate\MeasurementPricing\Calculator;

use WPCalibrate\MeasurementPricing\Conversion\ConversionService;
use WPCalibrate\MeasurementPricing\Measurement\UnitRegistry;

/**
 * Calculator for 2D area measurements (e.g. flooring, glass, carpet, tiles).
 * Formula: width × length (or height) converted to target area pricing unit.
 */
class AreaCalculator extends AbstractCalculator {

	/**
	 * Identifier.
	 */
	public const TYPE = 'area';

	public function get_type(): string {
		return self::TYPE;
	}

	public function get_label(): string {
		return __( 'Area (Width × Length/Height)', 'wpcalibrate-measurement-pricing' );
	}

	public function get_dimension(): string {
		return UnitRegistry::DIMENSION_AREA;
	}

	public function get_dimension_definitions(): array {
		return array(
			'width'  => array(
				'label'        => __( 'Width', 'wpcalibrate-measurement-pricing' ),
				'default_unit' => 'm',
				'dimension'    => UnitRegistry::DIMENSION_LENGTH,
			),
			'length' => array(
				'label'        => __( 'Length / Height', 'wpcalibrate-measurement-pricing' ),
				'default_unit' => 'm',
				'dimension'    => UnitRegistry::DIMENSION_LENGTH,
			),
		);
	}

	public function calculate_raw_measurement(
		array $dimension_values,
		array $dimension_units,
		string $pricing_unit_id
	): float {
		$raw_width   = $dimension_values['width'] ?? 0.0;
		$raw_length  = $dimension_values['length'] ?? 0.0;

		$width_unit  = $dimension_units['width'] ?? 'm';
		$length_unit = $dimension_units['length'] ?? 'm';

		// 1. Convert width and length to canonical meters.
		$width_m  = ConversionService::convert( $raw_width, $width_unit, 'm' );
		$length_m = ConversionService::convert( $raw_length, $length_unit, 'm' );

		// 2. Canonical area in square meters.
		$canonical_area_sq_m = $width_m * $length_m;

		// 3. Convert canonical sq_m to the target pricing area unit (e.g. sq_m, sq_ft, sq_cm).
		return ConversionService::convert( $canonical_area_sq_m, 'sq_m', $pricing_unit_id );
	}
}
