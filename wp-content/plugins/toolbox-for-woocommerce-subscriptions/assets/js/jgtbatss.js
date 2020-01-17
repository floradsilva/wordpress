/* global wcsatt_admin_params */
(function($, window, document, undefined) {
	$(function() {

		var quantity = $('.quantity input');
			toForm = $('.jgtb-add-to-subscription'),
			toQuantity = toForm.find('input[name="ats_quantity"]'),
			button = toForm.find('button'),
			subscriptionList = toForm.find('#jgtb_add_to_existing');

		subscriptionList.on('change', function() {
			var val = $(this).find(':selected').val();

			if(val == 'null') {
				button.attr('disabled', 'disabled');
			} else if ( val != 'null') {
				button.removeAttr('disabled');
			}
		});

		quantity.on('change', function() {
			console.log('wot', quantity.val());
			toQuantity.val(quantity.val());
		});

		toQuantity.val(quantity.val());
	});
}(window.jQuery, window, document));
