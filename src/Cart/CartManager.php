<?php
declare(strict_types=1);

namespace WPCalibrate\MeasurementPricing\Cart;

use WC_Cart;
use WC_Product;
use WPCalibrate\MeasurementPricing\Calculator\CalculatorRegistry;
use WPCalibrate\MeasurementPricing\Measurement\UnitRegistry;
use WPCalibrate\MeasurementPricing\Pricing\PricingEngine;
use WPCalibrate\MeasurementPricing\Pricing\PricingRequest;
use WPCalibrate\MeasurementPricing\Pricing\PricingResult;
use WPCalibrate\MeasurementPricing\Product\ProductConfigurationRepository;

/**
 * Handles WooCommerce cart validation, authoritative pricing injection, and session persistence.
 */
class CartManager {

	/**
	 * Ephemeral request cache for validated calculation results during Add to Cart.
	 *
	 * @var array<string, PricingResult>
	 */
	private static array $validated_calculations = array();

	/**
	 * Register cart hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		// Add to cart validation.
		add_filter( 'woocommerce_add_to_cart_validation', array( $this, 'validate_add_to_cart' ), 10, 5 );

		// Cart item data injection.
		add_filter( 'woocommerce_add_cart_item_data', array( $this, 'add_cart_item_data' ), 10, 3 );

		// Recalculate price in cart totals.
		add_action( 'woocommerce_before_calculate_totals', array( $this, 'recalculate_cart_item_price' ), 20, 1 );

		// Session restore.
		add_filter( 'woocommerce_get_cart_item_from_session', array( $this, 'get_cart_item_from_session' ), 20, 2 );

		// Display measurement data in cart and checkout line items.
		add_filter( 'woocommerce_get_item_data', array( $this, 'display_cart_item_data' ), 10, 2 );
	}

	/**
	 * Authoritative Add to Cart validation.
	 *
	 * @param bool  $passed
	 * @param int   $product_id
	 * @param int   $quantity
	 * @param int   $variation_id
	 * @param array $variations
	 * @return bool
	 */
	public function validate_add_to_cart( $passed, $product_id, $quantity, $variation_id = 0, $variations = array() ): bool {
		$target_id = $variation_id > 0 ? $variation_id : $product_id;
		$product   = wc_get_product( $target_id );

		if ( ! $product instanceof WC_Product ) {
			return $passed;
		}

		$config = ProductConfigurationRepository::get( $product );

		// Standard products without measurement pricing remain untouched.
		if ( ! $config->enabled ) {
			return $passed;
		}

		$calculator = CalculatorRegistry::get( $config->calculator_type );
		if ( null === $calculator ) {
			wc_add_notice( __( 'Measurement configuration error: invalid calculator strategy.', 'wpcalibrate-measurement-pricing' ), 'error' );
			return false;
		}

		// Nonce check or POST check.
		$raw_measurements = isset( $_POST['wpcalibrate_mp_measurements'] ) && is_array( $_POST['wpcalibrate_mp_measurements'] )
			? wp_unslash( $_POST['wpcalibrate_mp_measurements'] )
			: array();

		$raw_units = isset( $_POST['wpcalibrate_mp_units'] ) && is_array( $_POST['wpcalibrate_mp_units'] )
			? wp_unslash( $_POST['wpcalibrate_mp_units'] )
			: array();

		if ( empty( $raw_measurements ) ) {
			wc_add_notice( __( 'Please enter all required measurements before adding this product to your cart.', 'wpcalibrate-measurement-pricing' ), 'error' );
			return false;
		}

		// Validate raw inputs against calculator definitions.
		$validation = $calculator->validate_inputs( $raw_measurements, $config->dimension_fields );
		if ( ! $validation->is_valid() ) {
			foreach ( $validation->get_errors() as $error_msg ) {
				wc_add_notice( $error_msg, 'error' );
			}
			return false;
		}

		// Clean and map dimension units safely.
		$sanitized_units = array();
		$definitions     = $calculator->get_dimension_definitions();

		foreach ( $definitions as $key => $def ) {
			$submitted_unit = isset( $raw_units[ $key ] ) ? sanitize_key( (string) $raw_units[ $key ] ) : '';
			$unit_obj       = ! empty( $submitted_unit ) ? UnitRegistry::get( $submitted_unit ) : null;

			// Verify unit exists and matches dimension.
			if ( $unit_obj && $unit_obj->get_dimension() === $def['dimension'] ) {
				$sanitized_units[ $key ] = $unit_obj->get_id();
			} else {
				// Fallback to configured or default dimension unit.
				$configured_unit = $config->dimension_fields[ $key ]['default_unit'] ?? $def['default_unit'];
				$sanitized_units[ $key ] = $configured_unit;
			}
		}

		// Build authoritative PricingRequest on the server.
		$request = new PricingRequest(
			product_id: $product_id,
			variation_id: $variation_id,
			calculator_type: $config->calculator_type,
			pricing_unit_id: $config->pricing_unit,
			dimension_values: $validation->get_sanitized_inputs(),
			dimension_units: $sanitized_units,
			base_unit_price: $config->unit_price,
			base_charge: $config->base_charge,
			minimum_charge: $config->minimum_charge,
			minimum_measurement: $config->min_measurement,
			maximum_measurement: $config->max_measurement,
			increment: $config->increment,
			rounding_mode: $config->rounding_mode,
			precision: $config->precision,
			waste_percentage: $config->waste_percentage,
			pricing_tiers: $config->pricing_tiers
		);

		$result = PricingEngine::calculate( $request );

		if ( ! $result->success ) {
			wc_add_notice( $result->error ?: __( 'Measurement calculation failed.', 'wpcalibrate-measurement-pricing' ), 'error' );
			return false;
		}

		// Cache validated result for add_cart_item_data.
		$cache_key = sprintf( '%d_%d', $product_id, $variation_id );
		self::$validated_calculations[ $cache_key ] = $result;

		return $passed;
	}

