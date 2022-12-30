/**
 * This file is part of the MailWizz EMA application.
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.1.11
 */
jQuery(document).ready(function($){

	var ajaxData = {};
	if ($('meta[name=csrf-token-name]').length && $('meta[name=csrf-token-value]').length) {
			var csrfTokenName = $('meta[name=csrf-token-name]').attr('content');
			var csrfTokenValue = $('meta[name=csrf-token-value]').attr('content');
			ajaxData[csrfTokenName] = csrfTokenValue;
	}

    $(document).on('click', '.favorite-page-toggle', function(){
        const $this = $(this);
        if (!$this.data('confirm') || !confirm($this.data('confirm'))) {
            return false;
        }
        const data = $.extend(ajaxData, {
            label: $this.data('label'),
            route: $this.data('route'),
            route_params: $this.data('route_params')
        }, ajaxData);

        $.post($this.data('url'), data, function(json) {
            if (json.status === 'error') {
                notify.remove().addError(json.message).show();
                return false;
            }

            notify.remove().addSuccess(json.message).show();

            if (json.favoritePageWidgetColorClass && json.favoritePageWidgetColorClass.length) {
                $this.removeClass('favorite-page-green').removeClass('favorite-page-gray').addClass(json.favoritePageWidgetColorClass);
                $this.data('current_color_class', json.favoritePageWidgetColorClass);
            }

            if (json.dataConfirmText && json.dataConfirmText.length) {
                $this.data('confirm', json.dataConfirmText);
            }

            if (json.title && json.title.length) {
                $this.attr('data-original-title', json.title);
            }

            if (json.sideMenuItems && json.sideMenuItems.length) {
                const $favoritePagesSideMenu = $('.favorite-pages-side-menu');

                $favoritePagesSideMenu.next('ul').html(json.sideMenuItems);

                // Hide the menu if only 1 items in it
                if ($favoritePagesSideMenu.next('ul').find('li').length > 1) {
                    $favoritePagesSideMenu.removeClass('hidden');
                } else {
                    $favoritePagesSideMenu.addClass('hidden');
                    $favoritePagesSideMenu.next('ul').removeAttr('style');
                    $favoritePagesSideMenu.next('ul').removeClass('menu-open');
                    $favoritePagesSideMenu.closest('li').removeClass('active');
                }
            }
        });
        return false;
    });
});
