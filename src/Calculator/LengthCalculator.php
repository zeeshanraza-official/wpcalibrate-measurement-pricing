<?php
declare(strict_types=1);

namespace WPCalibrate\MeasurementPricing\Calculator;

use WPCalibrate\MeasurementPricing\Conversion\ConversionService;
use WPCalibrate\MeasurementPricing\Measurement\UnitRegistry;

/**
 * Calculator for 1D length measurements (e.g. fabric, rope, pipe, timber length).
 */
class LengthCalculator extends AbstractCalculator {

	/**
	 * Identifier.
	 */
	public const TYPE = 'length';

	public function get_type(): string {
		return self::TYPE;
	}

	public function get_label(): string {
		return __( 'Length', 'wpcalibrate-measurement-pricing' );
	}

	public function get_dimension(): string {
		return UnitRegistry::DIMENSION_LENGTH;
	}

	public function get_dimension_definitions(): array {
		return array(
			'length' => array(
				'label'        => __( 'Length', 'wpcalibrate-measurement-pricing' ),
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
		$raw_length   = $dimension_values['length'] ?? 0.0;
		$entered_unit = $dimension_units['length'] ?? $pricing_unit_id;

		// Convert entered length directly to the pricing unit.
		return ConversionService::convert( $raw_length, $entered_unit, $pricing_unit_id );
	}
}
