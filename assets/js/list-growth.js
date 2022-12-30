/**
 * This file is part of the MailWizz EMA application.
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.0.33
 */
jQuery(document).ready(function($){

    let chart;

    $(document).on('change', '.list-growth-wrapper #list_growth_ranges', function() {
        $('#list-growth-wrapper-content').hide();
        $('#list-growth-wrapper-loader').show();
        
        const $this = $(this);
        const data = $.extend({}, {
            range: $this.val()
        }, ajaxData);

        $.post($this.data('url'), data, function(json) {
            $('#list-growth-wrapper-loader').hide();
            $('#list-growth-wrapper-content').show();
            
            if (!json.chartData || !json.chartOptions) {
                return;
            }

            if (!chart) {
                chart = new Chart($('#list-growth-chart'), {
                    type: 'bar',
                    data: json.chartData,
                    options: json.chartOptions
                });
            }

            chart.data = json.chartData;
            chart.options = json.chartOptions;
            chart.update();
        }, 'json');
    });

    $('.list-growth-wrapper #list_growth_ranges').find('option:last').attr('selected', true);
    $('.list-growth-wrapper #list_growth_ranges').trigger('change');
});
