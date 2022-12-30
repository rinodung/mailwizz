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

    var $headersTemplate    = $('#headers-template'),
        headersCounter      = $headersTemplate.data('count');

    $('a.btn-add-header').on('click', function(){
        var $html = $($headersTemplate.html().replace(/__#__/g, headersCounter));
        $('#headers-list').append($html);
        $html.find('input').removeAttr('disabled');
        headersCounter++;
        return false;
    });

    $(document).on('click', 'a.remove-header', function(){
        $(this).closest('.header-item').remove();
        return false;
    });

    var $policiesTemplate    = $('#policies-template'),
        policiesCounter      = $policiesTemplate.data('count');

    $('a.btn-add-policy').on('click', function(){
        var $html = $($policiesTemplate.html().replace(/__#__/g, policiesCounter));
        $('#policies-list').append($html);
        $html.find('input, select').removeAttr('disabled');
        policiesCounter++;
        return false;
    });

    $(document).on('click', 'a.remove-policy', function(){
        $(this).closest('.policy-item').remove();
        return false;
    });

    $(document).on('click', 'a.copy-server, a.enable-server, a.disable-server', function() {
		$.post($(this).attr('href'), ajaxData, function(){
			window.location.reload();
		});
		return false;
	});

    $(document).on('keyup', '#select-server-type-modal input[name="search"]', function(){
        var val = $(this).val();
        if (val.length == 0) {
            $('ul.select-delivery-servers-list li').show();
            return true;
        }
        val = val.toLowerCase();
        $('ul.select-delivery-servers-list li').each(function(){
            if ($('a', this).eq(0).text().toLowerCase().indexOf(val) === -1) {
                $(this).hide();
            } else {
                $(this).show();
            }
        });
    });

    if ($('.warmup-plan-schedules-wrapper').length) {
        const planBadgeClasses = ['badge', 'badge-ds-warmup-plan-status'];
        const $planBadge = $('.' + planBadgeClasses.join('.'));
        const $quotaFormField = $('.delivery-server-quota-form-field');

        (function(){
            const handle = function(options) {
                options = $.extend({}, {
                    planChangeHandler: function(planId) {},
                    autocompleteItem: {}
                }, options || {});

                const $el = $('.warmup-plan-schedules-wrapper');
                if (!$el.length) {
                    return;
                }

                let planId = $('.delivery-server-warmup-plan-id').val();
                if (!planId) {
                    $el.html('');
                    return;
                }

                let url = $el.data('url');

                options.planChangeHandler(planId);

                if (url) {
                    url += '/plan_id/' + planId;

                    $el.css({opacity: .5});
                    $.get(url, {}, function(json){
                        $el.html(json.html);
                        $el.css({opacity: 1});

                        if (json.completed) {
                            $planBadge.addClass($planBadge.data('completed-class'));
                            $planBadge.text($planBadge.data('completed-text'));
                        } else {
                            $planBadge.addClass($planBadge.data('not-completed-class'));
                            $planBadge.text($planBadge.data('not-completed-text'));
                        }
                        $planBadge.show();
                    }, 'json');
                }
            }

            $('#warmup-plan').on('autocomplete:select', function() {
                $planBadge.hide().removeAttr('class').addClass(planBadgeClasses.join(' '));

                handle({
                    planChangeHandler: function (planId) {
                        if (planId) {
                            $quotaFormField.attr('disabled', true);
                            $quotaFormField.attr('readonly', true);
                        } else {
                            $quotaFormField.removeAttr('disabled');
                            $quotaFormField.removeAttr('readonly');
                        }
                    }
                });
            }).on('blur', function() {
                if (!$(this).val()) {
                    $planBadge.hide().removeAttr('class').addClass(planBadgeClasses.join(' '));

                    $('.delivery-server-warmup-plan-id').val('');
                    $('.warmup-plan-schedules-wrapper').html('');

                    $quotaFormField.removeAttr('disabled');
                    $quotaFormField.removeAttr('readonly');
                }
            });

            handle();
        })();
    }
});
