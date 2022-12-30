<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * UpdateWorkerFor_1_2
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.2
 */

class UpdateWorkerFor_1_2 extends UpdateWorkerAbstract
{
    /**
     * @return void
     * @throws CDbException
     * @throws CException
     */
    public function run()
    {
        // run the sql from file
        $this->runQueriesFromSqlFile('1.2');

        // alter users and add a unique uid
        $command = $this->getDb()->createCommand('SELECT user_id, user_uid FROM {{user}} WHERE user_uid = ""');
        $results = $command->queryAll();

        foreach ($results as $result) {
            $command = $this->getDb()->createCommand('UPDATE {{user}} SET user_uid = :uid WHERE user_id = :id');
            $command->execute([
                ':uid'  => $this->generateUserUid(),
                ':id'   => (int)$result['user_id'],
            ]);
        }

        // alter customers and add a unique uid
        $command = $this->getDb()->createCommand('SELECT customer_id, customer_uid FROM {{customer}} WHERE customer_uid = ""');
        $results = $command->queryAll();

        foreach ($results as $result) {
            $command = $this->getDb()->createCommand('UPDATE {{customer}} SET customer_uid = :uid WHERE customer_id = :id');
            $command->execute([
                ':uid'  => $this->generateCustomerUid(),
                ':id'   => (int)$result['customer_id'],
            ]);
        }

        // add unique keys here to avoid duplicate errors.
        $command = $this->getDb()->createCommand('ALTER TABLE `{{customer}}` ADD UNIQUE KEY `customer_uid_UNIQUE` (`customer_uid`)');
        $command->execute();

        $command = $this->getDb()->createCommand('ALTER TABLE `{{user}}` ADD UNIQUE KEY `user_uid_UNIQUE` (`user_uid`)');
        $command->execute();

        // add a note about the new cron job
        $phpCli = CommonHelper::findPhpCliPath();
        notify()->addInfo(t('update', 'Version {version} brings a new cron job that you have to add to run once a day. After addition, it must look like: {cron}', [
            '{version}' => '1.2',
            '{cron}'    => sprintf('<br /><strong>0 0 * * * %s -q ' . MW_ROOT_PATH . '/apps/console/console.php process-subscribers > /dev/null 2>&1</strong>', $phpCli),
        ]));
    }

    /**
     * @return string
     * @throws CException
     */
    protected function generateUserUid()
    {
        $unique  = StringHelper::uniqid();
        $command = $this->getDb()->createCommand('SELECT user_uid FROM {{user}} WHERE user_uid = :uid');
        $row     = $command->queryRow(true, [
            ':uid' => $unique,
        ]);

        if (!empty($row['user_uid'])) {
            return $this->generateUserUid();
        }

        return $unique;
    }

    /**
     * @return string
     * @throws CException
     */
    protected function generateCustomerUid()
    {
        $unique  = StringHelper::uniqid();
        $command = $this->getDb()->createCommand('SELECT customer_uid FROM {{customer}} WHERE customer_uid = :uid');
        $row     = $command->queryRow(true, [
            ':uid' => $unique,
        ]);

        if (!empty($row['customer_uid'])) {
            return $this->generateCustomerUid();
        }

        return $unique;
    }
}
