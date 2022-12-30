<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * DeleteMovedSubscribersCommand
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.9.16
 */

/**
 * Class DeleteMovedSubscribersCommand
 */
class DeleteMovedSubscribersCommand extends ConsoleCommand
{
    /**
     * @param string $list_uid
     * @param int $limit
     * @param int $days_back
     *
     * @return int
     */
    public function actionIndex($list_uid = '', $limit = 500, $days_back = 30)
    {
        $mutexKey = sha1(__METHOD__ . ':list_uid:' . $list_uid);
        if (!mutex()->acquire($mutexKey)) {
            return 1;
        }

        $listId = null;
        if (!empty($list_uid)) {
            $list = Lists::model()->findByUid($list_uid);
            if (empty($list)) {
                $this->stdout(sprintf('Cannot find the list having the UID: %s', $list_uid));
                mutex()->release($mutexKey);
                return 1;
            }
            $listId = (int)$list->list_id;
        }

        if (empty($listId)) {
            $this->stdout('Starting the removal process for all lists...');
        } else {
            $this->stdout(sprintf('Starting the removal process for the list with UID: %s(ID: %d)...', $list_uid, $listId));
        }

        $listsIds = [];

        while (true) {
            try {
                $criteria = new CDbCriteria();
                $criteria->select = 'subscriber_id, list_id';
                $criteria->compare('status', ListSubscriber::STATUS_MOVED);
                if (!empty($listId)) {
                    $criteria->compare('list_id', (int)$listId);
                }
                $criteria->addCondition(sprintf('last_updated < DATE_SUB(NOW(), INTERVAL %d DAY)', $days_back));
                $criteria->limit = (int)$limit;

                $this->stdout(sprintf(
                    'Selecting at most %d subscribers having the status set to "moved" for more than %d days',
                    $limit,
                    $days_back
                ));

                $subscribers   = ListSubscriber::model()->findAll($criteria);
                $subscriberIds = [];
                foreach ($subscribers as $subscriber) {
                    $subscriberIds[] = $subscriber->subscriber_id;
                    $listsIds[] = $subscriber->list_id;
                }
                $listsIds = array_unique($listsIds);

                $this->stdout(sprintf('The select found %d subscribers', count($subscriberIds)));
                if (empty($subscriberIds)) {
                    break;
                }

                $criteria = new CDbCriteria();
                $criteria->addInCondition('subscriber_id', $subscriberIds);

                $this->stdout(sprintf('Deleting %d records...', count($subscriberIds)));
                $deletedCount = ListSubscriber::model()->deleteAll($criteria);
                $this->stdout(sprintf('Deleted %d records.', $deletedCount));
            } catch (Exception $e) {
                Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
                $this->stdout(sprintf('Got exception with message: %s', $e->getMessage()));
                break;
            }
        }

        $this->stdout('Rebuilding the lists cache, this might take a while...');
        try {
            Lists::flushSubscribersCountCacheByListsIds($listsIds, true);
        } catch (Exception $e) {
            Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
            $this->stdout(sprintf('Got exception with message: %s', $e->getMessage()));
        }

        $this->stdout('Done!');

        mutex()->release($mutexKey);
        return 0;
    }
}
