=== WPCalibrate Measurement Pricing for WooCommerce ===
Contributors: wpcalibrate
Tags: woocommerce, measurement pricing, price calculator, dimensions, area calculator
Requires at least: 6.5
Tested up to: 7.1.3
Requires PHP: 8.2
Stable tag: 1.0.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Sell WooCommerce products by length, area, volume, weight, or custom measurements with authoritative server-side pricing, live customer calculations, and tiered pricing.

== Description ==

**WPCalibrate Measurement Pricing for WooCommerce** allows store owners to sell physical and manufactured goods based on customer-entered dimensions rather than whole-number standard quantities.

Merchants selling fabric, flooring, tiles, glass, timber, bulk produce, or custom printing can configure exact measurement parameters, stepping rules, waste allowances, and volume tiers.

### Measurement Modes Supported
* **Length:** Sell products by linear distance (m, cm, mm, in, ft, yd) — perfect for fabric, cables, rope, and lumber length.
* **Area:** Sell products by surface area (width × length/height in mm², cm², m², in², ft², yd²) — ideal for flooring, wallpaper, glass, and tiles.
* **Volume:** Sell products by 3D volume (length × width × height in mm³, cm³, m³, in³, ft³, yd³, ml, l) — designed for timber blocks, mulch, and liquids.
* **Weight:** Sell bulk goods by mass (g, kg, oz, lb) — suited for coffee, bulk food, metal, and produce.
* **Custom Measured Quantity:** Sell by configurable count or custom pack increments (piece, roll, pack, sheet, custom).

### Authoritative Architecture & Security
* **Zero Trust in Browser Calculations:** JavaScript calculations exist solely for instant, responsive customer feedback. The PHP server independently recalculates every cart price directly from authoritative product configuration and validated customer measurements.
* **Anti-Tampering Enforcement:** Client attempts to manipulate unit prices, pricing units, conversion ratios, or boundaries are strictly rejected.
* **High-Performance Order Storage (HPOS):** Fully verified and compliant with WooCommerce Custom Order Tables (HPOS).
* **Cart & Checkout Blocks:** Verified compatibility with modern WooCommerce Store API and Blocks workflows as well as classic checkout.
* **Deterministic Cart Identity:** Adding the same product with distinct measurements produces separated cart line items, while identical measurements merge cleanly.
* **Immutable Order Auditing:** Orders persist an immutable historical snapshot of measurements and rules, protecting historical accounting from subsequent product edits.

== Installation ==

1. Upload the `wpcalibrate-measurement-pricing` folder to the `/wp-content/plugins/` directory, or install via WordPress Plugins > Add New > Upload Plugin.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Ensure WooCommerce is active and updated to version 8.5 or higher.
4. Navigate to **WPCalibrate > Measurement Pricing** to review global defaults.
5. Edit any WooCommerce product to configure measurement pricing in the **Product Data > Measurement Pricing** panel.

== How to Configure Measurement Pricing ==

Follow these step-by-step instructions to configure measurement pricing on any WooCommerce product:

1. In WordPress Admin, navigate to **Products > All Products** and click **Edit** on your target product.
2. Scroll to the **Product Data** meta box.
3. Click the **Measurement Pricing** tab in the left column.
4. Check **Enable Measurement Pricing**.
5. Select your desired **Measurement Mode** (Length, Area, Volume, Weight, or Custom Quantity).
6. Select the target **Pricing Unit** (e.g., Meter for length, Square Meter for area, Kilogram for weight).
7. Enter the **Price per Unit** (or leave empty to inherit the standard Product Regular Price).
8. Enter an optional **Fixed Base Charge** (setup/cutting fee) or **Minimum Charge** (price floor).
9. Define optional constraints such as **Minimum Measurement**, **Maximum Measurement**, or **Increment/Step**.
10. Select the **Rounding Mode** (Nearest Increment, Round Up, or Round Down).
11. Enter an optional **Waste / Overage Percentage** (e.g. 10% for flooring cuts).
12. If volume discounts apply, click **+ Add Pricing Tier** to add quantity-based rate brackets.
13. Click **Update** to save the product.
14. Open the product page on your store to test the live calculator.
15. Enter measurements and click **Add to Cart** to verify line-item pricing and checkout metadata.

---

### Step-by-Step Configuration Examples

#### Example 1: Fabric Sold by the Meter (Length)
* **Measurement Mode:** Length
* **Pricing Unit:** Meter (m)
* **Price per Unit:** $14.50
* **Increment:** 0.1 m
* **Rounding Mode:** Round Up (Ceiling)
* **Customer Experience:** Customer enters 2.45 m; calculation rounds up to 2.50 m at $14.50/m = $36.25.

