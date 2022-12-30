/**
 * This file is part of the MailWizz EMA application.
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.4.5
 */
jQuery(document).ready(function($){

    var ajaxData = {};
    if ($('meta[name=csrf-token-name]').length && $('meta[name=csrf-token-value]').length) {
        var csrfTokenName = $('meta[name=csrf-token-name]').attr('content');
        var csrfTokenValue = $('meta[name=csrf-token-value]').attr('content');
        ajaxData[csrfTokenName] = csrfTokenValue;
    }

    var placeholder = $('#campaign-geo-opens');
    if (placeholder.length) {
        $.plot(placeholder, placeholder.data('chartdata'), {
            colors: [
                "#3699FF",
                "#0BB783",
                "#8950FC",
                "#F64E60",
                "#0dcaf0",
            ],
            series: {
                pie: {
                    show: true,
                    label: {
                        show: false
                    },
                    stroke: {
                        width: 3
                    }
                }
            },
            legend: {
                show: true,
                labelFormatter: function(label, series) {
                    return '<div>&nbsp; ' + label + ' ( ' + series.count_formatted + ' / ' +  Math.round(series.percent) + '% ) </div>';
                }
            },
            grid: {
                hoverable: true,
                clickable: true
            }
        });    
    }
});