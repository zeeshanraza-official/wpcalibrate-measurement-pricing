<?php
declare(strict_types=1);

namespace WPCalibrate\MeasurementPricing\Product;

use WPCalibrate\MeasurementPricing\Pricing\PricingTier;

/**
 * Value object representing product or variation measurement pricing configuration.
 */
class ProductConfiguration {

	/**
	 * Constructor.
	 *
	 * @param int                  $product_id WooCommerce product or variation ID.
	 * @param bool                 $enabled Whether measurement pricing is enabled.
	 * @param string               $calculator_type Calculator strategy type ('length', 'area', etc.).
	 * @param string               $pricing_unit Target unit ID for pricing.
	 * @param float                $unit_price Base price per pricing unit.
	 * @param float                $base_charge Fixed base charge.
	 * @param float                $minimum_charge Minimum charge floor.
	 * @param float                $min_measurement Minimum allowable measurement.
	 * @param float                $max_measurement Maximum allowable measurement.
	 * @param float                $increment Step/increment.
	 * @param string               $rounding_mode 'nearest', 'up', 'down'.
	 * @param int                  $precision Decimal precision.
	 * @param float                $waste_percentage Overage percentage.
	 * @param list<PricingTier>    $pricing_tiers List of pricing tiers.
	 * @param array<string, mixed> $dimension_fields Field configuration (labels, min, max, default units).
	 * @param string               $help_text Customer-facing help text.
	 * @param bool                 $show_in_cart Whether to display measurement details in cart.
	 * @param bool                 $show_in_order Whether to display measurement details in order/email.
	 * @param bool                 $is_variation Whether this is a variation configuration.
	 * @param bool                 $override_parent Whether this variation overrides parent settings.
	 */
	public function __construct(
		public readonly int $product_id,
		public readonly bool $enabled,
		public readonly string $calculator_type,
		public readonly string $pricing_unit,
		public readonly float $unit_price,
		public readonly float $base_charge = 0.0,
		public readonly float $minimum_charge = 0.0,
		public readonly float $min_measurement = 0.0,
		public readonly float $max_measurement = 0.0,
		public readonly float $increment = 0.0,
		public readonly string $rounding_mode = 'nearest',
		public readonly int $precision = 4,
		public readonly float $waste_percentage = 0.0,
		public readonly array $pricing_tiers = array(),
		public readonly array $dimension_fields = array(),
		public readonly string $help_text = '',
		public readonly bool $show_in_cart = true,
		public readonly bool $show_in_order = true,
		public readonly bool $is_variation = false,
		public readonly bool $override_parent = false
	) {}

	/**
	 * Create an empty disabled configuration.
	 *
	 * @param int $product_id
	 * @return self
	 */
	public static function disabled( int $product_id ): self {
		return new self(
			product_id: $product_id,
			enabled: false,
			calculator_type: 'length',
			pricing_unit: 'm',
			unit_price: 0.0
		);
	}
}
