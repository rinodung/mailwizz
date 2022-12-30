<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

use Enqueue\Consumption\Result;

/**
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.0.0
 */

class QueueStatus
{
    public const ACK = Result::ACK;
    public const REJECT =  Result::REJECT;
    public const REQUEUE = Result::REQUEUE;
    public const ALREADY_ACKNOWLEDGED = Result::ALREADY_ACKNOWLEDGED;
    public const WAITING = 'enqueue.waiting';
    public const PROCESSING = 'enqueue.processing';
}
