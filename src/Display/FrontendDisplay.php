<?php
declare(strict_types=1);

namespace WPCalibrate\MeasurementPricing\Display;

use WC_Product;
use WPCalibrate\MeasurementPricing\Calculator\CalculatorRegistry;
use WPCalibrate\MeasurementPricing\Measurement\UnitRegistry;
use WPCalibrate\MeasurementPricing\Product\ProductConfigurationRepository;

/**
 * Handles rendering the measurement calculator interface on single product pages.
 */
class FrontendDisplay {

	/**
	 * Register frontend hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		// Hook right before add to cart button / quantity input.
		add_action( 'woocommerce_before_add_to_cart_button', array( $this, 'render_calculator' ), 15 );
		add_filter( 'woocommerce_get_price_html', array( $this, 'filter_price_html' ), 10, 2 );
	}

	/**
	 * Render calculator template on enabled products.
	 *
	 * @return void
	 */
	public function render_calculator(): void {
		global $product;

		if ( ! $product instanceof WC_Product ) {
			return;
		}

		$config = ProductConfigurationRepository::get( $product );

		// If simple product and not enabled, do not render.
		// For variable products, render container so variations can activate or customize it dynamically.
		if ( ! $config->enabled && ! $product->is_type( 'variable' ) ) {
			return;
		}

		$calculator   = CalculatorRegistry::get( $config->calculator_type );
		$pricing_unit = UnitRegistry::get( $config->pricing_unit );

		$template_path = WPCALIBRATE_MP_PATH . 'templates/single-product/measurement-calculator.php';

		if ( file_exists( $template_path ) ) {
			include $template_path;
		}
	}

	/**
	 * Filter product price HTML to display pricing unit suffix (e.g. "$12.00 / m").
	 *
	 * @param string     $price_html
	 * @param WC_Product $product
	 * @return string
	 */
	public function filter_price_html( string $price_html, WC_Product $product ): string {
		$settings = get_option( 'wpcalibrate_mp_settings', array() );
		if ( isset( $settings['price_display_suffix'] ) && 'no' === $settings['price_display_suffix'] ) {
			return $price_html;
		}

		$config = ProductConfigurationRepository::get( $product );

		if ( ! $config->enabled ) {
			return $price_html;
		}

		$unit = UnitRegistry::get( $config->pricing_unit );
		if ( null === $unit ) {
			return $price_html;
		}

		$suffix = sprintf(
			' <span class="wpcalibrate-mp-price-unit-suffix">/ %s</span>',
			esc_html( $unit->get_symbol() )
		);

		return $price_html . $suffix;
	}
}
