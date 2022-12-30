<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * SettingsController
 *
 * Handles the settings for the application
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

class SettingsController extends Controller
{
    /**
     * @return void
     */
    public function init()
    {
        $this->addPageScript(['src' => AssetsUrl::js('settings.js')]);
        parent::init();
    }

    /**
     * Handle the common settings page
     *
     * @return void
     * @throws CException
     */
    public function actionIndex()
    {
        /** @var OptionCommon $commonModel */
        $commonModel = container()->get(OptionCommon::class);

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($commonModel->getModelName(), []))) {
            $commonModel->attributes = $attributes;
            if (!$commonModel->save()) {
                notify()->addError(t('app', 'Your form contains a few errors, please fix them and try again!'));
            } else {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));

                // since 2.1.11
                if ($commonModel->getUseCleanUrls()) {
                    urlManager()->showScriptName = false;
                } else {
                    urlManager()->showScriptName = true;
                }

                /** @var OptionUrl $optionUrl */
                $optionUrl = container()->get(OptionUrl::class);
                $optionUrl->regenerateSystemUrls();
                // do this so the next redirect will work even if there are no rewrite rules
                urlManager()->showScriptName = true;
                //
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller'    => $this,
                'success'       => notify()->getHasSuccess(),
                'commonModel'   => $commonModel,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['settings/index']);
            }
        }

        // since 1.5.1
        hooks()->addFilter('common_settings_auto_update_warning_message', [$this, '_commonSettingsAutoUpdateWarningMessage'], 5);

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('settings', 'Common settings'),
            'pageHeading'     => t('settings', 'Common settings'),
            'pageBreadcrumbs' => [
                t('settings', 'Settings') => createUrl('settings/index'),
                t('settings', 'Common settings'),
            ],
        ]);

        $this->render('index', compact('commonModel'));
    }

    /**
     * @param string $out
     * @return string
     */
    public function _commonSettingsAutoUpdateWarningMessage($out)
    {
        $out = [];
        $out[] = CHtml::tag('strong', [], t('settings', 'Warning!'));
        $out[] = t('settings', 'While this feature should be very safe for use, please make sure you also understand the downsides of enabling it.');
        $out[] = t('settings', 'Since this is an automated process, there are chances for an update to break your app.');
        $out[] = t('settings', 'Please make sure you have some sort of backup process in place so that you can restore your app in case things go wrong.');
        $out[] = t('settings', 'If you have the {ext} extension enabled, when an auto-update runs, it will also create a backup for you automatically!', [
            '{ext}' => CHtml::link(t('settings', 'Backup manager'), 'https://codecanyon.net/item/backup-manager-for-mailwizz-ema/8184361?ref=twisted1919', [
                'target' => '_blank',
                'style'  => 'color: #fff',
            ]),
        ]);
        $out[] = t('settings', 'Also note that we expect the following functions to be enabled on your server: {funcs} and your server must also have following binaries installed: {binaries}', [
            '{funcs}'    => sprintf('<strong>%s</strong>', 'exec'),
            '{binaries}' => sprintf('<strong>%s</strong>', 'curl, unzip, cp'),
        ]);

        return implode('<br />', $out);
    }

    /**
     * Handle the settings for system urls
     *
     * @return void
     * @throws CException
     */
    public function actionSystem_urls()
    {
        $apps = apps()->getWebApps();

        /** @var OptionUrl $optionUrl */
        $optionUrl = container()->get(OptionUrl::class);

        if (request()->getIsPostRequest()) {
            $scheme = OptionUrl::SCHEME_HTTP;
            if (request()->getPost('scheme', OptionUrl::SCHEME_HTTP) == OptionUrl::SCHEME_HTTPS) {
                $scheme = OptionUrl::SCHEME_HTTPS;
            }
            $optionUrl->saveAttributes(['scheme' => $scheme]);

            $optionUrl->regenerateSystemUrls();

            notify()->addSuccess(t('app', 'Your form has been successfully saved!'));

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller' => $this,
                'success'    => notify()->getHasSuccess(),
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['settings/system_urls']);
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('settings', 'System urls'),
            'pageHeading'     => t('settings', 'System urls'),
            'pageBreadcrumbs' => [
                t('settings', 'Settings') => createUrl('settings/index'),
                t('settings', 'System urls'),
            ],
        ]);

        // the scheme
        $scheme = $optionUrl->scheme;

        $this->render('system-urls', compact('apps', 'scheme', 'optionUrl'));
    }

    /**
     * Handle the settings for importer/exporter
     *
     * @return void
     * @throws CException
     */
    public function actionImport_export()
    {
        /** @var OptionImporter $importModel */
        $importModel = container()->get(OptionImporter::class);

        /** @var OptionExporter $exportModel */
        $exportModel = container()->get(OptionExporter::class);

        if (request()->getIsPostRequest()) {
            $importModel->attributes = (array)request()->getPost($importModel->getModelName(), []);
            $exportModel->attributes = (array)request()->getPost($exportModel->getModelName(), []);

            if (!$importModel->save() || !$exportModel->save()) {
                notify()->addError(t('app', 'Your form contains a few errors, please fix them and try again!'));
            } else {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller'   => $this,
                'success'      => notify()->getHasSuccess(),
                'importModel'  => $importModel,
                'exportModel'  => $exportModel,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['settings/import_export']);
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('settings', 'Import/Export settings'),
            'pageHeading'     => t('settings', 'Import/Export settings'),
            'pageBreadcrumbs' => [
                t('settings', 'Settings') => createUrl('settings/index'),
                t('settings', 'Import/Export settings'),
            ],
        ]);

        $this->render('import-export', compact('importModel', 'exportModel'));
    }

    /**
     * Handle the settings for console commands
     *
     * @return void
     * @throws CException
     */
    public function actionCron()
    {
        /** @var OptionCronDelivery $cronDeliveryModel */
        $cronDeliveryModel = container()->get(OptionCronDelivery::class);

        /** @var OptionCronProcessDeliveryBounce $cronLogsModel */
        $cronLogsModel = container()->get(OptionCronProcessDeliveryBounce::class);

        /** @var OptionCronProcessSubscribers $cronSubscribersModel */
        $cronSubscribersModel = container()->get(OptionCronProcessSubscribers::class);

        /** @var OptionCronProcessResponders $cronRespondersModel */
        $cronRespondersModel = container()->get(OptionCronProcessResponders::class);

        /** @var OptionCronProcessBounceServers $cronBouncesModel */
        $cronBouncesModel = container()->get(OptionCronProcessBounceServers::class);

        /** @var OptionCronProcessFeedbackLoopServers $cronFeedbackModel */
        $cronFeedbackModel = container()->get(OptionCronProcessFeedbackLoopServers::class);

        /** @var OptionCronProcessEmailBoxMonitors $cronEmailBoxModel */
        $cronEmailBoxModel = container()->get(OptionCronProcessEmailBoxMonitors::class);

        /** @var OptionCronProcessTransactionalEmails $cronTransEmailsModel */
        $cronTransEmailsModel = container()->get(OptionCronProcessTransactionalEmails::class);

        /** @var OptionCronDeleteLogs $cronDeleteLogsModel */
        $cronDeleteLogsModel = container()->get(OptionCronDeleteLogs::class);

        /** @var OptionCronProcessSendingDomains $cronSendingDomainsModel */
        $cronSendingDomainsModel = container()->get(OptionCronProcessSendingDomains::class);

        /** @var OptionCronProcessTrackingDomains $cronTrackingDomainsModel */
        $cronTrackingDomainsModel = container()->get(OptionCronProcessTrackingDomains::class);

        if (request()->getIsPostRequest()) {
            $cronDeliveryModel->attributes          = (array)request()->getPost($cronDeliveryModel->getModelName(), []);
            $cronLogsModel->attributes              = (array)request()->getPost($cronLogsModel->getModelName(), []);
            $cronSubscribersModel->attributes       = (array)request()->getPost($cronSubscribersModel->getModelName(), []);
            $cronRespondersModel->attributes        = (array)request()->getPost($cronRespondersModel->getModelName(), []);
            $cronBouncesModel->attributes           = (array)request()->getPost($cronBouncesModel->getModelName(), []);
            $cronFeedbackModel->attributes          = (array)request()->getPost($cronFeedbackModel->getModelName(), []);
            $cronEmailBoxModel->attributes          = (array)request()->getPost($cronEmailBoxModel->getModelName(), []);
            $cronTransEmailsModel->attributes       = (array)request()->getPost($cronTransEmailsModel->getModelName(), []);
            $cronDeleteLogsModel->attributes        = (array)request()->getPost($cronDeleteLogsModel->getModelName(), []);
            $cronSendingDomainsModel->attributes    = (array)request()->getPost($cronSendingDomainsModel->getModelName(), []);
            $cronTrackingDomainsModel->attributes   = (array)request()->getPost($cronTrackingDomainsModel->getModelName(), []);

            $models = [
                $cronDeliveryModel, $cronLogsModel, $cronSubscribersModel, $cronRespondersModel,
                $cronBouncesModel, $cronFeedbackModel, $cronEmailBoxModel, $cronTransEmailsModel,
                $cronDeleteLogsModel, $cronSendingDomainsModel, $cronTrackingDomainsModel,
            ];

            $saved = true;
            foreach ($models as $model) {
                if (!$model->save()) {
                    $saved = false;
                }
            }

            if (!$saved) {
                notify()->addError(t('app', 'Your form contains a few errors, please fix them and try again!'));
            } else {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller'                => $this,
                'success'                   => notify()->getHasSuccess(),
                'cronDeliveryModel'         => $cronDeliveryModel,
                'cronLogsModel'             => $cronLogsModel,
                'cronSubscribersModel'      => $cronSubscribersModel,
                'cronRespondersModel'       => $cronRespondersModel,
                'cronBouncesModel'          => $cronBouncesModel,
                'cronFeedbackModel'         => $cronFeedbackModel,
                'cronEmailBoxModel'         => $cronEmailBoxModel,
                'cronTransEmailsModel'      => $cronTransEmailsModel,
                'cronDeleteLogsModel'       => $cronDeleteLogsModel,
                'cronSendingDomainsModel'   => $cronSendingDomainsModel,
                'cronTrackingDomainsModel'  => $cronTrackingDomainsModel,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['settings/cron']);
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('settings', 'Cron jobs settings'),
            'pageHeading'     => t('settings', 'Cron jobs settings'),
            'pageBreadcrumbs' => [
                t('settings', 'Settings') => createUrl('settings/index'),
                t('settings', 'Cron jobs settings'),
            ],
        ]);

        $this->render('cron', compact(
            'cronDeliveryModel',
            'cronLogsModel',
            'cronSubscribersModel',
            'cronRespondersModel',
            'cronBouncesModel',
            'cronFeedbackModel',
            'cronEmailBoxModel',
            'cronTransEmailsModel',
            'cronDeleteLogsModel',
            'cronSendingDomainsModel',
            'cronTrackingDomainsModel'
        ));
    }

    /**
     * Handle the settings for email templates
     *
     * @param string $type
     *
     * @return void
     * @throws CException
     */
    public function actionEmail_templates($type = 'common')
    {
        $types = OptionEmailTemplate::getTypesList();
        $type  = OptionEmailTemplate::getTypeById($type);

        $model = new OptionEmailTemplate($type['id']);
        $model->fieldDecorator->onHtmlOptionsSetup = [$this, '_setupEditorOptions'];

        if (request()->getIsPostRequest()) {
            $model->attributes = (array)request()->getPost($model->getModelName(), []);
            /** @var array $post */
            $post = (array)request()->getOriginalPost('', []);
            if (isset($post[$model->getModelName()][$type['id']])) {
                $model->{$type['id']} = $post[$model->getModelName()][$type['id']];
            }

            if (!$model->save()) {
                notify()->addError(t('app', 'Your form contains a few errors, please fix them and try again!'));
            } else {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller' => $this,
                'success'    => notify()->getHasSuccess(),
                'model'      => $model,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['settings/email_templates']);
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('settings', 'Email templates settings'),
            'pageHeading'     => t('settings', 'Email templates settings'),
            'pageBreadcrumbs' => [
                t('settings', 'Settings') => createUrl('settings/index'),
                t('settings', 'Email templates'),
            ],
        ]);

        $this->render('email-templates', compact('model', 'types', 'type'));
    }

    /**
     * Handle the settings for email blacklist checks
     *
     * @return void
     * @throws CException
     */
    public function actionEmail_blacklist()
    {
        /** @var OptionEmailBlacklist $blacklistModel */
        $blacklistModel = container()->get(OptionEmailBlacklist::class);

        if (request()->getIsPostRequest()) {
            $blacklistModel->unsetAttributes();
            $blacklistModel->attributes = (array)request()->getPost($blacklistModel->getModelName(), []);

            if (!$blacklistModel->save()) {
                notify()->addError(t('app', 'Your form contains a few errors, please fix them and try again!'));
            } else {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));

                // since 1.6.4
                if (request()->getPost('regex_test_email')) {
                    notify()->clearAll();

                    $regexes = CommonHelper::getArrayFromString((string)$blacklistModel->regular_expressions, "\n");
                    $emails  = CommonHelper::getArrayFromString((string)request()->getPost('regex_test_email'));
                    foreach ($emails as $email) {
                        if (!FilterVarHelper::email($email)) {
                            notify()->addError(t('settings', '{email} is invalid!', [
                                '{email}' => html_encode((string)$email),
                            ]));
                            continue;
                        }
                        foreach ($regexes as $regex) {
                            if (preg_match($regex, $email)) {
                                notify()->addError(t('settings', '{email} has been matched by {regex}!', [
                                    '{email}'   => sprintf('<strong>%s</strong>', $email),
                                    '{regex}'   => sprintf('<strong>%s</strong>', html_encode((string)$regex)),
                                ]));
                                break;
                            }
                        }
                    }

                    if (!notify()->getHasError()) {
                        notify()->addSuccess(t('settings', 'No regex matched given email addresses!'));
                    }
                }
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller'        => $this,
                'success'           => notify()->getHasSuccess(),
                'blacklistModel'    => $blacklistModel,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['settings/email_blacklist']);
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('settings', 'Email blacklist settings'),
            'pageHeading'     => t('settings', 'Email blacklist settings'),
            'pageBreadcrumbs' => [
                t('settings', 'Settings') => createUrl('settings/index'),
                t('settings', 'Email blacklist settings'),
            ],
        ]);

        $this->render('email-blacklist', compact('blacklistModel'));
    }

    /**
     * Handle the settings for api ip access
     *
     * @return void
     * @throws CException
     */
    public function actionApi_ip_access()
    {
        /** @var OptionApiIpAccess $model */
        $model = container()->get(OptionApiIpAccess::class);

        if (request()->getIsPostRequest()) {
            $model->unsetAttributes();
            $model->attributes = (array)request()->getPost($model->getModelName(), []);

            if (!$model->save()) {
                notify()->addError(t('app', 'Your form contains a few errors, please fix them and try again!'));
            } else {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller' => $this,
                'success'    => notify()->getHasSuccess(),
                'model'      => $model,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['settings/api_ip_access']);
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('settings', 'Api settings'),
            'pageHeading'     => t('settings', 'Api settings'),
            'pageBreadcrumbs' => [
                t('settings', 'Settings') => createUrl('settings/index'),
                t('settings', 'Api settings') => createUrl('settings/api'),
                t('settings', 'IP access'),
            ],
        ]);

        $this->render('api-ip-access', compact('model'));
    }

    /**
     * Handle the common settings for customers options
     *
     * @return void
     * @throws CException
     */
    public function actionCustomer_common()
    {
        /** @var OptionCustomerCommon $model */
        $model = container()->get(OptionCustomerCommon::class);

        if (request()->getIsPostRequest()) {
            $model->unsetAttributes();
            $model->attributes = (array)request()->getPost($model->getModelName(), []);
            /** @var array $post */
            $post = (array)request()->getOriginalPost('', []);
            if (isset($post[$model->getModelName()]['notification_message'])) {
                $model->notification_message = (string)ioFilter()->purify($post[$model->getModelName()]['notification_message']);
            }

            if (!$model->save()) {
                notify()->addError(t('app', 'Your form contains a few errors, please fix them and try again!'));
            } else {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller' => $this,
                'success'    => notify()->getHasSuccess(),
                'model'      => $model,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['settings/customer_common']);
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('settings', 'Customers common settings'),
            'pageHeading'     => t('settings', 'Customers common settings'),
            'pageBreadcrumbs' => [
                t('settings', 'Settings')  => createUrl('settings/index'),
                t('settings', 'Customers') => createUrl('settings/customer_common'),
                t('settings', 'Common'),
            ],
        ]);

        $model->fieldDecorator->onHtmlOptionsSetup = [$this, '_setupEditorOptions'];

        $this->render('customer-common', compact('model'));
    }

    /**
     * Handle the settings for customer server options
     *
     * @return void
     * @throws CException
     */
    public function actionCustomer_servers()
    {
        /** @var OptionCustomerServers $model */
        $model = container()->get(OptionCustomerServers::class);

        if (request()->getIsPostRequest()) {
            $model->unsetAttributes();
            $model->attributes = (array)request()->getPost($model->getModelName(), []);

            if (!$model->save()) {
                notify()->addError(t('app', 'Your form contains a few errors, please fix them and try again!'));
            } else {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller' => $this,
                'success'    => notify()->getHasSuccess(),
                'model'      => $model,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['settings/customer_servers']);
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('settings', 'Customers server settings'),
            'pageHeading'     => t('settings', 'Customers server settings'),
            'pageBreadcrumbs' => [
                t('settings', 'Settings')  => createUrl('settings/index'),
                t('settings', 'Customers') => createUrl('settings/customer_common'),
                t('settings', 'Servers'),
            ],
        ]);

        $this->render('customer-servers', compact('model'));
    }

    /**
     * Handle the settings for customer domains options
     *
     * @return void
     * @throws CException
     */
    public function actionCustomer_domains()
    {
        /** @var OptionCustomerTrackingDomains $tracking */
        $tracking = container()->get(OptionCustomerTrackingDomains::class);

        /** @var OptionCustomerSendingDomains $sending */
        $sending  = container()->get(OptionCustomerSendingDomains::class);

        if (request()->getIsPostRequest()) {
            $tracking->attributes = (array)request()->getPost($tracking->getModelName(), []);
            $sending->attributes  = (array)request()->getPost($sending->getModelName(), []);

            if (!$tracking->save() || !$sending->save()) {
                notify()->addError(t('app', 'Your form contains a few errors, please fix them and try again!'));
            } else {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller' => $this,
                'success'    => notify()->getHasSuccess(),
                'models'     => compact('tracking', 'sending'),
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['settings/customer_domains']);
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('settings', 'Customers domains settings'),
            'pageHeading'     => t('settings', 'Customers domains settings'),
            'pageBreadcrumbs' => [
                t('settings', 'Settings')  => createUrl('settings/index'),
                t('settings', 'Customers') => createUrl('settings/customer_common'),
                t('settings', 'Domains'),
            ],
        ]);

        $this->render('customer-domains', compact('tracking', 'sending'));
    }

    /**
     * Handle the settings for customer lists options
     *
     * @return void
     * @throws CException
     */
    public function actionCustomer_lists()
    {
        /** @var OptionCustomerLists $model */
        $model = container()->get(OptionCustomerLists::class);

        if (request()->getIsPostRequest()) {
            $model->unsetAttributes();
            $model->attributes = (array)request()->getPost($model->getModelName(), []);

            if (!$model->save()) {
                notify()->addError(t('app', 'Your form contains a few errors, please fix them and try again!'));
            } else {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller' => $this,
                'success'    => notify()->getHasSuccess(),
                'model'      => $model,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['settings/customer_lists']);
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('settings', 'Customers lists settings'),
            'pageHeading'     => t('settings', 'Customers lists settings'),
            'pageBreadcrumbs' => [
                t('settings', 'Settings')  => createUrl('settings/index'),
                t('settings', 'Customers') => createUrl('settings/customer_common'),
                t('settings', 'Lists'),
            ],
        ]);

        $this->render('customer-lists', compact('model'));
    }

    /**
     * Handle the settings for customer registration options
     *
     * @return void
     * @throws CException
     */
    public function actionCustomer_registration()
    {
        /** @var OptionCustomerRegistration $model */
        $model = container()->get(OptionCustomerRegistration::class);
        $model->fieldDecorator->onHtmlOptionsSetup = [$this, '_setupEditorOptions'];

        if (request()->getIsPostRequest()) {
            $model->unsetAttributes();
            $model->attributes = (array)request()->getPost($model->getModelName(), []);
            /** @var array $post */
            $post = (array)request()->getOriginalPost('', []);
            if (isset($post[$model->getModelName()]['welcome_email_content'])) {
                $model->welcome_email_content = (string)ioFilter()->purify($post[$model->getModelName()]['welcome_email_content']);
            }

            if (!$model->save()) {
                notify()->addError(t('app', 'Your form contains a few errors, please fix them and try again!'));
            } else {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller' => $this,
                'success'    => notify()->getHasSuccess(),
                'model'      => $model,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['settings/customer_registration']);
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('settings', 'Customers registration settings'),
            'pageHeading'     => t('settings', 'Customers registration settings'),
            'pageBreadcrumbs' => [
                t('settings', 'Settings')  => createUrl('settings/index'),
                t('settings', 'Customers') => createUrl('settings/customer_common'),
                t('settings', 'Registration'),
            ],
        ]);

        $this->render('customer-registration', compact('model'));
    }

    /**
     * Handle the settings for customer api options
     *
     * @return void
     * @throws CException
     */
    public function actionCustomer_api()
    {
        /** @var OptionCustomerApi $model */
        $model = container()->get(OptionCustomerApi::class);

        if (request()->getIsPostRequest()) {
            $model->unsetAttributes();
            $model->attributes = (array)request()->getPost($model->getModelName(), []);

            if (!$model->save()) {
                notify()->addError(t('app', 'Your form contains a few errors, please fix them and try again!'));
            } else {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller' => $this,
                'success'    => notify()->getHasSuccess(),
                'model'      => $model,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['settings/customer_api']);
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('settings', 'Customers API settings'),
            'pageHeading'     => t('settings', 'Customers API settings'),
            'pageBreadcrumbs' => [
                t('settings', 'Settings')  => createUrl('settings/index'),
                t('settings', 'Customers') => createUrl('settings/customer_common'),
                t('settings', 'API'),
            ],
        ]);

        $this->render('customer-api', compact('model'));
    }

    /**
     * Handle the settings for customer subaccounts options
     *
     * @return void
     * @throws CException
     */
    public function actionCustomer_subaccounts()
    {
        /** @var OptionCustomerSubaccounts $model */
        $model = container()->get(OptionCustomerSubaccounts::class);

        if (request()->getIsPostRequest()) {
            $model->unsetAttributes();
            $model->attributes = (array)request()->getPost($model->getModelName(), []);

            if (!$model->save()) {
                notify()->addError(t('app', 'Your form contains a few errors, please fix them and try again!'));
            } else {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller' => $this,
                'success'    => notify()->getHasSuccess(),
                'model'      => $model,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['settings/customer_subaccounts']);
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('settings', 'Customers subaccounts settings'),
            'pageHeading'     => t('settings', 'Customers subaccounts settings'),
            'pageBreadcrumbs' => [
                t('settings', 'Settings')  => createUrl('settings/index'),
                t('settings', 'Customers') => createUrl('settings/customer_common'),
                t('settings', 'Subaccounts'),
            ],
        ]);

        $this->render('customer-subaccounts', compact('model'));
    }

    /**
     * Handle the settings for customer sending options
     *
     * @return void
     * @throws CException
     */
    public function actionCustomer_sending()
    {
        /** @var OptionCustomerSending $model */
        $model = container()->get(OptionCustomerSending::class);

        if (request()->getIsPostRequest()) {
            $model->unsetAttributes();
            $model->attributes = (array)request()->getPost($model->getModelName(), []);
            /** @var array $post */
            $post = (array)request()->getOriginalPost('', []);
            if (isset($post[$model->getModelName()]['quota_notify_email_content'])) {
                $model->quota_notify_email_content = (string)ioFilter()->purify($post[$model->getModelName()]['quota_notify_email_content']);
            }

            if (!$model->save()) {
                notify()->addError(t('app', 'Your form contains a few errors, please fix them and try again!'));
            } else {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller' => $this,
                'success'    => notify()->getHasSuccess(),
                'model'      => $model,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['settings/customer_sending']);
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('settings', 'Customers sending settings'),
            'pageHeading'     => t('settings', 'Customers sending settings'),
            'pageBreadcrumbs' => [
                t('settings', 'Settings')  => createUrl('settings/index'),
                t('settings', 'Customers') => createUrl('settings/customer_common'),
                t('settings', 'Sending'),
            ],
        ]);

        $model->fieldDecorator->onHtmlOptionsSetup = [$this, '_setupEditorOptions'];

        $this->render('customer-sending', compact('model'));
    }

    /**
     * Handle the settings for customer quota counters options
     *
     * @return void
     * @throws CException
     */
    public function actionCustomer_quota_counters()
    {
        /** @var OptionCustomerQuotaCounters $model */
        $model = container()->get(OptionCustomerQuotaCounters::class);

        if (request()->getIsPostRequest()) {
            $model->unsetAttributes();
            $model->attributes = (array)request()->getPost($model->getModelName(), []);

            if (!$model->save()) {
                notify()->addError(t('app', 'Your form contains a few errors, please fix them and try again!'));
            } else {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller' => $this,
                'success'    => notify()->getHasSuccess(),
                'model'      => $model,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['settings/customer_quota_counters']);
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('settings', 'Customers quota counters settings'),
            'pageHeading'     => t('settings', 'Customers quota counters settings'),
            'pageBreadcrumbs' => [
                t('settings', 'Settings')  => createUrl('settings/index'),
                t('settings', 'Customers') => createUrl('settings/customer_common'),
                t('settings', 'Quota counters'),
            ],
        ]);

        $this->render('customer-quota-counters', compact('model'));
    }

    /**
     * Handle the settings for customer campaigns options
     *
     * @return void
     * @throws CException
     */
    public function actionCustomer_campaigns()
    {
        /** @var OptionCustomerCampaigns $model */
        $model = container()->get(OptionCustomerCampaigns::class);

        if (request()->getIsPostRequest()) {
            $model->unsetAttributes();
            $model->attributes = (array)request()->getPost($model->getModelName(), []);
            /** @var array $post */
            $post = (array)request()->getOriginalPost('', []);
            if (isset($post[$model->getModelName()]['email_header'])) {
                $model->email_header = (string)ioFilter()->purify($post[$model->getModelName()]['email_header']);
            }

            if (isset($post[$model->getModelName()]['email_footer'])) {
                $model->email_footer = (string)ioFilter()->purify($post[$model->getModelName()]['email_footer']);
            }

            if (!$model->save()) {
                notify()->addError(t('app', 'Your form contains a few errors, please fix them and try again!'));
            } else {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller' => $this,
                'success'    => notify()->getHasSuccess(),
                'model'      => $model,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['settings/customer_campaigns']);
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('settings', 'Customers campaigns settings'),
            'pageHeading'     => t('settings', 'Customers campaigns settings'),
            'pageBreadcrumbs' => [
                t('settings', 'Settings')  => createUrl('settings/index'),
                t('settings', 'Customers') => createUrl('settings/customer_common'),
                t('settings', 'Campaigns'),
            ],
        ]);

        $model->fieldDecorator->onHtmlOptionsSetup = [$this, '_addCustomerCampaignEmailFooterEditor'];

        $this->render('customer-campaigns', compact('model'));
    }

    /**
     * Handle the settings for customer surveys options
     *
     * @return void
     * @throws CException
     */
    public function actionCustomer_surveys()
    {
        /** @var OptionCustomerSurveys $model */
        $model = container()->get(OptionCustomerSurveys::class);

        if (request()->getIsPostRequest()) {
            $model->unsetAttributes();
            $model->attributes = (array)request()->getPost($model->getModelName(), []);

            if (!$model->save()) {
                notify()->addError(t('app', 'Your form contains a few errors, please fix them and try again!'));
            } else {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller' => $this,
                'success'    => notify()->getHasSuccess(),
                'model'      => $model,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['settings/customer_surveys']);
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('settings', 'Customers surveys settings'),
            'pageHeading'     => t('settings', 'Customers surveys settings'),
            'pageBreadcrumbs' => [
                t('settings', 'Settings')  => createUrl('settings/index'),
                t('settings', 'Customers') => createUrl('settings/customer_common'),
                t('settings', 'Surveys'),
            ],
        ]);

        $this->render('customer-surveys', compact('model'));
    }

    /**
     * Handle the settings for customer cdn options
     *
     * @return void
     * @throws CException
     */
    public function actionCustomer_cdn()
    {
        /** @var OptionCustomerCdn $model */
        $model = container()->get(OptionCustomerCdn::class);

        if (request()->getIsPostRequest()) {
            $model->unsetAttributes();
            $model->attributes = (array)request()->getPost($model->getModelName(), []);

            if (!$model->save()) {
                notify()->addError(t('app', 'Your form contains a few errors, please fix them and try again!'));
            } else {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller' => $this,
                'success'    => notify()->getHasSuccess(),
                'model'      => $model,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['settings/customer_cdn']);
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('settings', 'Customers CDN settings'),
            'pageHeading'     => t('settings', 'Customers CDN settings'),
            'pageBreadcrumbs' => [
                t('settings', 'Settings')  => createUrl('settings/index'),
                t('settings', 'Customers') => createUrl('settings/customer_common'),
                t('settings', 'CDN'),
            ],
        ]);

        $this->render('customer-cdn', compact('model'));
    }

    /**
     * Handle the settings for campaign attachments
     *
     * @return void
     * @throws CException
     */
    public function actionCampaign_attachments()
    {
        /** @var OptionCampaignAttachment $model */
        $model = container()->get(OptionCampaignAttachment::class);

        if (request()->getIsPostRequest()) {
            $model->unsetAttributes();
            $model->attributes = (array)request()->getPost($model->getModelName(), []);

            if (!$model->save()) {
                notify()->addError(t('app', 'Your form contains a few errors, please fix them and try again!'));
            } else {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller' => $this,
                'success'    => notify()->getHasSuccess(),
                'model'      => $model,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['settings/campaign_attachments']);
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('settings', 'Campaigns attachments settings'),
            'pageHeading'     => t('settings', 'Campaigns attachments settings'),
            'pageBreadcrumbs' => [
                t('settings', 'Settings')  => createUrl('settings/index'),
                t('settings', 'Campaigns') => createUrl('settings/campaign_attachments'),
                t('settings', 'Attachments'),
            ],
        ]);

        $this->render('campaign-attachments', compact('model'));
    }

    /**
     * Handle the settings for campaign available tags
     *
     * @return void
     * @throws CException
     */
    public function actionCampaign_template_tags()
    {
        /** @var OptionCampaignTemplateTag $model */
        $model = container()->get(OptionCampaignTemplateTag::class);

        if (request()->getIsPostRequest()) {
            $model->unsetAttributes();
            $model->attributes = (array)request()->getPost($model->getModelName(), []);

            if (!$model->save()) {
                notify()->addError(t('app', 'Your form contains a few errors, please fix them and try again!'));
            } else {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller' => $this,
                'success'    => notify()->getHasSuccess(),
                'model'      => $model,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['settings/campaign_template_tags']);
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('settings', 'Campaigns template tags settings'),
            'pageHeading'     => t('settings', 'Campaigns template tags settings'),
            'pageBreadcrumbs' => [
                t('settings', 'Settings')  => createUrl('settings/index'),
                t('settings', 'Campaigns') => createUrl('settings/campaign_attachments'),
                t('settings', 'Template tags'),
            ],
        ]);

        $this->render('campaign-template-tags', compact('model'));
    }

    /**
     * Handle the settings for campaigns to exclude various ips from tracking(opens/clicks)
     *
     * @return void
     * @throws CException
     */
    public function actionCampaign_exclude_ips_from_tracking()
    {
        /** @var OptionCampaignExcludeIpsFromTracking $model */
        $model = container()->get(OptionCampaignExcludeIpsFromTracking::class);

        if (request()->getIsPostRequest()) {
            $model->unsetAttributes();
            $model->attributes = (array)request()->getPost($model->getModelName(), []);

            if (!$model->save()) {
                notify()->addError(t('app', 'Your form contains a few errors, please fix them and try again!'));
            } else {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller' => $this,
                'success'    => notify()->getHasSuccess(),
                'model'      => $model,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['settings/campaign_exclude_ips_from_tracking']);
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('settings', 'Exclude IPs from tracking'),
            'pageHeading'     => t('settings', 'Exclude IPs from tracking'),
            'pageBreadcrumbs' => [
                t('settings', 'Settings')  => createUrl('settings/index'),
                t('settings', 'Campaigns') => createUrl('settings/campaign_attachments'),
                t('settings', 'Exclude IPs from tracking'),
            ],
        ]);

        $this->render('campaign-exclude-ips-from-tracking', compact('model'));
    }

    /**
     * Handle the settings for campaigns to blacklist various words from subject and/or content
     *
     * @return void
     * @throws CException
     */
    public function actionCampaign_blacklist_words()
    {
        /** @var OptionCampaignBlacklistWords $model */
        $model = container()->get(OptionCampaignBlacklistWords::class);

        if (request()->getIsPostRequest()) {
            $model->unsetAttributes();
            $model->attributes = (array)request()->getPost($model->getModelName(), []);

            if (!$model->save()) {
                notify()->addError(t('app', 'Your form contains a few errors, please fix them and try again!'));
            } else {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller' => $this,
                'success'    => notify()->getHasSuccess(),
                'model'      => $model,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['settings/campaign_blacklist_words']);
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('settings', 'Blacklist words'),
            'pageHeading'     => t('settings', 'Blacklist words'),
            'pageBreadcrumbs' => [
                t('settings', 'Settings')  => createUrl('settings/index'),
                t('settings', 'Campaigns') => createUrl('settings/campaign_attachments'),
                t('settings', 'Blacklist words'),
            ],
        ]);

        $this->render('campaign-blacklist-words', compact('model'));
    }

    /**
     * Handle the settings for campaign template engine options
     *
     * @return void
     * @throws CException
     */
    public function actionCampaign_template_engine()
    {
        /** @var OptionCampaignTemplateEngine $model */
        $model = container()->get(OptionCampaignTemplateEngine::class);

        if (request()->getIsPostRequest()) {
            $model->unsetAttributes();
            $model->attributes = (array)request()->getPost($model->getModelName(), []);

            if (!$model->save()) {
                notify()->addError(t('app', 'Your form contains a few errors, please fix them and try again!'));
            } else {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller' => $this,
                'success'    => notify()->getHasSuccess(),
                'model'      => $model,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['settings/campaign_template_engine']);
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('settings', 'Template engine settings'),
            'pageHeading'     => t('settings', 'Template engine settings'),
            'pageBreadcrumbs' => [
                t('settings', 'Settings')  => createUrl('settings/index'),
                t('settings', 'Campaigns') => createUrl('settings/campaign_attachments'),
                t('settings', 'Template engine'),
            ],
        ]);

        $this->render('campaign-template-engine', compact('model'));
    }

    /**
     * Handle the settings for campaign webhooks options
     *
     * @return void
     * @throws CException
     */
    public function actionCampaign_webhooks()
    {
        /** @var OptionCampaignWebhooks $model */
        $model = container()->get(OptionCampaignWebhooks::class);

        if (request()->getIsPostRequest()) {
            $model->unsetAttributes();
            $model->attributes = (array)request()->getPost($model->getModelName(), []);

            if (!$model->save()) {
                notify()->addError(t('app', 'Your form contains a few errors, please fix them and try again!'));
            } else {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller' => $this,
                'success'    => notify()->getHasSuccess(),
                'model'      => $model,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['settings/campaign_webhooks']);
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('settings', 'Campaigns webhooks settings'),
            'pageHeading'     => t('settings', 'Campaigns webhooks settings'),
            'pageBreadcrumbs' => [
                t('settings', 'Settings')  => createUrl('settings/index'),
                t('settings', 'Campaigns') => createUrl('settings/campaign_attachments'),
                t('settings', 'Webhooks'),
            ],
        ]);

        $this->render('campaign-webhooks', compact('model'));
    }

    /**
     * Handle the settings for misc campaign options
     *
     * @return void
     * @throws CException
     */
    public function actionCampaign_misc()
    {
        /** @var OptionCampaignMisc $model */
        $model = container()->get(OptionCampaignMisc::class);

        if (request()->getIsPostRequest()) {
            $model->unsetAttributes();
            $model->attributes = (array)request()->getPost($model->getModelName(), []);

            if (!$model->save()) {
                notify()->addError(t('app', 'Your form contains a few errors, please fix them and try again!'));
            } else {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller' => $this,
                'success'    => notify()->getHasSuccess(),
                'model'      => $model,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['settings/campaign_misc']);
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('settings', 'Campaigns miscellaneous'),
            'pageHeading'     => t('settings', 'Campaigns miscellaneous'),
            'pageBreadcrumbs' => [
                t('settings', 'Settings')  => createUrl('settings/index'),
                t('settings', 'Campaigns') => createUrl('settings/campaign_attachments'),
                t('settings', 'Miscellaneous'),
            ],
        ]);

        $this->render('campaign-misc', compact('model'));
    }

    /**
     * Handle the settings for campaign options
     *
     * @return void
     * @throws CException
     */
    public function actionCampaign_options()
    {
        /** @var OptionCampaignOptions $model */
        $model = container()->get(OptionCampaignOptions::class);

        if (request()->getIsPostRequest()) {
            $model->unsetAttributes();
            $model->attributes = (array)request()->getPost($model->getModelName(), []);

            if (!$model->save()) {
                notify()->addError(t('app', 'Your form contains a few errors, please fix them and try again!'));
            } else {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller' => $this,
                'success'    => notify()->getHasSuccess(),
                'model'      => $model,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['settings/campaign_options']);
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('settings', 'Campaign options settings'),
            'pageHeading'     => t('settings', 'Campaign options settings'),
            'pageBreadcrumbs' => [
                t('settings', 'Settings') => createUrl('settings/index'),
                t('settings', 'Campaign options'),
            ],
        ]);

        $this->render('campaign-options', compact('model'));
    }

    /**
     * Handle the settings for monetization options
     *
     * @return void
     * @throws CException
     */
    public function actionMonetization()
    {
        /** @var OptionMonetizationMonetization $model */
        $model = container()->get(OptionMonetizationMonetization::class);

        if (request()->getIsPostRequest()) {
            $model->unsetAttributes();
            $model->attributes = (array)request()->getPost($model->getModelName(), []);

            if (!$model->save()) {
                notify()->addError(t('app', 'Your form contains a few errors, please fix them and try again!'));
            } else {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller' => $this,
                'success'    => notify()->getHasSuccess(),
                'model'      => $model,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['settings/monetization']);
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('settings', 'Monetization settings'),
            'pageHeading'     => t('settings', 'Monetization settings'),
            'pageBreadcrumbs' => [
                t('settings', 'Settings')  => createUrl('settings/index'),
                t('settings', 'Monetization') => createUrl('settings/monetization'),
            ],
        ]);

        $this->render('monetization', compact('model'));
    }

    /**
     * Handle the settings for monetization orders
     *
     * @return void
     * @throws CException
     */
    public function actionMonetization_orders()
    {
        /** @var OptionMonetizationOrders $model */
        $model = container()->get(OptionMonetizationOrders::class);

        if (request()->getIsPostRequest()) {
            $model->unsetAttributes();
            $model->attributes = (array)request()->getPost($model->getModelName(), []);

            if (!$model->save()) {
                notify()->addError(t('app', 'Your form contains a few errors, please fix them and try again!'));
            } else {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller' => $this,
                'success'    => notify()->getHasSuccess(),
                'model'      => $model,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['settings/monetization_orders']);
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('settings', 'Monetization orders settings'),
            'pageHeading'     => t('settings', 'Monetization orders settings'),
            'pageBreadcrumbs' => [
                t('settings', 'Settings')  => createUrl('settings/index'),
                t('settings', 'Monetization') => createUrl('settings/monetization'),
                t('settings', 'Orders'),
            ],
        ]);

        $this->render('monetization-orders', compact('model'));
    }

    /**
     * Handle the settings for monetization invoices
     *
     * @return void
     * @throws CException
     */
    public function actionMonetization_invoices()
    {
        /** @var OptionMonetizationInvoices $model */
        $model = container()->get(OptionMonetizationInvoices::class);

        if (request()->getIsPostRequest()) {
            $model->unsetAttributes();
            $model->attributes = (array)request()->getPost($model->getModelName(), []);

            if (!$model->save()) {
                notify()->addError(t('app', 'Your form contains a few errors, please fix them and try again!'));
            } else {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller' => $this,
                'success'    => notify()->getHasSuccess(),
                'model'      => $model,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['settings/monetization_invoices']);
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('settings', 'Monetization invoices settings'),
            'pageHeading'     => t('settings', 'Monetization invoices settings'),
            'pageBreadcrumbs' => [
                t('settings', 'Settings')  => createUrl('settings/index'),
                t('settings', 'Monetization') => createUrl('settings/monetization'),
                t('settings', 'Invoices'),
            ],
        ]);

        $this->render('monetization-invoices', compact('model'));
    }

    /**
     * Handle the settings for license options
     *
     * @return void
     * @throws CException
     */
    public function actionLicense()
    {
        /** @var OptionLicense $model */
        $model = container()->get(OptionLicense::class);

        /** @var OptionCommon $common */
        $common = container()->get(OptionCommon::class);

        if (request()->getIsPostRequest()) {
            $model->unsetAttributes();
            $model->attributes = (array)request()->getPost($model->getModelName(), []);

            $error = '';
            try {
                $response = LicenseHelper::verifyLicense($model);
            } catch (Exception $e) {
                $error = $e->getMessage();
            }

            if (empty($error) && !empty($response)) {
                $response = (array)json_decode((string)$response->getBody(), true);
                if (empty($response['status'])) {
                    $error = t('settings', 'Invalid response, please try again later!');
                } elseif ($response['status'] != 'success') {
                    $error = $response['message'];
                    $model->saveAttributes([
                        'error_message' => $error,
                    ]);
                }
            }

            if (empty($error)) {
                if (!$model->save()) {
                    notify()->addError(t('app', 'Your form contains a few errors, please fix them and try again!'));
                } else {
                    notify()->clearAll()->addSuccess(t('app', 'Your form has been successfully saved!'));
                    $model->saveAttributes([
                        'error_message' => '',
                    ]);
                    $common->saveAttributes([
                        'site_status' => OptionCommon::STATUS_ONLINE,
                    ]);
                }
            } else {
                notify()->addError($error);
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller' => $this,
                'success'    => notify()->getHasSuccess(),
                'model'      => $model,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['settings/license']);
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('settings', 'License settings'),
            'pageHeading'     => t('settings', 'License settings'),
            'pageBreadcrumbs' => [
                t('settings', 'Settings')  => createUrl('settings/index'),
                t('settings', 'License'),
            ],
        ]);

        $this->render('license', compact('model'));
    }

    /**
     * Handle the settings for social links options
     *
     * @return void
     * @throws CException
     */
    public function actionSocial_links()
    {
        /** @var OptionSocialLinks $model */
        $model = container()->get(OptionSocialLinks::class);

        if (request()->getIsPostRequest()) {
            $model->unsetAttributes();
            $model->attributes = (array)request()->getPost($model->getModelName(), []);

            if (!$model->save()) {
                notify()->addError(t('app', 'Your form contains a few errors, please fix them and try again!'));
            } else {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller' => $this,
                'success'    => notify()->getHasSuccess(),
                'model'      => $model,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['settings/social_links']);
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('settings', 'Social links settings'),
            'pageHeading'     => t('settings', 'Social links settings'),
            'pageBreadcrumbs' => [
                t('settings', 'Settings')  => createUrl('settings/index'),
                t('settings', 'Social links'),
            ],
        ]);

        $this->render('social-links', compact('model'));
    }

    /**
     * Handle the settings for CDN options
     *
     * @return void
     * @throws CException
     */
    public function actionCdn()
    {
        /** @var OptionCdn $model */
        $model = container()->get(OptionCdn::class);

        if (request()->getIsPostRequest()) {
            $model->unsetAttributes();
            $model->attributes = (array)request()->getPost($model->getModelName(), []);

            if (!$model->save()) {
                notify()->addError(t('app', 'Your form contains a few errors, please fix them and try again!'));
            } else {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller' => $this,
                'success'    => notify()->getHasSuccess(),
                'model'      => $model,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['settings/cdn']);
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('settings', 'CDN settings'),
            'pageHeading'     => t('settings', 'CDN settings'),
            'pageBreadcrumbs' => [
                t('settings', 'Settings')  => createUrl('settings/index'),
                t('settings', 'CDN'),
            ],
        ]);

        $this->render('cdn', compact('model'));
    }

    /**
     * Handle the settings for CDN options
     *
     * @return void
     * @throws CException
     */
    public function actionSpf_dkim()
    {
        /** @var OptionSpfDkim $model */
        $model = container()->get(OptionSpfDkim::class);

        if (request()->getIsPostRequest()) {
            $model->unsetAttributes();
            $model->attributes = (array)request()->getPost($model->getModelName(), []);

            if (!$model->save()) {
                notify()->addError(t('app', 'Your form contains a few errors, please fix them and try again!'));
            } else {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            }

            $disabledDomains = [];

            // 1.4.9
            if ($model->update_sending_domains == OptionSpfDkim::TEXT_YES) {
                $keys = ['dkim_private_key', 'dkim_public_key'];
                $domains = SendingDomain::model()->findAllByAttributes(['verified' => SendingDomain::TEXT_YES]);
                foreach ($domains as $domain) {
                    foreach ($keys as $key) {
                        if ($domain->$key != $model->$key) {
                            $domain->dkim_private_key = $model->dkim_private_key;
                            $domain->dkim_public_key = $model->dkim_public_key;
                            $domain->verified = SendingDomain::TEXT_NO;
                            $domain->save(false);
                            $disabledDomains[] = $domain->name;
                            break;
                        }
                    }
                }
                $disabledDomains = array_filter(array_unique($disabledDomains));
            }

            if ($disabledDomains) {
                notify()->addWarning(t('app', 'Please note that following sending domains have been disabled because their dkim signature is not valid anymore: {domains}', [
                    '{domains}' => implode(', ', $disabledDomains),
                ]));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller' => $this,
                'success'    => notify()->getHasSuccess(),
                'model'      => $model,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['settings/spf_dkim']);
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('settings', 'Spf/Dkim Settings'),
            'pageHeading'     => t('settings', 'Spf/Dkim Settings'),
            'pageBreadcrumbs' => [
                t('settings', 'Settings')  => createUrl('settings/index'),
                t('settings', 'Spf/Dkim'),
            ],
        ]);

        $this->render('spf-dkim', compact('model'));
    }

    /**
     * Handle the settings for customization options
     *
     * @return void
     * @throws CException
     */
    public function actionCustomization()
    {
        /** @var OptionCustomization $model */
        $model = container()->get(OptionCustomization::class);

        if (request()->getIsPostRequest()) {
            $model->unsetAttributes();
            $model->attributes = (array)request()->getPost($model->getModelName(), []);

            if (!$model->save()) {
                notify()->addError(t('app', 'Your form contains a few errors, please fix them and try again!'));
            } else {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller' => $this,
                'success'    => notify()->getHasSuccess(),
                'model'      => $model,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['settings/customization']);
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('settings', 'Customization settings'),
            'pageHeading'     => t('settings', 'Customization settings'),
            'pageBreadcrumbs' => [
                t('settings', 'Settings')  => createUrl('settings/index'),
                t('settings', 'Customization'),
            ],
        ]);

        $this->render('customization', compact('model'));
    }

    /**
     * Handle the settings for 2FA
     *
     * @return void
     * @throws CException
     */
    public function action2fa()
    {
        /** @var OptionTwoFactorAuth $model */
        $model = container()->get(OptionTwoFactorAuth::class);

        if (request()->getIsPostRequest()) {
            $model->unsetAttributes();
            $model->attributes = (array)request()->getPost($model->getModelName(), []);

            if (!$model->save()) {
                notify()->addError(t('app', 'Your form contains a few errors, please fix them and try again!'));
            } else {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller' => $this,
                'success'    => notify()->getHasSuccess(),
                'model'      => $model,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['settings/2fa']);
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('settings', '2FA settings'),
            'pageHeading'     => t('settings', '2FA settings'),
            'pageBreadcrumbs' => [
                t('settings', 'Settings') => createUrl('settings/index'),
                t('settings', '2FA settings'),
            ],
        ]);

        $this->render('2fa', compact('model'));
    }

    /**
     * Handle the settings for campaign attachments
     *
     * @return void
     * @throws CException
     */
    public function actionTransactional_email_attachments()
    {
        /** @var OptionTransactionalEmailAttachment $model */
        $model = container()->get(OptionTransactionalEmailAttachment::class);

        if (request()->getIsPostRequest()) {
            $model->unsetAttributes();
            $model->attributes = (array)request()->getPost($model->getModelName(), []);

            if (!$model->save()) {
                notify()->addError(t('app', 'Your form contains a few errors, please fix them and try again!'));
            } else {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller' => $this,
                'success'    => notify()->getHasSuccess(),
                'model'      => $model,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['settings/transactional_email_attachments']);
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('settings', 'Transactional emails attachments'),
            'pageHeading'     => t('settings', 'Transactional emails attachments'),
            'pageBreadcrumbs' => [
                t('settings', 'Settings')  => createUrl('settings/index'),
                t('settings', 'Transactional emails') => createUrl('settings/transactional_email_attachments'),
                t('settings', 'Attachments'),
            ],
        ]);

        $this->render('transactional-email-attachments', compact('model'));
    }

    /**
     * Handle the settings for reverse proxy
     *
     * @return void
     * @throws CException
     */
    public function actionReverse_proxy()
    {
        /** @var OptionReverseProxy $model */
        $model = container()->get(OptionReverseProxy::class);

        if (request()->getIsPostRequest()) {
            $model->unsetAttributes();
            $model->attributes = (array)request()->getPost($model->getModelName(), []);

            if (!$model->save()) {
                notify()->addError(t('app', 'Your form contains a few errors, please fix them and try again!'));
            } else {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller' => $this,
                'success'    => notify()->getHasSuccess(),
                'model'      => $model,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['settings/reverse_proxy']);
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('settings', 'Reverse proxy settings'),
            'pageHeading'     => t('settings', 'Reverse proxy settings'),
            'pageBreadcrumbs' => [
                t('settings', 'Settings')  => createUrl('settings/index'),
                t('settings', 'Reverse proxy'),
            ],
        ]);

        $this->render('reverse-proxy', compact('model'));
    }

    /**
     * Display the modal window with for htaccess
     *
     * @return void
     * @throws CException
     */
    public function actionHtaccess_modal()
    {
        if (!request()->getIsAjaxRequest()) {
            $this->redirect(['settings/index']);
        }
        $this->renderPartial('_htaccess_modal');
    }

    /**
     * Tries to write the contents of the htaccess file
     *
     * @return void
     * @throws CException
     */
    public function actionWrite_htaccess()
    {
        if (!request()->getIsAjaxRequest()) {
            $this->redirect(['settings/index']);
        }

        if (!AppInitHelper::isModRewriteEnabled()) {
            $this->renderJson(['result' => 'error', 'message' => t('settings', 'Mod rewrite is not enabled on this host. Please enable it in order to use clean urls!')]);
        }

        if (!is_file($file = (string)Yii::getPathOfAlias('root') . '/.htaccess')) {
            if (!touch($file)) {
                $this->renderJson(['result' => 'error', 'message' => t('settings', 'Unable to create the file: {file}. Please create the file manually and paste the htaccess contents into it.', ['{file}' => $file])]);
            }
        }

        if (!file_put_contents($file, $this->getHtaccessContent())) {
            $this->renderJson(['result' => 'error', 'message' => t('settings', 'Unable to write htaccess contents into the file: {file}. Please create the file manually and paste the htaccess contents into it.', ['{file}' => $file])]);
        }

        $this->renderJson(['result' => 'success', 'message' => t('settings', 'The htaccess file has been successfully created. Do not forget to save the changes!')]);
    }

    /**
     * Callback method to set the editor options for email settings
     *
     * @param CEvent $event
     * @return void
     */
    public function _setupEditorOptions(CEvent $event)
    {
        if (!in_array($event->params['attribute'], ['common', 'notification_message', 'welcome_email_content', 'quota_notify_email_content'])) {
            return;
        }

        $options = [];
        if ($event->params['htmlOptions']->contains('wysiwyg_editor_options')) {
            $options = (array)$event->params['htmlOptions']->itemAt('wysiwyg_editor_options');
        }

        $options['id']     = CHtml::activeId($event->sender->owner, $event->params['attribute']);
        $options['height'] = 500;

        if ($event->params['attribute'] == 'common') {
            $options['fullPage'] = true;
            $options['allowedContent'] = true;
        }

        if ($event->params['attribute'] == 'notification_message') {
            $options['height'] = 100;
        }

        if ($event->params['attribute'] == 'welcome_email_content') {
            $options['height'] = 300;
        }

        if ($event->params['attribute'] == 'quota_notify_email_content') {
            $options['height'] = 200;
        }

        $event->params['htmlOptions']->add('wysiwyg_editor_options', $options);
    }

    /**
     * Callback method to set the editor options for email footer in campaigns
     *
     * @param CEvent $event
     * @return void
     */
    public function _addCustomerCampaignEmailFooterEditor(CEvent $event)
    {
        if (!in_array($event->params['attribute'], ['email_header', 'email_footer'])) {
            return;
        }

        $options = [];
        if ($event->params['htmlOptions']->contains('wysiwyg_editor_options')) {
            $options = (array)$event->params['htmlOptions']->itemAt('wysiwyg_editor_options');
        }
        $options['id'] = CHtml::activeId($event->sender->owner, $event->params['attribute']);
        $event->params['htmlOptions']->add('wysiwyg_editor_options', $options);
    }

    /**
     * Will generate the contents of the htaccess file which later
     * should be written in the document root of the application
     *
     * @return string
     */
    public function getHtaccessContent()
    {
        $webApps = apps()->getWebApps();
        $baseUrl = '/' . trim(apps()->getAppUrl('frontend', '', false, true), '/') . '/';
        $baseUrl = str_replace('//', '/', $baseUrl);

        if (($index = array_search('frontend', $webApps)) !== false) {
            unset($webApps[$index]);
        }

        try {
            $content = $this->renderPartial('_htaccess', compact('webApps', 'baseUrl'), true);
        } catch (Exception $e) {
            $content = '';
        }

        return $content;
    }
}
