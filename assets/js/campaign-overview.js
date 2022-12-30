/**
 * This file is part of the MailWizz EMA application.
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.1.4
 */
jQuery(document).ready(function($){
    // since 1.3.7.3
    function randomString(length) {
        var text 	 = [],
            possible = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789",
            length	 = typeof length == 'number' ? length : 12;

        for (var i = 0; i < length; i++) {
            text.push(possible.charAt(Math.floor(Math.random() * possible.length)));
        }

        return text.join("");
    }
    $(document).on('click', '.btn-generate-share-password', function(){
        $('#CampaignOptionShareReports_share_reports_password').val(randomString());
        return false;
    });
    $(document).on('submit', '#campaign-share-reports-form', function(){
        var $form = $(this), $message = $form.find('.message');
        $message.empty();
        $message.append('<div class="alert alert-info">' + $message.data('wait') + '</div>');
        $.post($form.attr('action'), $form.serialize(), function(json){
            $message.empty();
            if (json.result == 'success') {
                $message.append('<div class="alert alert-success">' + json.message + '</div>');
            } else {
                $message.append('<div class="alert alert-danger">' + json.message + '</div>');
            }
        }, 'json');
        return false;
    });
    //

    // since 1.4.4
    $(document).on('change', '#CampaignOptionShareReports_share_reports_enabled', function(){
        if ($(this).val() != 'yes') {
            $('#CampaignOptionShareReports_share_reports_email').attr('disabled', true);
            $('.btn-send-share-stats-details').attr('disabled', true);
        } else {
            $('#CampaignOptionShareReports_share_reports_email').removeAttr('disabled');
            $('.btn-send-share-stats-details').removeAttr('disabled');
        }
    }).trigger('change');
    $(document).on('click', '.btn-send-share-stats-details', function(){
        var $this = $(this), $form = $this.closest('form'), $message = $form.find('.message'), data = $form.serialize();
        $this.attr('disabled', true);
        $message.empty();
        $message.append('<div class="alert alert-info">' + $message.data('wait') + '</div>');
        $.post($this.data('action'), data, function(json){
            $message.empty();
            if (json.result == 'success') {
                $message.append('<div class="alert alert-success">' + json.message + '</div>');
            } else {
                $message.append('<div class="alert alert-danger">' + json.message + '</div>');
            }
            $this.removeAttr('disabled');
        }, 'json');
        return false;
    });
    //
});
