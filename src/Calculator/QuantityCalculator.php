<?php
declare(strict_types=1);

namespace WPCalibrate\MeasurementPricing\Calculator;

use WPCalibrate\MeasurementPricing\Measurement\UnitRegistry;

/**
 * Calculator for custom measured quantity products (e.g. custom count, rolls, packs, sheets).
 */
class QuantityCalculator extends AbstractCalculator {

	/**
	 * Identifier.
	 */
	public const TYPE = 'quantity';

	public function get_type(): string {
		return self::TYPE;
	}

	public function get_label(): string {
		return __( 'Custom Measured Quantity / Units', 'wpcalibrate-measurement-pricing' );
	}

	public function get_dimension(): string {
		return UnitRegistry::DIMENSION_CUSTOM;
	}

	public function get_dimension_definitions(): array {
		return array(
			'quantity' => array(
				'label'        => __( 'Measured Quantity', 'wpcalibrate-measurement-pricing' ),
				'default_unit' => 'piece',
				'dimension'    => UnitRegistry::DIMENSION_CUSTOM,
			),
		);
	}

	public function calculate_raw_measurement(
		array $dimension_values,
		array $dimension_units,
		string $pricing_unit_id
	): float {
		// Custom measured quantity does not convert across physical dimensions.
		return (float) ( $dimension_values['quantity'] ?? 0.0 );
	}
}
