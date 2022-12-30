/**
 * This file is part of the MailWizz EMA application.
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.1.9
 */
jQuery(document).ready(function($){

    $.plot("#delivery-server-warmup-plan-graph", $('#delivery-server-warmup-plan-graph').data('chartdata'), {
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
            lines: {
                show: true
            },
            points: {
                show: true
            }
        },
        grid: {
            borderWidth: 1,
            hoverable: true,
            clickable: false,
        },
        xaxis: {
            tickLength: 0,
        },
    });

    $("<div id='tooltip'></div>").css({
        position: "absolute",
        display: "none",
        border: "1px 009ef7 #181c32",
        borderRadius: "4px",
        color: '#f8f9fa',
        fontWeight: "600",
        fontFamily: "Poppins,Helvetica,sans-serif",
        padding: "2px 4px",
        "background-color": "#181c32",
        opacity: 1
    }).appendTo("body");

    $("#delivery-server-warmup-plan-graph").bind("plothover", function (event, pos, item) {
        if (item) {

            const y = item.datapoint[1].toFixed(0);
            $("#tooltip")
                .html(item.series.label + ': ' + y)
                .css({
                    top: item.pageY + 5,
                    left: item.pageX + 5
                })
                .fadeIn(200);

        } else {

            $("#tooltip").hide();
        }

    });
});
