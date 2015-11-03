jQuery( document ).ready( function($) {
	$(document).on( 'click', '.delete-card', function() {
		var id = $(this).data('id');
		var nonce = $(this).data('nonce');
		var post = $(this).parents('tr:first');
		$.ajax({
			type: 'post',
			url: MyAjax.ajaxurl,
			data: {
				action: 'delete_card',
				nonce: nonce,
				id: id
			},
			success: function( result ) {
				if ( result == 'error' ) {
					$('.payeezy-success-message').hide();
					$('.payeezy-error-message').show();
				} else {
					post.fadeOut( function() {
						post.remove();
					});
					$('.payeezy-error-message').hide();
					$('.payeezy-success-message').show();
				}
			}
		});
		return false;
	});

	$(document).on( 'submit', '#payeezy-cc-form', function(e) {
		e.preventDefault();
		var payeezyCcForm = $(this).serialize();
		$.ajax({
			type: 'post',
			url: MyAjax.ajaxurl+'?action=add_update_card',
			data: payeezyCcForm,
			success: function( result ) {
				if ( result == 'error' ) {
					$('html, body').animate({
						scrollTop: $('#credit-cards').offset().top
					}, 500);
					$('.payeezy-success-message').hide();
					$('.payeezy-error-message').show();
				} else {
					$('#credit-cards-table').replaceWith(result);
					$('.add-card-heading').hide();
					$('.edit-card-heading').hide();
					$('.make-default').hide();
					$('.payeezy-credit-card').hide();
					$('#payeezy-card-number').val('');
					$('#payeezy-card-expiry').val('');
					$('#payeezy-card-cvc').val('');
					$('#payeezy-card-id').val('');
					$('#payeezy-make-default').prop('checked', false);
					$('.add-card').show();
					$('html, body').animate({
						scrollTop: $('#credit-cards').offset().top
					}, 500);
					$('.payeezy-error-message').hide();
					$('.payeezy-success-message').show();
				}
			}
		});
		return false;
	});

	$(document).on( 'click', '.add-card', function(e) {
		e.preventDefault();
		$('.edit-card-heading').hide();
		$('.add-card-heading').show();
		$('.payeezy-credit-card').show();
		$('.make-default').show();
		$('.add-card').hide();
		$('#payeezy-card-id').val('');
		$('html, body').animate({
			scrollTop: $('.add-card-heading').offset().top
		}, 500);
	});

	$(document).on( 'click', '.edit-card', function(e) {
		e.preventDefault();
		var id = $(this).data('id');
		var title = $(this).data('title');
		var exp = $(this).data('exp');
		var is_default = $(this).data('default');
		$('.edit-card-heading').show();
		$('.add-card-heading').hide();
		$('.payeezy-credit-card').show();
		$('.add-card').hide();
		$('.edit-card-heading').html(title);
		$('#payeezy-card-expiry').val(exp);
		$('#payeezy-card-id').val(id);
		if (is_default == 'no') {
			$('.make-default').show();
		} else {
			$('.make-default').hide();
		}
		$('html, body').animate({
			scrollTop: $('.edit-card-heading').offset().top
		}, 500);
	});

	$(document).on( 'click', '.cc-form-cancel', function(e) {
		e.preventDefault();
		$('.add-card-heading').hide();
		$('.edit-card-heading').hide();
		$('.make-default').hide();
		$('.payeezy-credit-card').hide();
		$('#payeezy-card-number').val('');
		$('#payeezy-card-expiry').val('');
		$('#payeezy-card-cvc').val('');
		$('#payeezy-card-id').val('');
		$('#payeezy-make-default').prop('checked', false);
		$('.add-card').show();
		$('html, body').animate({
			scrollTop: $('#credit-cards').offset().top
		}, 500);
	});

	$(document).ajaxStart(function() {
		$( '.payeezy-credit-card' ).block({
			message: null,
			overlayCSS: {
				background: '#fff',
				opacity: 0.6
			}
		});
	});

	$(document).ajaxComplete(function() {
		$( '.payeezy-credit-card' ).unblock();
	});
});
