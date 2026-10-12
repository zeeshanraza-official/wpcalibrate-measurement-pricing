/**
 * WPCalibrate Measurement Pricing - Admin JavaScript
 */
(function ($) {
	'use strict';

	$(document).ready(function () {
		// Toggle measurement fields in Product Data.
		var $enableCheckbox = $('#wpcalibrate_mp\\[enabled\\]');
		var $configFields   = $('.wpcalibrate-mp-config-fields');

		function toggleFields() {
			if ($enableCheckbox.is(':checked')) {
				$configFields.slideDown(150);
			} else {
				$configFields.slideUp(150);
			}
		}

		$enableCheckbox.on('change', toggleFields);

		// Dynamic Calculator Mode -> Pricing Unit Filtering.
		var $modeSelect = $('#wpcalibrate_mp_calculator_type');
		var $unitSelect = $('#wpcalibrate_mp_pricing_unit');

		var dimensionMap = {
			'length': 'length',
			'area': 'area',
			'volume': 'volume',
			'weight': 'weight',
			'quantity': 'custom'
		};

		function filterUnits() {
			var selectedMode = $modeSelect.val();
			var expectedDim  = dimensionMap[selectedMode] || 'length';

			var matchedFirst = false;
			$unitSelect.find('option').each(function () {
				var $opt = $(this);
				var dim  = $opt.attr('data-dimension');

				if (dim === expectedDim) {
					$opt.show().prop('disabled', false);
					if (!matchedFirst && $opt.is(':selected')) {
						matchedFirst = true;
					}
				} else {
					$opt.hide().prop('disabled', true);
				}
			});

			// If current selected option is now disabled, select first visible option.
			if ($unitSelect.find('option:selected').prop('disabled')) {
				$unitSelect.find('option:enabled').first().prop('selected', true);
			}
		}

		if ($modeSelect.length && $unitSelect.length) {
			$modeSelect.on('change', filterUnits);
			filterUnits();
		}

		// Pricing Tiers Repeater in Product Data.
		var $tbody = $('#wpcalibrate_mp_tiers_tbody');
		var $addBtn = $('#wpcalibrate_mp_add_tier');

		$addBtn.on('click', function (e) {
			e.preventDefault();
			var index = $tbody.find('tr').length;

			var rowHtml = '<tr>' +
				'<td><input type="number" step="any" min="0" name="wpcalibrate_mp[pricing_tiers][' + index + '][min]" class="short" required /></td>' +
				'<td><input type="number" step="any" min="0" name="wpcalibrate_mp[pricing_tiers][' + index + '][max]" class="short" placeholder="Unlimited" /></td>' +
				'<td><input type="number" step="any" min="0" name="wpcalibrate_mp[pricing_tiers][' + index + '][price]" class="short" required /></td>' +
				'<td><button type="button" class="button button-small wpcalibrate-mp-remove-tier">&times;</button></td>' +
				'</tr>';

			$tbody.append(rowHtml);
		});

		$tbody.on('click', '.wpcalibrate-mp-remove-tier', function (e) {
			e.preventDefault();
			$(this).closest('tr').remove();
		});

		// Variation override checkbox toggling.
		$('#woocommerce-product-data').on('change', '.wpcalibrate-mp-override-toggle', function () {
			var $fields = $(this).closest('.wpcalibrate-mp-variation-wrapper').find('.wpcalibrate-mp-variation-fields');
			if ($(this).is(':checked')) {
				$fields.slideDown(150);
			} else {
				$fields.slideUp(150);
			}
		});
	});
})(jQuery);
