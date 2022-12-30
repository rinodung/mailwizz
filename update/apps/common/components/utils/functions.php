<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.0.0
 */

/**
 * @return CApplication
 */
function app(): CApplication
{
    return Yii::app();
}

/**
 * @return AppsBehavior
 */
function apps(): AppsBehavior
{
    return app()->asa('apps');
}

/**
 * @return BaseHttpRequest
 */
function request(): BaseHttpRequest
{
    return app()->getRequest();
}

/**
 * @return NotifyManager
 */
function notify(): NotifyManager
{
    return app()->getComponent('notify');
}

/**
 * @return OptionsManager
 */
function options(): OptionsManager
{
    return app()->getComponent('options');
}

/**
 * @return HooksManager
 */
function hooks(): HooksManager
{
    return app()->getComponent('hooks');
}

/**
 * @return WebCustomer
 */
function customer(): WebCustomer
{
    return app()->getComponent('customer');
}

/**
 * @return CustomerSubaccountHelper
 */
function subaccount(): CustomerSubaccountHelper
{
    /** @var CustomerSubaccountHelper $helper */
    $helper = container()->get(CustomerSubaccountHelper::class);

    return $helper;
}

/**
 * @return bool
 */
function is_subaccount(): bool
{
    return subaccount()->customer() !== null;
}

/**
 * @return WebUser
 */
function user(): WebUser
{
    return app()->getComponent('user');
}

/**
 * @return IOFilter
 */
function ioFilter(): IOFilter
{
    return app()->getComponent('ioFilter');
}

/**
 * @return League\Container\Container
 */
function container(): League\Container\Container
{
    return app()->getComponent('dependencyInjectionContainer')->getContainer();
}

/**
 * @return CController
 */
function controller(): CController
{
    /** @var CController $controller */
    $controller = app()->getController();

    return $controller;
}

/**
 * @return CDbConnection
 */
function db(): CDbConnection
{
    return app()->getDb();
}

/**
 * @return CFormatter
 */
function formatter(): CFormatter
{
    return app()->getFormat();
}

/**
 * @return CNumberFormatter
 */
function numberFormatter(): CNumberFormatter
{
    return app()->getNumberFormatter();
}

/**
 * @return CDateFormatter
 */
function dateFormatter(): CDateFormatter
{
    return app()->getDateFormatter();
}

/**
 * @return ICache
 */
function cache(): ICache
{
    return app()->getComponent('cache');
}

/**
 * @return ICache
 */
function dbCache(): ICache
{
    return app()->getComponent('dbCache');
}

/**
 * @return CHttpSession
 */
function session(): CHttpSession
{
    return app()->getComponent('session');
}

/**
 * @return BaseMutex
 */
function mutex(): BaseMutex
{
    return app()->getComponent('mutex');
}

/**
 * @return QueueInterface
 */
function queue(): QueueInterface
{
    return app()->getComponent('queue');
}

/**
 * @param array|string $queue
 * @param array $properties
 * @param array $headers
 * @param int|null $deliveryDelay
 * @param int|null $priority
 * @param int|null $timeToLive
 *
 * @return void
 */
function queue_send($queue, array $properties = [], array $headers = [], ?int $deliveryDelay = null, ?int $priority = null, ?int $timeToLive = null): void
{
    if (!is_array($queue)) {
        $queue = [$queue];
    }

    queue()->send(new QueueName(...$queue), new QueueMessage('', $properties, $headers, $deliveryDelay, $priority, $timeToLive));
}

/**
 * @param array|string $queue
 *
 * @param \Enqueue\Consumption\ExtensionInterface|null $extension
 *
 * @return void
 */
function queue_consume($queue, Enqueue\Consumption\ExtensionInterface $extension = null): void
{
    if (!is_array($queue)) {
        $queue = [$queue];
    }

    queue()->consume(new QueueName(...$queue), $extension);
}

/**
 * @param array|string $queue
 *
 * @return void
 */
function queue_purge($queue): void
{
    if (!is_array($queue)) {
        $queue = [$queue];
    }

    queue()->purge(new QueueName(...$queue));
}

/**
 * @return QueueMonitorInterface
 */
