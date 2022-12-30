<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * BaseCollection
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.0.0
 */

use Illuminate\Support\Collection;

abstract class BaseCollection extends Collection
{
    /**
     * @param mixed $condition
     *
     * @return mixed
     */
    abstract public static function findAll($condition = '');

    /**
     * @param array $attributes
     *
     * @return mixed
     */
    abstract public static function findAllByAttributes(array $attributes);
}
