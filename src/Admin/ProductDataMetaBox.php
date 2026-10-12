<?php
declare(strict_types=1);

namespace WPCalibrate\MeasurementPricing\Admin;

use WC_Product;
use WPCalibrate\MeasurementPricing\Calculator\CalculatorRegistry;
use WPCalibrate\MeasurementPricing\Measurement\UnitRegistry;
use WPCalibrate\MeasurementPricing\Product\ProductConfigurationRepository;

/**
 * Adds Measurement Pricing configuration tab and panel to WooCommerce Product Data meta box.
 */
class ProductDataMetaBox {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_filter( 'woocommerce_product_data_tabs', array( $this, 'add_product_data_tab' ) );
		add_action( 'woocommerce_product_data_panels', array( $this, 'render_product_data_panel' ) );
		add_action( 'woocommerce_process_product_meta', array( $this, 'save_product_meta' ), 20, 1 );
	}

	/**
	 * Add custom tab to WooCommerce Product Data tabs.
	 *
	 * @param array<string, mixed> $tabs
	 * @return array<string, mixed>
	 */
	public function add_product_data_tab( array $tabs ): array {
		$tabs['wpcalibrate_mp'] = array(
			'label'    => __( 'Measurement Pricing', 'wpcalibrate-measurement-pricing' ),
			'target'   => 'wpcalibrate_mp_product_data',
			'class'    => array( 'show_if_simple', 'show_if_variable' ),
			'priority' => 65,
		);
		return $tabs;
	}

	/**
	 * Render panel content.
	 *
	 * @return void
	 */
	public function render_product_data_panel(): void {
		global $post;

		$product = wc_get_product( $post->ID );
		if ( ! $product instanceof WC_Product ) {
			return;
		}

		$config      = ProductConfigurationRepository::get( $product );
		$calculators = CalculatorRegistry::get_all();
		$all_units   = UnitRegistry::get_all();

		$calc_options = array();
		foreach ( $calculators as $type => $calc ) {
			$calc_options[ $type ] = $calc->get_label();
		}
		?>
		<div id="wpcalibrate_mp_product_data" class="panel woocommerce_options_panel hidden">
			<!-- Section 1: Activation -->
			<div class="options_group">
				<?php
				woocommerce_wp_checkbox(
					array(
						'id'            => 'wpcalibrate_mp[enabled]',
						'wrapper_class' => 'wpcalibrate_mp_enabled_field',
						'label'         => __( 'Enable Measurement Pricing', 'wpcalibrate-measurement-pricing' ),
						'description'   => __( 'Enable customer-entered measurements and dynamic pricing for this product.', 'wpcalibrate-measurement-pricing' ),
						'value'         => $config->enabled ? 'yes' : 'no',
						'cbvalue'       => 'yes',
					)
				);
				?>
			</div>

			<div class="wpcalibrate-mp-config-fields" style="<?php echo $config->enabled ? '' : 'display:none;'; ?>">
				<!-- Section 2: Mode & Units -->
				<div class="options_group">
					<h4 class="wpcalibrate-mp-section-title"><?php esc_html_e( '1. Measurement Mode & Units', 'wpcalibrate-measurement-pricing' ); ?></h4>

					<?php
					woocommerce_wp_select(
						array(
							'id'          => 'wpcalibrate_mp_calculator_type',
							'name'        => 'wpcalibrate_mp[calculator_type]',
							'label'       => __( 'Measurement mode', 'wpcalibrate-measurement-pricing' ),
							'options'     => $calc_options,
							'value'       => $config->calculator_type,
							'description' => __( 'Select the physical dimension used to calculate the price (e.g. Length, Area, Volume, Weight).', 'wpcalibrate-measurement-pricing' ),
							'desc_tip'    => true,
						)
					);
					?>

					<p class="form-field wpcalibrate_mp_pricing_unit_field">
						<label for="wpcalibrate_mp_pricing_unit"><?php esc_html_e( 'Pricing unit', 'wpcalibrate-measurement-pricing' ); ?></label>
						<select id="wpcalibrate_mp_pricing_unit" name="wpcalibrate_mp[pricing_unit]" class="select short">
							<?php foreach ( $all_units as $unit_id => $unit_obj ) : ?>
								<option value="<?php echo esc_attr( $unit_id ); ?>"
										data-dimension="<?php echo esc_attr( $unit_obj->get_dimension() ); ?>"
										<?php selected( $unit_id, $config->pricing_unit ); ?>>
									<?php echo esc_html( sprintf( '%s (%s)', $unit_obj->get_label(), $unit_obj->get_symbol() ) ); ?>
								</option>
							<?php endforeach; ?>
						</select>
						<?php echo wc_help_tip( __( 'The unit upon which the base price rate is charged (e.g. meter, sq_m, kg).', 'wpcalibrate-measurement-pricing' ) ); ?>
					</p>
				</div>

				<!-- Section 3: Pricing & Charges -->
				<div class="options_group">
					<h4 class="wpcalibrate-mp-section-title"><?php esc_html_e( '2. Pricing & Base Charges', 'wpcalibrate-measurement-pricing' ); ?></h4>

					<?php
					woocommerce_wp_text_input(
						array(
							'id'          => 'wpcalibrate_mp[unit_price]',
							'label'       => sprintf( __( 'Price per unit (%s)', 'wpcalibrate-measurement-pricing' ), get_woocommerce_currency_symbol() ),
							'placeholder' => $product->get_price() ? (string) $product->get_price() : '0.00',
							'description' => __( 'Base rate charged per measurement unit. Leave empty to inherit the standard Product Regular Price.', 'wpcalibrate-measurement-pricing' ),
							'desc_tip'    => true,
							'value'       => $config->unit_price > 0 ? $config->unit_price : '',
							'type'        => 'text',
							'data_type'   => 'price',
						)
					);

					woocommerce_wp_text_input(
						array(
							'id'          => 'wpcalibrate_mp[base_charge]',
							'label'       => sprintf( __( 'Fixed base charge (%s)', 'wpcalibrate-measurement-pricing' ), get_woocommerce_currency_symbol() ),
							'placeholder' => '0.00',
							'description' => __( 'Optional fixed setup or cutting fee added on top of the calculated measurement price.', 'wpcalibrate-measurement-pricing' ),
							'desc_tip'    => true,
							'value'       => $config->base_charge > 0 ? $config->base_charge : '',
							'type'        => 'text',
							'data_type'   => 'price',
						)
					);

					woocommerce_wp_text_input(
						array(
							'id'          => 'wpcalibrate_mp[minimum_charge]',
							'label'       => sprintf( __( 'Minimum charge (%s)', 'wpcalibrate-measurement-pricing' ), get_woocommerce_currency_symbol() ),
							'placeholder' => '0.00',
							'description' => __( 'Minimum total price floor charged for this product regardless of small dimensions.', 'wpcalibrate-measurement-pricing' ),
							'desc_tip'    => true,
							'value'       => $config->minimum_charge > 0 ? $config->minimum_charge : '',
							'type'        => 'text',
							'data_type'   => 'price',
						)
					);
					?>
				</div>

				<!-- Section 4: Rules & Constraints -->
				<div class="options_group">
					<h4 class="wpcalibrate-mp-section-title"><?php esc_html_e( '3. Measurement Rules & Stepping', 'wpcalibrate-measurement-pricing' ); ?></h4>

					<?php
					woocommerce_wp_text_input(
						array(
							'id'          => 'wpcalibrate_mp[min_measurement]',
							'label'       => __( 'Minimum measurement', 'wpcalibrate-measurement-pricing' ),
							'placeholder' => '0',
							'description' => __( 'Smallest total measurement allowed to be purchased (in pricing units).', 'wpcalibrate-measurement-pricing' ),
							'desc_tip'    => true,
							'value'       => $config->min_measurement > 0 ? $config->min_measurement : '',
							'type'        => 'text',
							'data_type'   => 'decimal',
						)
					);

					woocommerce_wp_text_input(
						array(
							'id'          => 'wpcalibrate_mp[max_measurement]',
							'label'       => __( 'Maximum measurement', 'wpcalibrate-measurement-pricing' ),
							'placeholder' => __( 'Unlimited', 'wpcalibrate-measurement-pricing' ),
							'description' => __( 'Largest total measurement allowed to be purchased. Leave blank for unlimited.', 'wpcalibrate-measurement-pricing' ),
							'desc_tip'    => true,
							'value'       => $config->max_measurement > 0 ? $config->max_measurement : '',
							'type'        => 'text',
							'data_type'   => 'decimal',
						)
					);

					woocommerce_wp_text_input(
						array(
							'id'          => 'wpcalibrate_mp[increment]',
							'label'       => __( 'Increment step', 'wpcalibrate-measurement-pricing' ),
							'placeholder' => '0',
							'description' => __( 'Stepping interval (e.g. 0.25, 0.5, 1.0). Customer measurements round according to the selected mode.', 'wpcalibrate-measurement-pricing' ),
							'desc_tip'    => true,
							'value'       => $config->increment > 0 ? $config->increment : '',
							'type'        => 'text',
							'data_type'   => 'decimal',
						)
					);

					woocommerce_wp_select(
						array(
							'id'          => 'wpcalibrate_mp_rounding_mode',
							'name'        => 'wpcalibrate_mp[rounding_mode]',
							'label'       => __( 'Rounding mode', 'wpcalibrate-measurement-pricing' ),
							'options'     => array(
								'nearest' => __( 'Nearest increment', 'wpcalibrate-measurement-pricing' ),
								'up'      => __( 'Round up (Ceiling)', 'wpcalibrate-measurement-pricing' ),
								'down'    => __( 'Round down (Floor)', 'wpcalibrate-measurement-pricing' ),
							),
							'value'       => $config->rounding_mode,
							'description' => __( 'Method used when rounding customer measurements to the increment step.', 'wpcalibrate-measurement-pricing' ),
							'desc_tip'    => true,
						)
					);

					woocommerce_wp_text_input(
						array(
							'id'          => 'wpcalibrate_mp[waste_percentage]',
							'label'       => __( 'Waste / overage (%)', 'wpcalibrate-measurement-pricing' ),
							'placeholder' => '0',
							'description' => __( 'Percentage automatically added to customer measurement for scrap and cutting waste (e.g. 10%).', 'wpcalibrate-measurement-pricing' ),
							'desc_tip'    => true,
							'value'       => $config->waste_percentage > 0 ? $config->waste_percentage : '',
							'type'        => 'text',
							'data_type'   => 'decimal',
						)
					);
					?>
				</div>

				<!-- Section 5: Tiered Pricing -->
				<div class="options_group">
					<h4 class="wpcalibrate-mp-section-title"><?php esc_html_e( '4. Volume / Tiered Pricing Rates', 'wpcalibrate-measurement-pricing' ); ?></h4>
					<p class="description" style="margin-left: 0; padding-left: 0;">
						<?php esc_html_e( 'Optionally define volume tiers where price per unit drops when the customer orders larger total measurements.', 'wpcalibrate-measurement-pricing' ); ?>
					</p>

					<div class="wpcalibrate-mp-tiers-container">
						<table class="widefat wpcalibrate-mp-tiers-table">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Min Measurement', 'wpcalibrate-measurement-pricing' ); ?></th>
									<th><?php esc_html_e( 'Max Measurement', 'wpcalibrate-measurement-pricing' ); ?></th>
									<th><?php esc_html_e( 'Unit Price', 'wpcalibrate-measurement-pricing' ); ?></th>
									<th style="width: 50px;"></th>
								</tr>
							</thead>
							<tbody id="wpcalibrate_mp_tiers_tbody">
								<?php
								if ( ! empty( $config->pricing_tiers ) ) :
									foreach ( $config->pricing_tiers as $index => $tier ) :
								?>
									<tr>
										<td><input type="number" step="any" min="0" name="wpcalibrate_mp[pricing_tiers][<?php echo esc_attr( (string) $index ); ?>][min]" value="<?php echo esc_attr( (string) $tier->min_measurement ); ?>" class="short" required /></td>
										<td><input type="number" step="any" min="0" name="wpcalibrate_mp[pricing_tiers][<?php echo esc_attr( (string) $index ); ?>][max]" value="<?php echo esc_attr( null !== $tier->max_measurement ? (string) $tier->max_measurement : '' ); ?>" class="short" placeholder="<?php esc_attr_e( 'Unlimited', 'wpcalibrate-measurement-pricing' ); ?>" /></td>
										<td><input type="number" step="any" min="0" name="wpcalibrate_mp[pricing_tiers][<?php echo esc_attr( (string) $index ); ?>][price]" value="<?php echo esc_attr( (string) $tier->unit_price ); ?>" class="short" required /></td>
										<td><button type="button" class="button button-small wpcalibrate-mp-remove-tier">&times;</button></td>
									</tr>
								<?php
									endforeach;
								endif;
								?>
							</tbody>
						</table>
						<p style="margin-top: 10px; margin-bottom: 0;">
							<button type="button" class="button button-secondary" id="wpcalibrate_mp_add_tier">
								+ <?php esc_html_e( 'Add Pricing Tier', 'wpcalibrate-measurement-pricing' ); ?>
							</button>
						</p>
					</div>
				</div>

				<!-- Section 6: Customer Display & Instructions -->
				<div class="options_group">
					<h4 class="wpcalibrate-mp-section-title"><?php esc_html_e( '5. Storefront Display & Instructions', 'wpcalibrate-measurement-pricing' ); ?></h4>

					<?php
					woocommerce_wp_textarea_input(
						array(
							'id'          => 'wpcalibrate_mp[help_text]',
							'label'       => __( 'Customer help text', 'wpcalibrate-measurement-pricing' ),
							'placeholder' => __( 'e.g. Enter your exact required dimensions. 10% waste is automatically factored in.', 'wpcalibrate-measurement-pricing' ),
							'description' => __( 'Instructions displayed above the measurement fields on the product page.', 'wpcalibrate-measurement-pricing' ),
							'desc_tip'    => true,
							'value'       => $config->help_text,
						)
					);

					woocommerce_wp_checkbox(
						array(
							'id'          => 'wpcalibrate_mp[show_in_cart]',
							'label'       => __( 'Cart details', 'wpcalibrate-measurement-pricing' ),
							'description' => __( 'Display customer measurements and calculated totals in cart and checkout line items.', 'wpcalibrate-measurement-pricing' ),
							'value'       => $config->show_in_cart ? 'yes' : 'no',
							'cbvalue'     => 'yes',
						)
					);

					woocommerce_wp_checkbox(
						array(
							'id'          => 'wpcalibrate_mp[show_in_order]',
							'label'       => __( 'Order details', 'wpcalibrate-measurement-pricing' ),
							'description' => __( 'Display measurement details in order records and customer transactional emails.', 'wpcalibrate-measurement-pricing' ),
							'value'       => $config->show_in_order ? 'yes' : 'no',
							'cbvalue'     => 'yes',
						)
					);
					?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Save product metadata when product is saved in admin.
	 *
	 * @param int $post_id
	 * @return void
	 */
	public function save_product_meta( int $post_id ): void {
		$product = wc_get_product( $post_id );
		if ( ! $product instanceof WC_Product ) {
			return;
		}

		if ( ! current_user_can( 'edit_product', $post_id ) ) {
			return;
		}

		$data = isset( $_POST['wpcalibrate_mp'] ) && is_array( $_POST['wpcalibrate_mp'] )
			? wp_unslash( $_POST['wpcalibrate_mp'] )
			: array( 'enabled' => 'no' );

		ProductConfigurationRepository::save( $product, $data );
	}
}
