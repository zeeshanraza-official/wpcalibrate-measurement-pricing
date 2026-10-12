<?php
declare(strict_types=1);

namespace WPCalibrate\MeasurementPricing\Pricing;

/**
 * Value object containing the complete, immutable results of an authoritative pricing calculation.
 */
class PricingResult {

	/**
	 * Constructor.
	 *
	 * @param bool                  $success Whether calculation succeeded.
	 * @param string|null           $error Error message if failed.
	 * @param float                 $raw_measurement Raw calculated measurement before waste/increments.
	 * @param float                 $waste_percentage Applied waste percentage.
	 * @param float                 $waste_amount Calculated waste quantity added.
	 * @param float                 $chargeable_measurement Final rounded, stepped chargeable measurement.
	 * @param string                $pricing_unit Target unit ID.
	 * @param float                 $base_unit_price Default unit price.
	 * @param float                 $effective_unit_price Effective unit price (after tier resolution).
	 * @param float                 $measurement_price Chargeable measurement * effective unit price.
	 * @param float                 $base_charge Fixed base charge.
	 * @param float                 $minimum_charge Minimum charge floor.
	 * @param float                 $final_price Final price for one unit of product.
	 * @param array<string, float>  $dimension_values Cleaned dimension values.
	 * @param array<string, string> $dimension_units Dimension units.
	 */
	public function __construct(
		public readonly bool $success,
		public readonly ?string $error = null,
		public readonly float $raw_measurement = 0.0,
		public readonly float $waste_percentage = 0.0,
		public readonly float $waste_amount = 0.0,
		public readonly float $chargeable_measurement = 0.0,
		public readonly string $pricing_unit = '',
		public readonly float $base_unit_price = 0.0,
		public readonly float $effective_unit_price = 0.0,
		public readonly float $measurement_price = 0.0,
		public readonly float $base_charge = 0.0,
		public readonly float $minimum_charge = 0.0,
		public readonly float $final_price = 0.0,
		public readonly array $dimension_values = array(),
		public readonly array $dimension_units = array()
	) {}

	/**
	 * Create a failure result.
	 *
	 * @param string $error
	 * @return self
	 */
	public static function failure( string $error ): self {
		return new self(
			success: false,
			error: $error
		);
	}
}
