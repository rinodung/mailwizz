<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * TranslateCommand
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.6.6
 *
 */

class TranslateCommand extends ConsoleCommand
{
    /**
     * TODO: Remove this command in 3.x
     *
     * @return int
     */
    public function actionIndex()
    {
        $this->stdout('This command has been disabled, MailWizz 2.0 uses database translations!');
        return 0;
    }
}
