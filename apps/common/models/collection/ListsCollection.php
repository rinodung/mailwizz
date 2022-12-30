<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * ListsCollection
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.0.0
 */

class ListsCollection extends BaseCollection
{
    /**
     * @param mixed $condition
     *
     * @return ListsCollection
     */
    public static function findAll($condition = ''): self
    {
        return new self(Lists::model()->findAll($condition));
    }

    /**
     * @param array $attributes
     *
     * @return ListsCollection
     */
    public static function findAllByAttributes(array $attributes): self
    {
        return new self(Lists::model()->findAllByAttributes($attributes));
    }
}
