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
    
    var plot = $.plot("#24hours-performance", $('#24hours-performance').data('chartdata'), {
        colors: [
            "#3699FF",
            "#0BB783",
            "#8950FC",
            "#F64E60",
            "#0dcaf0",
        ],
        series: {
            lines: {
                show: true,
            },
            points: {
                show: true,
            },
        },
        grid: {
            hoverable: true,
            clickable: true,
            autoHighlight: true,
            // borderColor: "rgba(0, 0, 0, 0.1)",
            borderWidth: 1,
        },
        
        xaxis: {
            // mode: "time",
            // timeformat: "%H:00%P"
            
            // since 1.4.4
            mode: "time",
            timeformat: "%H:00%P",
            tickSize: [1, "hour"],
            timezone: "browser",
            // color: "rgba(0, 0, 0, 0.1)",
        },
        // yaxis: {
        //     color: "rgba(0, 0, 0, 0.1)",
        // },
        crosshair: {
            mode: "x"
        }
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

    $("#24hours-performance").bind("plothover", function (event, pos, item) {

        if (item) {
            
            var y = item.datapoint[1].toFixed(0);
            $("#tooltip")
                .html(y + ' ' + item.series.label)
                .css({
                    top: item.pageY + 5, 
                    left: item.pageX + 5
                })
                .fadeIn(200);
            
        } else {
            
            $("#tooltip").hide();
        }

    });

    $("#24hours-performance").bind("plotclick", function (event, pos, item) {
        
        if (item) {
            plot.highlight(item.series, item.datapoint);
        }
        
    });
});