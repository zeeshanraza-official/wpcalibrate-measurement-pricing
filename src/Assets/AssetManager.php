<?php
declare(strict_types=1);

namespace WPCalibrate\MeasurementPricing\Assets;

use WC_Product;
use WPCalibrate\MeasurementPricing\Admin\MenuManager;
use WPCalibrate\MeasurementPricing\Measurement\UnitRegistry;

/**
 * Manages conditional enqueueing and localization of plugin assets.
 */
class AssetManager {

	/**
	 * Register asset hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	/**
	 * Enqueue frontend CSS and JavaScript only on product pages.
	 *
	 * @return void
	 */
	public function enqueue_frontend_assets(): void {
		if ( ! function_exists( 'is_product' ) || ! is_product() ) {
			return;
		}

		global $post;
		$product = wc_get_product( $post->ID ?? 0 );

		if ( ! $product instanceof WC_Product ) {
			return;
		}

		// Enqueue frontend styles.
		wp_enqueue_style(
			'wpcalibrate-mp-frontend',
			WPCALIBRATE_MP_URL . 'assets/css/frontend.css',
			array(),
			WPCALIBRATE_MP_VERSION
		);

		// Enqueue frontend calculator script.
		wp_enqueue_script(
			'wpcalibrate-mp-calculator',
			WPCALIBRATE_MP_URL . 'assets/js/calculator.js',
			array( 'jquery' ),
			WPCALIBRATE_MP_VERSION,
			true
		);

		// Localize script with currency settings and unit conversions.
		$units_data = array();
		foreach ( UnitRegistry::get_all() as $id => $u ) {
			$units_data[ $id ] = array(
				'id'        => $u->get_id(),
				'dimension' => $u->get_dimension(),
				'symbol'    => $u->get_symbol(),
				'factor'    => (float) $u->get_to_canonical_factor(),
			);
		}

		$settings = get_option( 'wpcalibrate_mp_settings', array() );
		$debounce = isset( $settings['live_calculator_debounce'] ) ? absint( $settings['live_calculator_debounce'] ) : 150;

		wp_localize_script(
			'wpcalibrate-mp-calculator',
			'wpcalibrate_mp_vars',
			array(
				'ajax_url'           => admin_url( 'admin-ajax.php' ),
				'currency_symbol'    => get_woocommerce_currency_symbol(),
				'currency_pos'       => get_option( 'woocommerce_currency_pos', 'left' ),
				'decimal_separator'  => wc_get_price_decimal_separator(),
				'thousand_separator' => wc_get_price_thousand_separator(),
				'decimals'           => wc_get_price_decimals(),
				'debounce_ms'        => $debounce,
				'units'              => $units_data,
				'i18n'               => array(
					'calculating'      => __( 'Calculating estimate…', 'wpcalibrate-measurement-pricing' ),
					'invalid_number'   => __( 'Please enter a valid positive number.', 'wpcalibrate-measurement-pricing' ),
					'below_minimum'    => __( 'Measurement is below the minimum allowed.', 'wpcalibrate-measurement-pricing' ),
					'exceeds_maximum'  => __( 'Measurement exceeds the maximum allowed.', 'wpcalibrate-measurement-pricing' ),
				),
			)
		);
	}

	/**
	 * Enqueue admin assets only on relevant screens.
	 *
	 * @param string $hook_suffix
	 * @return void
	 */
	public function enqueue_admin_assets( string $hook_suffix ): void {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		$is_settings_page = isset( $_GET['page'] ) && MenuManager::SUBMENU_SLUG === $_GET['page'];
		$is_product_page  = $screen && 'product' === $screen->post_type;

		if ( ! $is_settings_page && ! $is_product_page ) {
			return;
		}

		wp_enqueue_style(
			'wpcalibrate-mp-admin',
			WPCALIBRATE_MP_URL . 'assets/css/admin.css',
			array(),
			WPCALIBRATE_MP_VERSION
		);

		wp_enqueue_script(
			'wpcalibrate-mp-admin',
			WPCALIBRATE_MP_URL . 'assets/js/admin.js',
			array( 'jquery' ),
			WPCALIBRATE_MP_VERSION,
			true
		);

		wp_localize_script(
			'wpcalibrate-mp-admin',
			'wpcalibrate_mp_admin_vars',
			array(
				'i18n' => array(
					'confirm_remove_tier' => __( 'Are you sure you want to remove this pricing tier?', 'wpcalibrate-measurement-pricing' ),
					'invalid_tier'        => __( 'Please check tier values: Min must be less than Max and rates must be valid numbers.', 'wpcalibrate-measurement-pricing' ),
				),
			)
		);
	}
}
