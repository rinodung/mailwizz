<?php declare(strict_types=1); 
defined('MW_APP_NAME') or exit('No direct script access allowed');
version_compare(PHP_VERSION, '7.2', '>=') or exit('In order to run this application, you need PHP >= 7.2!');
/**
 * Bootstrap file
 *
 * This file needs to be included by the init script of each application.
 * Please do not alter this file in any way, otherwise bad things can happen.
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

// set the developer ip addresses, separated by a comma
defined('MW_DEVELOPERS_IPS') or define('MW_DEVELOPERS_IPS', '');

// decide if we're in read only mode, note that MW_DEVELOPERS_IPS still have full access
defined('MW_IS_APP_READ_ONLY') or define('MW_IS_APP_READ_ONLY', false);

// whether we should force debug mode
defined('MW_FORCE_DEBUG_MODE') or define('MW_FORCE_DEBUG_MODE', isset($_SERVER['REMOTE_ADDR']) && in_array($_SERVER['REMOTE_ADDR'], array_map('trim', explode(',', MW_DEVELOPERS_IPS))));

// if debug mode is forced then go with it
if (defined('MW_FORCE_DEBUG_MODE') && MW_FORCE_DEBUG_MODE) {
    error_reporting(-1);
    ini_set('display_errors', '1');
    define('MW_CACHE_TTL', 300);
    define('YII_DEBUG', true);
    define('YII_TRACE_LEVEL', 3);
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
    define('MW_CACHE_TTL', 60 * 60 * 24 * 365);
}

// a few base mw constants
define('MW_NAME', 'MailWizz EMA');
define('MW_VERSION', '2.1.13'); // never remove or alter this constant, never!
define('MW_PATH', realpath(dirname(__FILE__).'/..'));
define('MW_ROOT_PATH', MW_PATH);
define('MW_APPS_PATH', MW_PATH.'/apps');
define('MW_VENDOR_PATH', MW_PATH.'/vendor');

// mark if the app in debug mode.
define('MW_DEBUG', defined('YII_DEBUG'));

// easier access to see if cli
define('MW_IS_CLI', php_sapi_name() == 'cli' || (!isset($_SERVER['SERVER_SOFTWARE']) && !empty($_SERVER['argv'])));

// mark if the incoming request is an ajax request.
define('MW_IS_AJAX', !MW_IS_CLI && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower((string)$_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

// fcgi doesn't have STDIN nor STDOUT defined by default.
defined('STDIN') or define('STDIN', fopen('php://stdin', 'r'));
defined('STDOUT') or define('STDOUT', fopen('php://stdout', 'w'));

// since 1.3.5.5 - performance levels
require MW_APPS_PATH . '/common/config/performance-levels.php';
if (is_file($performanceLevelsFile = MW_APPS_PATH . '/common/config/performance-levels-custom.php')) {
    require $performanceLevelsFile;
}
unset($performanceLevelsFile);
defined('MW_PERF_LVL') or define('MW_PERF_LVL', 0);

// support forum.
defined('MW_SUPPORT_FORUM_URL') or define('MW_SUPPORT_FORUM_URL', 'https://forum.mailwizz.com/');

// support KB.
defined('MW_SUPPORT_KB_URL') or define('MW_SUPPORT_KB_URL', 'https://www.mailwizz.com/kb/');

// again, for some fcgi installs
if (empty($_SERVER['SCRIPT_FILENAME'])) {
    $_SERVER['SCRIPT_FILENAME'] = __FILE__;
}

// misc ini settings
ini_set('file_uploads', 'On');

// forced post max size
if (!MW_IS_CLI && defined('MW_POST_MAX_SIZE')) {
    ini_set('post_max_size', MW_POST_MAX_SIZE);
}

// forced upload size
if (!MW_IS_CLI && defined('MW_UPLOAD_MAX_FILESIZE')) {
    ini_set('upload_max_filesize', MW_UPLOAD_MAX_FILESIZE);
}

setlocale(LC_ALL, 'en_US.UTF-8');
// seems some mb_* functions are missing in some cases even if the mb extension is installed
$encodingFuncs = ['mb_internal_encoding', 'mb_http_output', 'mb_regex_encoding'];
foreach ($encodingFuncs as $func) {
    if (function_exists($func)) {
        $func('UTF-8');
    }
}
unset($encodingFuncs, $func);

// various constants
define('MW_DATETIME_NOW', (new DateTime('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s'));

// require the autoloader
require_once MW_VENDOR_PATH . '/autoload.php';

// set the main paths of alias
Yii::setPathOfAlias('root', realpath(dirname(__FILE__).'/..'));
Yii::setPathOfAlias('common', Yii::getPathOfAlias('root.apps.common'));

// check to see if the app type exists.
if (MW_APP_NAME === 'common') {
    if (!defined('MW_RUNNING_TESTS') || !MW_RUNNING_TESTS) {
        throw new Exception('The "common" application name is restricted.');
    }
} elseif (MW_IS_CLI && !is_dir(dirname(__FILE__).'/'.MW_APP_NAME)) {
    throw new Exception('Invalid application.');
} elseif (!MW_IS_CLI && (!is_dir(dirname(__FILE__).'/'.MW_APP_NAME) || !is_dir(realpath(dirname(__FILE__).'/../'.MW_APP_NAME)))) {
    throw new Exception('Invalid application.');
}

// require a few helpers to help things out.
require_once(Yii::getPathOfAlias('common.components.helpers.FileSystemHelper').'.php');
require_once(Yii::getPathOfAlias('common.components.helpers.FilterVarHelper').'.php');
require_once(Yii::getPathOfAlias('common.components.helpers.AppInitHelper').'.php');

// list of available apps.
$availableApps = FileSystemHelper::getDirectoryNames(dirname(__FILE__));
$webApps = [];
foreach ($availableApps as $appName) {
    if (file_exists(MW_PATH . '/' . $appName) && is_dir(MW_PATH . '/' . $appName)) {
        $webApps[] = $appName;
    }
}
$notWebApps = array_diff($availableApps, $webApps);

// set path alias for apps
foreach ($availableApps as $appName) {
    Yii::setPathOfAlias($appName, Yii::getPathOfAlias('root.apps.'.$appName));
}

// load main configuration file and also check to see if there is a custom one to load that too
$commonConfig = require Yii::getPathOfAlias('common.config.main') . '.php';
if (is_file($customConfigFile = Yii::getPathOfAlias('common.config.main-custom') . '.php')) {
    $commonConfig = CMap::mergeArray($commonConfig, require $customConfigFile);
}

// load the config file for the current app and also check to see if there is a custom one to load that too
$appConfig = [];
if (MW_APP_NAME !== 'common' && is_file($appConfigFile = Yii::getPathOfAlias(MW_APP_NAME . '.config.main') . '.php')) {
    $appConfig = require $appConfigFile;
}
if (is_file($customConfigFile = Yii::getPathOfAlias(MW_APP_NAME . '.config.main-custom') . '.php')) {
    $appConfig = CMap::mergeArray($appConfig, require $customConfigFile);
}

// merge the app config with the base config
$appConfig = CMap::mergeArray($commonConfig, $appConfig);

// require the functions file
require MW_APPS_PATH . '/common/components/utils/functions.php';

// create the application instance.
if (!MW_IS_CLI) {
    $app = Yii::createWebApplication($appConfig);
} else {
    $webSpecific = ['defaultController', 'modules', 'controllerNamespace'];
    foreach ($webSpecific as $prop) {
        if (isset($appConfig[$prop])) {
            unset($appConfig[$prop]);
        }
    }
    $app = Yii::createConsoleApplication($appConfig);
}

// correct th extensions path
Yii::setPathOfAlias('extensions', Yii::getPathOfAlias('root.apps.extensions'));

// set apps data behavior for easier data access!
$app->attachBehavior('apps', [
    'class'             => 'common.components.behaviors.AppsBehavior',
    'availableApps'     => $availableApps,
    'webApps'           => $webApps,
    'notWebApps'        => $notWebApps,
    'currentAppName'    => MW_APP_NAME,
    'currentAppIsWeb'   => in_array(MW_APP_NAME, $webApps),
]);

// unset all the created variables since the party just starts and we don't want them around anymore.
unset($yii, $commonConfig, $customConfigFile, $appConfig, $availableApps, $webApps, $notWebApps, $appName, $webSpecific);

// since 1.3.6.2 - class map override
Yii::$classMap = [
    'CEmailValidator' => Yii::getPathOfAlias('common.components.validators.CEmailValidator') . '.php',
    'CUrlValidator'   => Yii::getPathOfAlias('common.components.validators.CUrlValidator') . '.php',
];

// since 1.3.9.5
if (is_file($customInitFile = dirname(__FILE__) . '/init-custom.php')) {
    require $customInitFile;
}
unset($customInitFile);

// add the ability to return the app instance instead of running it.
if (defined('MW_RETURN_APP_INSTANCE') && MW_RETURN_APP_INSTANCE) {
    return $app;
}

// and run the application
$app->run();