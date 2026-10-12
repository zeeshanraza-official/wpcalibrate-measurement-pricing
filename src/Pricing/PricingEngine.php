<?php
declare(strict_types=1);

namespace WPCalibrate\MeasurementPricing\Pricing;

use Exception;
use WPCalibrate\MeasurementPricing\Calculator\CalculatorInterface;
use WPCalibrate\MeasurementPricing\Calculator\CalculatorRegistry;
use WPCalibrate\MeasurementPricing\Measurement\UnitRegistry;

/**
 * Authoritative, server-side pricing engine for measurement products.
 *
 * Implements a strict, single order of operations for calculating prices.
 */
class PricingEngine {

	/**
	 * Execute authoritative pricing calculation from request.
	 *
	 * @param PricingRequest $request
	 * @return PricingResult
	 */
	public static function calculate( PricingRequest $request ): PricingResult {
		$calculator = CalculatorRegistry::get( $request->calculator_type );

		if ( ! $calculator instanceof CalculatorInterface ) {
			return PricingResult::failure(
				sprintf(
					/* translators: %s: calculator type */
					__( 'Invalid calculator strategy: %s.', 'wpcalibrate-measurement-pricing' ),
					$request->calculator_type
				)
			);
		}

		$pricing_unit = UnitRegistry::get( $request->pricing_unit_id );
		if ( null === $pricing_unit ) {
			return PricingResult::failure(
				sprintf(
					/* translators: %s: unit identifier */
					__( 'Invalid pricing unit: %s.', 'wpcalibrate-measurement-pricing' ),
					$request->pricing_unit_id
				)
			);
		}

		// 1. Calculate raw measurement in the target pricing unit.
		try {
			$raw_measurement = $calculator->calculate_raw_measurement(
				$request->dimension_values,
				$request->dimension_units,
				$request->pricing_unit_id
			);
		} catch ( Exception $e ) {
			return PricingResult::failure( $e->getMessage() );
		}

		if ( $raw_measurement <= 0.0 ) {
			return PricingResult::failure(
				__( 'Calculated measurement must be greater than zero.', 'wpcalibrate-measurement-pricing' )
			);
		}

		// 2. Apply waste percentage.
		$waste_amount = 0.0;
		if ( $request->waste_percentage > 0.0 ) {
			$waste_amount = ( $raw_measurement * $request->waste_percentage ) / 100.0;
		}
		$measurement_after_waste = $raw_measurement + $waste_amount;

		// 3. Apply increment / step rounding.
		$chargeable_measurement = RoundingService::round_to_increment(
			$measurement_after_waste,
			$request->increment,
			$request->rounding_mode,
			$request->precision
		);

		// 4. Enforce product minimum and maximum measurement constraints.
		if ( $request->minimum_measurement > 0.0 && $chargeable_measurement < $request->minimum_measurement ) {
			return PricingResult::failure(
				sprintf(
					/* translators: 1: calculated measurement, 2: minimum allowed measurement, 3: unit symbol */
					__( 'Calculated measurement (%1$s %3$s) is below the minimum required measurement (%2$s %3$s).', 'wpcalibrate-measurement-pricing' ),
					(string) $chargeable_measurement,
					(string) $request->minimum_measurement,
					$pricing_unit->get_symbol()
				)
			);
		}

		if ( $request->maximum_measurement > 0.0 && $chargeable_measurement > $request->maximum_measurement ) {
			return PricingResult::failure(
				sprintf(
					/* translators: 1: calculated measurement, 2: maximum allowed measurement, 3: unit symbol */
					__( 'Calculated measurement (%1$s %3$s) exceeds the maximum allowed measurement (%2$s %3$s).', 'wpcalibrate-measurement-pricing' ),
					(string) $chargeable_measurement,
					(string) $request->maximum_measurement,
					$pricing_unit->get_symbol()
				)
			);
		}

		// 5. Resolve applicable pricing tier.
		$effective_unit_price = TierResolver::resolve(
			$request->pricing_tiers,
			$chargeable_measurement,
			$request->base_unit_price
		);

		// 6. Calculate measurement price.
		$measurement_price = $chargeable_measurement * $effective_unit_price;

		// 7. Add base charge.
		$price_with_base = $measurement_price + $request->base_charge;

		// 8. Apply minimum charge.
		$final_price = max( $price_with_base, $request->minimum_charge );

		// Round final price to currency precision (4 decimals for internal accuracy).
		$final_price = round( $final_price, 4 );

		$result = new PricingResult(
			success: true,
			error: null,
			raw_measurement: round( $raw_measurement, $request->precision ),
			waste_percentage: $request->waste_percentage,
			waste_amount: round( $waste_amount, $request->precision ),
			chargeable_measurement: $chargeable_measurement,
			pricing_unit: $request->pricing_unit_id,
			base_unit_price: $request->base_unit_price,
			effective_unit_price: $effective_unit_price,
			measurement_price: round( $measurement_price, 4 ),
			base_charge: $request->base_charge,
			minimum_charge: $request->minimum_charge,
			final_price: $final_price,
			dimension_values: $request->dimension_values,
			dimension_units: $request->dimension_units
		);

		if ( function_exists( 'apply_filters' ) ) {
			/**
			 * Filter final calculated pricing result.
			 *
			 * @param PricingResult  $result
			 * @param PricingRequest $request
			 */
			$result = apply_filters( 'wpcalibrate_mp_calculated_pricing_result', $result, $request );
		}

		return $result;
	}
}
