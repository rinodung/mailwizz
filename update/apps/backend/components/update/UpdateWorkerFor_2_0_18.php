<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * UpdateWorkerFor_2_0_18
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.0.18
 */

class UpdateWorkerFor_2_0_18 extends UpdateWorkerAbstract
{
    public function run()
    {
        // run the sql from file
        $this->runQueriesFromSqlFile('2.0.18');

        try {
            CommonEmailTemplate::reinstallCoreTemplateByDefinitionId('campaign-pending-approval-approved');
            CommonEmailTemplate::reinstallCoreTemplateByDefinitionId('campaign-pending-approval-disapproved');
        } catch (Exception $e) {
            Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
        }
    }
}
