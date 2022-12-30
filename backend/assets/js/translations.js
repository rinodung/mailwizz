/**
 * This file is part of the MailWizz EMA application.
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.0
 */
jQuery(document).ready(function($){

	var ajaxData = {};
	if ($('meta[name=csrf-token-name]').length && $('meta[name=csrf-token-value]').length) {
			var csrfTokenName = $('meta[name=csrf-token-name]').attr('content');
			var csrfTokenValue = $('meta[name=csrf-token-value]').attr('content');
			ajaxData[csrfTokenName] = csrfTokenValue;
	}

	$(document).on('click', 'a.save-one', function() {
		var $this = $(this);

		if ($this.data('running')) {
			return false;
		}
		$this.data('running', true);

		$this.find('i').removeAttr('class').addClass('fa fa-spinner fa-spin');

		var data = $this.closest('tr').find('input, textarea').serialize() + "&" + csrfTokenName + "=" + csrfTokenValue;

		$.post($this.attr('href'), data, function(json){
			$this.find('i').removeAttr('class').addClass('fa fa-check');

			setTimeout(function() {
				$this.find('i').removeAttr('class').addClass('fa fa-save');
				$this.data('running', false);
			}, 1000);
		});
		return false;
	});

	$(document).on('click', 'a.save-all', function() {
		var $this = $(this);

		if ($this.data('running')) {
			return false;
		}
		$this.data('running', true);
		$this.find('i').removeAttr('class').addClass('fa fa-spinner fa-spin');

		var data = $('table.table tbody').find('input, textarea').serialize() + "&" + csrfTokenName + "=" + csrfTokenValue;
		$.post($this.attr('href'), data, function(json){
			notify.remove();
			if (json.result === 'success') {
				notify.addSuccess(json.message);
			} else {
				notify.addError(json.message);
			}
			notify.show();
			$this.data('running', false);
			$this.find('i').removeAttr('class').addClass('fa fa-save');
		});
		return false;
	});
});
