<?php
declare(strict_types=1);

namespace WPCalibrate\MeasurementPricing\Measurement;

/**
 * Value object representing a measurement unit.
 */
class Unit {

	/**
	 * Constructor.
	 *
	 * @param string $id Unique unit identifier (e.g. 'm', 'cm', 'sq_m', 'kg').
	 * @param string $dimension Physical dimension ('length', 'area', 'volume', 'weight', 'custom').
	 * @param string $label Human-readable name.
	 * @param string $symbol Display symbol (e.g. 'm', 'm²', 'kg').
	 * @param string $to_canonical_factor Conversion multiplier to the canonical dimension unit.
	 */
	public function __construct(
		private string $id,
		private string $dimension,
		private string $label,
		private string $symbol,
		private string $to_canonical_factor
	) {}

	/**
	 * Get unit identifier.
	 *
	 * @return string
	 */
	public function get_id(): string {
		return $this->id;
	}

	/**
	 * Get dimension type.
	 *
	 * @return string
	 */
	public function get_dimension(): string {
		return $this->dimension;
	}

	/**
	 * Get unit label.
	 *
	 * @return string
	 */
	public function get_label(): string {
		return $this->label;
	}

	/**
	 * Get unit display symbol.
	 *
	 * @return string
	 */
	public function get_symbol(): string {
		return $this->symbol;
	}

	/**
	 * Get multiplier to canonical base unit.
	 *
	 * @return string
	 */
	public function get_to_canonical_factor(): string {
		return $this->to_canonical_factor;
	}
}
