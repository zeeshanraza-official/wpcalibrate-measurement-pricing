<?php
declare(strict_types=1);

namespace WPCalibrate\MeasurementPricing\Order;

use WC_Order;
use WC_Order_Item_Product;
use WPCalibrate\MeasurementPricing\Measurement\UnitRegistry;

/**
 * Handles persistence of immutable measurement metadata on order line items
 * and display in customer orders, admin screens, and emails.
 *
 * Fully compliant with WooCommerce HPOS (Custom Order Tables).
 */
class OrderManager {

	/**
	 * Register order hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		// HPOS / CRUD hook when order line items are created from cart.
		add_action( 'woocommerce_checkout_create_order_line_item', array( $this, 'save_order_item_metadata' ), 20, 4 );

		// Filter displayed meta keys/values for clean presentation if needed.
		add_filter( 'woocommerce_order_item_display_meta_key', array( $this, 'filter_display_meta_key' ), 10, 3 );
	}

	/**
	 * Persist immutable measurement metadata on order item using WooCommerce CRUD.
	 *
	 * @param WC_Order_Item_Product $item
	 * @param string                $cart_item_key
	 * @param array<string, mixed>  $values
	 * @param WC_Order              $order
	 * @return void
	 */
	public function save_order_item_metadata( WC_Order_Item_Product $item, string $cart_item_key, array $values, WC_Order $order ): void {
		if ( empty( $values['wpcalibrate_mp'] ) ) {
			return;
		}

		$data         = $values['wpcalibrate_mp'];
		$pricing_unit = UnitRegistry::get( $data['pricing_unit'] );
		$unit_symbol  = $pricing_unit ? $pricing_unit->get_symbol() : $data['pricing_unit'];

		// 1. Save hidden raw snapshot for historical audit, re-orders, and refunds.
		$item->add_meta_data( '_wpcalibrate_mp_snapshot', $data, true );

		// 2. Save customer and merchant visible dimension metadata.
		if ( ! empty( $data['dimension_values'] ) && is_array( $data['dimension_values'] ) ) {
			foreach ( $data['dimension_values'] as $key => $val ) {
				$unit_id  = $data['dimension_units'][ $key ] ?? '';
				$unit_obj = UnitRegistry::get( $unit_id );
				$dim_sym  = $unit_obj ? $unit_obj->get_symbol() : $unit_id;

				$meta_label = ucfirst( str_replace( '_', ' ', $key ) );
				$item->add_meta_data( $meta_label, sprintf( '%s %s', (string) $val, $dim_sym ), true );
			}
		}

		// 3. Save chargeable measurement metadata.
		$item->add_meta_data(
			__( 'Calculated Measurement', 'wpcalibrate-measurement-pricing' ),
			sprintf( '%s %s', (string) $data['chargeable_measurement'], $unit_symbol ),
			true
		);
	}

	/**
	 * Format meta key labels if needed.
	 *
	 * @param string $display_key
	 * @param mixed  $meta
	 * @param mixed  $item
	 * @return string
	 */
	public function filter_display_meta_key( string $display_key, $meta = null, $item = null ): string {
		return $display_key;
	}
}
