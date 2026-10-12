<?php
declare(strict_types=1);

namespace WPCalibrate\MeasurementPricing;

use WPCalibrate\MeasurementPricing\Admin\MenuManager;
use WPCalibrate\MeasurementPricing\Admin\ProductDataMetaBox;
use WPCalibrate\MeasurementPricing\Admin\VariationMetaBox;
use WPCalibrate\MeasurementPricing\Assets\AssetManager;
use WPCalibrate\MeasurementPricing\Cart\CartManager;
use WPCalibrate\MeasurementPricing\Compatibility\BlocksCompatibility;
use WPCalibrate\MeasurementPricing\Display\FrontendDisplay;
use WPCalibrate\MeasurementPricing\Order\OrderManager;

/**
 * Main plugin orchestrator and lifecycle container.
 */
final class Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Subsystem instances.
	 */
	private MenuManager $menu_manager;
	private ProductDataMetaBox $product_data_metabox;
	private VariationMetaBox $variation_metabox;
	private AssetManager $asset_manager;
	private FrontendDisplay $frontend_display;
	private CartManager $cart_manager;
	private OrderManager $order_manager;
	private BlocksCompatibility $blocks_compatibility;

	/**
	 * Whether the plugin has been initialized.
	 *
	 * @var bool
	 */
	private bool $initialized = false;

	/**
	 * Private constructor.
	 */
	private function __construct() {
		$this->menu_manager         = new MenuManager();
		$this->product_data_metabox = new ProductDataMetaBox();
		$this->variation_metabox    = new VariationMetaBox();
		$this->asset_manager        = new AssetManager();
		$this->frontend_display     = new FrontendDisplay();
		$this->cart_manager         = new CartManager();
		$this->order_manager        = new OrderManager();
		$this->blocks_compatibility = new BlocksCompatibility();
	}

	/**
	 * Get singleton instance.
	 *
	 * @return self
	 */
	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Initialize plugin hooks and subsystems.
	 *
	 * @return void
	 */
	public function init(): void {
		if ( $this->initialized ) {
			return;
		}

		$this->initialized = true;

		// Admin subsystems.
		if ( is_admin() ) {
			$this->menu_manager->register();
			$this->product_data_metabox->register();
			$this->variation_metabox->register();
		}

		// Shared assets.
		$this->asset_manager->register();

		// Frontend product display.
		$this->frontend_display->register();

		// Cart and checkout engine.
		$this->cart_manager->register();

		// Orders and HPOS.
		$this->order_manager->register();

		// Blocks & Store API compatibility.
		$this->blocks_compatibility->register();
	}

	/**
	 * Prevent cloning.
	 */
	private function __clone() {}

	/**
	 * Prevent unserializing.
	 */
	public function __wakeup(): void {
		throw new \Exception( 'Cannot unserialize singleton.' );
	}
}
