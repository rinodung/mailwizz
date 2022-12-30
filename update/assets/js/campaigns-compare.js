/**
 * This file is part of the MailWizz EMA application.
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.9.17
 */
jQuery(document).ready(function($){

    var ajaxData = {};
    if ($('meta[name=csrf-token-name]').length && $('meta[name=csrf-token-value]').length) {
        var csrfTokenName = $('meta[name=csrf-token-name]').attr('content');
        var csrfTokenValue = $('meta[name=csrf-token-value]').attr('content');
        ajaxData[csrfTokenName] = csrfTokenValue;
    }

    var $modal = $('#campaigns-compare-modal');
    $modal.on('hide.bs.modal', function(){
        $modal.find('.modal-body-loader').show();
        $modal.find('.modal-body-content').html('').hide();
        $modal.find('input[type="checkbox"]').remove();
    }).on('shown.bs.modal', function(){
        
    });
    
    $(document).on('submit', '#campaigns-compare-form', function() {
        var $this = $(this);
        $.post($this.attr('action'), $this.serialize(), function (html) {
            $modal.find('.modal-body-loader').hide();
            $modal.find('.modal-body-content').html(html).show();
        }, 'html');
        return false;
    });
});