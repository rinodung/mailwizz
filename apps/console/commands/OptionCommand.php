<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * OptionCommand
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.4
 */

class OptionCommand extends ConsoleCommand
{
    /**
     * @param string $name
     * @param mixed $default
     *
     * @return int
     */
    public function actionGet_option($name, $default = null)
    {
        echo (string)options()->get($name, $default);
        return 0;
    }

    /**
     * @param string $name
     * @param mixed $value
     *
     * @return int
     */
    public function actionSet_option($name, $value)
    {
        options()->set($name, $value);
        return 0;
    }
}
