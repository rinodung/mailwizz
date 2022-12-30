<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * SurveyCsvExport
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.0
 */

class SurveyCsvExport extends FormModel
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
     * @var Survey
     */
    private $_survey;

    /**
     * @var SurveySegment
     */
    private $_segment;

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
        if (!empty($this->segment_id)) {
            $count = $this->countRespondersBySurveySegment();
        } else {
            $count = $this->countRespondersBySurvey();
        }

        return (int)$count;
    }

    /**
     * @param int $limit
     * @param int $offset
     *
     * @return array
     * @throws CDbException
     * @throws CException
     */
    public function findResponders(int $limit = 10, int $offset = 0): array
    {
        if (!empty($this->segment_id)) {
            $responders = $this->findRespondersBySurveySegment($offset, $limit);
        } else {
            $responders = $this->findRespondersBySurvey($offset, $limit);
        }

        if (empty($responders)) {
            return [];
        }

        $data = [];
        foreach ($responders as $responder) {
            $data[] = collect($responder->getFullData())->mapWithKeys(function ($value, $key): array {
                $label = ucwords(strtolower((string)str_replace('_', ' ', (string)$key)));

                /** @var SurveyField|null $field */
                $field = null;
                if (preg_match('/_(\d+)$/', $key, $matches)) {
                    /** @var SurveyField|null $field */
                    $field = SurveyField::model()->findByPk((int)$matches[1]);
                }

                if (!empty($field)) {
                    $label = $field->label;
                } else {
                    $field = new SurveyField();
                    if ($field->hasAttribute(strtolower($key))) {
                        $label = $field->getAttributeLabel(strtolower($key));
                    }
                }

                return [$label => $value];
            })->all();
        }

        return $data;
    }

    /**
     * @return Survey|null
     */
    public function getSurvey(): ?Survey
    {
        if ($this->_survey !== null) {
            return $this->_survey;
        }
        return $this->_survey = Survey::model()->findByPk((int)$this->survey_id);
    }

    /**
     * @return SurveySegment|null
     */
    public function getSegment(): ?SurveySegment
    {
        if ($this->_segment !== null) {
            return $this->_segment;
        }
        return $this->_segment = SurveySegment::model()->findByPk((int)$this->segment_id);
    }

    /**
     * @return int
     * @throws CDbException
     */
    protected function countRespondersBySurveySegment(): int
    {
        $criteria = new CDbCriteria();
        $criteria->compare('t.survey_id', (int)$this->survey_id);

        /** @var SurveySegment $segment */
        $segment = $this->getSegment();

        return (int)$segment->countResponders($criteria);
    }

    /**
     * @param int $offset
     * @param int $limit
     *
     * @return array
     * @throws CDbException
     */
    protected function findRespondersBySurveySegment(int $offset = 0, int $limit = 100): array
    {
        $criteria = new CDbCriteria();
        $criteria->select = 't.survey_id, t.responder_id, t.responder_uid, t.subscriber_id, t.status, t.ip_address, t.date_added';
        $criteria->compare('t.survey_id', (int)$this->survey_id);

        /** @var SurveySegment $segment */
        $segment = $this->getSegment();

        return $segment->findResponders($offset, $limit, $criteria);
    }

    /**
     * @return int
     */
    protected function countRespondersBySurvey(): int
    {
        $criteria = new CDbCriteria();
        $criteria->compare('t.survey_id', (int)$this->survey_id);

        return (int)SurveyResponder::model()->count($criteria);
    }

    /**
     * @param int $offset
     * @param int $limit
     *
     * @return array
     */
    protected function findRespondersBySurvey(int $offset = 0, int $limit = 100): array
    {
        $criteria = new CDbCriteria();
        $criteria->select = 't.survey_id, t.responder_id, t.responder_uid, t.subscriber_id, t.status, t.ip_address, t.date_added';
        $criteria->compare('t.survey_id', (int)$this->survey_id);
        $criteria->offset = $offset;
        $criteria->limit  = $limit;

        return SurveyResponder::model()->findAll($criteria);
    }
}
