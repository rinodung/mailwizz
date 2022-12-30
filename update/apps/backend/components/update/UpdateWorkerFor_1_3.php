<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * UpdateWorkerFor_1_3
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3
 */

class UpdateWorkerFor_1_3 extends UpdateWorkerAbstract
{
    /**
     * @inheritDoc
     */
    public function run()
    {
        // run the sql from file
        $this->runQueriesFromSqlFile('1.3');

        // select available campaigns to create the campaign option connection
        $command = $this->getDb()->createCommand('SELECT campaign_id FROM {{campaign}} WHERE 1');
        $results = $command->queryAll();

        foreach ($results as $result) {
            $command = $this->getDb()->createCommand('INSERT INTO {{campaign_option}} SET campaign_id = :cid, url_tracking = "yes"');
            $command->execute([
                ':cid'  => (int)$result['campaign_id'],
            ]);
        }
    }
}
