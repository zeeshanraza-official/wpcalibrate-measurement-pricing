<?php
declare(strict_types=1);

namespace WPCalibrate\MeasurementPricing\Calculator;

use WPCalibrate\MeasurementPricing\Conversion\ConversionService;
use WPCalibrate\MeasurementPricing\Measurement\UnitRegistry;

/**
 * Calculator for weight-based products (e.g. bulk grain, coffee, produce, metal, precious metals).
 */
class WeightCalculator extends AbstractCalculator {

	/**
	 * Identifier.
	 */
	public const TYPE = 'weight';

	public function get_type(): string {
		return self::TYPE;
	}

	public function get_label(): string {
		return __( 'Weight', 'wpcalibrate-measurement-pricing' );
	}

	public function get_dimension(): string {
		return UnitRegistry::DIMENSION_WEIGHT;
	}

	public function get_dimension_definitions(): array {
		return array(
			'weight' => array(
				'label'        => __( 'Weight', 'wpcalibrate-measurement-pricing' ),
				'default_unit' => 'kg',
				'dimension'    => UnitRegistry::DIMENSION_WEIGHT,
			),
		);
	}

	public function calculate_raw_measurement(
		array $dimension_values,
		array $dimension_units,
		string $pricing_unit_id
	): float {
		$raw_weight   = $dimension_values['weight'] ?? 0.0;
		$entered_unit = $dimension_units['weight'] ?? $pricing_unit_id;

		// Convert entered weight unit to target pricing weight unit (e.g. g -> kg, oz -> lb).
		return ConversionService::convert( $raw_weight, $entered_unit, $pricing_unit_id );
	}
}
