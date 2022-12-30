/**
 * This file is part of the MailWizz EMA application.
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.0.30
 */
jQuery(document).ready(function($){
    const $menuItemsTemplate    = $('#menu-items-template');
    let  menuItemsCounter      = $menuItemsTemplate.data('count');

    $('a.btn-add-menu-item').on('click', function(){
        let $html = $($menuItemsTemplate.html().replace(/__#__/g, menuItemsCounter));
        $('#menu-items-list').append($html);
        $html.find('input, select').removeAttr('disabled');
        menuItemsCounter++;
        return false;
    });

    $(document).on('click', 'a.remove-menu-item', function(){
        $(this).closest('.menu-item').remove();
        return false;
    });
});
