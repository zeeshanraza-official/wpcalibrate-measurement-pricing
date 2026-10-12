<?php
/**
 * Single Product Measurement Calculator Template.
 *
 * @package WPCalibrate\MeasurementPricing
 * @var \WC_Product                                         $product
 * @var \WPCalibrate\MeasurementPricing\Product\ProductConfiguration $config
 * @var \WPCalibrate\MeasurementPricing\Calculator\CalculatorInterface|null $calculator
 * @var \WPCalibrate\MeasurementPricing\Measurement\Unit|null $pricing_unit
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! $calculator instanceof \WPCalibrate\MeasurementPricing\Calculator\CalculatorInterface ) {
	return;
}

$product_id   = $product->get_id();
$definitions  = $calculator->get_dimension_definitions();
$unit_symbol  = $pricing_unit ? $pricing_unit->get_symbol() : '';
$is_variable  = $product->is_type( 'variable' );
$initial_hide = $is_variable && ! $config->enabled;

$client_config = array(
	'productId'          => $product_id,
	'isVariable'         => $is_variable,
	'enabled'            => $config->enabled,
	'calculatorType'     => $config->calculator_type,
	'pricingUnit'        => $config->pricing_unit,
	'pricingUnitSymbol'  => $unit_symbol,
	'unitPrice'          => $config->unit_price,
	'baseCharge'         => $config->base_charge,
	'minimumCharge'      => $config->minimum_charge,
	'minMeasurement'     => $config->min_measurement,
	'maxMeasurement'     => $config->max_measurement,
	'increment'          => $config->increment,
	'roundingMode'       => $config->rounding_mode,
	'precision'          => $config->precision,
	'wastePercentage'    => $config->waste_percentage,
	'pricingTiers'       => array_map( static fn( $t ) => $t->to_array(), $config->pricing_tiers ),
);
?>

<div class="wpcalibrate-mp-calculator-container"
	 id="wpcalibrate-mp-calculator-<?php echo esc_attr( (string) $product_id ); ?>"
	 data-config="<?php echo esc_attr( wp_json_encode( $client_config ) ); ?>"
	 style="<?php echo $initial_hide ? 'display: none;' : ''; ?>">

	<div class="wpcalibrate-mp-calculator-card">
		<div class="wpcalibrate-mp-header">
			<h4 class="wpcalibrate-mp-title">
				<?php esc_html_e( 'Measurement & Custom Sizing', 'wpcalibrate-measurement-pricing' ); ?>
			</h4>
			<?php if ( ! empty( $config->help_text ) ) : ?>
				<p class="wpcalibrate-mp-help-text"><?php echo esc_html( $config->help_text ); ?></p>
			<?php endif; ?>
		</div>

		<div class="wpcalibrate-mp-fields">
			<?php foreach ( $definitions as $key => $def ) :
				$field_config = $config->dimension_fields[ $key ] ?? array();
				$field_label  = ! empty( $field_config['label'] ) ? $field_config['label'] : $def['label'];
				$compatible_units = \WPCalibrate\MeasurementPricing\Measurement\UnitRegistry::get_by_dimension( $def['dimension'] );
				$default_unit     = ! empty( $field_config['default_unit'] )
					? $field_config['default_unit']
					: ( ( ! empty( $config->pricing_unit ) && isset( $compatible_units[ $config->pricing_unit ] ) ) ? $config->pricing_unit : $def['default_unit'] );
				$min_val          = ! empty( $field_config['min'] ) ? $field_config['min'] : '0.01';
				$max_val          = ! empty( $field_config['max'] ) ? $field_config['max'] : '';
				$step_val         = ! empty( $field_config['step'] ) ? $field_config['step'] : 'any';
				$input_id         = 'wpcalibrate_mp_' . esc_attr( $key ) . '_' . esc_attr( (string) $product_id );
			?>
				<div class="wpcalibrate-mp-field-group">
					<label for="<?php echo esc_attr( $input_id ); ?>" class="wpcalibrate-mp-field-label">
						<?php echo esc_html( $field_label ); ?>
						<span class="required" aria-hidden="true">*</span>
					</label>

					<div class="wpcalibrate-mp-input-wrap">
						<input type="number"
							   id="<?php echo esc_attr( $input_id ); ?>"
							   name="wpcalibrate_mp_measurements[<?php echo esc_attr( $key ); ?>]"
							   class="wpcalibrate-mp-dimension-input"
							   data-dimension="<?php echo esc_attr( $key ); ?>"
							   min="<?php echo esc_attr( (string) $min_val ); ?>"
							   <?php echo '' !== $max_val ? 'max="' . esc_attr( (string) $max_val ) . '"' : ''; ?>
							   step="<?php echo esc_attr( (string) $step_val ); ?>"
							   placeholder="<?php echo esc_attr( $field_label ); ?>"
							   required
							   aria-required="true" />

						<?php if ( count( $compatible_units ) > 1 ) : ?>
							<select name="wpcalibrate_mp_units[<?php echo esc_attr( $key ); ?>]"
									class="wpcalibrate-mp-unit-select"
									aria-label="<?php echo esc_attr( sprintf( __( '%s unit', 'wpcalibrate-measurement-pricing' ), $field_label ) ); ?>">
								<?php foreach ( $compatible_units as $unit_key => $unit_obj ) : ?>
									<option value="<?php echo esc_attr( $unit_key ); ?>" <?php selected( $unit_key, $default_unit ); ?>>
										<?php echo esc_html( $unit_obj->get_symbol() ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						<?php else :
							$single_unit = reset( $compatible_units );
							$single_key  = $single_unit ? $single_unit->get_id() : $default_unit;
							$single_sym  = $single_unit ? $single_unit->get_symbol() : $default_unit;
						?>
							<span class="wpcalibrate-mp-unit-badge"><?php echo esc_html( $single_sym ); ?></span>
							<input type="hidden"
								   name="wpcalibrate_mp_units[<?php echo esc_attr( $key ); ?>]"
								   value="<?php echo esc_attr( $single_key ); ?>" />
						<?php endif; ?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>

		<!-- Live Calculation Result Card -->
		<div class="wpcalibrate-mp-estimate-box" aria-live="polite">
			<div class="wpcalibrate-mp-estimate-row">
				<span class="wpcalibrate-mp-estimate-label"><?php esc_html_e( 'Total Measurement:', 'wpcalibrate-measurement-pricing' ); ?></span>
				<span class="wpcalibrate-mp-estimate-value">
					<strong class="wpcalibrate-mp-live-measurement">—</strong>
					<span class="wpcalibrate-mp-live-unit"><?php echo esc_html( $unit_symbol ); ?></span>
				</span>
			</div>

			<?php if ( $config->waste_percentage > 0 ) : ?>
				<div class="wpcalibrate-mp-estimate-row wpcalibrate-mp-subtext">
					<span class="wpcalibrate-mp-estimate-label"><?php esc_html_e( 'Included Waste/Overage:', 'wpcalibrate-measurement-pricing' ); ?></span>
					<span class="wpcalibrate-mp-estimate-value">+<?php echo esc_html( (string) $config->waste_percentage ); ?>%</span>
				</div>
			<?php endif; ?>

			<div class="wpcalibrate-mp-estimate-row wpcalibrate-mp-price-row">
				<span class="wpcalibrate-mp-estimate-label"><?php esc_html_e( 'Estimated Calculated Price:', 'wpcalibrate-measurement-pricing' ); ?></span>
				<span class="wpcalibrate-mp-estimate-value">
					<strong class="wpcalibrate-mp-live-price">—</strong>
				</span>
			</div>

			<div class="wpcalibrate-mp-notice" role="alert" style="display: none;"></div>
		</div>
	</div>
</div>
