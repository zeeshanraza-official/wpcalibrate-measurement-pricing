<?php
declare(strict_types=1);

namespace WPCalibrate\MeasurementPricing\Admin;

use WC_Product;
use WC_Product_Variation;
use WPCalibrate\MeasurementPricing\Measurement\UnitRegistry;
use WPCalibrate\MeasurementPricing\Product\ProductConfigurationRepository;

/**
 * Handles variable product variation overrides and frontend variation data integration.
 */
class VariationMetaBox {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'woocommerce_product_after_variable_attributes', array( $this, 'render_variation_fields' ), 10, 3 );
		add_action( 'woocommerce_save_product_variation', array( $this, 'save_variation_fields' ), 10, 2 );
		add_filter( 'woocommerce_available_variation', array( $this, 'add_variation_data' ), 10, 3 );
	}

	/**
	 * Render variation fields inside WooCommerce Variations tab.
	 *
	 * @param int                  $loop
	 * @param array<string, mixed> $variation_data
	 * @param \WP_Post             $variation_post
	 * @return void
	 */
	public function render_variation_fields( int $loop, array $variation_data, \WP_Post $variation_post ): void {
		$variation = wc_get_product( $variation_post->ID );
		if ( ! $variation instanceof WC_Product ) {
			return;
		}

		$override        = 'yes' === $variation->get_meta( ProductConfigurationRepository::META_PREFIX . 'override_parent', true );
		$unit_price      = $variation->get_meta( ProductConfigurationRepository::META_PREFIX . 'unit_price', true );
		$base_charge     = $variation->get_meta( ProductConfigurationRepository::META_PREFIX . 'base_charge', true );
		$minimum_charge  = $variation->get_meta( ProductConfigurationRepository::META_PREFIX . 'minimum_charge', true );
		?>
		<div class="wpcalibrate-mp-variation-wrapper" style="margin-top: 10px; padding: 10px; background: #fdfdfd; border: 1px solid #e5e5e5; border-radius: 4px;">
			<h4 style="margin: 0 0 8px 0;"><?php esc_html_e( 'Measurement Pricing Overrides', 'wpcalibrate-measurement-pricing' ); ?></h4>
			<p class="form-row form-row-full">
				<label>
					<input type="checkbox"
						   class="wpcalibrate-mp-override-toggle"
						   name="wpcalibrate_mp_variation[<?php echo esc_attr( (string) $loop ); ?>][override_parent]"
						   value="yes"
						   <?php checked( $override ); ?> />
					<?php esc_html_e( 'Override parent measurement pricing settings for this variation', 'wpcalibrate-measurement-pricing' ); ?>
				</label>
			</p>

			<div class="wpcalibrate-mp-variation-fields" style="<?php echo $override ? '' : 'display:none;'; ?>">
				<p class="form-row form-row-first">
					<label><?php esc_html_e( 'Variation Price per Unit', 'wpcalibrate-measurement-pricing' ); ?> (<?php echo esc_html( get_woocommerce_currency_symbol() ); ?>)</label>
					<input type="text"
						   class="wc_input_price"
						   name="wpcalibrate_mp_variation[<?php echo esc_attr( (string) $loop ); ?>][unit_price]"
						   value="<?php echo esc_attr( (string) $unit_price ); ?>"
						   placeholder="<?php esc_attr_e( 'Inherit from parent', 'wpcalibrate-measurement-pricing' ); ?>" />
				</p>

				<p class="form-row form-row-last">
					<label><?php esc_html_e( 'Variation Base Charge', 'wpcalibrate-measurement-pricing' ); ?> (<?php echo esc_html( get_woocommerce_currency_symbol() ); ?>)</label>
					<input type="text"
						   class="wc_input_price"
						   name="wpcalibrate_mp_variation[<?php echo esc_attr( (string) $loop ); ?>][base_charge]"
						   value="<?php echo esc_attr( (string) $base_charge ); ?>"
						   placeholder="<?php esc_attr_e( 'Inherit from parent', 'wpcalibrate-measurement-pricing' ); ?>" />
				</p>

				<p class="form-row form-row-first">
					<label><?php esc_html_e( 'Variation Minimum Charge', 'wpcalibrate-measurement-pricing' ); ?> (<?php echo esc_html( get_woocommerce_currency_symbol() ); ?>)</label>
					<input type="text"
						   class="wc_input_price"
						   name="wpcalibrate_mp_variation[<?php echo esc_attr( (string) $loop ); ?>][minimum_charge]"
						   value="<?php echo esc_attr( (string) $minimum_charge ); ?>"
						   placeholder="<?php esc_attr_e( 'Inherit from parent', 'wpcalibrate-measurement-pricing' ); ?>" />
				</p>
			</div>
		</div>
		<?php
	}

	/**
	 * Save variation fields.
	 *
	 * @param int $variation_id
	 * @param int $i
	 * @return void
	 */
	public function save_variation_fields( int $variation_id, int $i ): void {
		$variation = wc_get_product( $variation_id );
		if ( ! $variation instanceof WC_Product ) {
			return;
		}

		$data = isset( $_POST['wpcalibrate_mp_variation'][ $i ] ) && is_array( $_POST['wpcalibrate_mp_variation'][ $i ] )
			? wp_unslash( $_POST['wpcalibrate_mp_variation'][ $i ] )
			: array();

		ProductConfigurationRepository::save_variation( $variation, $data );
	}

	/**
	 * Enrich WooCommerce variation payload with measurement config for live frontend calculator.
	 *
	 * @param array<string, mixed>  $variation_data
	 * @param WC_Product            $product
	 * @param WC_Product_Variation  $variation
	 * @return array<string, mixed>
	 */
	public function add_variation_data( array $variation_data, WC_Product $product, WC_Product_Variation $variation ): array {
		$config = ProductConfigurationRepository::get( $variation );

		$unit        = UnitRegistry::get( $config->pricing_unit );
		$unit_symbol = $unit ? $unit->get_symbol() : $config->pricing_unit;

		$variation_data['wpcalibrate_mp_config'] = array(
			'productId'         => $variation->get_id(),
			'isVariable'        => true,
			'enabled'           => $config->enabled,
			'calculatorType'    => $config->calculator_type,
			'pricingUnit'       => $config->pricing_unit,
			'pricingUnitSymbol' => $unit_symbol,
			'unitPrice'         => $config->unit_price,
			'baseCharge'        => $config->base_charge,
			'minimumCharge'     => $config->minimum_charge,
			'minMeasurement'    => $config->min_measurement,
			'maxMeasurement'    => $config->max_measurement,
			'increment'         => $config->increment,
			'roundingMode'      => $config->rounding_mode,
			'precision'         => $config->precision,
			'wastePercentage'   => $config->waste_percentage,
			'pricingTiers'      => array_map( static fn( $t ) => $t->to_array(), $config->pricing_tiers ),
		);

		return $variation_data;
	}
}
