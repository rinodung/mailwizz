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
    
    $.plot("#opens-clicks-by-domain", $('#opens-clicks-by-domain').data('chartdata'), {
        colors: [
            "#3699FF",
            "#0BB783",
            "#8950FC",
            "#F64E60",
            "#0dcaf0",
        ],
        legend: {
            labelBoxBorderColor: "transparent", // border color for the little label boxes// default to no legend sorting
        },
        series: {
            bars: {
                show: true,
                barWidth: 0.5,
                align: "center",
                lineWidth: 0,
                fill:.60
            }
        },
        grid: {
            // borderColor: "rgba(0, 0, 0, 0.1)",
            borderWidth: 1,
        },
        xaxis: {
            mode: "categories",
            tickLength: 0,
            // color: "rgba(0, 0, 0, 0.1)",
        },
        // yaxis: {
        //     color: "rgba(0, 0, 0, 0.1)",
        // },
    });
    
});
