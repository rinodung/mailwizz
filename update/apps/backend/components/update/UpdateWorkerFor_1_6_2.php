<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * UpdateWorkerFor_1_6_2
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.6.2
 */

class UpdateWorkerFor_1_6_2 extends UpdateWorkerAbstract
{
    public function run()
    {
        // run the sql from file
        $this->runQueriesFromSqlFile('1.6.2');

        // since 1.6.2
        if (options()->get('system.installer.freshinstallcommonemailtemplates', 0) == 0) {
            options()->set('system.installer.freshinstallcommonemailtemplates', 1);
            CommonEmailTemplate::reinstallCoreTemplates();
        }
    }
}
