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
 * @since 2.1.4
 */

/** @var Controller $controller */
$controller = controller();

/** @var Campaign $campaign */
$campaign = $controller->getData('campaign');

$controller->widget('customer.components.web.widgets.campaign-tracking.CampaignTopDomainsOpensClicksGraphWidget', [
    'campaign' => $campaign,
]);
