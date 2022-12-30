<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * EmailBlacklistForceSubscribersBlacklistStatusCommand
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.0
 */

class EmailBlacklistForceSubscribersBlacklistStatusCommand extends ConsoleCommand
{
    /**
     * @return int
     */
    public function actionIndex()
    {
        $input = $this->confirm('This will change the subscribers status matching the blacklist regardless of their current status. Are you sure?');

        if (!$input) {
            return 0;
        }

        // set the lock name
        $lockName = sha1(__METHOD__);

        if (!mutex()->acquire($lockName, 5)) {
            $this->stdout('Could not acquire mutex lock!');
            return 0;
        }

        try {
            hooks()->doAction('console_command_email_blacklist_force_subscribers_blacklist_status_before_process', $this);

            $this->process();

            hooks()->doAction('console_command_email_blacklist_force_subscribers_blacklist_status_process', $this);
        } catch (Exception $e) {
            $this->stdout(__LINE__ . ': ' . $e->getMessage());
            Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);

            return 1;
        }

        mutex()->release($lockName);

        return 0;
    }

    /**
     * @return void
     */
    public function process(): void
    {
        $limit  = 1000;
        $offset = 0;

        $blacklistAddresses = $this->getBlacklistAddresses($limit, $offset);
        if (empty($blacklistAddresses)) {
            $this->stdout('Done, nothing else to process!');
            return;
        }

        $this->stdout(sprintf('Started a batch from %d to %d and found %d results to process...', $offset, $limit, count($blacklistAddresses)));

        while (!empty($blacklistAddresses)) {
            $blacklistAddressesParts = array_chunk($blacklistAddresses, 100);

            foreach ($blacklistAddressesParts as $blacklistAddressesPart) {
                $criteria = new CDbCriteria();
                $criteria->addInCondition('email', $blacklistAddressesPart);
                $criteria->compare('status', ListSubscriber::STATUS_CONFIRMED);

                ListSubscriber::model()->updateAll([
                    'status'       => ListSubscriber::STATUS_BLACKLISTED,
                    'last_updated' => MW_DATETIME_NOW,
                ], $criteria);
            }

            $offset = $offset + $limit;
            $blacklistAddresses = $this->getBlacklistAddresses($limit, $offset);

            if (!empty($blacklistAddresses)) {
                $this->stdout(sprintf('Started a batch from %d to %d and found %d results to process...', $offset, $offset + $limit, count($blacklistAddresses)));
            } else {
                $this->stdout('Done, nothing else to process!');
            }
        }
    }

    /**
     * @param int $limit
     * @param int $offset
     * @return array
     */
    protected function getBlacklistAddresses(int $limit, int $offset): array
    {
        $criteria = new CDbCriteria();
        $criteria->select = 'email';
        $criteria->limit  = $limit;
        $criteria->offset = $offset;

        /** @var EmailBlacklist[] $models */
        $models = EmailBlacklist::model()->findAll($criteria);
        $addresses = [];

        /** @var EmailBlacklist $model */
        foreach ($models as $model) {
            $addresses[] = $model->email;
        }

        return $addresses;
    }
}
