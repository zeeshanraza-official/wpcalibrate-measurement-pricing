<?php
declare(strict_types=1);

namespace WPCalibrate\MeasurementPricing\Product;

use WC_Product;
use WPCalibrate\MeasurementPricing\Pricing\PricingTier;
use WPCalibrate\MeasurementPricing\Pricing\TierResolver;

/**
 * Repository for reading and persisting product measurement pricing configuration.
 *
 * Uses WooCommerce CRUD metadata APIs and handles variation inheritance.
 */
class ProductConfigurationRepository {

	/**
	 * In-memory request cache: product_id => ProductConfiguration.
	 *
	 * @var array<int, ProductConfiguration>
	 */
	private static array $cache = array();

	/**
	 * Meta key prefix.
	 */
	public const META_PREFIX = '_wpcalibrate_mp_';

	/**
	 * Get configuration for a product or variation.
	 *
	 * @param int|WC_Product $product
	 * @return ProductConfiguration
	 */
	public static function get( int|WC_Product $product ): ProductConfiguration {
		$product_obj = is_numeric( $product ) ? wc_get_product( $product ) : $product;

		if ( ! $product_obj instanceof WC_Product ) {
			return ProductConfiguration::disabled( is_numeric( $product ) ? (int) $product : 0 );
		}

		$product_id = $product_obj->get_id();

		if ( isset( self::$cache[ $product_id ] ) ) {
			return self::$cache[ $product_id ];
		}

		// Handle variation.
		if ( $product_obj->is_type( 'variation' ) ) {
			$config = self::load_variation_configuration( $product_obj );
			self::$cache[ $product_id ] = $config;
			return $config;
		}

		$config = self::load_product_configuration( $product_obj );
		self::$cache[ $product_id ] = $config;
		return $config;
	}

	/**
	 * Load configuration for a simple or parent variable product.
	 *
	 * @param WC_Product $product
	 * @return ProductConfiguration
	 */
	private static function load_product_configuration( WC_Product $product ): ProductConfiguration {
		$product_id = $product->get_id();
		$enabled    = 'yes' === $product->get_meta( self::META_PREFIX . 'enabled', true );

		if ( ! $enabled ) {
			return ProductConfiguration::disabled( $product_id );
		}

		$calculator_type = (string) $product->get_meta( self::META_PREFIX . 'calculator_type', true );
		if ( empty( $calculator_type ) ) {
			$calculator_type = 'length';
		}

		$pricing_unit = (string) $product->get_meta( self::META_PREFIX . 'pricing_unit', true );
		if ( empty( $pricing_unit ) ) {
			$pricing_unit = 'm';
		}

		$unit_price_raw = $product->get_meta( self::META_PREFIX . 'unit_price', true );
		if ( '' === $unit_price_raw || false === $unit_price_raw ) {
			// Fallback to standard product price.
			$unit_price = (float) $product->get_price();
		} else {
			$unit_price = (float) $unit_price_raw;
		}

		$base_charge        = (float) $product->get_meta( self::META_PREFIX . 'base_charge', true );
		$minimum_charge     = (float) $product->get_meta( self::META_PREFIX . 'minimum_charge', true );
		$min_measurement    = (float) $product->get_meta( self::META_PREFIX . 'min_measurement', true );
		$max_measurement    = (float) $product->get_meta( self::META_PREFIX . 'max_measurement', true );
		$increment          = (float) $product->get_meta( self::META_PREFIX . 'increment', true );
		$rounding_mode      = (string) $product->get_meta( self::META_PREFIX . 'rounding_mode', true ) ?: 'nearest';
		$precision          = (int) ( $product->get_meta( self::META_PREFIX . 'precision', true ) ?: 4 );
		$waste_percentage   = (float) $product->get_meta( self::META_PREFIX . 'waste_percentage', true );
		$help_text          = (string) $product->get_meta( self::META_PREFIX . 'help_text', true );
		$show_in_cart       = 'no' !== $product->get_meta( self::META_PREFIX . 'show_in_cart', true );
		$show_in_order      = 'no' !== $product->get_meta( self::META_PREFIX . 'show_in_order', true );

		// Load dimension fields.
		$dimension_fields = $product->get_meta( self::META_PREFIX . 'dimension_fields', true );
		if ( ! is_array( $dimension_fields ) ) {
			$dimension_fields = array();
		}

		// Load tiers.
		$raw_tiers     = $product->get_meta( self::META_PREFIX . 'pricing_tiers', true );
		$pricing_tiers = array();
		if ( is_array( $raw_tiers ) ) {
			$validation = TierResolver::validate_tiers( $raw_tiers );
			if ( $validation['is_valid'] ) {
				$pricing_tiers = $validation['tiers'];
			}
		}

		return new ProductConfiguration(
			product_id: $product_id,
			enabled: true,
			calculator_type: $calculator_type,
			pricing_unit: $pricing_unit,
			unit_price: $unit_price,
			base_charge: $base_charge,
			minimum_charge: $minimum_charge,
			min_measurement: $min_measurement,
			max_measurement: $max_measurement,
			increment: $increment,
			rounding_mode: $rounding_mode,
			precision: $precision,
			waste_percentage: $waste_percentage,
			pricing_tiers: $pricing_tiers,
			dimension_fields: $dimension_fields,
			help_text: $help_text,
			show_in_cart: $show_in_cart,
			show_in_order: $show_in_order,
			is_variation: false,
			override_parent: false
		);
	}

