<?php declare(strict_types=1);

/**
 * Console application bootstrap file
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

// for some fcgi installs
if (empty($_SERVER['SCRIPT_FILENAME'])) {
    $_SERVER['SCRIPT_FILENAME'] = __FILE__;
}

// define the type of application we are creating.
define('MW_APP_NAME', 'console');

// and start an instance of it.
require_once dirname(__FILE__) . '/../init.php';