	/**
	 * Attach immutable measurement data and deterministic item hash to cart line.
	 *
	 * @param array<string, mixed> $cart_item_data
	 * @param int                  $product_id
	 * @param int                  $variation_id
	 * @return array<string, mixed>
	 */
	public function add_cart_item_data( array $cart_item_data, int $product_id, int $variation_id ): array {
		$cache_key = sprintf( '%d_%d', $product_id, $variation_id );

		if ( ! isset( self::$validated_calculations[ $cache_key ] ) ) {
			return $cart_item_data;
		}

		$result = self::$validated_calculations[ $cache_key ];
		unset( self::$validated_calculations[ $cache_key ] );

		$cart_item_data['wpcalibrate_mp'] = array(
			'calculator_type'        => $result->pricing_unit,
			'pricing_unit'           => $result->pricing_unit,
			'dimension_values'       => $result->dimension_values,
			'dimension_units'        => $result->dimension_units,
			'raw_measurement'        => $result->raw_measurement,
			'waste_percentage'       => $result->waste_percentage,
			'waste_amount'           => $result->waste_amount,
			'chargeable_measurement' => $result->chargeable_measurement,
			'base_unit_price'        => $result->base_unit_price,
			'effective_unit_price'   => $result->effective_unit_price,
			'measurement_price'      => $result->measurement_price,
			'base_charge'            => $result->base_charge,
			'minimum_charge'         => $result->minimum_charge,
			'calculated_price'       => $result->final_price,
		);

		// Deterministic hash based on specific customer measurements so identical ones merge and different ones stay separate.
		$cart_item_data['wpcalibrate_mp_hash'] = md5(
			wp_json_encode(
				array(
					'dimensions' => $result->dimension_values,
					'units'      => $result->dimension_units,
					'chargeable' => $result->chargeable_measurement,
					'price'      => $result->final_price,
				)
			)
		);

		return $cart_item_data;
	}

	/**
	 * Authoritatively set cart item price during totals calculation.
	 *
	 * @param WC_Cart $cart
	 * @return void
	 */
	public function recalculate_cart_item_price( WC_Cart $cart ): void {
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
			return;
		}

		if ( did_action( 'woocommerce_before_calculate_totals' ) >= 2 ) {
			// Avoid infinite recursion.
		}

		foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
			if ( ! empty( $cart_item['wpcalibrate_mp']['calculated_price'] ) ) {
				$cart_item['data']->set_price( (float) $cart_item['wpcalibrate_mp']['calculated_price'] );
			}
		}
	}

	/**
	 * Restore measurement pricing when cart session is reloaded.
	 *
	 * @param array<string, mixed> $cart_item
	 * @param array<string, mixed> $values
	 * @return array<string, mixed>
	 */
	public function get_cart_item_from_session( array $cart_item, array $values ): array {
		if ( ! empty( $values['wpcalibrate_mp'] ) ) {
			$cart_item['wpcalibrate_mp'] = $values['wpcalibrate_mp'];
			$cart_item['data']->set_price( (float) $values['wpcalibrate_mp']['calculated_price'] );
		}
		return $cart_item;
	}

	/**
	 * Format measurement details for customer in cart and checkout line items.
	 *
	 * @param array<int, array<string, mixed>> $item_data
	 * @param array<string, mixed>             $cart_item
	 * @return array<int, array<string, mixed>>
	 */
	public function display_cart_item_data( array $item_data, array $cart_item ): array {
		if ( empty( $cart_item['wpcalibrate_mp'] ) ) {
			return $item_data;
		}

		$data         = $cart_item['wpcalibrate_mp'];
		$pricing_unit = UnitRegistry::get( $data['pricing_unit'] );
		$unit_symbol  = $pricing_unit ? $pricing_unit->get_symbol() : $data['pricing_unit'];

		// Show individual dimensions.
		if ( ! empty( $data['dimension_values'] ) && is_array( $data['dimension_values'] ) ) {
			foreach ( $data['dimension_values'] as $key => $val ) {
				$unit_id  = $data['dimension_units'][ $key ] ?? '';
				$unit_obj = UnitRegistry::get( $unit_id );
				$dim_sym  = $unit_obj ? $unit_obj->get_symbol() : $unit_id;

				$item_data[] = array(
					'key'   => ucfirst( str_replace( '_', ' ', $key ) ),
					'value' => sprintf( '%s %s', (string) $val, $dim_sym ),
				);
			}
		}

		// Total chargeable measurement.
		$item_data[] = array(
			'key'   => __( 'Total Measurement', 'wpcalibrate-measurement-pricing' ),
			'value' => sprintf( '%s %s', (string) $data['chargeable_measurement'], $unit_symbol ),
		);

		return $item_data;
	}
}
