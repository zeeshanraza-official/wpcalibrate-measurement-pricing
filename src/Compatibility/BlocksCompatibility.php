<?php
declare(strict_types=1);

namespace WPCalibrate\MeasurementPricing\Compatibility;

use WPCalibrate\MeasurementPricing\Measurement\UnitRegistry;

/**
 * Handles compatibility with WooCommerce Cart and Checkout Blocks and Store API.
 */
class BlocksCompatibility {

	/**
	 * Register blocks and Store API integration hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		// Hook when WooCommerce Blocks are loaded.
		add_action( 'woocommerce_blocks_loaded', array( $this, 'on_blocks_loaded' ) );
	}

	/**
	 * Extend Store API cart line item data if Store API is active.
	 *
	 * @return void
	 */
	public function on_blocks_loaded(): void {
		if ( ! function_exists( 'woocommerce_store_api_register_endpoint_data' ) ) {
			return;
		}

		woocommerce_store_api_register_endpoint_data(
			array(
				'endpoint'        => 'cart/items',
				'namespace'       => 'wpcalibrate_measurement_pricing',
				'data_callback'   => array( $this, 'get_cart_item_block_data' ),
				'schema_callback' => array( $this, 'get_cart_item_block_schema' ),
				'schema_type'     => ARRAY_A,
			)
		);
	}

	/**
	 * Callback providing formatted measurement details to the Store API cart item endpoint.
	 *
	 * @param array<string, mixed> $cart_item
	 * @return array<string, mixed>
	 */
	public function get_cart_item_block_data( array $cart_item ): array {
		if ( empty( $cart_item['wpcalibrate_mp'] ) ) {
			return array(
				'has_measurement' => false,
			);
		}

		$data         = $cart_item['wpcalibrate_mp'];
		$pricing_unit = UnitRegistry::get( $data['pricing_unit'] );
		$unit_symbol  = $pricing_unit ? $pricing_unit->get_symbol() : $data['pricing_unit'];

		return array(
			'has_measurement'        => true,
			'chargeable_measurement' => (float) $data['chargeable_measurement'],
			'unit_symbol'            => (string) $unit_symbol,
			'calculated_price'       => (float) $data['calculated_price'],
			'dimensions'             => $data['dimension_values'] ?? array(),
		);
	}

	/**
	 * Schema callback for Store API endpoint.
	 *
	 * @return array<string, mixed>
	 */
	public function get_cart_item_block_schema(): array {
		return array(
			'has_measurement'        => array(
				'description' => __( 'Whether line item has measurement pricing', 'wpcalibrate-measurement-pricing' ),
				'type'        => 'boolean',
				'readonly'    => true,
			),
			'chargeable_measurement' => array(
				'description' => __( 'Total chargeable measurement', 'wpcalibrate-measurement-pricing' ),
				'type'        => 'number',
				'readonly'    => true,
			),
			'unit_symbol'            => array(
				'description' => __( 'Unit display symbol', 'wpcalibrate-measurement-pricing' ),
				'type'        => 'string',
				'readonly'    => true,
			),
			'calculated_price'       => array(
				'description' => __( 'Unit calculated price', 'wpcalibrate-measurement-pricing' ),
				'type'        => 'number',
				'readonly'    => true,
			),
		);
	}
}
