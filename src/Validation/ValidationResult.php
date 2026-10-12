<?php
declare(strict_types=1);

namespace WPCalibrate\MeasurementPricing\Validation;

/**
 * Value object representing input validation results.
 */
class ValidationResult {

	/**
	 * Constructor.
	 *
	 * @param bool                  $is_valid Whether validation passed.
	 * @param array<string, string> $errors Associative array of field => error message.
	 * @param array<string, float>  $sanitized_inputs Cleaned numeric inputs.
	 */
	public function __construct(
		private bool $is_valid,
		private array $errors = array(),
		private array $sanitized_inputs = array()
	) {}

	/**
	 * Check if validation succeeded.
	 *
	 * @return bool
	 */
	public function is_valid(): bool {
		return $this->is_valid;
	}

	/**
	 * Get error messages.
	 *
	 * @return array<string, string>
	 */
	public function get_errors(): array {
		return $this->errors;
	}

	/**
	 * Get first error message.
	 *
	 * @return string|null
	 */
	public function get_first_error(): ?string {
		if ( empty( $this->errors ) ) {
			return null;
		}
		$values = array_values( $this->errors );
		return $values[0];
	}

	/**
	 * Get sanitized inputs.
	 *
	 * @return array<string, float>
	 */
	public function get_sanitized_inputs(): array {
		return $this->sanitized_inputs;
	}
}
