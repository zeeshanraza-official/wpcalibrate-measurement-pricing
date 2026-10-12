<?php
declare(strict_types=1);

namespace WPCalibrate\MeasurementPricing\Admin;

/**
 * Manages WPCalibrate admin menus safely across multiple independent WPCalibrate plugins.
 */
class MenuManager {

	/**
	 * Top-level WPCalibrate menu slug.
	 */
	public const PARENT_MENU_SLUG = 'wpcalibrate';

	/**
	 * This plugin's dedicated submenu slug.
	 */
	public const SUBMENU_SLUG = 'wpcalibrate-measurement-pricing';

	/**
	 * Required capability for accessing settings.
	 */
	public const REQUIRED_CAPABILITY = 'manage_woocommerce';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		// Hook with priority 25 so any primary hub plugin can register first if present,
		// or our plugin will create the parent menu safely if it does not yet exist.
		add_action( 'admin_menu', array( $this, 'register_menus' ), 25 );

		// Output menu icon styling globally across WordPress admin to ensure exact 20x20 size and color match.
		add_action( 'admin_head', array( $this, 'output_menu_icon_styles' ) );
	}

	/**
	 * Register the parent menu if not exists, and add this plugin's submenu.
	 *
	 * @return void
	 */
	public function register_menus(): void {
		global $admin_page_hooks;

		$parent_registered = isset( $admin_page_hooks[ self::PARENT_MENU_SLUG ] );

		if ( ! $parent_registered ) {
			// Get icon URL for dark sidebar contrast.
			$icon_url = WPCALIBRATE_MP_URL . 'branding/icon-white.png';

			add_menu_page(
				__( 'WPCalibrate', 'wpcalibrate-measurement-pricing' ),
				__( 'WPCalibrate', 'wpcalibrate-measurement-pricing' ),
				self::REQUIRED_CAPABILITY,
				self::PARENT_MENU_SLUG,
				array( $this, 'render_parent_fallback' ),
				$icon_url,
				58 // Position below WooCommerce
			);
		}

		// Register our dedicated submenu.
		add_submenu_page(
			self::PARENT_MENU_SLUG,
			__( 'Measurement Pricing — WPCalibrate', 'wpcalibrate-measurement-pricing' ),
			__( 'Measurement Pricing', 'wpcalibrate-measurement-pricing' ),
			self::REQUIRED_CAPABILITY,
			self::SUBMENU_SLUG,
			array( SettingsPage::class, 'render' )
		);
	}

	/**
	 * Fallback renderer if user clicks top-level WPCalibrate menu directly
	 * and no dedicated hub dashboard has claimed it.
	 *
	 * @return void
	 */
	public function render_parent_fallback(): void {
		if ( ! current_user_can( self::REQUIRED_CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'wpcalibrate-measurement-pricing' ) );
		}

		// Redirect directly to this plugin's settings page.
		wp_safe_redirect( admin_url( 'admin.php?page=' . self::SUBMENU_SLUG ) );
		exit;
	}

	/**
	 * Output menu icon styling globally in admin_head to enforce 20x20px size
	 * and match standard WordPress dashboard icon colors.
	 *
	 * @return void
	 */
	public function output_menu_icon_styles(): void {
		$icon_url = esc_url( WPCALIBRATE_MP_URL . 'branding/icon-white.png' );
		?>
		<style id="wpcalibrate-mp-menu-icon-css">
			#adminmenu .toplevel_page_wpcalibrate .wp-menu-image {
				display: flex !important;
				align-items: center !important;
				justify-content: center !important;
				padding: 0 !important;
				overflow: hidden !important;
			}
			#adminmenu .toplevel_page_wpcalibrate .wp-menu-image img {
				display: block !important;
				width: 20px !important;
				height: 20px !important;
				max-width: 20px !important;
				max-height: 20px !important;
				padding: 0 !important;
				margin: 0 !important;
				object-fit: contain !important;
				opacity: 0.88 !important;
				transition: opacity 0.15s ease, filter 0.15s ease !important;
			}
			#adminmenu .toplevel_page_wpcalibrate:hover .wp-menu-image img,
			#adminmenu .toplevel_page_wpcalibrate.opensub .wp-menu-image img,
			#adminmenu .toplevel_page_wpcalibrate:focus .wp-menu-image img {
				opacity: 1 !important;
				filter: brightness(0) saturate(100%) invert(69%) sepia(48%) saturate(962%) hue-rotate(183deg) brightness(97%) contrast(92%) !important;
			}
			#adminmenu .toplevel_page_wpcalibrate.wp-has-current-submenu .wp-menu-image img,
			#adminmenu .toplevel_page_wpcalibrate.current .wp-menu-image img {
				opacity: 1 !important;
				filter: brightness(0) invert(1) !important;
			}
			#adminmenu .toplevel_page_wpcalibrate .wp-menu-image::before {
				display: none !important;
			}
		</style>
		<?php
	}
}
