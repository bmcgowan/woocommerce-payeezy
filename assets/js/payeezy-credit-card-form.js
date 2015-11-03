jQuery(document).ajaxComplete(function () {
	var initToken = jQuery('#payeezy-token').val();

	if (initToken) {
		jQuery('.hide-if-token').hide();
	}

	jQuery('#payeezy-token').change(function () {
		var token = jQuery('#payeezy-token').val();
		if (token) {
			jQuery('.hide-if-token').hide();
		} else {
			jQuery('.hide-if-token').show();
		}
	});
});

jQuery(document).ready(function () {
	var initToken = jQuery('#payeezy-token').val();

	if (initToken) {
		jQuery('.hide-if-token').hide();
	}

	jQuery('#payeezy-token').change(function () {
		var token = jQuery('#payeezy-token').val();
		if (token) {
			jQuery('.hide-if-token').hide();
		} else {
			jQuery('.hide-if-token').show();
		}
	});
});
