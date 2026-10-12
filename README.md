# WPCalibrate Measurement Pricing for WooCommerce

[![WooCommerce Compatibility](https://img.shields.io/badge/WooCommerce-8.5%2B-blue.svg)](https://woocommerce.com/)
[![HPOS Verified](https://img.shields.io/badge/HPOS-Compatible-success.svg)](https://woocommerce.com/)
[![PHP Version](https://img.shields.io/badge/PHP-8.2%2B-777bb4.svg)](https://www.php.net/)
[![License: GPL v2+](https://img.shields.io/badge/License-GPL%20v2%2B-blue.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
[![Release](https://img.shields.io/badge/Release-v1.0.3-brightgreen.svg)](https://github.com/zeeshanraza-official/wpcalibrate-measurement-pricing/releases)

**WPCalibrate Measurement Pricing for WooCommerce** empowers store owners to sell physical and manufactured goods based on customer-entered dimensions rather than whole-number standard quantities. 

Merchants selling fabric, flooring, tiles, glass, lumber, bulk produce, or custom printing can configure exact measurement parameters, stepping rules, waste allowances, and volume pricing tiers with live customer preview and authoritative server recalculation.

---

## Table of Contents
- [About](#about)
- [Key Features](#features)
- [Measurement Modes](#measurement-modes)
- [Calculation Architecture & Security](#calculation-architecture--security)
- [Documentation & How to Use](#documentation--how-to-use)
- [WordPress Dashboard Automatic Updates](#wordpress-dashboard-automatic-updates)
- [Developer Hooks & API](#developer-hooks)
- [Changelog](#changelog)
- [License & Support](#license--support)

---

## About

By default, WooCommerce assumes all products are sold in whole units (1, 2, 3 items). For stores selling by linear meter, square meter, cubic meter, kilogram, or custom dimensions, standard quantities fail to represent actual customer requirements.

**WPCalibrate Measurement Pricing** bridges this gap by introducing a multi-dimension calculator with high-precision unit conversions and strict server-side validation. Store owners set the price per unit (e.g. $15 / m² or $25 / kg), and customers simply type in their required measurements. The plugin automatically calculates the total price, rounds to stepping rules, factors in cutting waste, enforces minimum charge floors, and seamlessly adds the custom-priced product to the cart.

---

## Features

- **5 Modular Measurement Modes:** Length, Area, Volume, Weight, and Custom Measured Quantity.
- **High-Precision Unit Conversion:** 12-decimal high-precision unit conversions supporting metric and imperial systems (m, cm, mm, in, ft, yd, sq m, sq ft, kg, g, lb, oz, and more).
- **Authoritative Server Pricing:** Zero trust in browser calculations. The server independently verifies and recalculates every price, preventing client-side tampering.
- **Stepping & Increment Rounding:** Round customer measurements to the nearest, ceiling (up), or floor (down) increment (e.g. 0.25m, 0.5m, 1.0m).
- **Waste & Overage Allowance:** Automatically add a scrap or waste percentage (e.g. +10% for flooring or wallpaper cuts).
- **Base Charges & Minimum Charges:** Add fixed setup/cutting fees and enforce total minimum price floors for small dimensions.
- **Tiered & Volume Pricing:** Discount rates automatically based on total measurement thresholds (e.g. 10m² @ $20/m², 50m² @ $16/m²).
- **High-Performance Order Storage (HPOS):** 100% compatible with WooCommerce Custom Order Tables (HPOS) and legacy post tables.
- **Cart & Checkout Blocks Compatible:** Fully tested with WooCommerce Cart & Checkout Blocks via the Store API as well as classic checkout.
- **Deterministic Cart Identity:** Distinct customer measurements create separated cart line items; identical measurements merge cleanly.
- **Audit Trail & Order Persistence:** Orders persist an immutable historical snapshot of measurements, rates, and dimensions, protecting past accounting against subsequent product edits.

---

## Measurement Modes

| Mode | Input Dimensions | Supported Units | Ideal Use Cases |
| :--- | :--- | :--- | :--- |
| **Length** | Single Dimension (Length / Distance) | m, cm, mm, in, ft, yd | Fabric, rope, cables, trim, ribbons, lumber |
| **Area** | 2 Dimensions (Width × Length/Height) | mm², cm², m², in², ft², yd² | Flooring, wallpaper, glass, tiles, synthetic turf |
| **Volume** | 3 Dimensions (Length × Width × Height) | mm³, cm³, m³, in³, ft³, yd³, ml, l | Timber blocks, gravel, mulch, liquids, aggregates |
| **Weight** | Single Mass Dimension | g, kg, oz, lb | Bulk coffee, tea, produce, metal, precious gems |
| **Quantity** | Custom measured step count | piece, roll, pack, sheet, custom | Printing reams, sheet packs, custom bundle sizes |

---

## Calculation Architecture & Security

WPCalibrate adheres to a strict server-side trust boundary:

```
[Customer Input] ──> [Client Live Preview (JS)]
       │
       ▼ (Add to Cart Request)
[1. Dimension Validation]
       │
[2. Canonical Unit Conversion (12-decimal BCMath)]
       │
[3. Raw Measurement Computation]
       │
[4. Waste / Overage Percentage (+X%)]
       │
[5. Increment & Stepping Rounding (Nearest / Up / Down)]
       │
[6. Boundary Enforcement (Min / Max Measurement)]
       │
[7. Tiered Volume Rate Resolution]
       │
[8. Base Charge Addition (+ Fixed Setup Fee)]
       │
[9. Minimum Price Floor Enforcement]
       │
       ▼
[Authoritative Line Price & Cart Hash Generation]
```

---

## Documentation & How to Use

### 1. Installation
1. Download `wpcalibrate-measurement-pricing.zip` from the latest [GitHub Release](https://github.com/zeeshanraza-official/wpcalibrate-measurement-pricing/releases).
2. In your WordPress admin, go to **Plugins > Add New > Upload Plugin**.
3. Choose the ZIP file and click **Install Now**, then **Activate**.

### 2. Configure a Product
1. Go to **Products > All Products** and click **Edit** on your target product.
2. In the **Product Data** meta box, click the **Measurement Pricing** tab.
3. Check **Enable customer-entered measurements and dynamic pricing for this product**.
4. Select the **Measurement Mode** (e.g. *Area* for flooring, *Length* for fabric).
5. Choose the **Pricing Unit** (e.g. *Square meter (sq_m)*).
6. Set optional constraints:
   - **Price per Unit:** Base price per measurement unit (or leave blank to inherit Regular Price).
   - **Fixed Base Charge:** Setup or cutting fee added on top of calculated measurement price.
   - **Minimum Charge:** Price floor charged regardless of small dimensions.
   - **Increment Step:** Stepping interval (e.g. `0.25`, `0.5`, `1.0`).
   - **Waste Percentage:** Scrap allowance percentage (e.g. `10%`).
   - **Tiered Rates:** Multi-volume pricing breaks.
7. Click **Update** to save the product.

---

## WordPress Dashboard Automatic Updates

This repository is structured for compatibility with standard WordPress update engines and GitHub release tracking:

1. **GitHub Releases:** Tagged production releases include installable ZIP archives.
2. **Dashboard Updates:** When paired with [Plugin Update Checker](https://github.com/YahnisElsts/plugin-update-checker) or GitHub updater integrations, WordPress detects new releases pushed to this repository and prompts automatic 1-click updates from the Plugins dashboard.

---

## Developer Hooks

```php
// Register custom measurement units
add_filter( 'wpcalibrate_mp_registered_units', function( array $units ) {
    // Return modified units array
    return $units;
} );

// Register custom calculator strategy
add_filter( 'wpcalibrate_mp_registered_calculators', function( array $calculators ) {
    // Add custom CalculatorInterface implementation
    return $calculators;
} );

// Inspect or modify server pricing result
add_filter( 'wpcalibrate_mp_calculated_pricing_result', function( $result, $request, $config ) {
    return $result;
}, 10, 3 );
```

---

## Changelog

### [1.0.3] - 2026-10-12
- **Fixed:** Sidebar menu icon color and contrast to match other WordPress admin icons (bright crisp appearance, blue hover, and pure white active state).
- **Fixed:** Product Data Measurement Pricing tab left gutter — added 40px left padding from vertical tabs to resolve layout crowding.
- **Fixed:** Styled Enable Measurement Pricing checkbox on a single clean row with label and description.

### [1.0.2] - 2026-10-12
- **Fixed:** Frontend number input collapse where theme stylesheets forced select width to 100%, squishing numeric input display.
- **Fixed:** Default pricing unit automatically selected in frontend calculator dropdown.
- **Changed:** Redesigned Product Data Measurement Pricing tab with 5 organized option groups, concise labels, and native tooltips.

### [1.0.1] - 2026-10-12
- **Added:** Interactive Overview tab to settings explaining calculation methods, step-by-step tutorial, and industry presets.
- **Fixed:** Constrained sidebar menu logo to standard 20px x 20px dimensions.

### [1.0.0] - 2026-10-12
- **Initial Release:** 5 modular calculation strategies (Length, Area, Volume, Weight, Custom Quantity).
- Full WooCommerce HPOS (Custom Order Tables) and Store API Blocks compatibility.
- Authoritative server-side pricing engine with waste and volume tiers.

---

## License & Support

- **License:** GNU General Public License v2.0 or later ([GPL-2.0-or-later](LICENSE))
- **Author:** WPCalibrate
- **Website:** [https://wpcalibrate.com](https://wpcalibrate.com)
- **Support:** [support@wpcalibrate.com](mailto:support@wpcalibrate.com)
