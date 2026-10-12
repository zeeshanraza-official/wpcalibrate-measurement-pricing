/**
 * WPCalibrate Measurement Pricing - Frontend Live Calculator UX
 *
 * Provides responsive, debounced live preview calculation on product pages.
 * Server independently validates and calculates authoritative prices on Add to Cart.
 */
(function ($) {
	'use strict';

	var WPCalibrateCalculator = {
		debounceTimer: null,

		init: function () {
			var self = this;

			$(document).ready(function () {
				$('.wpcalibrate-mp-calculator-container').each(function () {
					self.initContainer($(this));
				});

				self.bindVariationEvents();
			});
		},

		initContainer: function ($container) {
			var self = this;
			var rawConfig = $container.attr('data-config');
			if (!rawConfig) {
				return;
			}

			try {
				$container.data('config', JSON.parse(rawConfig));
			} catch (e) {
				return;
			}

			// Bind input changes.
			$container.on('input change', '.wpcalibrate-mp-dimension-input, .wpcalibrate-mp-unit-select', function () {
				self.scheduleCalculation($container);
			});

			// If inputs have initial values, run once.
			self.calculate($container);
		},

		scheduleCalculation: function ($container) {
			var self = this;
			var debounceMs = (window.wpcalibrate_mp_vars && window.wpcalibrate_mp_vars.debounce_ms) ? window.wpcalibrate_mp_vars.debounce_ms : 150;

			clearTimeout(self.debounceTimer);
			self.debounceTimer = setTimeout(function () {
				self.calculate($container);
			}, debounceMs);
		},

		calculate: function ($container) {
			var config = $container.data('config');
			if (!config || !config.enabled) {
				return;
			}

			var $notice = $container.find('.wpcalibrate-mp-notice');
			var $measurementDisplay = $container.find('.wpcalibrate-mp-live-measurement');
			var $priceDisplay = $container.find('.wpcalibrate-mp-live-price');
			var $submitBtn = $container.closest('form.cart').find(':submit');

			var dimensionValues = {};
			var dimensionUnits = {};
			var allFilled = true;
			var hasInvalid = false;

			$container.find('.wpcalibrate-mp-dimension-input').each(function () {
				var $input = $(this);
				var dimKey = $input.data('dimension');
				var rawVal = $input.val();

				if (rawVal === '' || rawVal === null || typeof rawVal === 'undefined') {
					allFilled = false;
					return;
				}

				var numVal = parseFloat(String(rawVal).replace(',', '.'));
				if (isNaN(numVal) || numVal <= 0) {
					hasInvalid = true;
					return;
				}

				dimensionValues[dimKey] = numVal;

				var $wrap = $input.closest('.wpcalibrate-mp-input-wrap');
				var unitVal = $wrap.find('.wpcalibrate-mp-unit-select, input[type="hidden"]').val();
				dimensionUnits[dimKey] = unitVal || config.pricingUnit;
			});

			if (hasInvalid) {
				$notice.text(window.wpcalibrate_mp_vars.i18n.invalid_number).show();
				$measurementDisplay.text('—');
				$priceDisplay.text('—');
				return;
			}

			if (!allFilled) {
				$notice.hide();
				$measurementDisplay.text('—');
				$priceDisplay.text('—');
				return;
			}

			$notice.hide();

			// 1. Calculate raw measurement.
			var rawMeasurement = this.computeRawMeasurement(config.calculatorType, dimensionValues, dimensionUnits, config.pricingUnit);
			if (rawMeasurement <= 0) {
				$measurementDisplay.text('—');
				$priceDisplay.text('—');
				return;
			}

			// 2. Apply waste percentage.
			var wasteAmount = 0;
			if (config.wastePercentage > 0) {
				wasteAmount = (rawMeasurement * config.wastePercentage) / 100;
			}
			var measurementAfterWaste = rawMeasurement + wasteAmount;

			// 3. Increment & Rounding.
			var chargeableMeasurement = this.roundToIncrement(
				measurementAfterWaste,
				parseFloat(config.increment) || 0,
				config.roundingMode || 'nearest',
				parseInt(config.precision, 10) || 4
			);

			// 4. Boundary constraints.
			var minConstraint = parseFloat(config.minMeasurement) || 0;
			var maxConstraint = parseFloat(config.maxMeasurement) || 0;

			if (minConstraint > 0 && chargeableMeasurement < minConstraint) {
				$notice.text(window.wpcalibrate_mp_vars.i18n.below_minimum + ' (' + minConstraint + ' ' + config.pricingUnitSymbol + ')').show();
			} else if (maxConstraint > 0 && chargeableMeasurement > maxConstraint) {
				$notice.text(window.wpcalibrate_mp_vars.i18n.exceeds_maximum + ' (' + maxConstraint + ' ' + config.pricingUnitSymbol + ')').show();
			}

			// 5. Resolve pricing tier.
			var effectiveUnitPrice = this.resolveTierPrice(config.pricingTiers, chargeableMeasurement, parseFloat(config.unitPrice) || 0);

			// 6. Calculate total price.
			var measurementPrice = chargeableMeasurement * effectiveUnitPrice;
			var baseCharge = parseFloat(config.baseCharge) || 0;
			var minimumCharge = parseFloat(config.minimumCharge) || 0;
			var finalPrice = Math.max(measurementPrice + baseCharge, minimumCharge);

			// Update UI.
			$measurementDisplay.text(this.formatNumber(chargeableMeasurement, parseInt(config.precision, 10) || 2));
			$priceDisplay.text(this.formatCurrency(finalPrice));
		},

		computeRawMeasurement: function (type, values, units, targetPricingUnit) {
			var self = this;

			if (type === 'length') {
				var rawLen = values['length'] || 0;
				return self.convertUnit(rawLen, units['length'] || targetPricingUnit, targetPricingUnit);
			}

			if (type === 'area') {
				var widthM = self.convertUnit(values['width'] || 0, units['width'] || 'm', 'm');
				var lengthM = self.convertUnit(values['length'] || 0, units['length'] || 'm', 'm');
				var areaSqM = widthM * lengthM;
				return self.convertUnit(areaSqM, 'sq_m', targetPricingUnit);
			}

			if (type === 'volume') {
				var lenM = self.convertUnit(values['length'] || 0, units['length'] || 'm', 'm');
				var wM = self.convertUnit(values['width'] || 0, units['width'] || 'm', 'm');
				var hM = self.convertUnit(values['height'] || 0, units['height'] || 'm', 'm');
				var volCuM = lenM * wM * hM;
				return self.convertUnit(volCuM, 'cu_m', targetPricingUnit);
			}

			if (type === 'weight') {
				var rawWeight = values['weight'] || 0;
				return self.convertUnit(rawWeight, units['weight'] || targetPricingUnit, targetPricingUnit);
			}

			if (type === 'quantity') {
				return values['quantity'] || 0;
			}

			return 0;
		},

		convertUnit: function (amount, fromUnit, toUnit) {
			if (fromUnit === toUnit) {
				return amount;
			}

			var registry = (window.wpcalibrate_mp_vars && window.wpcalibrate_mp_vars.units) ? window.wpcalibrate_mp_vars.units : {};
			var source = registry[fromUnit];
			var target = registry[toUnit];

			if (!source || !target || source.dimension !== target.dimension) {
				return amount;
			}

			var canonical = amount * source.factor;
			return canonical / target.factor;
		},

		roundToIncrement: function (value, increment, mode, precision) {
			if (value <= 0) {
				return 0;
			}
			if (increment <= 0) {
				return parseFloat(value.toFixed(precision));
			}

			var ratio = value / increment;
			var roundedRatio;

			if (mode === 'up') {
				roundedRatio = Math.ceil(ratio - 1e-9);
			} else if (mode === 'down') {
				roundedRatio = Math.floor(ratio + 1e-9);
			} else {
				roundedRatio = Math.round(ratio);
			}

			var result = roundedRatio * increment;
			return parseFloat(result.toFixed(precision));
		},

		resolveTierPrice: function (tiers, measurement, defaultPrice) {
			if (!tiers || !tiers.length) {
				return defaultPrice;
			}

			for (var i = 0; i < tiers.length; i++) {
				var t = tiers[i];
				var min = parseFloat(t.min) || 0;
				var max = (t.max !== null && typeof t.max !== 'undefined' && t.max !== '') ? parseFloat(t.max) : null;

				if (measurement >= min && (max === null || measurement <= max)) {
					return parseFloat(t.price) || 0;
				}
			}

			var lastTier = tiers[tiers.length - 1];
			if (lastTier && measurement > (parseFloat(lastTier.max) || 0)) {
				return parseFloat(lastTier.price) || 0;
			}

			return defaultPrice;
		},

		formatCurrency: function (price) {
			var vars = window.wpcalibrate_mp_vars || {};
			var decimals = parseInt(vars.decimals, 10) || 2;
			var decSep = vars.decimal_separator || '.';
			var thouSep = vars.thousand_separator || ',';
			var sym = vars.currency_symbol || '$';
			var pos = vars.currency_pos || 'left';

			var parts = price.toFixed(decimals).split('.');
			parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, thouSep);
			var formatted = parts.join(decSep);

			if (pos === 'right') {
				return formatted + sym;
			} else if (pos === 'right_space') {
				return formatted + ' ' + sym;
			} else if (pos === 'left_space') {
				return sym + ' ' + formatted;
			}
			return sym + formatted;
		},

		formatNumber: function (num, decimals) {
			return parseFloat(num.toFixed(decimals)).toString();
		},

		bindVariationEvents: function () {
			var self = this;

			$(document).on('found_variation', 'form.variations_form', function (event, variation) {
				var $container = $(this).find('.wpcalibrate-mp-calculator-container');
				if (!$container.length) {
					return;
				}

				if (variation.wpcalibrate_mp_config) {
					$container.data('config', variation.wpcalibrate_mp_config);
					if (variation.wpcalibrate_mp_config.enabled) {
						$container.show();
						self.calculate($container);
					} else {
						$container.hide();
					}
				} else {
					$container.show();
					self.calculate($container);
				}
			});

			$(document).on('reset_data', 'form.variations_form', function () {
				var $container = $(this).find('.wpcalibrate-mp-calculator-container');
				if (!$container.length) {
					return;
				}
				var initialConfig = $container.attr('data-config');
				if (initialConfig) {
					try {
						var parsed = JSON.parse(initialConfig);
						$container.data('config', parsed);
						if (!parsed.enabled) {
							$container.hide();
						}
					} catch (e) {}
				}
			});
		}
	};

	WPCalibrateCalculator.init();
})(jQuery);
