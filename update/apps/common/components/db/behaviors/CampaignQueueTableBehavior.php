<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * CampaignQueueTableBehavior
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.7.9
 *
 */

/**
 * @property Campaign $owner
 */
class CampaignQueueTableBehavior extends CActiveRecordBehavior
{
    /**
     * @var array
     */
    protected static $_tablesIndex = [];

    /**
     * @param CEvent $event
     *
     * @return void
     * @throws CDbException
     * @throws CException
     */
    public function afterDelete($event)
    {
        parent::afterDelete($event);

        // make sure we remove the table in case it remains there
        if ($this->owner->getIsPendingDelete()) {
            $this->dropTable();
        }
    }

    /**
     * @return string
     */
    public function getTableName(): string
    {
        return '{{campaign_queue_' . (int)$this->owner->campaign_id . '}}';
    }

    /**
     * @return bool
     * @throws CException
     */
    public function tableExists(): bool
    {
        // check from cache
        $tableName = $this->getTableName();
        if (!empty(self::$_tablesIndex[$tableName])) {
            return true;
        }

        $rows = db()->createCommand('SHOW TABLES LIKE "' . $tableName . '"')->queryAll();

        // make sure we add into cache
        return self::$_tablesIndex[$tableName] = ((is_countable($rows) ? count($rows) : 0) > 0);
    }

    /**
     * @return bool
     * @throws CDbException
     * @throws CException
     */
    public function createTable(): bool
    {
        if ($this->tableExists()) {
            return false;
        }

        $db         = db();
        $owner      = $this->owner;
        $schema     = $db->getSchema();
        $tableName  = $this->getTableName();
        $campaignId = (int)$owner->campaign_id;

        if ($owner->getIsAutoresponder()) {
            $db->createCommand($schema->createTable($tableName, [
                'subscriber_id' => 'INT(11) NOT NULL UNIQUE',
                'send_at'       => 'DATETIME NOT NULL',
            ]))->execute();

            $key = $schema->createIndex('subscriber_id_send_at_' . $campaignId, $tableName, ['subscriber_id', 'send_at']);
            $db->createCommand($key)->execute();
        } else {
            $db->createCommand($schema->createTable($tableName, [
                'subscriber_id' => 'INT(11) NOT NULL UNIQUE',
                'failures'      => 'INT(11) NOT NULL DEFAULT 0',
            ]))->execute();
        }

        $fk = $schema->addForeignKey('subscriber_id_fk_' . $campaignId, $tableName, 'subscriber_id', '{{list_subscriber}}', 'subscriber_id', 'CASCADE', 'NO ACTION');
        $db->createCommand($fk)->execute();

        // mark as created
        self::$_tablesIndex[$tableName] = true;

        return true;
    }

    /**
     * @return bool
     * @throws CDbException
     * @throws CException
     */
    public function dropTable(): bool
    {
        if (!$this->tableExists()) {
            return false;
        }

        $db         = db();
        $owner      = $this->owner;
        $schema     = $db->getSchema();
        $tableName  = $this->getTableName();
        $campaignId = (int)$owner->campaign_id;

        $db->createCommand()->delete($tableName);

        if ($owner->getIsAutoresponder()) {
            $db->createCommand($schema->dropIndex('subscriber_id_send_at_' . $campaignId, $tableName))->execute();
        }

        $db->createCommand($schema->dropForeignKey('subscriber_id_fk_' . $campaignId, $tableName))->execute();
        $db->createCommand($schema->dropTable($tableName))->execute();

        // remove from cache
        if (array_key_exists($tableName, self::$_tablesIndex)) {
            unset(self::$_tablesIndex[$tableName]);
        }

        return true;
    }

