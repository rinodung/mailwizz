/**
 * This file is part of the MailWizz EMA application.
 * 
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com> 
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */
jQuery(document).ready(function($){
	
	var ajaxData = {};
	if ($('meta[name=csrf-token-name]').length && $('meta[name=csrf-token-value]').length) {
			var csrfTokenName = $('meta[name=csrf-token-name]').attr('content');
			var csrfTokenValue = $('meta[name=csrf-token-value]').attr('content');
			ajaxData[csrfTokenName] = csrfTokenValue;
	}

	if ($('#campaigns-overview-wrapper').length) {
		(function(){
			var handle = function(){
				var $el = $('#campaigns-overview-wrapper');
				$el.css({opacity: .5});

				var cid = $el.find('#campaign_id').length ? $el.find('#campaign_id').val() : 0,
					lid = $el.data('list'),
					data = 'campaign_id='+cid+'&list_id=' + lid;

				for (var i in ajaxData) {
					data += '&' + i + '=' + ajaxData[i];
				}

				$.post($el.data('url'), data, function(json){
					$el.html(json.html);
					$el.css({opacity: 1});
				}, 'json');
			};
			$(document).on('change', '#campaigns-overview-wrapper #campaign_id', function(){
				$(this).attr('disabled', true);
				handle();
			});
			handle();
		})()
	}

	// since 2.1.4
	(async function() {
		const promises = [];
		[
			'dashboard-glance-stats-wrapper',
			'dashboard-timeline-items-wrapper'
		].forEach(function(value) {
			let xhr = (function(value){
				return $.get($('#' + value).data('url'), {}, function(response) {
					$('#' + value).html(response.html);
					return response;
				}, 'json');
			})(value);
			promises.push(xhr);
		});

		const results = await Promise.allSettled(promises);
		let hasContent = results.filter(function(result) {
			return result.value.html.length > 0;
		}).length > 0;

		if (!hasContent) {
			$('#dashboard-start-page-wrapper').show();
		}
	})();
});