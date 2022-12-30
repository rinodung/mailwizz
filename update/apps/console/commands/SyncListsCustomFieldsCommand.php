<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * SyncListCustomFieldsCommand
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.8.8
 */

class SyncListsCustomFieldsCommand extends ConsoleCommand
{
    /**
     * @return int
     */
    public function actionIndex()
    {
        try {
            $this->stdout('Loading all lists...');

            // check if pcntl enabled
            $pcntl = CommonHelper::functionExists('pcntl_fork') && CommonHelper::functionExists('pcntl_waitpid');

            // load all lists at once
            $sql   = 'SELECT list_id FROM {{list}} WHERE `status` = "active"';
            $lists = db()->createCommand($sql)->queryAll();

            foreach ($lists as $list) {
                $this->stdout('Processing list id: ' . $list['list_id']);

                /** @var int $count */
                $count = (int)ListSubscriber::model()->countByAttributes([
                    'list_id' => $list['list_id'],
                ]);

                $cacheKey = sha1(
                    'system.cron.process_subscribers.sync_custom_fields_values.list_id.' . $list['list_id'] . '.avg_last_updated' .
                    '.count_' . $count
                );
                $mutexKey = $cacheKey . ':' . date('Ymd');

                $this->stdout('Acquiring the mutex lock...');
                if (!mutex()->acquire($mutexKey, 10)) {
                    $this->stdout('Unable to acquire the mutex lock...');
                    continue;
                }

                $cachedAvg = (string)cache()->get($cacheKey);
                $row       = db()->createCommand('SELECT AVG(last_updated) AS avg_last_updated FROM {{list_field}} WHERE list_id = :lid')->queryRow(true, [
                    ':lid' => $list['list_id'],
                ]);
                $avgLastUpdated  = (string)$row['avg_last_updated'];
                $invalidateCache = $avgLastUpdated !== $cachedAvg;

                // nothing has changed in the fields, we can stop
                if (!$invalidateCache) {
                    $this->stdout('No change detected in the custom fields for this list, we can continue with next list!');

                    // release the mutex
                    mutex()->release($mutexKey);

                    continue;
                }

                // load all custom fields for the given list
                $this->stdout('Loading all custom fields for this list...');
                $sql    = 'SELECT field_id, default_value FROM {{list_field}} WHERE list_id = :lid';
                $fields = db()->createCommand($sql)->queryAll(true, [':lid' => $list['list_id']]);

                $sql    = 'SELECT * FROM {{list_subscriber}} WHERE list_id = :lid ORDER BY subscriber_id ASC LIMIT %d OFFSET %d';
                $limit  = 1000;
                $offset = 0;

                $processesCount = 10;
                while (true) {
                    $children = [];
                    $batchCounter = [];

                    for ($i = 0; $i < $processesCount; $i++) {
                        $this->stdout(sprintf('[%d] Loading subscribers set for the list with limit: %d and offset %d', $i, $limit, $offset));

                        $subscribers = db()
                            ->createCommand(sprintf($sql, $limit, $offset))
                            ->queryAll(true, [':lid' => (int)$list['list_id']]);

                        $batchCounter[$i] = count($subscribers);
                        $offset           = $limit + $offset;

                        if (empty($subscribers)) {
                            continue;
                        }

                        if (!$pcntl) {
                            $this->processBatch($fields, $subscribers, $i);
                        } else {

                            // close the external connections
                            $this->setExternalConnectionsActive(false);

                            $pid = pcntl_fork();
                            if ($pid == -1) {
                                continue;
                            }

                            // Parent
                            if ($pid) {
                                $children[] = $pid;
                            }

                            // Child
                            if (!$pid) {
                                $this->processBatch($fields, $subscribers, $i);
                                app()->end();
                            }
                        }
                    }

                    if ($pcntl) {
                        while (count($children) > 0) {
                            foreach ($children as $key => $pid) {
                                $res = pcntl_waitpid($pid, $status, WNOHANG);
                                if ($res == -1 || $res > 0) {
                                    unset($children[$key]);
                                }
                            }
                            usleep(500000);
                        }
                    }

                    // if any of the workers has nothing to process it means we're done
                    if (count(array_filter(array_values($batchCounter))) != $processesCount) {
                        break;
                    }
                }

                // update the cache
                cache()->set($cacheKey, $avgLastUpdated);

                // release the mutex
                mutex()->release($mutexKey);

                // and ... done
                $this->stdout('Done, no more subscribers for this list!');
            }

            $this->stdout('Done!');
        } catch (Exception $e) {
            $this->stdout(__LINE__ . ': ' . $e->getMessage());
            Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
        }

        return 0;
    }

