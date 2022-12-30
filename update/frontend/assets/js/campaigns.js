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
