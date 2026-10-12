<?php
declare(strict_types=1);

namespace WPCalibrate\MeasurementPricing\Pricing;

use InvalidArgumentException;

/**
 * Value object representing a pricing tier range and its corresponding unit rate.
 */
class PricingTier {

	/**
	 * Constructor.
	 *
	 * @param float      $min_measurement Minimum measurement threshold (inclusive).
	 * @param float|null $max_measurement Maximum measurement threshold (inclusive), or null for open upper bound.
	 * @param float      $unit_price Unit price applied when chargeable measurement falls in this tier.
	 */
	public function __construct(
		public readonly float $min_measurement,
		public readonly ?float $max_measurement,
		public readonly float $unit_price
	) {
		if ( $this->min_measurement < 0.0 ) {
			throw new InvalidArgumentException( 'Tier minimum measurement cannot be negative.' );
		}

		if ( null !== $this->max_measurement && $this->max_measurement <= $this->min_measurement ) {
			throw new InvalidArgumentException( 'Tier maximum measurement must be strictly greater than minimum measurement.' );
		}

		if ( $this->unit_price < 0.0 ) {
			throw new InvalidArgumentException( 'Tier unit price cannot be negative.' );
		}
	}

	/**
	 * Check if a measurement value falls within this tier range.
	 *
	 * Range is [min, max] (inclusive).
	 *
	 * @param float $measurement
	 * @return bool
	 */
	public function matches( float $measurement ): bool {
		if ( $measurement < $this->min_measurement ) {
			return false;
		}

		if ( null !== $this->max_measurement && $measurement > $this->max_measurement ) {
			return false;
		}

		return true;
	}

	/**
	 * Convert to array.
	 *
	 * @return array{min: float, max: float|null, price: float}
	 */
	public function to_array(): array {
		return array(
			'min'   => $this->min_measurement,
			'max'   => $this->max_measurement,
			'price' => $this->unit_price,
		);
	}

	/**
	 * Factory from raw array.
	 *
	 * @param array<string, mixed> $data
	 * @return self
	 */
	public static function from_array( array $data ): self {
		$min   = (float) ( $data['min'] ?? 0.0 );
		$max   = isset( $data['max'] ) && '' !== $data['max'] && null !== $data['max'] ? (float) $data['max'] : null;
		$price = (float) ( $data['price'] ?? 0.0 );

		return new self( $min, $max, $price );
	}
}
