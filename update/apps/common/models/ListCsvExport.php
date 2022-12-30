<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * ListCsvExport
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

class ListCsvExport extends FormModel
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
     * @var Lists
     */
    private $_list;

    /**
     * @var ListSegment
     */
    private $_segment;

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
        if (!empty($this->segment_id)) {
            $count = $this->countSubscribersByListSegment();
        } else {
            $count = $this->countSubscribersByList();
        }

        return (int)$count;
    }

    /**
     * @param int $limit
     * @param int $offset
     *
     * @return array
     * @throws CDbException
     */
    public function findSubscribers(int $limit = 10, int $offset = 0): array
    {
        if (!empty($this->segment_id)) {
            $subscribers = $this->findSubscribersByListSegment($offset, $limit);
        } else {
            $subscribers = $this->findSubscribersByList($offset, $limit);
        }

        if (empty($subscribers)) {
            return [];
        }

        $data = [];
        foreach ($subscribers as $subscriber) {
            $data[] = $subscriber->getFullData();
        }

        return $data;
    }

    /**
     * @return Lists|null
     */
    public function getList(): ?Lists
    {
        if ($this->_list !== null) {
            return $this->_list;
        }
        return $this->_list = Lists::model()->findByPk((int)$this->list_id);
    }

    /**
     * @return ListSegment|null
     */
    public function getSegment(): ?ListSegment
    {
        if ($this->_segment !== null) {
            return $this->_segment;
        }
        return $this->_segment = ListSegment::model()->findByPk((int)$this->segment_id);
    }

    /**
     * @return int
     * @throws CDbException
     */
    protected function countSubscribersByListSegment(): int
    {
        $criteria = new CDbCriteria();
        $criteria->compare('t.list_id', (int)$this->list_id);

        /** @var ListSegment $segment */
        $segment = $this->getSegment();

        return (int)$segment->countSubscribers($criteria);
    }

    /**
     * @param int $offset
     * @param int $limit
     *
     * @return array
     * @throws CDbException
     */
    protected function findSubscribersByListSegment(int $offset = 0, int $limit = 100): array
    {
        $criteria = new CDbCriteria();
        $criteria->select = 't.list_id, t.subscriber_id, t.subscriber_uid, t.email, t.status, t.ip_address, t.source, t.date_added';
        $criteria->compare('t.list_id', (int)$this->list_id);

        /** @var ListSegment $segment */
        $segment = $this->getSegment();

        return $segment->findSubscribers($offset, $limit, $criteria);
    }

    /**
     * @return int
     */
    protected function countSubscribersByList(): int
    {
        $criteria = new CDbCriteria();
        $criteria->compare('t.list_id', (int)$this->list_id);

        return (int)ListSubscriber::model()->count($criteria);
    }

    /**
     * @param int $offset
     * @param int $limit
     *
     * @return array
     */
    protected function findSubscribersByList(int $offset = 0, int $limit = 100): array
    {
        $criteria = new CDbCriteria();
        $criteria->select = 't.list_id, t.subscriber_id, t.subscriber_uid, t.email, t.status, t.ip_address, t.source, t.date_added';
        $criteria->compare('t.list_id', (int)$this->list_id);
        $criteria->offset = $offset;
        $criteria->limit  = $limit;

        return ListSubscriber::model()->findAll($criteria);
    }
}
