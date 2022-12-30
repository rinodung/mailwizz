/**
 * This file is part of the MailWizz EMA application.
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.1.10
 */
jQuery(document).ready(function($){

    const ajaxData = {};
    if ($('meta[name=csrf-token-name]').length && $('meta[name=csrf-token-value]').length) {
        const csrfTokenName = $('meta[name=csrf-token-name]').attr('content');
        const csrfTokenValue = $('meta[name=csrf-token-value]').attr('content');
        ajaxData[csrfTokenName] = csrfTokenValue;
    }
    
    // since 2.1.10
    (function() {
        const $wrapper = $('#reverse-proxy-checker-widget');
        if (!$wrapper.length) {
            return;
        }
        
        const ipFromServer = $wrapper.data('ip');
        if (!ipFromServer) {
            return;
        }
        
        const messageTemplate = $('#reverse-proxy-checker-widget-message').html();

        // https://stackoverflow.com/questions/391979/how-to-get-clients-ip-address-using-javascript
        $.get('https://www.cloudflare.com/cdn-cgi/trace', function(data) {
            // Convert key-value pairs to JSON
            // https://stackoverflow.com/a/39284735/452587
            const parsedData = $.extend({}, {
                colo: "",
                fl: "",
                gateway: "",
                h: "",
                http: "",
                ip: "",
                loc: "",
                sni: "",
                tls: "",
                ts: "",
                uag: "",
                visit_scheme: "",
                warp: ""
            }, data.trim().split('\n').reduce(function(obj, pair) {
                pair = pair.split('=');
                return obj[pair[0]] = pair[1], obj;
            }, {}));

            // if same IP, nothing to do
            const ipFromClient = parsedData.ip;
            if (ipFromClient === ipFromServer) {
                return;
            }
            
            const message = messageTemplate.replace('{ipFromClient}', ipFromClient);
            if (!$('#notify-container .alert-warning').length) {
                const alertWrapper = '<div class="alert alert-block alert-warning"><button type="button" class="close" data-dismiss="alert">×</button><ul></ul></div>';
                $('#notify-container').append(alertWrapper);
            }
            $('#notify-container .alert-warning ul').append('<li>'+ message +'</li>');
        });
    })();
});