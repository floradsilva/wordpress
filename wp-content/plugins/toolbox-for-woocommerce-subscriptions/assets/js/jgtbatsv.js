/* global wcsatt_admin_params */
(function($, window, document, undefined) {
	$(function() {

		var fromForm = $('.variations_form'),
			quantity = fromForm.find('input[name="quantity"]'),
			toForm = $('.jgtb-add-to-subscription'),
			toQuantity = toForm.find('input[name="ats_quantity"]'),
			button = toForm.find('button'),
			subscriptionList = toForm.find('#jgtb_add_to_existing'),
			variationId = toForm.find('input[name="ats_variation_id"]'),
			variationAttr = toForm.find('input[name="ats_variation_attributes"]');

		fromForm.each( function() {
			$(this).wc_variation_form().on('found_variation', function(e, variation) {
				var selectedAttributes = {};

				variationId.val(variation.variation_id);
				/**
				 * Let's find all the selected variations for the found variation
				 * to catch the "Any..." attributes
				 */
				Object.keys(variation['attributes']).map(function(attribute, index) {
					selectedAttributes[attribute] = $('select#' + attribute.replace('attribute_', '')).val();
				});
				variationAttr.val(JSON.stringify(selectedAttributes));

				if ( subscriptionList.find(':selected').val() !== 'null' ) {
					button.removeAttr('disabled');
				}
			}).on('reset_data', function(e) {
				variationId.val(0);
				button.attr('disabled', 'disabled');
			});
		});

		subscriptionList.on('change', function() {
			var val = $(this).find(':selected').val();
			if(variationId.val() == 0 || val == 'null') {
				button.attr('disabled', 'disabled');
			} else if ( val != 'null') {
				button.removeAttr('disabled');
			}
		});

		quantity.on('change', function() {
			toQuantity.val(quantity.val());
		});

		toQuantity.val(quantity.val());
	});
}(window.jQuery, window, document));
