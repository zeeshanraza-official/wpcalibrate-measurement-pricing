<?php
declare(strict_types=1);

namespace WPCalibrate\MeasurementPricing\Measurement;

/**
 * Registry of all physical and custom measurement units.
 */
class UnitRegistry {

	/**
	 * Map of registered units keyed by unit ID.
	 *
	 * @var array<string, Unit>|null
	 */
	private static ?array $units = null;

	/**
	 * Dimensions supported.
	 */
	public const DIMENSION_LENGTH = 'length';
	public const DIMENSION_AREA   = 'area';
	public const DIMENSION_VOLUME = 'volume';
	public const DIMENSION_WEIGHT = 'weight';
	public const DIMENSION_CUSTOM = 'custom';

	/**
	 * Get all registered units.
	 *
	 * @return array<string, Unit>
	 */
	public static function get_all(): array {
		if ( null !== self::$units ) {
			return self::$units;
		}

		$units = array();

		// Length units (Canonical base: 'm')
		$units['mm'] = new Unit( 'mm', self::DIMENSION_LENGTH, __( 'Millimeter', 'wpcalibrate-measurement-pricing' ), 'mm', '0.001' );
		$units['cm'] = new Unit( 'cm', self::DIMENSION_LENGTH, __( 'Centimeter', 'wpcalibrate-measurement-pricing' ), 'cm', '0.01' );
		$units['m']  = new Unit( 'm', self::DIMENSION_LENGTH, __( 'Meter', 'wpcalibrate-measurement-pricing' ), 'm', '1.0' );
		$units['in'] = new Unit( 'in', self::DIMENSION_LENGTH, __( 'Inch', 'wpcalibrate-measurement-pricing' ), 'in', '0.0254' );
		$units['ft'] = new Unit( 'ft', self::DIMENSION_LENGTH, __( 'Foot', 'wpcalibrate-measurement-pricing' ), 'ft', '0.3048' );
		$units['yd'] = new Unit( 'yd', self::DIMENSION_LENGTH, __( 'Yard', 'wpcalibrate-measurement-pricing' ), 'yd', '0.9144' );

		// Area units (Canonical base: 'sq_m')
		$units['sq_mm'] = new Unit( 'sq_mm', self::DIMENSION_AREA, __( 'Square Millimeter', 'wpcalibrate-measurement-pricing' ), 'mm²', '0.000001' );
		$units['sq_cm'] = new Unit( 'sq_cm', self::DIMENSION_AREA, __( 'Square Centimeter', 'wpcalibrate-measurement-pricing' ), 'cm²', '0.0001' );
		$units['sq_m']  = new Unit( 'sq_m', self::DIMENSION_AREA, __( 'Square Meter', 'wpcalibrate-measurement-pricing' ), 'm²', '1.0' );
		$units['sq_in'] = new Unit( 'sq_in', self::DIMENSION_AREA, __( 'Square Inch', 'wpcalibrate-measurement-pricing' ), 'in²', '0.00064516' );
		$units['sq_ft'] = new Unit( 'sq_ft', self::DIMENSION_AREA, __( 'Square Foot', 'wpcalibrate-measurement-pricing' ), 'ft²', '0.09290304' );
		$units['sq_yd'] = new Unit( 'sq_yd', self::DIMENSION_AREA, __( 'Square Yard', 'wpcalibrate-measurement-pricing' ), 'yd²', '0.83612736' );

		// Volume units (Canonical base: 'cu_m')
		$units['cu_mm'] = new Unit( 'cu_mm', self::DIMENSION_VOLUME, __( 'Cubic Millimeter', 'wpcalibrate-measurement-pricing' ), 'mm³', '0.000000001' );
		$units['cu_cm'] = new Unit( 'cu_cm', self::DIMENSION_VOLUME, __( 'Cubic Centimeter', 'wpcalibrate-measurement-pricing' ), 'cm³', '0.000001' );
		$units['cu_m']  = new Unit( 'cu_m', self::DIMENSION_VOLUME, __( 'Cubic Meter', 'wpcalibrate-measurement-pricing' ), 'm³', '1.0' );
		$units['cu_in'] = new Unit( 'cu_in', self::DIMENSION_VOLUME, __( 'Cubic Inch', 'wpcalibrate-measurement-pricing' ), 'in³', '0.000016387064' );
		$units['cu_ft'] = new Unit( 'cu_ft', self::DIMENSION_VOLUME, __( 'Cubic Foot', 'wpcalibrate-measurement-pricing' ), 'ft³', '0.028316846592' );
		$units['cu_yd'] = new Unit( 'cu_yd', self::DIMENSION_VOLUME, __( 'Cubic Yard', 'wpcalibrate-measurement-pricing' ), 'yd³', '0.764554857984' );
		$units['ml']    = new Unit( 'ml', self::DIMENSION_VOLUME, __( 'Milliliter', 'wpcalibrate-measurement-pricing' ), 'ml', '0.000001' );
		$units['l']     = new Unit( 'l', self::DIMENSION_VOLUME, __( 'Liter', 'wpcalibrate-measurement-pricing' ), 'l', '0.001' );

		// Weight units (Canonical base: 'kg')
		$units['g']  = new Unit( 'g', self::DIMENSION_WEIGHT, __( 'Gram', 'wpcalibrate-measurement-pricing' ), 'g', '0.001' );
		$units['kg'] = new Unit( 'kg', self::DIMENSION_WEIGHT, __( 'Kilogram', 'wpcalibrate-measurement-pricing' ), 'kg', '1.0' );
		$units['oz'] = new Unit( 'oz', self::DIMENSION_WEIGHT, __( 'Ounce', 'wpcalibrate-measurement-pricing' ), 'oz', '0.028349523125' );
		$units['lb'] = new Unit( 'lb', self::DIMENSION_WEIGHT, __( 'Pound', 'wpcalibrate-measurement-pricing' ), 'lb', '0.45359237' );

		// Custom units (Canonical base: 'custom')
		$units['piece']  = new Unit( 'piece', self::DIMENSION_CUSTOM, __( 'Piece', 'wpcalibrate-measurement-pricing' ), 'pc', '1.0' );
		$units['roll']   = new Unit( 'roll', self::DIMENSION_CUSTOM, __( 'Roll', 'wpcalibrate-measurement-pricing' ), 'roll', '1.0' );
		$units['pack']   = new Unit( 'pack', self::DIMENSION_CUSTOM, __( 'Pack', 'wpcalibrate-measurement-pricing' ), 'pack', '1.0' );
		$units['sheet']  = new Unit( 'sheet', self::DIMENSION_CUSTOM, __( 'Sheet', 'wpcalibrate-measurement-pricing' ), 'sheet', '1.0' );
		$units['custom'] = new Unit( 'custom', self::DIMENSION_CUSTOM, __( 'Custom Unit', 'wpcalibrate-measurement-pricing' ), 'unit', '1.0' );

		// Extensibility hook.
		if ( function_exists( 'apply_filters' ) ) {
			/**
			 * Filter registered units in the registry.
			 *
			 * @param array<string, Unit> $units
			 */
			$units = apply_filters( 'wpcalibrate_mp_registered_units', $units );
		}

		self::$units = $units;
		return self::$units;
	}

	/**
	 * Get a unit by identifier.
	 *
	 * @param string $unit_id Unit identifier.
	 * @return Unit|null
	 */
	public static function get( string $unit_id ): ?Unit {
		$all = self::get_all();
		return $all[ $unit_id ] ?? null;
	}

	/**
	 * Get units by physical dimension.
	 *
	 * @param string $dimension Physical dimension ('length', 'area', 'volume', 'weight', 'custom').
	 * @return array<string, Unit>
	 */
	public static function get_by_dimension( string $dimension ): array {
		$all      = self::get_all();
		$filtered = array();

		foreach ( $all as $id => $unit ) {
			if ( $unit->get_dimension() === $dimension ) {
				$filtered[ $id ] = $unit;
			}
		}

		return $filtered;
	}

	/**
	 * Reset in-memory cache (primarily for tests).
	 *
	 * @return void
	 */
	public static function reset(): void {
		self::$units = null;
	}
}
