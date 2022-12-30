<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.0.0
 */

interface QueueMessageInterface
{
    /**
     * @return string
     */
    public function getBody(): string;

    /**
     * @return array
     */
    public function getProperties(): array;

    /**
     * @return array
     */
    public function getHeaders(): array;

    /**
     * @return null|int
     */
    public function getDeliveryDelay(): ?int;

    /**
     * @return null|int
     */
    public function getPriority(): ?int;

    /**
     * @return null|int
     */
    public function getTimeToLive(): ?int;
}