	/**
	 * Load configuration for a variation, respecting inheritance or override.
	 *
	 * @param WC_Product $variation
	 * @return ProductConfiguration
	 */
	private static function load_variation_configuration( WC_Product $variation ): ProductConfiguration {
		$variation_id    = $variation->get_id();
		$parent_id       = $variation->get_parent_id();
		$parent          = wc_get_product( $parent_id );
		$parent_config   = $parent instanceof WC_Product ? self::load_product_configuration( $parent ) : ProductConfiguration::disabled( $parent_id );

		// Check if variation has explicit override.
		$override = 'yes' === $variation->get_meta( self::META_PREFIX . 'override_parent', true );

		if ( ! $override ) {
			// Inherit everything from parent, except price if variation defines distinct unit price.
			$var_unit_price_raw = $variation->get_meta( self::META_PREFIX . 'unit_price', true );
			$unit_price         = ( '' !== $var_unit_price_raw && false !== $var_unit_price_raw )
				? (float) $var_unit_price_raw
				: ( (float) $variation->get_price() ?: $parent_config->unit_price );

			return new ProductConfiguration(
				product_id: $variation_id,
				enabled: $parent_config->enabled,
				calculator_type: $parent_config->calculator_type,
				pricing_unit: $parent_config->pricing_unit,
				unit_price: $unit_price,
				base_charge: $parent_config->base_charge,
				minimum_charge: $parent_config->minimum_charge,
				min_measurement: $parent_config->min_measurement,
				max_measurement: $parent_config->max_measurement,
				increment: $parent_config->increment,
				rounding_mode: $parent_config->rounding_mode,
				precision: $parent_config->precision,
				waste_percentage: $parent_config->waste_percentage,
				pricing_tiers: $parent_config->pricing_tiers,
				dimension_fields: $parent_config->dimension_fields,
				help_text: $parent_config->help_text,
				show_in_cart: $parent_config->show_in_cart,
				show_in_order: $parent_config->show_in_order,
				is_variation: true,
				override_parent: false
			);
		}

		// Variation explicitly overrides.
		$enabled = 'no' !== $variation->get_meta( self::META_PREFIX . 'enabled', true );
		if ( ! $enabled ) {
			return ProductConfiguration::disabled( $variation_id );
		}

		$var_unit_price = $variation->get_meta( self::META_PREFIX . 'unit_price', true );
		$unit_price     = ( '' !== $var_unit_price && false !== $var_unit_price )
			? (float) $var_unit_price
			: ( (float) $variation->get_price() ?: $parent_config->unit_price );

		$base_charge    = $variation->get_meta( self::META_PREFIX . 'base_charge', true );
		$base_charge    = '' !== $base_charge ? (float) $base_charge : $parent_config->base_charge;

		$minimum_charge = $variation->get_meta( self::META_PREFIX . 'minimum_charge', true );
		$minimum_charge = '' !== $minimum_charge ? (float) $minimum_charge : $parent_config->minimum_charge;

		// Load variation tiers if defined, else fallback to parent tiers.
		$raw_tiers     = $variation->get_meta( self::META_PREFIX . 'pricing_tiers', true );
		$pricing_tiers = $parent_config->pricing_tiers;
		if ( is_array( $raw_tiers ) && ! empty( $raw_tiers ) ) {
			$validation = TierResolver::validate_tiers( $raw_tiers );
			if ( $validation['is_valid'] ) {
				$pricing_tiers = $validation['tiers'];
			}
		}

		return new ProductConfiguration(
			product_id: $variation_id,
			enabled: true,
			calculator_type: $parent_config->calculator_type,
			pricing_unit: $parent_config->pricing_unit,
			unit_price: $unit_price,
			base_charge: $base_charge,
			minimum_charge: $minimum_charge,
			min_measurement: $parent_config->min_measurement,
			max_measurement: $parent_config->max_measurement,
			increment: $parent_config->increment,
			rounding_mode: $parent_config->rounding_mode,
			precision: $parent_config->precision,
			waste_percentage: $parent_config->waste_percentage,
			pricing_tiers: $pricing_tiers,
			dimension_fields: $parent_config->dimension_fields,
			help_text: $parent_config->help_text,
			show_in_cart: $parent_config->show_in_cart,
			show_in_order: $parent_config->show_in_order,
			is_variation: true,
			override_parent: true
		);
	}

