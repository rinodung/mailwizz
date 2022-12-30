<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

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

?>

<div id="reverse-proxy-checker-widget" 
     data-ip="<?php echo html_encode(request()->getUserHostAddress()); ?>"
     style="display: none"
>
    <div id="reverse-proxy-checker-widget-message">
        <?php echo t('app', 'Your client IP address is {ipFromClient}, but the server reports it is {ipFromServer}. This generally suggests your server runs under a reverse proxy, if that is the case, please update your application settings from {here}.', [
            '{ipFromClient}'   => '{ipFromClient}',
            '{ipFromServer}'   => html_encode(request()->getUserHostAddress()),
            '{here}'           => CHtml::link(t('app', 'here'), createUrl('settings/reverse_proxy')),
        ]); ?>
    </div>
</div>