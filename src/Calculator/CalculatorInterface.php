<?php
declare(strict_types=1);

namespace WPCalibrate\MeasurementPricing\Calculator;

use WPCalibrate\MeasurementPricing\Validation\ValidationResult;

/**
 * Interface for measurement calculation strategies.
 */
interface CalculatorInterface {

	/**
	 * Unique calculator type identifier (e.g. 'length', 'area', 'volume', 'weight', 'quantity').
	 *
	 * @return string
	 */
	public function get_type(): string;

	/**
	 * Human-readable label for admin dropdowns.
	 *
	 * @return string
	 */
	public function get_label(): string;

	/**
	 * Dimension category ('length', 'area', 'volume', 'weight', 'custom').
	 *
	 * @return string
	 */
	public function get_dimension(): string;

	/**
	 * List of required customer-facing dimension input definitions.
	 *
	 * @return array<string, array{label: string, default_unit: string, dimension: string}>
	 */
	public function get_dimension_definitions(): array;

	/**
	 * Calculate raw measurement in the target pricing unit.
	 *
	 * @param array<string, float>  $dimension_values Cleaned dimension values.
	 * @param array<string, string> $dimension_units Map of dimension key => entered unit ID.
	 * @param string                $pricing_unit_id Target pricing unit ID.
	 * @return float Chargeable measurement value.
	 */
	public function calculate_raw_measurement(
		array $dimension_values,
		array $dimension_units,
		string $pricing_unit_id
	): float;

	/**
	 * Validate customer input values for this calculator.
	 *
	 * @param array<string, mixed> $raw_inputs Raw customer inputs from request.
	 * @param array<string, mixed> $field_configs Product-configured constraints (min, max, step, required).
	 * @return ValidationResult
	 */
	public function validate_inputs( array $raw_inputs, array $field_configs = array() ): ValidationResult;
}
