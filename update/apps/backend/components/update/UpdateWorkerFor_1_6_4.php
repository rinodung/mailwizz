<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * UpdateWorkerFor_1_6_4
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.6.4
 */

class UpdateWorkerFor_1_6_4 extends UpdateWorkerAbstract
{
    public function run()
    {
        // run the sql from file
        $this->runQueriesFromSqlFile('1.6.4');

        /** @var OptionExporter $exportOptions */
        $exportOptions = container()->get(OptionExporter::class);
        if ($exportOptions->getProcessAtOnce() < 500) {
            $exportOptions->saveAttributes(['process_at_once' => 500]);
        }
    }
}
