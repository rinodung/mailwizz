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
	var segmentConditionsIndex = $('.conditions-container .item').length, 
		$segmentConditionsTemplate = $('#condition-template');
	
	$('.btn-add-condition').on('click', function(){
		var html = $segmentConditionsTemplate.html();
		html = html.replace(/\{index\}/g, segmentConditionsIndex);
		$('.conditions-container').append(html);
		$('.btn-show-segment-subscribers').hide();
		++segmentConditionsIndex;
		return false;
	});
	
	$(document).on('click', '.btn-remove-condition', function(){
		$(this).closest('.item').remove();
		$('.btn-show-segment-subscribers').hide();
		return false;
	});

	var segmentCampaignConditionsIndex = $('.campaigns-conditions-container .item').length,
		$segmentCampaignConditionsTemplate = $('#campaign-condition-template');

	$('.btn-add-campaign-condition').on('click', function(){
		var html = $segmentCampaignConditionsTemplate.html();
		html = html.replace(/\{index\}/g, segmentCampaignConditionsIndex);
		$('.campaigns-conditions-container').append(html);
		$('.btn-show-segment-subscribers').hide();
		++segmentCampaignConditionsIndex;
		return false;
	});

	$(document).on('click', '.btn-remove-campaign-condition', function(){
		$(this).closest('.item').remove();
		$('.btn-show-segment-subscribers').hide();
		return false;
	});
	
	$('.btn-show-segment-subscribers').on('click', function(){
		var $this = $(this);
		$.get($this.attr('href'), {}, function(html){
			$('#subscribers-wrapper').html(html);
			$('.subscribers-wrapper').show();
			$this.hide();
		});
		return false;
	});
	
	$(document).on('click', 'ul#subscribers-pagination li a', function(){
		$.get($(this).attr('href'), {}, function(html){
			$('#subscribers-wrapper').html(html);
			$('.subscribers-wrapper').show();
		});
		return false;
	});
	
    $(document).on('click', 'a.copy-segment', function() {
		$.post($(this).attr('href'), ajaxData, function(){
			window.location.reload();
		});
		return false;
	});
});