<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * DashboardController
 *
 * Handles the actions for dashboard related tasks
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

class DashboardController extends Controller
{
    /**
     * @return void
     */
    public function init()
    {
        $this->addPageScripts([
            ['src' => AssetsUrl::js('dashboard.js')],
        ]);
        parent::init();
    }

    /**
     * Display dashboard info
     *
     * @return void
     * @throws CException
     */
    public function actionIndex()
    {
        if (file_exists((string)Yii::getPathOfAlias('root.install')) && is_dir($dir = (string)Yii::getPathOfAlias('root.install'))) {
            notify()->addWarning(t('app', 'Please remove the install directory({dir}) from your application!', [
                '{dir}' => $dir,
            ]));
        }

        // since 1.3.6.3
        if (options()->get('system.installer.freshinstallextensionscheck', 0) == 0) {
            options()->set('system.installer.freshinstallextensionscheck', 1);

            notify()->clearAll()->addInfo(t('extensions', 'Conducting extensions checks for the fresh install...'));

            $extensions = extensionsManager()->getCoreExtensions();
            $errors     = [];
            foreach ($extensions as $id => $instance) {
                if (extensionsManager()->extensionMustUpdate($id) && !extensionsManager()->updateExtension($id)) {
                    $errors[] = t('extensions', 'The extension "{name}" has failed to update!', [
                        '{name}' => html_encode((string)$instance->name),
                    ]);
                    $errors = CMap::mergeArray($errors, (array)extensionsManager()->getErrors());
                    extensionsManager()->resetErrors();
                }
            }

            if (!empty($errors)) {
                notify()->addError($errors);
            } else {
                notify()->addSuccess(t('extensions', 'All extension checks were conducted successfully.'));
            }

            // enable extensions
            $enableExtensions = ['tour', 'email-template-builder', 'search'];
            foreach ($enableExtensions as $ext) {
                if (extensionsManager()->enableExtension($ext)) {
                    /** @var ExtensionInit $extension */
                    $extension = extensionsManager()->getExtensionInstance($ext);
                    $extension->setOption('enabled', 'yes');
                }
            }
            //

            $this->redirect(['dashboard/index']);
        }

        // since 1.6.2
        if (options()->get('system.installer.freshinstallcommonemailtemplates', 0) == 0) {
            options()->set('system.installer.freshinstallcommonemailtemplates', 1);
            CommonEmailTemplate::reinstallCoreTemplates();
        }

        // since 1.9.17
        if (options()->get('system.updater.servers.sendinblueapiupgradev2tov3.info', 0) == 0) {
            options()->set('system.updater.servers.sendinblueapiupgradev2tov3.info', 1);

            $serversCount = DeliveryServerSendinblueWebApi::model()->countByAttributes([
                'type'   => DeliveryServerSendinblueWebApi::TRANSPORT_SENDINBLUE_WEB_API,
                'status' => DeliveryServerSendinblueWebApi::STATUS_ACTIVE,
            ]);
            if (!empty($serversCount)) {
                DeliveryServerSendinblueWebApi::model()->updateAll([
                    'status' => DeliveryServerSendinblueWebApi::STATUS_INACTIVE,
                ], '`type` = :type AND `status` = :status', [
                    ':type'   => DeliveryServerSendinblueWebApi::TRANSPORT_SENDINBLUE_WEB_API,
                    ':status' => DeliveryServerSendinblueWebApi::STATUS_ACTIVE,
                ]);
                notify()->addInfo(t('servers', 'Because of the API upgrade, all SendInBlue delivery servers have been disabled. Please update their API keys and validate them again.'));
            }
        }
        //

        // since 2.0.0
        if (options()->get('system.installer.freshinstalltranslations', 0) == 0) {
            options()->set('system.installer.freshinstalltranslations', 1);

            TranslationHelper::importFromJsonFiles(MW_APPS_PATH . '/common/data/translations');
        }

        // since 1.9.19
        if (app_param('console.save_command_history', true)) {
            foreach (ConsoleCommandList::getCommandMapCheckInterval() as $commandName => $seconds) {
                if (!ConsoleCommandList::isCommandActive($commandName, $seconds)) {
                    notify()->addWarning(t('app', 'The "{command}" command did not run in the last {num}. Please check your cron jobs and make sure they are properly set!', [
                        '{command}' => $commandName,
                        '{num}'     => DateTimeHelper::timespan(time() - $seconds),
                    ]));
                }
            }
        }
        //

        // since 2.1.8
        if (options()->get('system.updater.themes.defaultskinblue218', 0) == 0) {
            options()->set('system.updater.themes.defaultskinblue218', 1);
            notify()->addInfo(
                t('app', 'Default theme has been updated to a cleaner look and it also includes a dark version, which you can toggle from the top-right corner of the page. The old theme can be restored from {here}, it is called Blue V1.', [
                    '{here}' => CHtml::link(t('app', 'here'), createUrl('settings/customization')),
                ])
            );
        }
        //

        // since 2.1.11
        if (options()->get('system.installer.freshinstallsystemurls', 0) == 0) {
            options()->set('system.installer.freshinstallsystemurls', 1);
            /** @var OptionUrl $optionUrl */
            $optionUrl = container()->get(OptionUrl::class);
            $optionUrl->saveAttributes([
                'scheme' => request()->getIsSecureConnection() ? OptionUrl::SCHEME_HTTPS : OptionUrl::SCHEME_HTTP,
            ]);
            $optionUrl->regenerateSystemUrls();
        }

        // since 2.1.10
        /** @var OptionReverseProxy $optionReverseProxy */
        $optionReverseProxy = container()->get(OptionReverseProxy::class);
        if ($optionReverseProxy->getSiteLooksLikeBehindReverseProxy() && !$optionReverseProxy->getIsSiteBehindReverseProxy()) {
            notify()->addWarning(
                t('app', 'Your site seems to run behind a reverse proxy, but your settings suggest otherwise. Please update your settings from {here}.', [
                    '{here}' => CHtml::link(t('app', 'here'), createUrl('settings/reverse_proxy')),
                ])
            );
        } elseif (!$optionReverseProxy->getSiteLooksLikeBehindReverseProxy() && $optionReverseProxy->getIsSiteBehindReverseProxy()) {
            notify()->addWarning(
                t('app', 'Your site is not running behind a reverse proxy, but your settings suggest otherwise. Please update your settings from {here}.', [
                    '{here}' => CHtml::link(t('app', 'here'), createUrl('settings/reverse_proxy')),
                ])
            );
        }
        //

        /** @var OptionCommon $common */
        $common = container()->get(OptionCommon::class);
        $checkVersionUpdate = $common->getCheckVersionUpdate();

        $this->addPageStyle(['src' => apps()->getBaseUrl('assets/css/placeholder-loading.css')]);

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('dashboard', 'Dashboard'),
            'pageHeading'     => t('dashboard', 'Dashboard'),
            'pageBreadcrumbs' => [
                t('dashboard', 'Dashboard'),
            ],
        ]);

        $this->render('index', compact('checkVersionUpdate'));
    }

    /**
     * @return void
     * @throws CException
     */
    public function actionGlance_stats()
    {
        if (!request()->getIsAjaxRequest()) {
            $this->redirect(['dashboard/index']);
            return;
        }

        $appName = apps()->getCurrentAppName();

        /** @var array $glanceStats */
        $glanceStats = (array)hooks()->applyFilters($appName . '_dashboard_glance_stats_list', [], $this);
        if (empty($glanceStats)) {
            $glanceStats = (array)$this->getGlanceStats();
        }

        $keys = ['count', 'heading', 'icon', 'url'];

        /**
         * @var int $index
         * @var array $stat
         */
        foreach ($glanceStats as $index => $stat) {
            foreach ($keys as $key) {
                if (!array_key_exists($key, $stat)) {
                    unset($glanceStats[$index]);
                }
            }
        }

        $html = '';
        if (!empty($glanceStats)) {
            $html = $this->renderPartial('_glance-stats', compact('glanceStats'), true);
        }

        $this->renderJson([
            'html' => $html,
        ]);
    }

    /**
     * @return void
     * @throws CException
     */
    public function actionTimeline_items()
    {
        if (!request()->getIsAjaxRequest()) {
            $this->redirect(['dashboard/index']);
            return;
        }

        $appName       = apps()->getCurrentAppName();
        $timelineItems = $this->getTimelineItems();
        $timelineItems = hooks()->applyFilters($appName . '_dashboard_timeline_items_list', $timelineItems, $this);

        $html = '';
        if (!empty($timelineItems)) {
            $html = $this->renderPartial('_timeline-items', compact('timelineItems'), true);
        }

        $this->renderJson([
            'html' => $html,
        ]);
    }

    /**
     * Check for updates
     *
     * @return void
     */
    public function actionCheck_update()
    {
        if (!request()->getIsAjaxRequest()) {
            $this->redirect(['dashboard/index']);
        }

        /** @var OptionCommon $common */
        $common = container()->get(OptionCommon::class);

        $now        = time();
        $lastCheck  = (int)$common->getAttribute('version_update.last_check', 0);
        $interval   = 60 * 60 * 24; // once at 24 hours should be enough

        if ($lastCheck + $interval > $now) {
            app()->end();
        }

        $common->saveAttributes([
            'version_update.last_check' => $now,
        ]);

        try {
            $url = sprintf('https://www.mailwizz.com/api/site/version?pvi=%d&mv=%s', PHP_VERSION_ID, MW_VERSION);
            $response = (string)(new GuzzleHttp\Client())->get($url)->getBody();
        } catch (Exception $e) {
            $response = '';
        }

        if (empty($response)) {
            app()->end();
        }

        /** @var array $json */
        $json = (array)json_decode($response, true);
        if (empty($json['current_version'])) {
            app()->end();
        }

        $dbVersion = $common->version;
        if (version_compare($json['current_version'], $dbVersion, '>')) {
            $common->saveAttributes([
                'version_update.current_version' => $json['current_version'],
            ]);
        }

        app()->end();
    }

    /**
     * Campaigns list
     *
     * @return void
     * @throws CException
     */
    public function actionCampaigns()
    {
        if (!request()->getIsAjaxRequest()) {
            $this->redirect(['dashboard/index']);
        }

        $listId     = (int)request()->getPost('list_id');
        $campaignId = (int)request()->getPost('campaign_id');

        $criteria = new CDbCriteria();
        $criteria->select = 'campaign_id, name';
        $criteria->compare('status', Campaign::STATUS_SENT);
        $criteria->compare('list_id', $listId);
        $criteria->order = 'campaign_id DESC';
        $criteria->limit = 50;

        $latestCampaigns = Campaign::model()->findAll($criteria);
        $campaignsList   = [];
        foreach ($latestCampaigns as $cmp) {

            /** @var Campaign $cmp */
            $campaignsList[$cmp->campaign_id] = $cmp->name;
        }

        if (empty($campaignId) && !empty($latestCampaigns)) {
            $campaignId = $latestCampaigns[0]->campaign_id;
        }

        $campaign = Campaign::model()->findByAttributes([
            'campaign_id' => $campaignId,
            'status'      => Campaign::STATUS_SENT,
        ]);

        if (empty($campaign)) {
            $this->renderJson([
                'html'  => '',
            ]);
        }

        $this->renderJson([
            'html'  => $this->renderPartial('_campaigns', compact('campaign', 'campaignsList'), true),
        ]);
    }


    /**
     * @return array
     */
    public function getGlanceStats(): array
    {
        /** @var User $user */
        $user = user()->getModel();

        $languageId = (int)$user->language_id;
        $cacheKey   = sha1('backend.dashboard.glanceStats.' . $languageId);

        if (($items = cache()->get($cacheKey))) {
            return $items;
        }

        // since 1.7.6
        $items = BackendDashboardHelper::getGlanceStats();

        cache()->set($cacheKey, $items, 600);

        return $items;
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getTimelineItems(): array
    {
        /** @var User $user */
        $user = user()->getModel();

        $languageId = (int)$user->language_id;
        $cacheKey   = sha1('backend.dashboard.timelineItems.' . $languageId);

        if (($items = cache()->get($cacheKey))) {
            return $items;
        }

        // since 1.7.6
        $items = BackendDashboardHelper::getTimelineItems();

        cache()->set($cacheKey, $items, 600);

        return $items;
    }
}
