<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * LanguageCollection
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.0.0
 */

class LanguageCollection extends BaseCollection
{
    /**
     * @param mixed $condition
     *
     * @return LanguageCollection
     */
    public static function findAll($condition = ''): self
    {
        return new self(Language::model()->findAll($condition));
    }

    /**
     * @param array $attributes
     *
     * @return LanguageCollection
     */
    public static function findAllByAttributes(array $attributes): self
    {
        return new self(Language::model()->findAllByAttributes($attributes));
    }
}
