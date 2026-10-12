<?php
declare(strict_types=1);

namespace WPCalibrate\MeasurementPricing\Admin;

use WPCalibrate\MeasurementPricing\Licensing\LicenseManager;

/**
 * Handles the WPCalibrate Measurement Pricing administration settings page.
 */
class SettingsPage {

	/**
	 * Render settings page.
	 *
	 * @return void
	 */
	public static function render(): void {
		if ( ! current_user_can( MenuManager::REQUIRED_CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to access these settings.', 'wpcalibrate-measurement-pricing' ) );
		}

		$license_manager = new LicenseManager();
		$active_tab      = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'overview';

		// Handle POST actions.
		self::handle_form_submission( $license_manager );

		$settings     = get_option( 'wpcalibrate_mp_settings', array() );
		$license_data = $license_manager->get_license_data();

		$tabs = array(
			'overview'      => __( 'Overview', 'wpcalibrate-measurement-pricing' ),
			'general'       => __( 'General', 'wpcalibrate-measurement-pricing' ),
			'display'       => __( 'Display', 'wpcalibrate-measurement-pricing' ),
			'calculation'   => __( 'Calculation', 'wpcalibrate-measurement-pricing' ),
			'compatibility' => __( 'Compatibility', 'wpcalibrate-measurement-pricing' ),
			'license'       => __( 'License', 'wpcalibrate-measurement-pricing' ),
			'support'       => __( 'Support & Diagnostics', 'wpcalibrate-measurement-pricing' ),
		);
		?>
		<div class="wrap wpcalibrate-mp-settings-wrap">
			<div class="wpcalibrate-mp-admin-header">
				<div class="wpcalibrate-mp-admin-branding">
					<img src="<?php echo esc_url( WPCALIBRATE_MP_URL . 'branding/icon-dark.png' ); ?>"
						 alt="WPCalibrate"
						 class="wpcalibrate-mp-header-icon" />
					<div class="wpcalibrate-mp-header-text">
						<h1><?php esc_html_e( 'WPCalibrate Measurement Pricing', 'wpcalibrate-measurement-pricing' ); ?></h1>
						<span class="wpcalibrate-mp-version-badge">v<?php echo esc_html( WPCALIBRATE_MP_VERSION ); ?></span>
					</div>
				</div>
			</div>

			<nav class="nav-tab-wrapper wpcalibrate-mp-nav-tab-wrapper">
				<?php foreach ( $tabs as $tab_key => $tab_title ) :
					$active_class = ( $active_tab === $tab_key ) ? 'nav-tab-active' : '';
					$tab_url      = add_query_arg(
						array(
							'page' => MenuManager::SUBMENU_SLUG,
							'tab'  => $tab_key,
						),
						admin_url( 'admin.php' )
					);
				?>
					<a href="<?php echo esc_url( $tab_url ); ?>" class="nav-tab <?php echo esc_attr( $active_class ); ?>">
						<?php echo esc_html( $tab_title ); ?>
					</a>
				<?php endforeach; ?>
			</nav>

			<div class="wpcalibrate-mp-settings-content">
				<?php
				switch ( $active_tab ) {
					case 'general':
						self::render_tab_general( $settings );
						break;
					case 'display':
						self::render_tab_display( $settings );
						break;
					case 'calculation':
						self::render_tab_calculation( $settings );
						break;
					case 'compatibility':
						self::render_tab_compatibility( $settings );
						break;
					case 'license':
						self::render_tab_license( $license_manager, $license_data );
						break;
					case 'support':
						self::render_tab_support();
						break;
					case 'overview':
					default:
						self::render_tab_overview();
						break;
				}
				?>
			</div>
		</div>
		<?php
	}

	/**
	 * Handle settings and license form submission with nonce verification.
	 *
	 * @param LicenseManager $license_manager
	 * @return void
	 */
	private static function handle_form_submission( LicenseManager $license_manager ): void {
		if ( empty( $_POST['wpcalibrate_mp_action'] ) ) {
			return;
		}

		check_admin_referer( 'wpcalibrate_mp_save_settings', 'wpcalibrate_mp_nonce' );

		$action   = sanitize_key( $_POST['wpcalibrate_mp_action'] );
		$settings = get_option( 'wpcalibrate_mp_settings', array() );

		if ( 'save_general' === $action ) {
			$settings['enable_plugin']            = isset( $_POST['enable_plugin'] ) ? 'yes' : 'no';
			$settings['delete_data_on_uninstall'] = isset( $_POST['delete_data_on_uninstall'] ) ? 'yes' : 'no';
			$settings['enable_logging']           = isset( $_POST['enable_logging'] ) ? 'yes' : 'no';
			update_option( 'wpcalibrate_mp_settings', $settings );
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'General settings saved successfully.', 'wpcalibrate-measurement-pricing' ) . '</p></div>';
		} elseif ( 'save_display' === $action ) {
			$settings['price_display_suffix']       = isset( $_POST['price_display_suffix'] ) ? 'yes' : 'no';
			$settings['show_measurement_in_cart']   = isset( $_POST['show_measurement_in_cart'] ) ? 'yes' : 'no';
			$settings['show_measurement_in_orders'] = isset( $_POST['show_measurement_in_orders'] ) ? 'yes' : 'no';
			$settings['live_calculator_debounce']   = absint( $_POST['live_calculator_debounce'] ?? 150 );
			update_option( 'wpcalibrate_mp_settings', $settings );
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Display settings saved successfully.', 'wpcalibrate-measurement-pricing' ) . '</p></div>';
		} elseif ( 'save_calculation' === $action ) {
			$settings['default_rounding_mode']      = sanitize_text_field( $_POST['default_rounding_mode'] ?? 'nearest' );
			$settings['default_waste_percentage']   = (float) ( $_POST['default_waste_percentage'] ?? 0 );
			$settings['strict_boundary_validation'] = isset( $_POST['strict_boundary_validation'] ) ? 'yes' : 'no';
			update_option( 'wpcalibrate_mp_settings', $settings );
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Calculation settings saved successfully.', 'wpcalibrate-measurement-pricing' ) . '</p></div>';
		} elseif ( 'save_compatibility' === $action ) {
			$settings['enable_blocks_support'] = isset( $_POST['enable_blocks_support'] ) ? 'yes' : 'no';
			update_option( 'wpcalibrate_mp_settings', $settings );
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Compatibility settings saved successfully.', 'wpcalibrate-measurement-pricing' ) . '</p></div>';
		} elseif ( 'save_license' === $action ) {
			$license_key = sanitize_text_field( $_POST['license_key'] ?? '' );
			if ( ! empty( $license_key ) ) {
				$license_manager->save_key( $license_key );
			}

			if ( isset( $_POST['activate_license'] ) ) {
				$resp = $license_manager->activate();
				$notice_class = $resp->success ? 'notice-success' : 'notice-warning';
				echo '<div class="notice ' . esc_attr( $notice_class ) . ' is-dismissible"><p>' . esc_html( $resp->message ) . '</p></div>';
			} elseif ( isset( $_POST['deactivate_license'] ) ) {
				$resp = $license_manager->deactivate();
				echo '<div class="notice notice-info is-dismissible"><p>' . esc_html( $resp->message ) . '</p></div>';
			} else {
				echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'License key saved.', 'wpcalibrate-measurement-pricing' ) . '</p></div>';
			}
		}
	}

	/**
	 * Tab: Overview (Purpose, Method, and How to Use Guide).
	 */
	private static function render_tab_overview(): void {
		$product_url  = admin_url( 'edit.php?post_type=product' );
		$general_url  = add_query_arg( array( 'page' => MenuManager::SUBMENU_SLUG, 'tab' => 'general' ), admin_url( 'admin.php' ) );
		$calc_url     = add_query_arg( array( 'page' => MenuManager::SUBMENU_SLUG, 'tab' => 'calculation' ), admin_url( 'admin.php' ) );
		$support_url  = add_query_arg( array( 'page' => MenuManager::SUBMENU_SLUG, 'tab' => 'support' ), admin_url( 'admin.php' ) );
		?>
		<div class="wpcalibrate-mp-overview-wrapper">
			<!-- Hero Card -->
			<div class="wpcalibrate-mp-overview-hero">
				<div class="wpcalibrate-mp-hero-content">
					<h2><?php esc_html_e( 'Welcome to WPCalibrate Measurement Pricing', 'wpcalibrate-measurement-pricing' ); ?></h2>
					<p class="wpcalibrate-mp-hero-sub">
						<?php esc_html_e( 'Sell physical goods and manufactured products by customer-entered measurements such as length, area, volume, weight, or custom units instead of standard whole-number quantities.', 'wpcalibrate-measurement-pricing' ); ?>
					</p>
					<div class="wpcalibrate-mp-hero-actions">
						<a href="<?php echo esc_url( $product_url ); ?>" class="button button-primary button-hero">
							<?php esc_html_e( 'Configure Products', 'wpcalibrate-measurement-pricing' ); ?> &rarr;
						</a>
						<a href="<?php echo esc_url( $general_url ); ?>" class="button button-secondary button-hero">
							<?php esc_html_e( 'Global Settings', 'wpcalibrate-measurement-pricing' ); ?>
						</a>
						<a href="<?php echo esc_url( $support_url ); ?>" class="button button-secondary button-hero">
							<?php esc_html_e( 'Help & Diagnostics', 'wpcalibrate-measurement-pricing' ); ?>
						</a>
					</div>
				</div>
			</div>

			<!-- Three Core Pillars -->
			<div class="wpcalibrate-mp-overview-grid">
				<div class="wpcalibrate-mp-card">
					<div class="wpcalibrate-mp-card-icon" style="background: #eff6ff; color: #2563eb;">🎯</div>
					<h3><?php esc_html_e( '1. Purpose & Problem Solved', 'wpcalibrate-measurement-pricing' ); ?></h3>
					<p>
						<?php esc_html_e( 'Standard WooCommerce forces stores to sell products using rigid whole numbers (1, 2, 3 items). This fails for merchants selling fabric by the meter, flooring by square meter, timber by volume, or bulk goods by weight.', 'wpcalibrate-measurement-pricing' ); ?>
					</p>
					<p>
						<?php esc_html_e( 'WPCalibrate allows customers to enter exact dimensions on product pages, calculates pricing in real time, and passes validated measurement data through cart, checkout, orders, invoices, and emails.', 'wpcalibrate-measurement-pricing' ); ?>
					</p>
				</div>

				<div class="wpcalibrate-mp-card">
					<div class="wpcalibrate-mp-card-icon" style="background: #f0fdf4; color: #16a34a;">📐</div>
					<h3><?php esc_html_e( '2. Calculation Method & Math', 'wpcalibrate-measurement-pricing' ); ?></h3>
					<p>
						<?php esc_html_e( 'The plugin implements 5 modular calculator strategies (Length, Area, Volume, Weight, and Custom Quantity) backed by high-precision BCMath unit conversions.', 'wpcalibrate-measurement-pricing' ); ?>
					</p>
					<ul class="wpcalibrate-mp-feature-bullets">
						<li><strong><?php esc_html_e( 'Waste Allowance:', 'wpcalibrate-measurement-pricing' ); ?></strong> <?php esc_html_e( 'Add configurable scrap/overage % for cuts.', 'wpcalibrate-measurement-pricing' ); ?></li>
						<li><strong><?php esc_html_e( 'Increments & Rounding:', 'wpcalibrate-measurement-pricing' ); ?></strong> <?php esc_html_e( 'Snap to stepping intervals (Up, Down, Nearest).', 'wpcalibrate-measurement-pricing' ); ?></li>
						<li><strong><?php esc_html_e( 'Volume Tiers:', 'wpcalibrate-measurement-pricing' ); ?></strong> <?php esc_html_e( 'Apply discounted rates based on total ordered size.', 'wpcalibrate-measurement-pricing' ); ?></li>
					</ul>
				</div>

				<div class="wpcalibrate-mp-card">
					<div class="wpcalibrate-mp-card-icon" style="background: #fef2f2; color: #dc2626;">🔒</div>
					<h3><?php esc_html_e( '3. Zero-Trust Security Boundary', 'wpcalibrate-measurement-pricing' ); ?></h3>
					<p>
						<?php esc_html_e( 'Browser-side JavaScript calculations are strictly for customer feedback and preview. The server never trusts client-supplied rates, prices, units, or formulas.', 'wpcalibrate-measurement-pricing' ); ?>
					</p>
					<p>
						<?php esc_html_e( 'When adding to cart, PHP independently validates every dimension, converts units, resolves applicable tiers, and recalculates the final price from authoritative product configuration.', 'wpcalibrate-measurement-pricing' ); ?>
					</p>
				</div>
			</div>

			<!-- How to Use Step-by-Step Guide -->
			<div class="wpcalibrate-mp-guide-section">
				<h3><?php esc_html_e( 'How to Configure Measurement Pricing on a Product', 'wpcalibrate-measurement-pricing' ); ?></h3>
				<p class="description">
					<?php esc_html_e( 'Follow these straightforward steps to enable and customize measurement pricing for any WooCommerce product:', 'wpcalibrate-measurement-pricing' ); ?>
				</p>

				<div class="wpcalibrate-mp-steps-list">
					<div class="wpcalibrate-mp-step-item">
						<div class="wpcalibrate-mp-step-badge">1</div>
						<div class="wpcalibrate-mp-step-content">
							<h4><?php esc_html_e( 'Open Product Data', 'wpcalibrate-measurement-pricing' ); ?></h4>
							<p><?php esc_html_e( 'In your WordPress dashboard, navigate to Products > All Products and click Edit on any simple or variable product.', 'wpcalibrate-measurement-pricing' ); ?></p>
						</div>
					</div>

					<div class="wpcalibrate-mp-step-item">
						<div class="wpcalibrate-mp-step-badge">2</div>
						<div class="wpcalibrate-mp-step-content">
							<h4><?php esc_html_e( 'Enable Measurement Pricing', 'wpcalibrate-measurement-pricing' ); ?></h4>
							<p><?php esc_html_e( 'Click the "Measurement Pricing" tab in the Product Data meta box and check the "Enable Measurement Pricing" checkbox.', 'wpcalibrate-measurement-pricing' ); ?></p>
						</div>
					</div>

					<div class="wpcalibrate-mp-step-item">
						<div class="wpcalibrate-mp-step-badge">3</div>
						<div class="wpcalibrate-mp-step-content">
							<h4><?php esc_html_e( 'Select Measurement Mode & Pricing Unit', 'wpcalibrate-measurement-pricing' ); ?></h4>
							<p><?php esc_html_e( 'Select the appropriate dimension mode (Length, Area, Volume, Weight, or Custom Quantity) and pick the unit upon which the price rate is based (e.g. m, m², kg, etc.).', 'wpcalibrate-measurement-pricing' ); ?></p>
						</div>
					</div>

					<div class="wpcalibrate-mp-step-item">
						<div class="wpcalibrate-mp-step-badge">4</div>
						<div class="wpcalibrate-mp-step-content">
							<h4><?php esc_html_e( 'Set Base Unit Price & Pricing Rules', 'wpcalibrate-measurement-pricing' ); ?></h4>
							<p><?php esc_html_e( 'Enter the price per unit, or leave blank to inherit standard product price. Optionally set a fixed base charge (e.g. setup/edging fee) or a minimum charge floor.', 'wpcalibrate-measurement-pricing' ); ?></p>
						</div>
					</div>

					<div class="wpcalibrate-mp-step-item">
						<div class="wpcalibrate-mp-step-badge">5</div>
						<div class="wpcalibrate-mp-step-content">
							<h4><?php esc_html_e( 'Configure Stepping, Rounding & Waste', 'wpcalibrate-measurement-pricing' ); ?></h4>
							<p><?php esc_html_e( 'Enter increment steps (e.g., 0.25), choose a rounding method (Nearest, Round Up, Round Down), and add waste percentage if your trade requires cutting allowances.', 'wpcalibrate-measurement-pricing' ); ?></p>
						</div>
					</div>

					<div class="wpcalibrate-mp-step-item">
						<div class="wpcalibrate-mp-step-badge">6</div>
						<div class="wpcalibrate-mp-step-content">
							<h4><?php esc_html_e( 'Add Volume Discount Tiers (Optional)', 'wpcalibrate-measurement-pricing' ); ?></h4>
							<p><?php esc_html_e( 'Use "+ Add Pricing Tier" to create quantity-based brackets where the price per unit drops when the customer orders larger total measurements.', 'wpcalibrate-measurement-pricing' ); ?></p>
						</div>
					</div>

					<div class="wpcalibrate-mp-step-item">
						<div class="wpcalibrate-mp-step-badge">7</div>
						<div class="wpcalibrate-mp-step-content">
							<h4><?php esc_html_e( 'Save & Test Live on Product Page', 'wpcalibrate-measurement-pricing' ); ?></h4>
							<p><?php esc_html_e( 'Click "Update" to save the product. Visit the product page to see the responsive live calculator in action and test Add to Cart.', 'wpcalibrate-measurement-pricing' ); ?></p>
						</div>
					</div>
				</div>
			</div>

			<!-- Industry Examples Cards -->
			<div class="wpcalibrate-mp-presets-section">
				<h3><?php esc_html_e( 'Common Industry Use Cases & Presets', 'wpcalibrate-measurement-pricing' ); ?></h3>
				<div class="wpcalibrate-mp-presets-grid">
					<div class="wpcalibrate-mp-preset-card">
						<h4>🧵 <?php esc_html_e( 'Fabric & Textiles', 'wpcalibrate-measurement-pricing' ); ?></h4>
						<p class="wpcalibrate-mp-preset-desc"><?php esc_html_e( 'Mode: Length | Unit: Meter (m) | Step: 0.1 m', 'wpcalibrate-measurement-pricing' ); ?></p>
						<p><?php esc_html_e( 'Customers enter required linear length. Rounding set to "Round Up (Ceiling)" ensures bolts are never cut short.', 'wpcalibrate-measurement-pricing' ); ?></p>
					</div>

					<div class="wpcalibrate-mp-preset-card">
						<h4>🪵 <?php esc_html_e( 'Flooring & Tiles', 'wpcalibrate-measurement-pricing' ); ?></h4>
						<p class="wpcalibrate-mp-preset-desc"><?php esc_html_e( 'Mode: Area | Unit: Square Meter (m²) | Waste: 10%', 'wpcalibrate-measurement-pricing' ); ?></p>
						<p><?php esc_html_e( 'Customers enter room width and length. A 10% waste allowance is automatically factored in for corner cuts and offcuts.', 'wpcalibrate-measurement-pricing' ); ?></p>
					</div>

					<div class="wpcalibrate-mp-preset-card">
						<h4>🪟 <?php esc_html_e( 'Glass & Mirrors', 'wpcalibrate-measurement-pricing' ); ?></h4>
						<p class="wpcalibrate-mp-preset-desc"><?php esc_html_e( 'Mode: Area | Base Fee: $15 | Min Charge: $45', 'wpcalibrate-measurement-pricing' ); ?></p>
						<p><?php esc_html_e( 'Custom cut-to-size glass incorporates an edging base charge and enforces a $45 minimum charge floor on small offcuts.', 'wpcalibrate-measurement-pricing' ); ?></p>
					</div>

					<div class="wpcalibrate-mp-preset-card">
						<h4>☕ <?php esc_html_e( 'Bulk Goods & Coffee', 'wpcalibrate-measurement-pricing' ); ?></h4>
						<p class="wpcalibrate-mp-preset-desc"><?php esc_html_e( 'Mode: Weight | Unit: Kilogram (kg)', 'wpcalibrate-measurement-pricing' ); ?></p>
						<p><?php esc_html_e( 'Customers enter grams (e.g. 750 g) which automatically converts to 0.75 kg and prices accurately according to base rate.', 'wpcalibrate-measurement-pricing' ); ?></p>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Tab: General.
	 */
	private static function render_tab_general( array $settings ): void {
		?>
		<form method="post" action="">
			<?php wp_nonce_field( 'wpcalibrate_mp_save_settings', 'wpcalibrate_mp_nonce' ); ?>
			<input type="hidden" name="wpcalibrate_mp_action" value="save_general" />

			<table class="form-table">
				<tr>
					<th scope="row"><?php esc_html_e( 'Enable Plugin Globally', 'wpcalibrate-measurement-pricing' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="enable_plugin" value="yes" <?php checked( $settings['enable_plugin'] ?? 'yes', 'yes' ); ?> />
							<?php esc_html_e( 'Enable measurement pricing calculations on products.', 'wpcalibrate-measurement-pricing' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Uninstall Behavior', 'wpcalibrate-measurement-pricing' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="delete_data_on_uninstall" value="yes" <?php checked( $settings['delete_data_on_uninstall'] ?? 'no', 'yes' ); ?> />
							<?php esc_html_e( 'Delete plugin settings, product pricing rules, and transients upon uninstalling plugin.', 'wpcalibrate-measurement-pricing' ); ?>
						</label>
						<p class="description">
							<?php esc_html_e( 'By default, product measurement settings and historical orders are preserved when deactivating or deleting the plugin.', 'wpcalibrate-measurement-pricing' ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Logging', 'wpcalibrate-measurement-pricing' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="enable_logging" value="yes" <?php checked( $settings['enable_logging'] ?? 'no', 'yes' ); ?> />
							<?php esc_html_e( 'Log detailed calculation events and debug info to WooCommerce > Status > Logs.', 'wpcalibrate-measurement-pricing' ); ?>
						</label>
					</td>
				</tr>
			</table>

			<?php submit_button( __( 'Save General Settings', 'wpcalibrate-measurement-pricing' ) ); ?>
		</form>
		<?php
	}

	/**
	 * Tab: Display.
	 */
	private static function render_tab_display( array $settings ): void {
		?>
		<form method="post" action="">
			<?php wp_nonce_field( 'wpcalibrate_mp_save_settings', 'wpcalibrate_mp_nonce' ); ?>
			<input type="hidden" name="wpcalibrate_mp_action" value="save_display" />

			<table class="form-table">
				<tr>
					<th scope="row"><?php esc_html_e( 'Price Suffix', 'wpcalibrate-measurement-pricing' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="price_display_suffix" value="yes" <?php checked( $settings['price_display_suffix'] ?? 'yes', 'yes' ); ?> />
							<?php esc_html_e( 'Display pricing unit suffix after standard product price (e.g. "$12.00 / m").', 'wpcalibrate-measurement-pricing' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Cart Line Items', 'wpcalibrate-measurement-pricing' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="show_measurement_in_cart" value="yes" <?php checked( $settings['show_measurement_in_cart'] ?? 'yes', 'yes' ); ?> />
							<?php esc_html_e( 'Show customer measurements and calculated totals in cart and checkout.', 'wpcalibrate-measurement-pricing' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Order Details & Emails', 'wpcalibrate-measurement-pricing' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="show_measurement_in_orders" value="yes" <?php checked( $settings['show_measurement_in_orders'] ?? 'yes', 'yes' ); ?> />
							<?php esc_html_e( 'Show measurement details in customer order invoices, admin screens, and emails.', 'wpcalibrate-measurement-pricing' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Live Preview Debounce', 'wpcalibrate-measurement-pricing' ); ?></th>
					<td>
						<input type="number" name="live_calculator_debounce" value="<?php echo esc_attr( (string) ( $settings['live_calculator_debounce'] ?? 150 ) ); ?>" min="50" max="1000" step="25" /> ms
						<p class="description"><?php esc_html_e( 'Delay before running live client preview calculation after typing (in milliseconds).', 'wpcalibrate-measurement-pricing' ); ?></p>
					</td>
				</tr>
			</table>

			<?php submit_button( __( 'Save Display Settings', 'wpcalibrate-measurement-pricing' ) ); ?>
		</form>
		<?php
	}

	/**
	 * Tab: Calculation.
	 */
	private static function render_tab_calculation( array $settings ): void {
		$rounding_mode = $settings['default_rounding_mode'] ?? 'nearest';
		?>
		<form method="post" action="">
			<?php wp_nonce_field( 'wpcalibrate_mp_save_settings', 'wpcalibrate_mp_nonce' ); ?>
			<input type="hidden" name="wpcalibrate_mp_action" value="save_calculation" />

			<table class="form-table">
				<tr>
					<th scope="row"><?php esc_html_e( 'Default Rounding Mode', 'wpcalibrate-measurement-pricing' ); ?></th>
					<td>
						<select name="default_rounding_mode">
							<option value="nearest" <?php selected( $rounding_mode, 'nearest' ); ?>><?php esc_html_e( 'Nearest Increment', 'wpcalibrate-measurement-pricing' ); ?></option>
							<option value="up" <?php selected( $rounding_mode, 'up' ); ?>><?php esc_html_e( 'Round Up (Ceiling)', 'wpcalibrate-measurement-pricing' ); ?></option>
							<option value="down" <?php selected( $rounding_mode, 'down' ); ?>><?php esc_html_e( 'Round Down (Floor)', 'wpcalibrate-measurement-pricing' ); ?></option>
						</select>
						<p class="description"><?php esc_html_e( 'Default method used when aligning customer measurements to product step/increments.', 'wpcalibrate-measurement-pricing' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Default Waste / Overage', 'wpcalibrate-measurement-pricing' ); ?></th>
					<td>
						<input type="number" name="default_waste_percentage" value="<?php echo esc_attr( (string) ( $settings['default_waste_percentage'] ?? 0 ) ); ?>" min="0" max="100" step="0.5" /> %
						<p class="description"><?php esc_html_e( 'Default waste percentage applied to increase chargeable measurement (e.g. 10% for flooring cuts).', 'wpcalibrate-measurement-pricing' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Strict Boundary Validation', 'wpcalibrate-measurement-pricing' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="strict_boundary_validation" value="yes" <?php checked( $settings['strict_boundary_validation'] ?? 'yes', 'yes' ); ?> />
							<?php esc_html_e( 'Strictly block Add to Cart when customer measurements violate minimum or maximum boundaries.', 'wpcalibrate-measurement-pricing' ); ?>
						</label>
					</td>
				</tr>
			</table>

			<?php submit_button( __( 'Save Calculation Settings', 'wpcalibrate-measurement-pricing' ) ); ?>
		</form>
		<?php
	}

	/**
	 * Tab: Compatibility.
	 */
	private static function render_tab_compatibility( array $settings ): void {
		?>
		<form method="post" action="">
			<?php wp_nonce_field( 'wpcalibrate_mp_save_settings', 'wpcalibrate_mp_nonce' ); ?>
			<input type="hidden" name="wpcalibrate_mp_action" value="save_compatibility" />

			<table class="form-table">
				<tr>
					<th scope="row"><?php esc_html_e( 'WooCommerce Blocks Support', 'wpcalibrate-measurement-pricing' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="enable_blocks_support" value="yes" <?php checked( $settings['enable_blocks_support'] ?? 'yes', 'yes' ); ?> />
							<?php esc_html_e( 'Enable integration with WooCommerce Cart and Checkout Blocks via Store API.', 'wpcalibrate-measurement-pricing' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'High-Performance Order Storage (HPOS)', 'wpcalibrate-measurement-pricing' ); ?></th>
					<td>
						<span class="badge badge-success" style="color: #16a34a; font-weight: 600;">✓ <?php esc_html_e( 'Fully Compatible', 'wpcalibrate-measurement-pricing' ); ?></span>
						<p class="description"><?php esc_html_e( 'The plugin exclusively utilizes WooCommerce CRUD order APIs and stores immutable item metadata without touching deprecated post tables.', 'wpcalibrate-measurement-pricing' ); ?></p>
					</td>
				</tr>
			</table>

			<?php submit_button( __( 'Save Compatibility Settings', 'wpcalibrate-measurement-pricing' ) ); ?>
		</form>
		<?php
	}

	/**
	 * Tab: License.
	 */
	private static function render_tab_license( LicenseManager $license_manager, array $license_data ): void {
		$masked_key = LicenseManager::mask_key( $license_data['key'] );
		?>
		<form method="post" action="">
			<?php wp_nonce_field( 'wpcalibrate_mp_save_settings', 'wpcalibrate_mp_nonce' ); ?>
			<input type="hidden" name="wpcalibrate_mp_action" value="save_license" />

			<div class="notice notice-info inline" style="margin-top: 15px;">
				<p>
					<strong><?php esc_html_e( 'Licensing Provider Status:', 'wpcalibrate-measurement-pricing' ); ?></strong>
					<?php esc_html_e( 'No external licensing provider API is currently configured for this distribution. All core measurement calculation features remain fully functional.', 'wpcalibrate-measurement-pricing' ); ?>
				</p>
			</div>

			<table class="form-table">
				<tr>
					<th scope="row"><?php esc_html_e( 'License Key', 'wpcalibrate-measurement-pricing' ); ?></th>
					<td>
						<input type="text"
							   name="license_key"
							   value="<?php echo esc_attr( $license_data['key'] ); ?>"
							   placeholder="<?php echo esc_attr( $masked_key ?: 'XXXX-XXXX-XXXX-XXXX' ); ?>"
							   class="regular-text" />
						<p class="description"><?php esc_html_e( 'Enter your WPCalibrate license key.', 'wpcalibrate-measurement-pricing' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Activation Status', 'wpcalibrate-measurement-pricing' ); ?></th>
					<td>
						<span class="badge" style="background: #f1f5f9; padding: 4px 8px; border-radius: 4px; font-weight: 500;">
							<?php echo esc_html( ucfirst( $license_data['status'] ) ); ?>
						</span>
					</td>
				</tr>
			</table>

			<p class="submit">
				<input type="submit" name="save_key_only" class="button button-primary" value="<?php esc_attr_e( 'Save License Key', 'wpcalibrate-measurement-pricing' ); ?>" />
				<input type="submit" name="activate_license" class="button button-secondary" value="<?php esc_attr_e( 'Activate License', 'wpcalibrate-measurement-pricing' ); ?>" />
				<?php if ( 'active' === $license_data['status'] ) : ?>
					<input type="submit" name="deactivate_license" class="button" value="<?php esc_attr_e( 'Deactivate', 'wpcalibrate-measurement-pricing' ); ?>" />
				<?php endif; ?>
			</p>
		</form>
		<?php
	}

	/**
	 * Tab: Support & Diagnostics.
	 */
	private static function render_tab_support(): void {
		global $wp_version;
		?>
		<div class="wpcalibrate-mp-support-container">
			<div class="wpcalibrate-mp-support-card">
				<h3><?php esc_html_e( 'WPCalibrate Support & Contact', 'wpcalibrate-measurement-pricing' ); ?></h3>
				<p><?php esc_html_e( 'Need assistance configuring complex measurements or have feedback? Reach out directly through any of our official channels:', 'wpcalibrate-measurement-pricing' ); ?></p>

				<ul class="wpcalibrate-mp-contact-list">
					<li>
						<strong><?php esc_html_e( 'Email:', 'wpcalibrate-measurement-pricing' ); ?></strong>
						<a href="mailto:support@wpcalibrate.com">support@wpcalibrate.com</a>
					</li>
					<li>
						<strong><?php esc_html_e( 'Official Website:', 'wpcalibrate-measurement-pricing' ); ?></strong>
						<a href="<?php echo esc_url( 'https://wpcalibrate.com' ); ?>" target="_blank" rel="noopener noreferrer">https://wpcalibrate.com</a>
					</li>
					<li>
						<strong><?php esc_html_e( 'Plugin Marketplace:', 'wpcalibrate-measurement-pricing' ); ?></strong>
						<a href="<?php echo esc_url( 'https://marketplace.wpcalibrate.com/' ); ?>" target="_blank" rel="noopener noreferrer">https://marketplace.wpcalibrate.com/</a>
					</li>
					<li>
						<strong><?php esc_html_e( 'Phone / WhatsApp:', 'wpcalibrate-measurement-pricing' ); ?></strong>
						<a href="https://wa.me/447474795976" target="_blank" rel="noopener noreferrer">+447474795976</a>
					</li>
				</ul>
			</div>

			<div class="wpcalibrate-mp-support-card">
				<h3><?php esc_html_e( 'Environment & Diagnostic Overview', 'wpcalibrate-measurement-pricing' ); ?></h3>
				<table class="widefat striped">
					<tbody>
						<tr>
							<td><strong><?php esc_html_e( 'PHP Version', 'wpcalibrate-measurement-pricing' ); ?></strong></td>
							<td><?php echo esc_html( PHP_VERSION ); ?></td>
						</tr>
						<tr>
							<td><strong><?php esc_html_e( 'WordPress Version', 'wpcalibrate-measurement-pricing' ); ?></strong></td>
							<td><?php echo esc_html( $wp_version ?? 'Unknown' ); ?></td>
						</tr>
						<tr>
							<td><strong><?php esc_html_e( 'WooCommerce Version', 'wpcalibrate-measurement-pricing' ); ?></strong></td>
							<td><?php echo esc_html( defined( 'WC_VERSION' ) ? WC_VERSION : 'Inactive' ); ?></td>
						</tr>
						<tr>
							<td><strong><?php esc_html_e( 'BCMath Extension', 'wpcalibrate-measurement-pricing' ); ?></strong></td>
							<td><?php echo function_exists( 'bcmul' ) ? esc_html__( 'Active (High Precision)', 'wpcalibrate-measurement-pricing' ) : esc_html__( 'Not installed', 'wpcalibrate-measurement-pricing' ); ?></td>
						</tr>
						<tr>
							<td><strong><?php esc_html_e( 'Plugin Version', 'wpcalibrate-measurement-pricing' ); ?></strong></td>
							<td><?php echo esc_html( WPCALIBRATE_MP_VERSION ); ?></td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>
		<?php
	}
}
