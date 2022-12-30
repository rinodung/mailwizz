<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

use Enqueue\Consumption\ExtensionInterface;

/**
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.0.0
 */

interface QueueMonitorConsumptionExtensionInterface extends ExtensionInterface
{
    /**
     * @return QueueInterface
     */
    public function getQueue(): QueueInterface;
}
