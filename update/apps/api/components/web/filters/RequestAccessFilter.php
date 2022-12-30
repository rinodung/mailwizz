<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * RequestAccessFilter
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

class RequestAccessFilter extends CFilter
{
    /**
     * @param CFilterChain $filterChain
     *
     * @return bool
     * @throws CException
     */
    protected function preFilter($filterChain)
    {
        /** @var Controller $controller */
        $controller = $filterChain->controller;

        // since 1.3.5.9
        /** @var OptionApiIpAccess $optionApiIpAccess */
        $optionApiIpAccess = container()->get(OptionApiIpAccess::class);

        $allowedIPs = $optionApiIpAccess->getAllowedIps();
        $deniedIPs  = $optionApiIpAccess->getDeniedIps();
        $currentIP  = (string)request()->getUserHostAddress();

        if (!empty($deniedIPs) && in_array($currentIP, $deniedIPs)) {
            $controller->renderJson([
                'status'    => 'error',
                'error'     => t('api', 'Your IP address is not allowed to access this server.'),
            ], 400);
            return false;
        }
        if (!empty($allowedIPs) && !in_array($currentIP, $allowedIPs)) {
            $controller->renderJson([
                'status'    => 'error',
                'error'     => t('api', 'Your IP address is not allowed to access this server.'),
            ], 400);
            return false;
        }
        //

        $unprotectedControllers = (array)app_param('unprotectedControllers', []);
        if (in_array($controller->getId(), $unprotectedControllers)) {
            return true;
        }

        // unfiltered _SERVER
        /** @var CMap $server */
        $server = app_param('SERVER');
        $server = $server->toArray();

        // keep BC
        $apiKey = $server['HTTP_X_MW_PUBLIC_KEY']  ?? '';
        if (!empty($server['HTTP_X_API_KEY'])) {
            $apiKey = $server['HTTP_X_API_KEY'];
        }

        // verify required params.
        if (empty($apiKey)) {
            $controller->renderJson([
                'status'    => 'error',
                'error'     => t('api', 'Invalid API request params. Please refer to the documentation.'),
            ], 400);
            return false;
        }

        $key = CustomerApiKey::model()->findByAttributes([
            'key' => $apiKey,
        ]);

        if (empty($key)) {
            $controller->renderJson([
                'status'    => 'error',
                'error'     => t('app', 'Invalid API key. Please refer to the documentation.'),
            ], 400);
            return false;
        }

        // since 1.3.6.2
        $deniedIPs  = !empty($key->ip_blacklist) ? CommonHelper::getArrayFromString((string)$key->ip_blacklist) : [];
        $allowedIPs = !empty($key->ip_whitelist) ? CommonHelper::getArrayFromString((string)$key->ip_whitelist) : [];
        if (!empty($deniedIPs) && in_array($currentIP, $deniedIPs)) {
            $controller->renderJson([
                'status'    => 'error',
                'error'     => t('api', 'Your IP address is not allowed to access this server.'),
            ], 400);
            return false;
        }
        if (!empty($allowedIPs) && !in_array($currentIP, $allowedIPs)) {
            $controller->renderJson([
                'status'    => 'error',
                'error'     => t('api', 'Your IP address is not allowed to access this server.'),
            ], 400);
            return false;
        }
        //

        $customer = Customer::model()->findByPk((int)$key->customer_id);

        // since 1.3.4.8
        if (!$customer->getIsActive()) {
            $controller->renderJson([
                'status'    => 'error',
                'error'     => t('app', 'Your account must be active in order to use the API.'),
            ], 400);
        }

        // since 1.5.3
        if ($customer->getGroupOption('api.enabled', 'yes') != 'yes') {
            $controller->renderJson([
                'status'    => 'error',
                'error'     => t('app', 'Your account is not allowed to use the API.'),
            ], 400);
        }

        // set language
        if (!empty($customer->language_id)) {
            $language = Language::model()->findByPk((int)$customer->language_id);
            app()->setLanguage($language->getLanguageAndLocaleCode());
        }

        user()->setModel($customer);
        user()->setId($customer->customer_id);

        return true;
    }

    /**
     * @param CFilterChain $filterChain
     *
     * @return void
     */
    protected function postFilter($filterChain)
    {
    }
}
