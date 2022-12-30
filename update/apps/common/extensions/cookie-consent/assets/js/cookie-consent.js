/**
 * This file is part of the MailWizz EMA application.
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 */
window.addEventListener("load", function() {

    var s = document.createElement( 'script' );
    s.setAttribute( 'src', '//cdnjs.cloudflare.com/ajax/libs/cookieconsent2/3.0.3/cookieconsent.min.js' );
    document.body.appendChild( s );

    var s = document.createElement( 'link' );
    s.setAttribute( 'href', '//cdnjs.cloudflare.com/ajax/libs/cookieconsent2/3.0.3/cookieconsent.min.css' );
    s.setAttribute( 'rel', 'stylesheet' );
    s.setAttribute( 'id', 'cookie-consent' );
    document.body.appendChild( s );

    var intval = setInterval(function(){
        if (!window.cookieconsent) {
            return;
        }
        clearInterval(intval);

        if (!document.getElementById('cookie-consent-wrapper')) {
            return;
        }

        window.cookieconsent.initialise(JSON.parse(document.getElementById('cookie-consent-wrapper').dataset.options));
    }, 500);
});
