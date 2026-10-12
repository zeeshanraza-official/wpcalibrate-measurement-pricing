<?php
declare(strict_types=1);

namespace WPCalibrate\MeasurementPricing\Calculator;

use WPCalibrate\MeasurementPricing\Conversion\ConversionService;
use WPCalibrate\MeasurementPricing\Measurement\UnitRegistry;

/**
 * Calculator for 3D volume measurements (e.g. soil, mulch, timber volume, liquid).
 * Formula: length × width × height converted to target volume pricing unit.
 */
class VolumeCalculator extends AbstractCalculator {

	/**
	 * Identifier.
	 */
	public const TYPE = 'volume';

	public function get_type(): string {
		return self::TYPE;
	}

	public function get_label(): string {
		return __( 'Volume (Length × Width × Height)', 'wpcalibrate-measurement-pricing' );
	}

	public function get_dimension(): string {
		return UnitRegistry::DIMENSION_VOLUME;
	}

	public function get_dimension_definitions(): array {
		return array(
			'length' => array(
				'label'        => __( 'Length', 'wpcalibrate-measurement-pricing' ),
				'default_unit' => 'm',
				'dimension'    => UnitRegistry::DIMENSION_LENGTH,
			),
			'width'  => array(
				'label'        => __( 'Width', 'wpcalibrate-measurement-pricing' ),
				'default_unit' => 'm',
				'dimension'    => UnitRegistry::DIMENSION_LENGTH,
			),
			'height' => array(
				'label'        => __( 'Height / Depth', 'wpcalibrate-measurement-pricing' ),
				'default_unit' => 'm',
				'dimension'    => UnitRegistry::DIMENSION_LENGTH,
			),
		);
	}

	public function calculate_raw_measurement(
		array $dimension_values,
		array $dimension_units,
		string $pricing_unit_id
	): float {
		$raw_length = $dimension_values['length'] ?? 0.0;
		$raw_width  = $dimension_values['width'] ?? 0.0;
		$raw_height = $dimension_values['height'] ?? 0.0;

		$length_unit = $dimension_units['length'] ?? 'm';
		$width_unit  = $dimension_units['width'] ?? 'm';
		$height_unit = $dimension_units['height'] ?? 'm';

		// 1. Convert dimensions to canonical meters.
		$length_m = ConversionService::convert( $raw_length, $length_unit, 'm' );
		$width_m  = ConversionService::convert( $raw_width, $width_unit, 'm' );
		$height_m = ConversionService::convert( $raw_height, $height_unit, 'm' );

		// 2. Canonical volume in cubic meters.
		$canonical_volume_cu_m = $length_m * $width_m * $height_m;

		// 3. Convert canonical cu_m to target volume pricing unit (e.g. cu_m, cu_ft, l, ml).
		return ConversionService::convert( $canonical_volume_cu_m, 'cu_m', $pricing_unit_id );
	}
}
