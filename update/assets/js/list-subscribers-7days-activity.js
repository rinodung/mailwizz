/**
 * This file is part of the MailWizz EMA application.
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.5.2
 */
jQuery(document).ready(function($){
    
    var plot = $.plot("#7days-activity", $('#7days-activity').data('chartdata'), {
        colors: [
            "#3699FF",
            "#0BB783",
            "#8950FC",
            "#F64E60",
            "#0dcaf0",
        ],
        series: {
            lines: {
                show: true
            },
            points: {
                show: true
            }
        },
        grid: {
            hoverable: true,
            clickable: true,
            autoHighlight: true,
            // borderColor: "rgba(0, 0, 0, 0.1)",
            borderWidth: 1,
        },
        xaxis: {
            mode: "time",
            timeformat: "%Y-%m-%d",
            tickSize: [1, "day"],
            timezone: "browser",
            tickFormatter: function(value, axis) {
                var d = new Date(value);
                return d.strftime('%A');
            },
            // color: "rgba(0, 0, 0, 0.1)",
        },
        crosshair: {
            mode: "x"
        }
    });

    $("<div id='tooltip'></div>").css({
        position: "absolute",
        display: "none",
        border: "1px solid #008ca9",
        color: '#000000',
        padding: "2px",
        "background-color": "#ebf6f8",
        opacity: 0.80
    }).appendTo("body");

    $("#7days-activity").bind("plothover", function (event, pos, item) {

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

    $("#7days-activity").bind("plotclick", function (event, pos, item) {
        
        if (item) {
            plot.highlight(item.series, item.datapoint);
        }
        
    });
    
});
