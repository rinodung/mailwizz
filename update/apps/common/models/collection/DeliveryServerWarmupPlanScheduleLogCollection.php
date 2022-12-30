<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * DeliveryServerWarmupPlanScheduleLogCollection
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.1.10
 */

class DeliveryServerWarmupPlanScheduleLogCollection extends BaseCollection
{
    /**
     * @param mixed $condition
     *
     * @return DeliveryServerWarmupPlanScheduleLogCollection
     */
    public static function findAll($condition = ''): self
    {
        return new self(DeliveryServerWarmupPlanScheduleLog::model()->findAll($condition));
    }

    /**
     * @param array $attributes
     *
     * @return DeliveryServerWarmupPlanScheduleLogCollection
     */
    public static function findAllByAttributes(array $attributes): self
    {
        return new self(DeliveryServerWarmupPlanScheduleLog::model()->findAllByAttributes($attributes));
    }
}