	/**
	 * Save product configuration.
	 *
	 * @param WC_Product           $product
	 * @param array<string, mixed> $data
	 * @return void
	 */
	public static function save( WC_Product $product, array $data ): void {
		$enabled = isset( $data['enabled'] ) && 'yes' === $data['enabled'] ? 'yes' : 'no';
		$product->update_meta_data( self::META_PREFIX . 'enabled', $enabled );

		if ( 'yes' !== $enabled ) {
			$product->save();
			self::clear_cache( $product->get_id() );
			return;
		}

		$product->update_meta_data( self::META_PREFIX . 'calculator_type', sanitize_text_field( $data['calculator_type'] ?? 'length' ) );
		$product->update_meta_data( self::META_PREFIX . 'pricing_unit', sanitize_text_field( $data['pricing_unit'] ?? 'm' ) );

		if ( isset( $data['unit_price'] ) && '' !== $data['unit_price'] ) {
			$product->update_meta_data( self::META_PREFIX . 'unit_price', wc_format_decimal( $data['unit_price'] ) );
		} else {
			$product->delete_meta_data( self::META_PREFIX . 'unit_price' );
		}

		$product->update_meta_data( self::META_PREFIX . 'base_charge', wc_format_decimal( $data['base_charge'] ?? 0 ) );
		$product->update_meta_data( self::META_PREFIX . 'minimum_charge', wc_format_decimal( $data['minimum_charge'] ?? 0 ) );
		$product->update_meta_data( self::META_PREFIX . 'min_measurement', wc_format_decimal( $data['min_measurement'] ?? 0 ) );
		$product->update_meta_data( self::META_PREFIX . 'max_measurement', wc_format_decimal( $data['max_measurement'] ?? 0 ) );
		$product->update_meta_data( self::META_PREFIX . 'increment', wc_format_decimal( $data['increment'] ?? 0 ) );
		$product->update_meta_data( self::META_PREFIX . 'rounding_mode', sanitize_text_field( $data['rounding_mode'] ?? 'nearest' ) );
		$product->update_meta_data( self::META_PREFIX . 'precision', absint( $data['precision'] ?? 4 ) );
		$product->update_meta_data( self::META_PREFIX . 'waste_percentage', wc_format_decimal( $data['waste_percentage'] ?? 0 ) );
		$product->update_meta_data( self::META_PREFIX . 'help_text', sanitize_textarea_field( $data['help_text'] ?? '' ) );
		$product->update_meta_data( self::META_PREFIX . 'show_in_cart', isset( $data['show_in_cart'] ) && 'no' === $data['show_in_cart'] ? 'no' : 'yes' );
		$product->update_meta_data( self::META_PREFIX . 'show_in_order', isset( $data['show_in_order'] ) && 'no' === $data['show_in_order'] ? 'no' : 'yes' );

		// Save dimension fields configuration.
		if ( isset( $data['dimension_fields'] ) && is_array( $data['dimension_fields'] ) ) {
			$sanitized_fields = array();
			foreach ( $data['dimension_fields'] as $key => $field ) {
				$sanitized_fields[ sanitize_key( $key ) ] = array(
					'label'        => sanitize_text_field( $field['label'] ?? '' ),
					'default_unit' => sanitize_text_field( $field['default_unit'] ?? '' ),
					'min'          => isset( $field['min'] ) && '' !== $field['min'] ? wc_format_decimal( $field['min'] ) : '',
					'max'          => isset( $field['max'] ) && '' !== $field['max'] ? wc_format_decimal( $field['max'] ) : '',
					'step'         => isset( $field['step'] ) && '' !== $field['step'] ? wc_format_decimal( $field['step'] ) : '',
				);
			}
			$product->update_meta_data( self::META_PREFIX . 'dimension_fields', $sanitized_fields );
		}

		// Save pricing tiers.
		if ( isset( $data['pricing_tiers'] ) && is_array( $data['pricing_tiers'] ) ) {
			$validation = TierResolver::validate_tiers( $data['pricing_tiers'] );
			if ( $validation['is_valid'] ) {
				$clean_tiers = array_map( static fn( PricingTier $t ) => $t->to_array(), $validation['tiers'] );
				$product->update_meta_data( self::META_PREFIX . 'pricing_tiers', $clean_tiers );
			}
		} else {
			$product->delete_meta_data( self::META_PREFIX . 'pricing_tiers' );
		}

		$product->save();
		self::clear_cache( $product->get_id() );
	}

