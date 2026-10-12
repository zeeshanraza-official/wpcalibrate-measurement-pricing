<?php
declare(strict_types=1);

namespace WPCalibrate\MeasurementPricing\Calculator;

use WPCalibrate\MeasurementPricing\Validation\ValidationResult;

/**
 * Base abstract class providing common validation and sanitation logic for calculators.
 */
abstract class AbstractCalculator implements CalculatorInterface {

	/**
	 * Validate inputs against definitions and constraints.
	 *
	 * @param array<string, mixed> $raw_inputs
	 * @param array<string, mixed> $field_configs
	 * @return ValidationResult
	 */
	public function validate_inputs( array $raw_inputs, array $field_configs = array() ): ValidationResult {
		$errors            = array();
		$sanitized_inputs  = array();
		$definitions       = $this->get_dimension_definitions();

		foreach ( $definitions as $key => $def ) {
			$raw_value = $raw_inputs[ $key ] ?? null;
			$config    = $field_configs[ $key ] ?? array();
			$label     = $config['label'] ?? $def['label'];

			// Check presence.
			if ( null === $raw_value || '' === trim( (string) $raw_value ) ) {
				$errors[ $key ] = sprintf(
					/* translators: %s: field label */
					__( '%s is required.', 'wpcalibrate-measurement-pricing' ),
					$label
				);
				continue;
			}

			// Clean decimal separators (support comma as decimal separator).
			$str_val = str_replace( ',', '.', trim( (string) $raw_value ) );

			// Numeric and finite check.
			if ( ! is_numeric( $str_val ) ) {
				$errors[ $key ] = sprintf(
					/* translators: %s: field label */
					__( '%s must be a valid number.', 'wpcalibrate-measurement-pricing' ),
					$label
				);
				continue;
			}

			$float_val = (float) $str_val;

			if ( is_nan( $float_val ) || is_infinite( $float_val ) ) {
				$errors[ $key ] = sprintf(
					/* translators: %s: field label */
					__( '%s must be a finite number.', 'wpcalibrate-measurement-pricing' ),
					$label
				);
				continue;
			}

			// Strictly positive by default (measurement cannot be negative or zero unless explicitly allowed).
			if ( $float_val <= 0 ) {
				$errors[ $key ] = sprintf(
					/* translators: %s: field label */
					__( '%s must be greater than zero.', 'wpcalibrate-measurement-pricing' ),
					$label
				);
				continue;
			}

			// Check minimum constraint.
			if ( isset( $config['min'] ) && is_numeric( $config['min'] ) && (float) $config['min'] > 0 ) {
				if ( $float_val < (float) $config['min'] ) {
					$errors[ $key ] = sprintf(
						/* translators: 1: field label, 2: minimum allowed value */
						__( '%1$s cannot be less than %2$s.', 'wpcalibrate-measurement-pricing' ),
						$label,
						$config['min']
					);
					continue;
				}
			}

			// Check maximum constraint.
			if ( isset( $config['max'] ) && is_numeric( $config['max'] ) && (float) $config['max'] > 0 ) {
				if ( $float_val > (float) $config['max'] ) {
					$errors[ $key ] = sprintf(
						/* translators: 1: field label, 2: maximum allowed value */
						__( '%1$s cannot exceed %2$s.', 'wpcalibrate-measurement-pricing' ),
						$label,
						$config['max']
					);
					continue;
				}
			}

			$sanitized_inputs[ $key ] = $float_val;
		}

		return new ValidationResult( empty( $errors ), $errors, $sanitized_inputs );
	}
}