#### Example 2: Hardwood Flooring (Area with Waste Allowance)
* **Measurement Mode:** Area (Width × Length)
* **Pricing Unit:** Square Meter (m²)
* **Price per Unit:** $35.00
* **Waste Percentage:** 10%
* **Increment:** 0.25 m²
* **Customer Experience:** Customer enters 3 m width × 4 m length (12 m²). With 10% waste (1.2 m²), total becomes 13.2 m², rounded to nearest 0.25 increment = 13.25 m² charged at $35.00/m² = $463.75.

#### Example 3: Custom Cut Glass (Area with Minimum Charge)
* **Measurement Mode:** Area (Width × Height)
* **Pricing Unit:** Square Meter (m²)
* **Price per Unit:** $80.00
* **Fixed Base Charge:** $15.00 (edging fee)
* **Minimum Charge:** $50.00
* **Customer Experience:** Customer enters small cut 0.4 m × 0.5 m = 0.2 m². Calculated price ($16 + $15 = $31) is below $50 floor; customer is charged the $50 minimum fee.

#### Example 4: Specialty Coffee (Weight)
* **Measurement Mode:** Weight
* **Pricing Unit:** Kilogram (kg)
* **Price per Unit:** $26.00
* **Customer Input Unit:** Grams (g)
* **Customer Experience:** Customer enters 750 g; server converts 750 g to 0.75 kg @ $26.00/kg = $19.50.

== Tiered Pricing ==

Tiered pricing lets merchants configure quantity-based discount tiers:
* **Tier 1:** Min: 0, Max: 10, Price: $20.00 / m²
* **Tier 2:** Min: 10, Max: 50, Price: $17.50 / m²
* **Tier 3:** Min: 50, Max: Unlimited, Price: $15.00 / m²

Exact boundary rules:
* Tiers are evaluated in ascending order.
* Range boundaries are inclusive: a measurement of exactly 10.0 falls in Tier 1.
* Any measurement strictly exceeding 10.0 and up to 50.0 falls in Tier 2.

== Rounding and Increments ==

When an increment is configured (e.g., 0.25 or 0.5):
* **Nearest Increment:** Rounds to closest step (e.g. 2.13 with 0.25 step becomes 2.25; 2.12 becomes 2.00).
* **Round Up (Ceiling):** Rounds to the next higher increment (e.g. 2.01 becomes 2.25).
* **Round Down (Floor):** Rounds down to the previous increment (e.g. 2.24 becomes 2.00).

== Waste and Overage ==

Certain trades (flooring, tiles, wallpaper, fabrics) require an extra percentage to compensate for cuts and scrap:
`chargeable_measurement = raw_measurement × (1 + waste_percentage / 100)`
The waste percentage is applied before increment rounding and boundary enforcement.

== Developer Hooks and Extensibility ==

The plugin provides clean developer hooks:

* `wpcalibrate_mp_registered_units` (filter): Register custom measurement units into the UnitRegistry.
* `wpcalibrate_mp_registered_calculators` (filter): Register new dimension calculation strategies.
* `wpcalibrate_mp_calculated_pricing_result` (filter): Inspect or modify the authoritative calculation result.

== Licensing Provider Notice ==

This distribution provides an internal `LicenseProviderInterface` and settings administration UI. No remote licensing API endpoint is hard-coded or simulated. All core calculator features operate without dependency on remote licensing servers.

== Support and Contact ==

* **Email:** support@wpcalibrate.com
* **Official Website:** https://wpcalibrate.com
* **Marketplace:** https://marketplace.wpcalibrate.com/
* **Phone / WhatsApp:** +447474795976

== Changelog ==

= 1.0.3 =
* Fix: Sidebar menu icon color and contrast to match other WordPress admin icons (bright crisp appearance, blue hover, and pure white active state).
* Fix: Product Data Measurement Pricing tab left gutter — added 40px left padding from vertical tabs to resolve layout crowding.
* Fix: Styled Enable Measurement Pricing checkbox on a single clean row with label and description.

= 1.0.2 =
* Fix: Frontend number input collapse where theme stylesheets forced select width to 100%, squishing input value display.
* Fix: Default pricing unit automatically selected in frontend calculator dropdown.
* Tweak: Redesigned Product Data Measurement Pricing tab with 5 organized option groups, concise labels, and native tooltips.

= 1.0.1 =
* Feature: Added interactive Overview tab to settings explaining calculation methods, step-by-step tutorial, and industry presets.
* Fix: Constrained sidebar menu logo to standard 20px x 20px dimensions.

= 1.0.0 =
* Initial production release.
* Modular calculation strategies: Length, Area, Volume, Weight, and Custom Measured Quantity.
* Full WooCommerce HPOS (Custom Order Tables) compatibility.
* WooCommerce Cart & Checkout Blocks Store API compatibility.
* Authoritative server-side pricing engine with waste and volume tiers.
