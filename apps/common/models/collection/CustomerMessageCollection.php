<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * CustomerMessageCollection
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.1.4
 */

class CustomerMessageCollection extends BaseCollection
{
    /**
     * @param mixed $condition
     *
     * @return CustomerMessageCollection
     */
    public static function findAll($condition = ''): self
    {
        return new self(CustomerMessage::model()->findAll($condition));
    }

    /**
     * @param array $attributes
     *
     * @return CustomerMessageCollection
     */
    public static function findAllByAttributes(array $attributes): self
    {
        return new self(CustomerMessage::model()->findAllByAttributes($attributes));
    }
}
