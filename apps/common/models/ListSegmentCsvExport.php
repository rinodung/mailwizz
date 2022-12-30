<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * ListSegmentCsvExport
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.4.8
 */

class ListSegmentCsvExport extends FormModel
{
    /**
     * @var int
     */
    public $list_id;

    /**
     * @var int
     */
    public $segment_id;

    /**
     * @var int
     */
    public $count = 0;

    /**
     * @var int
     */
    public $is_first_batch = 1;

    /**
     * @var int
     */
    public $current_page = 1;

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            ['count, current_page, is_first_batch', 'numerical', 'integerOnly' => true],
            ['list_id, segment_id', 'unsafe'],
        ];

        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return int
     * @throws CDbException
     */
    public function countSubscribers(): int
    {
        $segment = ListSegment::model()->findByAttributes([
            'segment_id' => (int)$this->segment_id,
            'list_id'    => (int)$this->list_id,
        ]);

        if (empty($segment)) {
            return 0;
        }

        return (int)$segment->countSubscribers();
    }

    /**
     * @param int $limit
     * @param int $offset
     *
     * @return array
     * @throws CDbException
     */
    public function findSubscribers($limit = 10, $offset = 0): array
    {
        $segment = ListSegment::model()->findByAttributes([
            'segment_id' => (int)$this->segment_id,
            'list_id'    => (int)$this->list_id,
        ]);

        if (empty($segment)) {
            return [];
        }

        $subscribers = $segment->findSubscribers($offset, $limit);

        if (empty($subscribers)) {
            return [];
        }

        $criteria = new CDbCriteria();
        $criteria->select = 'field_id, tag';
        $criteria->compare('list_id', $this->list_id);
        $criteria->order = 'sort_order ASC, tag ASC';
        $fields = ListField::model()->findAll($criteria);

        if (empty($fields)) {
            return [];
        }

        $data = [];
        foreach ($subscribers as $subscriber) {
            $_data = [];
            foreach ($fields as $field) {
                $value = '';
                $criteria = new CDbCriteria();
                $criteria->select = 'value';
                $criteria->compare('field_id', (int)$field->field_id);
                $criteria->compare('subscriber_id', (int)$subscriber->subscriber_id);
                $valueModels = ListFieldValue::model()->findAll($criteria);

                if (!empty($valueModels)) {
                    $value = [];
                    foreach ($valueModels as $valueModel) {
                        $value[] = $valueModel->value;
                    }
                    $value = implode(', ', $value);
                }
                $_data[$field->tag] = html_encode($value);
            }
            $data[] = $_data;
        }

        unset($subscribers, $fields, $_data, $subscriber, $field);

        return $data;
    }
}