function queue_monitor(): QueueMonitorInterface
{
    return queue()->getMonitor();
}

/**
 * @return QueueStorageInterface
 */
function queue_storage(): QueueStorageInterface
{
    return queue()->getStorage();
}

/**
 * @return CAssetManager
 */
function assetManager(): CAssetManager
{
    return app()->getComponent('assetManager');
}

/**
 * @return CClientScript
 */
function clientScript(): CClientScript
{
    return app()->getComponent('clientScript');
}

/**
 * @return PasswordHasher
 */
function passwordHasher(): PasswordHasher
{
    return app()->getComponent('passwordHasher');
}

/**
 * @return CUrlManager
 */
function urlManager(): CUrlManager
{
    return app()->getComponent('urlManager');
}

/**
 * @return ExtensionsManager
 */
function extensionsManager(): ExtensionsManager
{
    return app()->getComponent('extensionsManager');
}

/**
 * @return Mailer
 */
function mailer(): Mailer
{
    return app()->getComponent('mailer');
}

/**
 * @param string $category
 * @param string $message
 * @param mixed $params
 * @param string $source
 * @param string $language
 *
 * @return string
 */
function t(string $category, string $message, $params = [], string $source = '', string $language = ''): string
{
    $source   = $source === '' ? null : $source;
    $language = $language === '' ? null : $language;
    // @phpstan-ignore-next-line
    return Yii::t($category, $message, $params, $source, $language);
}

/**
 * @param string $route
 * @param array $params
 * @param string $ampersand
 *
 * @return string
 */
function createUrl(string $route, array $params = [], string $ampersand = '&'): string
{
    return app()->createUrl($route, $params, $ampersand);
}

/**
 * @param string $route
 * @param array $params
 * @param string $schema
 * @param string $ampersand
 *
 * @return string
 */
function createAbsoluteUrl(string $route, array $params = [], string $schema = '', string $ampersand = '&'): string
{
    return app()->createAbsoluteUrl($route, $params, $schema, $ampersand);
}

/**
 * @param string $text
 *
 * @return string
 */
function html_encode(string $text): string
{
    return CHtml::encode($text);
}

/**
 * @param string $text
 *
 * @return string
 */
function html_decode(string $text): string
{
    return CHtml::decode($text);
}

/**
 * @param string $text
 *
 * @return string
 */
function html_purify(string $text): string
{
    return (string)ioFilter()->purify($text);
}

/**
 * @param string $key
 * @param mixed $defaultValue
 *
 * @return mixed
 */
function app_param(string $key, $defaultValue = null)
{
    return app()->getParams()->contains($key) ? app()->getParams()->itemAt($key) : $defaultValue;
}

/**
 * @param mixed $key
 * @param mixed $value
 */
function app_param_set($key, $value = null): void
{
    if (!is_array($key)) {
        $key = [$key => $value];
    }

    foreach ($key as $k => $v) {
        app()->getParams()->add($k, $v);
    }
}

/**
 * @param mixed $key
 */
function app_param_unset($key): void
{
    if (!is_array($key)) {
        $key = [$key];
    }

    foreach ($key as $k) {
        app()->getParams()->remove($k);
    }
}

/**
 * @return bool
 */
function is_cli(): bool
{
    return defined('MW_IS_CLI') && MW_IS_CLI;
}

/**
 * @return bool
 */
function is_ajax(): bool
{
    if (defined('MW_IS_AJAX')) {
        return MW_IS_AJAX;
    }
    return false;
}

/**
 * @param string $version
 * @param string $operator
 *
 * @return bool
 */
function app_version_compare_to(string $version, string $operator): bool
{
    $appVersion = '1.0.0';
    if (defined('MW_VERSION')) {
        $appVersion = MW_VERSION;
    }
    return version_compare($appVersion, $version, $operator);
}

/**
 * Strict type comes with this price.
 *
 * @param bool $enable
 * @return void
 */
function toggle_ob_implicit_flush(bool $enable = true): void
{
    if ($enable) {
        ob_implicit_flush();
    } else {
        call_user_func('ob_implicit_flush', version_compare(PHP_VERSION, '8.0', '>=') ? false : 0);
    }
}