    /**
     * @param array $fields
     * @param array $subscribers
     * @param int $workerNum
     *
     * @throws CDbException
     * @throws CException
     * @throws \MaxMind\Db\Reader\InvalidDatabaseException
     */
    protected function processBatch(array $fields = [], array $subscribers = [], int $workerNum = 0): void
    {
        if (empty($fields) || empty($subscribers)) {
            return;
        }

        $this->stdout(sprintf('[%d] Starting a new batch counting %d subscribers...', $workerNum, count($subscribers)));

        // keep a reference
        $subscribersList = [];
        $sids            = [];
        foreach ($subscribers as $sub) {
            $sids[]                                 = $sub['subscriber_id'];
            $subscribersList[$sub['subscriber_id']] = $sub;
        }

        // since 1.9.10 - we must delete rows with empty values but with default values
        $sql = 'SELECT v.value_id, v.`value`, f.default_value FROM {{list_field_value}} v INNER JOIN {{list_field}} f ON f.field_id = v.field_id WHERE v.subscriber_id IN(' . implode(',', $sids) . ')';
        $fieldsValues = db()->createCommand($sql)->queryAll();
        foreach ($fieldsValues as $fieldValue) {
            if (strlen(trim((string)$fieldValue['value'])) === 0 && strlen(trim((string)$fieldValue['default_value'])) !== 0) {
                db()->createCommand('DELETE FROM {{list_field_value}} WHERE value_id = :id')->execute([
                    ':id' => (int)$fieldValue['value_id'],
                ]);
            }
        }
        //

        // load all custom fields values for existing subscribers
        $sql = 'SELECT field_id, subscriber_id FROM {{list_field_value}} WHERE subscriber_id IN(' . implode(',', $sids) . ')';
        $fieldsValues = db()->createCommand($sql)->queryAll();

        // populate this to have the defaults set so we can diff them later
        $fieldSubscribers = [];
        foreach ($fields as $field) {
            $fieldSubscribers[$field['field_id']] = [];
        }

        // we have set the defaults abive, we now just have to add to the array
        foreach ($fieldsValues as $fieldValue) {
            $fieldSubscribers[$fieldValue['field_id']][] = $fieldValue['subscriber_id'];
        }
        $fieldsValues = null;

        foreach ($fieldSubscribers as $fieldId => $_subscribers) {

            // exclude $subscribers from $sids
            $subscribers = array_diff($sids, $_subscribers);

            if (!count($subscribers)) {
                continue;
            }

            $this->stdout('[' . $workerNum . '] Field id ' . $fieldId . ' will add ' . count($subscribers) . ' records.');

            $fieldValues = [];
            foreach ($fields as $field) {
                if ($field['field_id'] == $fieldId) {
                    foreach ($subscribers as $subscriber) {
                        $subscriberObject = null;
                        if (isset($subscribersList[$subscriber])) {
                            $subscriberObject = new ListSubscriber();
                            $subscriberObject->setIsNewRecord(false);
                            $subscriberObject->setAttributes($subscribersList[$subscriber], false);
                        }
                        $fieldValues[$subscriber] = ListField::parseDefaultValueTags((string)$field['default_value'], $subscriberObject);
                    }
                    break;
                }
            }

            $inserts = [];
            foreach ($subscribers as $subscriberId) {
                $fieldValue = $fieldValues[$subscriberId] ?? '';
                $inserts[]  = [
                    'field_id'      => $fieldId,
                    'subscriber_id' => $subscriberId,
                    'value'         => $fieldValue,
                    'date_added'    => MW_DATETIME_NOW,
                    'last_updated'  => MW_DATETIME_NOW,
                ];
            }

            $inserts = array_chunk($inserts, 100);
            foreach ($inserts as $insert) {
                $connection = db()->getSchema()->getCommandBuilder();
                $command = $connection->createMultipleInsertCommand('{{list_field_value}}', $insert);
                $command->execute();
            }
            $inserts = null;
        }

        $this->stdout('[' . $workerNum . '] Batch is done...');
    }
}
