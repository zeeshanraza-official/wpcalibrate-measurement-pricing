<?php
declare(strict_types=1);

namespace WPCalibrate\MeasurementPricing\Pricing;

use InvalidArgumentException;

/**
 * Service for validating tier configuration and resolving effective unit price based on chargeable measurement.
 */
class TierResolver {

	/**
	 * Validate a collection of pricing tiers for correctness, ordering, and overlap.
	 *
	 * @param array<int, array<string, mixed>> $raw_tiers
	 * @return array{is_valid: bool, error: string|null, tiers: list<PricingTier>}
	 */
	public static function validate_tiers( array $raw_tiers ): array {
		if ( empty( $raw_tiers ) ) {
			return array(
				'is_valid' => true,
				'error'    => null,
				'tiers'    => array(),
			);
		}

		$tiers = array();

		foreach ( $raw_tiers as $index => $raw ) {
			try {
				$tier = PricingTier::from_array( $raw );
				$tiers[] = $tier;
			} catch ( InvalidArgumentException $e ) {
				return array(
					'is_valid' => false,
					'error'    => sprintf(
						/* translators: 1: tier number, 2: error message */
						__( 'Tier #%1$d error: %2$s', 'wpcalibrate-measurement-pricing' ),
						$index + 1,
						$e->getMessage()
					),
					'tiers'    => array(),
				);
			}
		}

		// Verify ordering by min_measurement ascending.
		$count = count( $tiers );
		for ( $i = 0; $i < $count; $i++ ) {
			$current = $tiers[ $i ];

			// An open upper bound (max = null) can only be on the last tier.
			if ( null === $current->max_measurement && $i < ( $count - 1 ) ) {
				return array(
					'is_valid' => false,
					'error'    => sprintf(
						/* translators: %d: tier number */
						__( 'Tier #%d has an open upper bound but is not the final tier.', 'wpcalibrate-measurement-pricing' ),
						$i + 1
					),
					'tiers'    => array(),
				);
			}

			if ( $i > 0 ) {
				$previous = $tiers[ $i - 1 ];

				// Check ordering and overlap.
				if ( $current->min_measurement < $previous->min_measurement ) {
					return array(
						'is_valid' => false,
						'error'    => sprintf(
							/* translators: 1: tier index, 2: previous tier index */
							__( 'Tier #%1$d minimum measurement is lower than Tier #%2$d minimum measurement. Tiers must be ordered ascending.', 'wpcalibrate-measurement-pricing' ),
							$i + 1,
							$i
						),
						'tiers'    => array(),
					);
				}

				if ( null !== $previous->max_measurement && $current->min_measurement < $previous->max_measurement ) {
					return array(
						'is_valid' => false,
						'error'    => sprintf(
							/* translators: 1: tier index, 2: previous tier index */
							__( 'Tier #%1$d overlaps with Tier #%2$d. Tier minimum (%3$s) must be greater than or equal to previous tier maximum (%4$s).', 'wpcalibrate-measurement-pricing' ),
							$i + 1,
							$i,
							(string) $current->min_measurement,
							(string) $previous->max_measurement
						),
						'tiers'    => array(),
					);
				}
			}
		}

		return array(
			'is_valid' => true,
			'error'    => null,
			'tiers'    => $tiers,
		);
	}

	/**
	 * Resolve the effective unit price for a given chargeable measurement.
	 *
	 * @param list<PricingTier> $tiers Validated list of tiers.
	 * @param float             $measurement Chargeable measurement value.
	 * @param float             $default_unit_price Fallback unit price if no tier matches.
	 * @return float Effective unit price.
	 */
	public static function resolve( array $tiers, float $measurement, float $default_unit_price ): float {
		if ( empty( $tiers ) ) {
			return $default_unit_price;
		}

		// Check matching tier.
		foreach ( $tiers as $tier ) {
			if ( $tier->matches( $measurement ) ) {
				return $tier->unit_price;
			}
		}

		// If measurement exceeds all capped tiers and last tier is capped, use the last tier's rate.
		$last_tier = end( $tiers );
		if ( $last_tier instanceof PricingTier && $measurement > ( $last_tier->max_measurement ?? 0.0 ) ) {
			return $last_tier->unit_price;
		}

		return $default_unit_price;
	}
}
