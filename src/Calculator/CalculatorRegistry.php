<?php
declare(strict_types=1);

namespace WPCalibrate\MeasurementPricing\Calculator;

/**
 * Registry of available measurement calculators.
 */
class CalculatorRegistry {

	/**
	 * Cached map of calculator instances.
	 *
	 * @var array<string, CalculatorInterface>|null
	 */
	private static ?array $calculators = null;

	/**
	 * Get all registered calculators.
	 *
	 * @return array<string, CalculatorInterface>
	 */
	public static function get_all(): array {
		if ( null !== self::$calculators ) {
			return self::$calculators;
		}

		$calculators = array(
			LengthCalculator::TYPE   => new LengthCalculator(),
			AreaCalculator::TYPE     => new AreaCalculator(),
			VolumeCalculator::TYPE   => new VolumeCalculator(),
			WeightCalculator::TYPE   => new WeightCalculator(),
			QuantityCalculator::TYPE => new QuantityCalculator(),
		);

		if ( function_exists( 'apply_filters' ) ) {
			/**
			 * Filter available measurement calculators.
			 *
			 * @param array<string, CalculatorInterface> $calculators
			 */
			$calculators = apply_filters( 'wpcalibrate_mp_registered_calculators', $calculators );
		}

		self::$calculators = $calculators;
		return self::$calculators;
	}

	/**
	 * Get a calculator by type.
	 *
	 * @param string $type
	 * @return CalculatorInterface|null
	 */
	public static function get( string $type ): ?CalculatorInterface {
		$all = self::get_all();
		return $all[ $type ] ?? null;
	}

	/**
	 * Reset in-memory cache (for tests).
	 *
	 * @return void
	 */
	public static function reset(): void {
		self::$calculators = null;
	}
}
