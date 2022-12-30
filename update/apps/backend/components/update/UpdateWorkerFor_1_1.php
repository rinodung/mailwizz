<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * UpdateWorkerFor_1_1
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.2
 */

class UpdateWorkerFor_1_1 extends UpdateWorkerAbstract
{
    /**
     * @inheritDoc
     */
    public function run()
    {
        // run the sql from file
        $this->runQueriesFromSqlFile('1.1');
    }
}
