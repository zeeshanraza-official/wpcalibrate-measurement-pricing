<?php
declare(strict_types=1);

namespace WPCalibrate\MeasurementPricing\Pricing;

/**
 * Value object encapsulating all inputs and product rules needed to perform an authoritative price calculation.
 */
class PricingRequest {

	/**
	 * Constructor.
	 *
	 * @param int                   $product_id WooCommerce product ID.
	 * @param int                   $variation_id WooCommerce variation ID (0 if simple).
	 * @param string                $calculator_type Calculator strategy type.
	 * @param string                $pricing_unit_id Authoritative pricing unit ID.
	 * @param array<string, float>  $dimension_values Cleaned customer dimension values.
	 * @param array<string, string> $dimension_units Customer-entered dimension units.
	 * @param float                 $base_unit_price Standard price per unit.
	 * @param float                 $base_charge Fixed base charge.
	 * @param float                 $minimum_charge Minimum order charge.
	 * @param float                 $minimum_measurement Minimum allowed measurement.
	 * @param float                 $maximum_measurement Maximum allowed measurement (0 = unlimited).
	 * @param float                 $increment Rounding increment step.
	 * @param string                $rounding_mode Rounding mode ('nearest', 'up', 'down').
	 * @param int                   $precision Decimal precision.
	 * @param float                 $waste_percentage Overage percentage.
	 * @param list<PricingTier>     $pricing_tiers Optional tiered rates.
	 */
	public function __construct(
		public readonly int $product_id,
		public readonly int $variation_id,
		public readonly string $calculator_type,
		public readonly string $pricing_unit_id,
		public readonly array $dimension_values,
		public readonly array $dimension_units,
		public readonly float $base_unit_price,
		public readonly float $base_charge = 0.0,
		public readonly float $minimum_charge = 0.0,
		public readonly float $minimum_measurement = 0.0,
		public readonly float $maximum_measurement = 0.0,
		public readonly float $increment = 0.0,
		public readonly string $rounding_mode = 'nearest',
		public readonly int $precision = 4,
		public readonly float $waste_percentage = 0.0,
		public readonly array $pricing_tiers = array()
	) {}
}
