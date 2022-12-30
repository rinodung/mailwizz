<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * ListSegmentCollection
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.0.0
 */

class ListSegmentCollection extends BaseCollection
{
    /**
     * @param mixed $condition
     *
     * @return ListSegmentCollection
     */
    public static function findAll($condition = ''): self
    {
        return new self(ListSegment::model()->findAll($condition));
    }

    /**
     * @param array $attributes
     *
     * @return ListSegmentCollection
     */
    public static function findAllByAttributes(array $attributes): self
    {
        return new self(ListSegment::model()->findAllByAttributes($attributes));
    }

    /**
     * @param int $listId
     *
     * @return ListSegmentCollection
     */
    public static function findAllByListId(int $listId): self
    {
        return new self(ListSegment::model()->findAllByListId($listId));
    }
}
