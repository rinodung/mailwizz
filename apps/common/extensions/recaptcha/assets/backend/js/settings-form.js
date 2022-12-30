/**
 * This file is part of the MailWizz EMA application.
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 */
jQuery(document).ready(function($) {

    var counter = $('.domains-keys-pair-items .domains-keys-pair-item').length;
    $('.btn-add-domains-keys-pair').on('click', function(){
        var template = $('#domains-keys-pair-item-template').html().replace(/\{COUNTER\}/g, counter);
        $('.domains-keys-pair-items').append(template);
        counter++;
    });
    
    $(document).on('click', '.btn-remove-domains-keys-pair', function(){
        $(this).closest('.domains-keys-pair-item').fadeOut('fast', function(){
            $(this).remove();
        });
        return false;
    });
    
});