    /**
     * This will return boolean if the table is already populated.
     * It will throw an exception if it fails to populate the table.
     *
     * @return bool
     * @throws CDbException
     * @throws CException
     */
    public function populateTable(): bool
    {
        if ($this->tableExists()) {
            return false;
        }

        // make sure the table is created
        $this->createTable();

        $offset    = 0;
        $limit     = (int)app_param('send.campaigns.command.tempQueueTables.copyAtOnce', 500);
        $count     = 0;
        $max       = 0;
        $subsCache = [];

        $db        = db();
        $owner     = $this->owner;
        $schema    = $db->getSchema();
        $tableName = $this->getTableName();
        $now       = date('Y-m-d H:i:s');

        $criteria = new CDbCriteria();
        $criteria->select = 't.subscriber_id';

        if (!empty($owner->option) && $owner->option->getCanSetMaxSendCount()) {
            $max = $owner->option->max_send_count;
            if ($owner->option->getCanSetMaxSendCountRandom()) {
                $criteria->order = 'RAND()';
            }
        }

        try {
            $subscribers = $owner->findSubscribers($offset, $limit, $criteria);

            // 1.7.4
            if ($owner->getIsAutoresponder()) {
                $minTimeHour   = !empty($owner->option->autoresponder_time_min_hour) ? $owner->option->autoresponder_time_min_hour : null;
                $minTimeMinute = !empty($owner->option->autoresponder_time_min_minute) ? $owner->option->autoresponder_time_min_minute : null;

                if (!empty($minTimeHour) && !empty($minTimeMinute)) {
                    $now = date(sprintf('Y-m-d %s:%s:00', $minTimeHour, $minTimeMinute));
                }
            }
            //

            while (!empty($subscribers)) {
                $insert = [];

                foreach ($subscribers as $subscriber) {
                    if (!isset($subsCache[$subscriber->subscriber_id])) {
                        $insertData = [
                            'subscriber_id' => $subscriber->subscriber_id,
                        ];

                        if ($owner->getIsAutoresponder()) {
                            $insertData['send_at'] = $now;
                        }

                        $insert[] = $insertData;
                        $subsCache[$subscriber->subscriber_id] = true;
                        $count++;
                    }

                    if ($max > 0 && $count >= $max) {
                        break;
                    }
                }

                if (!empty($insert)) {
                    $schema->getCommandBuilder()->createMultipleInsertCommand($tableName, $insert)->execute();
                }

                if ($max > 0 && $count >= $max) {
                    break;
                }

                $offset      = $offset + $limit;
                $subscribers = $owner->findSubscribers($offset, $limit, $criteria);
            }

            // 1.9.13 - Not ready for production yet
            // We need to see if the number of subscribers copied is equal, or close to equal to the ones in the list
            // Why almost equal? Because during table population, the original list might get new subscribers
            // Therefore, we think if the populated list is > $minimumPercent% from the original one, we're safe.
            /*
            $queueTableSubscribersCount    = $this->countSubscribers();
            $originalTableSubscribersCount = $max > 0 ? $max : $owner->countSubscribers();
            $currentPercent = ($queueTableSubscribersCount / $originalTableSubscribersCount) * 100;
            $minimumPercent = 98;
            if ($currentPercent < $minimumPercent) {
                throw new Exception(
                    sprintf(
                        'The queue table contains %d subscribers while the original table contains %d.
                        This means the queue table is populated only %.3f%%, which is under the minimum required of %.3f%%',
                        $queueTableSubscribersCount,
                        $originalTableSubscribersCount,
                        $currentPercent,
                        $minimumPercent
                    )
                );
            }
            */
            //

            unset($subscribers, $subsCache);
        } catch (Exception $e) {
            Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);

            $this->dropTable();

            throw $e;
        }

