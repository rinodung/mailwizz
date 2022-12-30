<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * AccountController
 *
 * Handles the actions for account related tasks
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

class AccountController extends Controller
{
    /**
     * @return void
     * @throws CException
     */
    public function init()
    {
        $this->onBeforeAction = [$this, '_registerJuiBs'];
        $this->addPageScript(['src' => AssetsUrl::js('account.js')]);
        parent::init();
    }

    /**
     * Default action, allowing to update the account.
     *
     * @return void
     * @throws CException
     */
    public function actionIndex()
    {
        /** @var Customer $customer */
        $customer = customer()->getModel();
        if (is_subaccount()) {
            /** @var Customer $customer */
            $customer = subaccount()->customer();
        }

        $customer->confirm_email = $customer->email;
        $customer->setScenario('update-profile');

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($customer->getModelName()))) {
            $customer->attributes = $attributes;
            if ($customer->save()) {
                notify()->addSuccess(t('customers', 'Profile info successfully updated!'));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller'=> $this,
                'success'   => notify()->getHasSuccess(),
                'customer'  => $customer,
            ]));
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('customers', 'Account info'),
            'pageHeading'     => t('customers', 'Account info'),
            'pageBreadcrumbs' => [
                t('customers', 'Account') => createUrl('account/index'),
                t('app', 'Update'),
            ],
        ]);

        $this->render('index', compact('customer'));
    }

    /**
     * Update the account 2fa settings
     *
     * @return void
     * @throws CException
     */
    public function action2fa()
    {
        /** @var OptionTwoFactorAuth $twoFaSettings */
        $twoFaSettings = container()->get(OptionTwoFactorAuth::class);

        // make sure 2FA is enabled
        if (!$twoFaSettings->getIsEnabled()) {
            notify()->addWarning(t('app', '2FA is not enabled in this system!'));
            $this->redirect(['index']);
            return;
        }

        /** @var Customer $customer */
        $customer = customer()->getModel();
        if (is_subaccount()) {
            /** @var Customer $customer */
            $customer = subaccount()->customer();
        }

        $customer = CustomerForTwoFactorAuth::model()->findByPk((int)$customer->customer_id);

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($customer->getModelName()))) {
            $customer->attributes = $attributes;
            if ($customer->save()) {
                notify()->addSuccess(t('customers', 'Customer info successfully updated!'));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller'=> $this,
                'success'   => notify()->getHasSuccess(),
                'customer'  => $customer,
            ]));
        }

        // make sure we have the secret
        if (empty($customer->twofa_secret)) {
            $manager = new Da\TwoFA\Manager();
            $customer->twofa_secret = $manager->generateSecretKey(64);
            $customer->save(false);
        }

        // we need to create our time-based one time password secret uri
        $company   = $twoFaSettings->companyName . ' / Customer';
        $totp      = new Da\TwoFA\Service\TOTPSecretKeyUriGeneratorService($company, $customer->email, $customer->twofa_secret);
        $qrCode    = new Da\TwoFA\Service\QrCodeDataUriGeneratorService($totp->run());
        $qrCodeUri = $qrCode->run();

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('customers', '2FA'),
            'pageHeading'     => t('customers', '2FA'),
            'pageBreadcrumbs' => [
                t('customers', 'Account') => createUrl('account/index'),
                t('customers', '2FA') => createUrl('account/2fa'),
                t('app', 'Update'),
            ],
        ]);

        $this->render('2fa', compact('customer', 'qrCodeUri'));
    }

    /**
     * Update the account company info
     *
     * @return void
     * @throws CException
     */
    public function actionCompany()
    {
        /** @var Customer $customer */
        $customer = customer()->getModel();

        if (is_subaccount()) {
            $this->redirect(['account/index']);
            return;
        }

        if (empty($customer->company)) {
            $customer->company = new CustomerCompany();
        }

        $company = $customer->company;

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($company->getModelName()))) {
            $company->attributes = $attributes;
            $company->customer_id = customer()->getId();

            if ($company->save()) {
                notify()->addSuccess(t('customers', 'Company info successfully updated!'));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller'=> $this,
                'success'   => notify()->getHasSuccess(),
                'customer'  => $customer,
                'company'   => $company,
            ]));
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('customers', 'Company'),
            'pageHeading'     => t('customers', 'Company'),
            'pageBreadcrumbs' => [
                 t('customers', 'Account') => createUrl('account/index'),
                 t('customers', 'Company') => createUrl('account/company'),
                 t('app', 'Update'),
            ],
        ]);

        $this->render('company', compact('company'));
    }

    /**
     * Disable the account
     *
     * @return void
     * @throws CException
     */
    public function actionDisable()
    {
        /** @var Customer $customer */
        $customer = customer()->getModel();
        if (is_subaccount()) {
            /** @var Customer $customer */
            $customer = subaccount()->customer();
        }

        if (request()->getIsPostRequest()) {
            $customer->saveStatus(Customer::STATUS_PENDING_DISABLE);

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller'=> $this,
                'success'   => true,
                'customer'  => $customer,
            ]));

            if ($collection->itemAt('success')) {
                customer()->logout();
                notify()->addSuccess(t('customers', 'Your account has been successfully disabled!'));
                $this->redirect(['guest/index']);
                return;
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('customers', 'Disable account'),
            'pageHeading'     => t('customers', 'Disable account'),
            'pageBreadcrumbs' => [
                t('customers', 'Account') => createUrl('account/index'),
                t('customers', 'Disable account'),
            ],
        ]);

        $this->render('disable');
    }

    /**
     * Display stats about the account, limits, etc
     *
     * @return void
     * @throws CException
     */
    public function actionUsage()
    {
        if (!request()->getIsAjaxRequest()) {
            $this->redirect(['account/index']);
            return;
        }

        /** @var Customer $customer */
        $customer = customer()->getModel();
        $data = [];

        // sending quota
        $allowed  = (int)$customer->getGroupOption('sending.quota', -1);
        $count    = $customer->countUsageFromQuotaMark();
        $data[] = [
            'heading' => t('customers', 'Quota usage (count)'),
            'allowed' => !$allowed ? 0 : ($allowed == -1 ? '&infin;' : html_encode(formatter()->formatNumber($allowed))),
            'used'    => formatter()->formatNumber($count),
            'percent' => $percent = ($allowed < 1 ? 0 : ($count > $allowed ? 100 : round(($count / $allowed) * 100, 2))),
            'url'     => 'javascript:;',
            'bar_color' => $percent < 50 ? 'green' : ($percent < 70 ? 'aqua' : ($percent < 90 ? 'yellow' : 'red')),
        ];

        if ((int)$customer->getGroupOption('sending.quota_time_value', -1) > -1) {
            $timeValue    = (int)$customer->getGroupOption('sending.quota_time_value', -1);
            $timeUnit     = (string)$customer->getGroupOption('sending.quota_time_unit', 'hour');
            $now          = time();

            $tsDateAdded  = (int)strtotime((string)$customer->getLastQuotaMark()->date_added);
            $tsAllowed    = ((int)strtotime(sprintf('+ %d %s', $timeValue, $timeUnit), $tsDateAdded) - $tsDateAdded);
            $daysAllowed  = $tsAllowed > 0 ? round($tsAllowed / (3600 * 24)) : 0;
            $daysAllowed  = $daysAllowed > 0 ? $daysAllowed : 0;

            $tsDaysUsed = $now - $tsDateAdded;
            $daysUsed   = $tsDaysUsed > 0 ? round($tsDaysUsed / (3600 * 24)) : 0;
            $daysUsed   = $daysUsed > 0 ? $daysUsed : 0;

            $data[] = [
                'heading' => t('customers', 'Quota usage (days)'),
                'allowed' => !$daysAllowed ? 0 : formatter()->formatNumber($daysAllowed),
                'used'    => formatter()->formatNumber($daysUsed),
                'percent' => $percent = ($daysAllowed < 1 ? 0 : ($daysUsed > $daysAllowed ? 100 : round(($daysUsed / $daysAllowed) * 100, 2))),
                'url'     => 'javascript:;',
                'bar_color' => $percent < 50 ? 'green' : ($percent < 70 ? 'aqua' : ($percent < 90 ? 'yellow' : 'red')),
            ];
        }

        // lists
        $allowed  = (int)$customer->getGroupOption('lists.max_lists', -1);
        $criteria = new CDbCriteria();
        $criteria->compare('customer_id', (int)$customer->customer_id);
        $criteria->addNotInCondition('status', [Lists::STATUS_PENDING_DELETE]);
        $count    = Lists::model()->count($criteria);

        $data[] = [
            'heading' => t('customers', 'Lists'),
            'allowed' => !$allowed ? 0 : ($allowed == -1 ? '&infin;' : html_encode(formatter()->formatNumber($allowed))),
            'used'    => formatter()->formatNumber($count),
            'percent' => $percent = ($allowed < 1 ? 0 : ($count > $allowed ? 100 : round(($count / $allowed) * 100, 2))),
            'url'     => createUrl('lists/index'),
            'bar_color' => $percent < 50 ? 'green' : ($percent < 70 ? 'aqua' : ($percent < 90 ? 'yellow' : 'red')),
        ];

        // campaigns
        $allowed  = (int)$customer->getGroupOption('campaigns.max_campaigns', -1);
        $criteria = new CDbCriteria();
        $criteria->compare('customer_id', (int)$customer->customer_id);
        $criteria->addNotInCondition('status', [Campaign::STATUS_PENDING_DELETE]);
        $count    = Campaign::model()->count($criteria);

        $data[] = [
            'heading' => t('customers', 'Campaigns'),
            'allowed' => !$allowed ? 0 : ($allowed == -1 ? '&infin;' : html_encode(formatter()->formatNumber($allowed))),
            'used'    => formatter()->formatNumber($count),
            'percent' => $percent = ($allowed < 1 ? 0 : ($count > $allowed ? 100 : round(($count / $allowed) * 100, 2))),
            'url'     => createUrl('campaigns/index'),
            'bar_color' => $percent < 50 ? 'green' : ($percent < 70 ? 'aqua' : ($percent < 90 ? 'yellow' : 'red')),
        ];

        // subscribers
        $criteria = new CDbCriteria();
        $criteria->select = 'COUNT(DISTINCT(t.email)) as counter';
        $criteria->with = [
            'list' => [
                'select'   => false,
                'together' => true,
                'joinType' => 'INNER JOIN',
                'condition'=> 'list.customer_id = :cid AND list.status != :st',
                'params'   => [':cid' => (int)$customer->customer_id, ':st' => Lists::STATUS_PENDING_DELETE],
            ],
        ];
        $count    = ListSubscriber::model()->count($criteria);
        $allowed  = (int)$customer->getGroupOption('lists.max_subscribers', -1);
        $data[] = [
            'heading' => t('customers', 'Subscribers'),
            'allowed' => !$allowed ? 0 : ($allowed == -1 ? '&infin;' : html_encode(formatter()->formatNumber($allowed))),
            'used'    => formatter()->formatNumber($count),
            'percent' => $percent = ($allowed < 1 ? 0 : ($count > $allowed ? 100 : round(($count / $allowed) * 100, 2))),
            'url'     => createUrl('lists/index'),
            'bar_color' => $percent < 50 ? 'green' : ($percent < 70 ? 'aqua' : ($percent < 90 ? 'yellow' : 'red')),
        ];

        // delivery servers
        $allowed  = (int)$customer->getGroupOption('servers.max_delivery_servers', 0);
        if ($allowed != 0) {
            $count    = DeliveryServer::model()->countByAttributes(['customer_id' => $customer->customer_id]);
            $data[] = [
                'heading' => t('customers', 'Delivery servers'),
                'allowed' => !$allowed ? 0 : ($allowed == -1 ? '&infin;' : html_encode(formatter()->formatNumber($allowed))), // @phpstan-ignore-line
                'used'    => formatter()->formatNumber($count),
                'percent' => $percent = ($allowed < 1 ? 0 : ($count > $allowed ? 100 : round(($count / $allowed) * 100, 2))),
                'url'     => createUrl('delivery_servers/index'),
                'bar_color' => $percent < 50 ? 'green' : ($percent < 70 ? 'aqua' : ($percent < 90 ? 'yellow' : 'red')),
            ];
        }

        // bounce servers
        $allowed  = (int)$customer->getGroupOption('servers.max_bounce_servers', 0);
        if ($allowed != 0) {
            $count    = BounceServer::model()->countByAttributes(['customer_id' => $customer->customer_id]);
            $data[] = [
                'heading' => t('customers', 'Bounce servers'),
                'allowed' => !$allowed ? 0 : ($allowed == -1 ? '&infin;' : html_encode(formatter()->formatNumber($allowed))), // @phpstan-ignore-line
                'used'    => formatter()->formatNumber($count),
                'percent' => $percent = ($allowed < 1 ? 0 : ($count > $allowed ? 100 : round(($count / $allowed) * 100, 2))),
                'url'     => createUrl('bounce_servers/index'),
                'bar_color' => $percent < 50 ? 'green' : ($percent < 70 ? 'aqua' : ($percent < 90 ? 'yellow' : 'red')),
            ];
        }

        // fbl servers
        $allowed  = (int)$customer->getGroupOption('servers.max_fbl_servers', 0);
        if ($allowed != 0) {
            $count    = FeedbackLoopServer::model()->countByAttributes(['customer_id' => $customer->customer_id]);
            $data[] = [
                'heading' => t('customers', 'Feedback servers'),
                'allowed' => !$allowed ? 0 : ($allowed == -1 ? '&infin;' : html_encode(formatter()->formatNumber($allowed))), // @phpstan-ignore-line
                'used'    => formatter()->formatNumber($count),
                'percent' => $percent = ($allowed < 1 ? 0 : ($count > $allowed ? 100 : round(($count / $allowed) * 100, 2))),
                'url'     => createUrl('feedback_loop_servers/index'),
                'bar_color' => $percent < 50 ? 'green' : ($percent < 70 ? 'aqua' : ($percent < 90 ? 'yellow' : 'red')),
            ];
        }

        // since 1.9.19
        $data = (array)hooks()->applyFilters('customer_account_usage_items', $data);

        $this->renderJson([
            'html' => $this->renderPartial('_usage', ['items' => $data], true),
        ]);
    }

    /**
     * Display country zones
     *
     * @return void
     * @throws CException
     */
    public function actionZones_by_country()
    {
        $criteria = new CDbCriteria();
        $criteria->select = 'zone_id, name';
        $criteria->compare('country_id', (int) request()->getQuery('country_id'));
        $models = Zone::model()->findAll($criteria);

        $zones = [
            ['zone_id' => '', 'name' => t('app', 'Please select')],
        ];
        foreach ($models as $model) {
            $zones[] = [
                'zone_id'    => $model->zone_id,
                'name'        => $model->name,
            ];
        }
        $this->renderJson(['zones' => $zones]);
    }

    /**
     * Log the customer out
     *
     * @return void
     */
    public function actionLogout()
    {
        $logoutUrl = customer()->loginUrl;

        if (customer()->getState('__customer_impersonate')) {
            $logoutUrl = apps()->getAppUrl('backend', 'customers/index', true);

            // since 2.1.4
            if (customer()->getState('__customer_impersonate_return_url')) {
                $logoutUrl = customer()->getState('__customer_impersonate_return_url');
            }
        }

        customer()->logout();
        $this->redirect($logoutUrl);
    }

    /**
     * Save the grid view columns for this user
     *
     * @return void
     * @throws CException
     */
    public function actionSave_grid_view_columns()
    {
        $model      = request()->getPost('model');
        $controller = request()->getPost('controller');
        $action     = request()->getPost('action');
        $columns    = request()->getPost('columns', []);

        if (!($redirect = request()->getServer('HTTP_REFERER'))) {
            $redirect = ['dashboard/index'];
        }

        if (!request()->getIsPostRequest()) {
            $this->redirect($redirect);
        }

        if (empty($model) || empty($controller) || empty($action) || empty($columns) || !is_array($columns)) {
            $this->redirect($redirect);
        }

        $optionKey  = sprintf('%s:%s:%s', (string)$model, (string)$controller, (string)$action);
        $customerId = (int)customer()->getId();
        if (is_subaccount()) {
            /** @var Customer $customer */
            $customer = subaccount()->customer();
            $customerId = (int)$customer->customer_id;
        }
        $optionKey  = sprintf('system.views.grid_view_columns.customers.%d.%s', $customerId, $optionKey);
        options()->set($optionKey, (array)$columns);

        notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
        $this->redirect($redirect);
    }

    /**
     * Export
     *
     * @return void
     */
    public function actionExport()
    {
        /** @var Customer $customer */
        $customer = customer()->getModel();
        if (is_subaccount()) {
            /** @var Customer $customer */
            $customer = subaccount()->customer();
        }

        $attributes = AttributeHelper::removeSpecialAttributes($customer->getAttributes());

        if (!empty($customer->group_id)) {
            $attributes['group'] = $customer->group->name;
        }

        if (!empty($customer->language_id)) {
            $attributes['language'] = $customer->language->name;
        }

        // Set the download headers
        HeaderHelper::setDownloadHeaders('account.csv');

        try {
            $csvWriter = League\Csv\Writer::createFromPath('php://output', 'w');

            $csvWriter->insertOne(array_map([$customer, 'getAttributeLabel'], array_keys($attributes)));
            $csvWriter->insertOne(array_values($attributes));
        } catch (Exception $e) {
        }

        app()->end();
    }

    /**
     * Callback method to render the customer account tabs
     *
     * @return string
     * @throws Exception
     */
    public function renderTabs()
    {
        $route      = app()->getController()->getRoute();
        $priority   = 0;
        $tabs       = [];

        $tabs[] = [
            'label'     => IconHelper::make('glyphicon-list') . ' ' . t('customers', 'Profile'),
            'url'       => ['account/index'],
            'active'    => strpos('account/index', $route) === 0,
            'priority'  => (++$priority),
        ];

        if (!is_subaccount()) {
            $tabs[] = [
                'label'     => IconHelper::make('glyphicon-briefcase') . ' ' . t('customers', 'Company'),
                'url'       => ['account/company'],
                'active'    => strpos('account/company', $route) === 0,
                'priority'  => (++$priority),
            ];
        }

        /** @var OptionTwoFactorAuth $twoFaSettings */
        $twoFaSettings = container()->get(OptionTwoFactorAuth::class);
        if ($twoFaSettings->getIsEnabled()) {
            $tabs[] = [
                'label'     => IconHelper::make('glyphicon-lock') . ' ' . t('customers', '2FA'),
                'url'       => ['account/2fa'],
                'active'    => strpos('account/2fa', $route) === 0,
                'priority'  => (++$priority),
            ];
        }

        $tabs[] = [
            'label'     => IconHelper::make('glyphicon-ban-circle') . ' ' . t('customers', 'Disable account'),
            'url'       => ['account/disable'],
            'active'    => strpos('account/disable', $route) === 0,
            'priority'  => 99,
        ];

        $tabs[] = [
            'label'     => IconHelper::make('export') . ' ' . t('customers', 'Export'),
            'url'       => ['account/export'],
            'active'    => strpos('account/export', $route) === 0,
            'priority'  => 99,
        ];

        // since 1.3.6.2
        /** @var array $tabs */
        $tabs = (array)hooks()->applyFilters('customer_account_edit_render_tabs', $tabs);

        $sort = [];
        foreach ($tabs as $index => $tab) {
            if (!isset($tab['label'], $tab['url'], $tab['active'])) {
                unset($tabs[$index]);
                continue;
            }

            $sort[] = isset($tab['priority']) ? (int)$tab['priority'] : (++$priority);

            if (isset($tabs['priority'])) {
                unset($tabs['priority']);
            }

            if (isset($tabs['items'])) {
                unset($tabs['items']);
            }
        }

        if (empty($tabs) || !is_array($tabs)) {
            return '';
        }

        array_multisort($sort, $tabs);

        return $this->widget('zii.widgets.CMenu', [
            'htmlOptions'   => ['class' => 'nav nav-tabs'],
            'items'         => $tabs,
            'encodeLabel'   => false,
        ], true);
    }

    /**
     * @param CEvent $event
     *
     * @return void
     */
    public function _registerJuiBs(CEvent $event)
    {
        if (in_array($event->params['action']->id, ['index'])) {
            $this->addPageStyles([
                ['src' => apps()->getBaseUrl('assets/css/jui-bs/jquery-ui-1.10.3.custom.css'), 'priority' => -1001],
            ]);
        }
    }
}
