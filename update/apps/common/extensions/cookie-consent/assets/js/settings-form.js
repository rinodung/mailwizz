/**
 * This file is part of the MailWizz EMA application.
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 */
jQuery(document).ready(function($){

    // Background color picker
    const $backgroundSelectIcon = $('#CookieConsentExtCommon_palette_popup_background').closest('div').find('a.btn-select-color');
    const $backgroundResetIcon  = $('#CookieConsentExtCommon_palette_popup_background').closest('div').find('a.btn-reset-color');

    $backgroundSelectIcon.colorpicker({
        format: 'hex'
    }).on('changeColor', function(e) {
        $('#CookieConsentExtCommon_palette_popup_background').val( e.color.toString('hex') );
    });

    $backgroundResetIcon.on('click', function(){
        $('#CookieConsentExtCommon_palette_popup_background').val('');
        return false;
    });

    // Button color picker
    const $buttonSelectIcon = $('#CookieConsentExtCommon_palette_button_background').closest('div').find('a.btn-select-color');
    const $buttonResetIcon  = $('#CookieConsentExtCommon_palette_button_background').closest('div').find('a.btn-reset-color');

    $buttonSelectIcon.colorpicker({
        format: 'hex'
    }).on('changeColor', function(e) {
        $('#CookieConsentExtCommon_palette_button_background').val( e.color.toString('hex') );
    });

    $buttonResetIcon.on('click', function(){
        $('#CookieConsentExtCommon_palette_button_background').val('');
        return false;
    });
});