        return true;
    }

    /**
     * @return void
     * @throws CDbException
     * @throws CException
     */
    public function handleSendingGiveups(): void
    {
        // make sure the table is created
        $this->createTable();

        $db        = db();
        $owner     = $this->owner;
        $schema    = $db->getSchema();
        $tableName = $this->getTableName();
        $now       = date('Y-m-d H:i:s');

        while (true) {
            $query = $db->createCommand()->select('subscriber_id')->from('{{campaign_delivery_log}}');
            $query->where('campaign_id = :cid AND `status` = :status', [
                ':cid'    => (int)$owner->campaign_id,
                ':status' => CampaignDeliveryLog::STATUS_GIVEUP,
            ]);
            $query->offset(0)->limit(500);
            $rows = $query->queryAll();

            if (empty($rows)) {
                break;
            }

            $insert        = [];
            $subscriberIds = [];

            foreach ($rows as $row) {
                $subscriberIds[] = (int)$row['subscriber_id'];

                if ($owner->getIsAutoresponder()) {
                    $insert[] = [
                        'subscriber_id' => $row['subscriber_id'],
                        'send_at'       => $now,
                    ];
                } else {
                    $insert[] = [
                        'subscriber_id' => $row['subscriber_id'],
                    ];
                }
            }

            if (!empty($insert)) {
                $schema->getCommandBuilder()->createMultipleInsertCommand($tableName, $insert)->execute();
            }

            if (!empty($subscriberIds)) {
                $sql = 'DELETE FROM {{campaign_delivery_log}} WHERE campaign_id = :cid AND subscriber_id IN (' . implode(',', $subscriberIds) . ')';
                $db->createCommand($sql)->execute([':cid' => $owner->campaign_id]);
            }
        }
    }

    /**
     * @param array $data
     *
     * @return bool
     * @throws CDbException
     * @throws CException
     */
    public function addSubscriber(array $data = []): bool
    {
        // make sure the table is created
        $this->createTable();

        return (bool)db()->createCommand()->insert($this->getTableName(), $data);
    }

    /**
     * @param int $subscriberId
     *
     * @return bool
     * @throws CDbException
     * @throws CException
     */
    public function deleteSubscriber(int $subscriberId): bool
    {
        // make sure the table is created
        $this->createTable();

        return (bool)db()->createCommand()->delete($this->getTableName(), 'subscriber_id = :sid', [
            ':sid' => (int)$subscriberId,
        ]);
    }

    /**
     * @return int
     * @throws CDbException
     * @throws CException
     */
    public function countSubscribers(): int
    {
        // make sure the table is created
        $this->createTable();

        $db        = db();
        $owner     = $this->owner;
        $tableName = $this->getTableName();

        $query = $db->createCommand()->select('count(*) as cnt')->from($tableName);

        if ($owner->getIsAutoresponder()) {
            $query->where('send_at <= NOW()');
        }

        $row = $query->queryRow();

        return (int)$row['cnt'];
    }

    /**
     * @param int $offset
     * @param int $limit
     *
     * @return array
     * @throws CDbException
     * @throws CException
     */
    public function findSubscribers(int $offset, int $limit): array
    {
        // make sure the table is created
        $this->createTable();

        $db        = db();
        $owner     = $this->owner;
        $tableName = $this->getTableName();

        $query = $db->createCommand()->select('subscriber_id')->from($tableName);

        if ($owner->getIsAutoresponder()) {
            $query->where('send_at <= NOW()');
        }

        $query->order('subscriber_id ASC')->offset($offset)->limit($limit);

        $rows        = $query->queryAll();
        $chunks      = array_chunk($rows, 300);
        $subscribers = [];

        foreach ($chunks as $chunk) {
            $ids = [];
            foreach ($chunk as $row) {
                $ids[] = $row['subscriber_id'];
            }

            $criteria = new CDbCriteria();
            $criteria->addInCondition('t.subscriber_id', $ids);

            // since 1.5.2
            if ($timewarpCriteria = $this->_getTimewarpCriteria()) {
                $criteria->mergeWith($timewarpCriteria);
            }

            /** @var ListSubscriber[] $models */
            $models = ListSubscriber::model()->findAll($criteria);

            foreach ($models as $model) {
                $subscribers[] = $model;
            }
        }

        try {
            $subscribers = $this->_applyOpenUnopenPreviousCampaignsCriteria($subscribers);
        } catch (Exception $e) {
        }

        return $subscribers;
    }

    /**
     * @param ListSubscriber[] $subscribers
     * @return array
     * @throws CDbException
     * @throws CException
     */
    protected function _applyOpenUnopenPreviousCampaignsCriteria(array $subscribers = []): array
    {
        $campaign = $this->owner;
        $openUnopenFilters = CampaignFilterOpenUnopen::model()->findAllByAttributes([
            'campaign_id' => $campaign->campaign_id,
        ]);

        if (empty($openUnopenFilters)) {
            return $subscribers;
        }

        foreach ($subscribers as $index => $subscriber) {
            $matched = false;
            foreach ($openUnopenFilters as $filter) {
                $hasOpened = $subscriber->hasOpenedCampaignById((int)$filter->previous_campaign_id);
                if ($filter->action === CampaignFilterOpenUnopen::ACTION_OPEN && $hasOpened) {
                    $matched = true;
                    break;
                }
                if ($filter->action === CampaignFilterOpenUnopen::ACTION_UNOPEN && !$hasOpened) {
                    $matched = true;
                    break;
                }
            }
            if ($matched) {
                continue;
            }

            $this->deleteSubscriber((int)$subscriber->subscriber_id);
            unset($subscribers[$index]);
        }

        $subscribers = array_values($subscribers);
        return $subscribers;
    }

    /**
     * @return bool
     */
    protected function _isTimewarpEnabled(): bool
    {
        return $this->owner->getIsRegular() && !empty($this->owner->option) && $this->owner->option->getTimewarpEnabled();
    }

    /**
     * @return CDbCriteria|null
     * @throws Exception
     */
    protected function _getTimewarpCriteria(): ?CDbCriteria
    {
        $timewarpCriteria = null;
        if ($this->_isTimewarpEnabled()) {

            /** @var Campaign $owner */
            $owner = $this->owner;

            $timewarpCriteria = CampaignHelper::getTimewarpCriteria($owner);
        }
        return $timewarpCriteria;
    }
}
