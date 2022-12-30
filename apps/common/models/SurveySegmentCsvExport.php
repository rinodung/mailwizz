<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * SurveySegmentCsvExport
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.7.8
 */

class SurveySegmentCsvExport extends FormModel
{
    /**
     * @var int
     */
    public $survey_id;

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
            ['survey_id, segment_id', 'unsafe'],
        ];

        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return int
     * @throws CDbException
     */
    public function countResponders(): int
    {
        $segment = SurveySegment::model()->findByAttributes([
            'segment_id' => (int)$this->segment_id,
            'survey_id'  => (int)$this->survey_id,
        ]);

        if (empty($segment)) {
            return 0;
        }

        return (int)$segment->countResponders();
    }

    /**
     * @param int $limit
     * @param int $offset
     *
     * @return array
     * @throws CDbException
     */
    public function findResponders(int $limit = 10, int $offset = 0): array
    {
        $segment = SurveySegment::model()->findByAttributes([
            'segment_id' => (int)$this->segment_id,
            'survey_id'  => (int)$this->survey_id,
        ]);

        if (empty($segment)) {
            return [];
        }

        $responders = $segment->findResponders($offset, $limit);

        if (empty($responders)) {
            return [];
        }

        $criteria = new CDbCriteria();
        $criteria->compare('survey_id', $this->survey_id);
        $criteria->order = 'sort_order ASC';
        $fields = SurveyField::model()->findAll($criteria);

        if (empty($fields)) {
            return [];
        }

        $data = [];
        foreach ($responders as $responder) {
            $_data = [
                $responder->getAttributeLabel('ip_address') => $responder->ip_address,
            ];
            foreach ($fields as $field) {
                $value = '';
                $criteria = new CDbCriteria();
                $criteria->select = 'value';
                $criteria->compare('field_id', (int)$field->field_id);
                $criteria->compare('responder_id', (int)$responder->responder_id);
                $valueModels = SurveyFieldValue::model()->findAll($criteria);

                if (!empty($valueModels)) {
                    $value = [];
                    foreach ($valueModels as $valueModel) {
                        $value[] = $valueModel->value;
                    }
                    $value = implode(', ', $value);
                }
                $_data[$field->label] = html_encode($value);
            }
            $data[] = $_data;
        }

        unset($responders, $fields, $_data, $responder, $field);

        return $data;
    }
}
