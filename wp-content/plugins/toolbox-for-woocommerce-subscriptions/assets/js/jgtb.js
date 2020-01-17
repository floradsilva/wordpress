/* global wcsatt_admin_params */
(function($, window, undefined) {
	$(function() {
		var new_date_input = $('#pickadate');

		new_date_input.datepicker({
			dateFormat: "yy-mm-dd",
			minDate: 1,
			maxDate: "+6m"
		});
	});
}(window.jQuery, window));
