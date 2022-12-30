<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * PricePlanPromoCodeCollection
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.0.0
 */

class PricePlanPromoCodeCollection extends BaseCollection
{
    /**
     * @param mixed $condition
     *
     * @return PricePlanPromoCodeCollection
     */
    public static function findAll($condition = ''): self
    {
        return new self(PricePlanPromoCode::model()->findAll($condition));
    }

    /**
     * @param array $attributes
     *
     * @return PricePlanPromoCodeCollection
     */
    public static function findAllByAttributes(array $attributes): self
    {
        return new self(PricePlanPromoCode::model()->findAllByAttributes($attributes));
    }
}
