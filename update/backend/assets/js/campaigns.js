/**
 * This file is part of the MailWizz EMA application.
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.5.5
 */
jQuery(document).ready(function($){

	var ajaxData = {};
	if ($('meta[name=csrf-token-name]').length && $('meta[name=csrf-token-value]').length) {
			var csrfTokenName = $('meta[name=csrf-token-name]').attr('content');
			var csrfTokenValue = $('meta[name=csrf-token-value]').attr('content');
			ajaxData[csrfTokenName] = csrfTokenValue;
	}

	$(document).on('click', 'a.pause-sending, a.unpause-sending', function() {
		if (!confirm($(this).data('message'))) {
			return false;
		}
		$.post($(this).attr('href'), ajaxData, function(){
			window.location.reload();
		});
		return false;
	});

    $(document).on('click', 'a.approve', function() {
        if (!confirm($(this).data('message'))) {
            return false;
        }
        $.post($(this).attr('href'), ajaxData, function(){
            window.location.reload();
        });
        return false;
    });

	(function() {

		var $modal = $('#disapprove-campaign-modal');
		$modal.on('hide.bs.modal', function(){
			$modal.find('textarea[name=disapprove-message]').val('');
			$modal.data('url', '');
		});

		$(document).on('click', 'a.disapprove', function() {
			if (!confirm($(this).data('message'))) {
				return false;
			}
			$modal.data('url', $(this).attr('href'));
			$modal.modal('show');

			return false;
		});

		var resetTextareaErrors = function() {
			var $textarea = $('#disapprove-campaign-modal textarea[name=disapprove-message]');
			$textarea.removeClass('error');
			$textarea.closest('div').find('.errorMessage').hide();
		};

		$(document).on('focus', '#disapprove-campaign-modal textarea[name=disapprove-message]', resetTextareaErrors);

		$(document).on('click', '#disapprove-campaign-modal .btn-disapprove-campaign', function() {
			var $this = $(this);
			if ($this.data('running')) {
				return false;
			}
			$this.data('running', true);

			var $textarea = $('#disapprove-campaign-modal textarea[name=disapprove-message]');
			resetTextareaErrors();

			var message = $textarea.val();
			if (!message || message.length < 5) {
				$textarea.addClass('error');
				$textarea.closest('div').find('.errorMessage').show();
				$this.data('running', false);
				return false;
			}

			$textarea.attr('disabled');
			$this.find('i').removeAttr('class').addClass('fa fa-spinner fa-spin');

			var data = $.extend({}, ajaxData, {
				message: message
			});

			$.post($modal.data('url'), data, function(){
				$modal.modal('hide');
				window.location.reload();
			});

			return false;
		});
	})();

	$(document).on('click', 'a.block-sending, a.unblock-sending', function() {
		if (!confirm($(this).data('message'))) {
			return false;
		}
		$.post($(this).attr('href'), ajaxData, function(){
			window.location.reload();
		});
		return false;
	});

    $(document).on('click', 'a.resume-campaign-sending', function() {
        if (!confirm($(this).data('message'))) {
			return false;
		}
		$.post($(this).attr('href'), ajaxData, function(){
			window.location.reload();
		});
		return false;
	});

    $(document).on('click', 'a.mark-campaign-as-sent', function() {
        if (!confirm($(this).data('message'))) {
			return false;
		}
		$.post($(this).attr('href'), ajaxData, function(){
			window.location.reload();
		});
		return false;
	});

	$(document).on('click', 'a.resend-campaign-giveups', function() {
		var $this = $(this);
		if (!confirm($this.data('message'))) {
			return false;
		}
		$.post($(this).attr('href'), ajaxData, function(json){
			if (json.result === 'success') {
				notify.addSuccess(json.message);
				$this.remove();
			} else {
				notify.addError(json.message);
			}
			$('html, body').animate({scrollTop: 0}, 500);
			notify.show();
		});
		return false;
	});

    $(document).on('click', '.toggle-filters-form', function(){
        $('#filters-form').toggle();
        return false;
    });

	$(document).on('click', '#btn-run-bulk-action', function(e) {
		if ($('#bulk_action').val() === 'compare-campaigns') {
			$('#campaigns-compare-form')
				.append($('.checkbox-column input[type=checkbox]:checked').clone())
				.submit();
			$('#campaigns-compare-modal').modal('show');
			return false;
		}
	});

	(function() {
		const $els = [
			$('#campaign-overview-index-wrapper'),
			$('#campaign-overview-counter-boxes-wrapper'),
			$('#campaign-overview-rate-boxes-wrapper'),
			$('#campaign-overview-daily-performance-wrapper'),
			$('#campaign-overview-top-domains-opens-clicks-graph-wrapper'),
			$('#campaign-overview-geo-opens-wrapper'),
			$('#campaign-overview-open-user-agents-wrapper'),
			$('#campaign-overview-tracking-top-clicked-links-wrapper'),
			$('#campaign-overview-tracking-latest-clicked-links-wrapper'),
			$('#campaign-overview-tracking-latest-opens-wrapper'),
			$('#campaign-overview-tracking-subscribers-with-most-opens-wrapper'),
		];
		$els.map(function($el) {
			if (!$el.length) {
				return;
			}

			$.get($el.data('url'), {}, function(json){
				$el.html(json.html);
			}, 'json');
		})
	})();
});