	/**
	 * Save variation override data.
	 *
	 * @param WC_Product           $variation
	 * @param array<string, mixed> $data
	 * @return void
	 */
	public static function save_variation( WC_Product $variation, array $data ): void {
		$override = isset( $data['override_parent'] ) && 'yes' === $data['override_parent'] ? 'yes' : 'no';
		$variation->update_meta_data( self::META_PREFIX . 'override_parent', $override );

		if ( isset( $data['unit_price'] ) && '' !== $data['unit_price'] ) {
			$variation->update_meta_data( self::META_PREFIX . 'unit_price', wc_format_decimal( $data['unit_price'] ) );
		} else {
			$variation->delete_meta_data( self::META_PREFIX . 'unit_price' );
		}

		if ( 'yes' === $override ) {
			if ( isset( $data['base_charge'] ) && '' !== $data['base_charge'] ) {
				$variation->update_meta_data( self::META_PREFIX . 'base_charge', wc_format_decimal( $data['base_charge'] ) );
			}
			if ( isset( $data['minimum_charge'] ) && '' !== $data['minimum_charge'] ) {
				$variation->update_meta_data( self::META_PREFIX . 'minimum_charge', wc_format_decimal( $data['minimum_charge'] ) );
			}
			if ( isset( $data['pricing_tiers'] ) && is_array( $data['pricing_tiers'] ) ) {
				$validation = TierResolver::validate_tiers( $data['pricing_tiers'] );
				if ( $validation['is_valid'] ) {
					$clean_tiers = array_map( static fn( PricingTier $t ) => $t->to_array(), $validation['tiers'] );
					$variation->update_meta_data( self::META_PREFIX . 'pricing_tiers', $clean_tiers );
				}
			}
		}

		$variation->save();
		self::clear_cache( $variation->get_id() );
	}

	/**
	 * Clear cache.
	 *
	 * @param int|null $product_id
	 * @return void
	 */
	public static function clear_cache( ?int $product_id = null ): void {
		if ( null !== $product_id ) {
			unset( self::$cache[ $product_id ] );
		} else {
			self::$cache = array();
		}
	}
}
