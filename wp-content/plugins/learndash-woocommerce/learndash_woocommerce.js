jQuery(function($){
	var pricingPane = $('#woocommerce-product-data');
	if(pricingPane.length){
		pricingPane.find('.pricing').addClass('show_if_course').end()
			.find('.inventory_tab').addClass('hide_if_course').end()
			.find('.shipping_tab').addClass('hide_if_course').end()
			.find('.linked_product_tab').addClass('hide_if_course').end()
			.find('.attributes_tab').addClass('hide_if_course')
		;
	}
});