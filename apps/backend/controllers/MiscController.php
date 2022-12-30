<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * MiscController
 *
 * Handles the actions for miscellaneous tasks
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.3
 */

class MiscController extends Controller
{
    /**
     * @return void
     */
    public function init()
    {
        $this->addPageScript(['src' => AssetsUrl::js('misc.js')]);
        parent::init();
    }

    /**
     * @return array
     */
    public function filters()
    {
        $filters = [
            'postOnly + delete_queue_monitor_item',
        ];

        return CMap::mergeArray($filters, parent::filters());
    }

    /**
     * Index action
     *
     * @return void
     */
    public function actionIndex()
    {
        $this->redirect(['misc/application_log']);
    }

    /**
     * Emergency actions
     *
     * @return void
     */
    public function actionEmergency_actions()
    {
        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('app', 'Emergency actions'),
            'pageHeading'     => t('app', 'Emergency actions'),
            'pageBreadcrumbs' => [
                t('app', 'Emergency actions'),
            ],
        ]);

        $this->render('emergency-actions');
    }

    /**
     * Reset campaigns
     *
     * @return void
     * @throws CException
     */
    public function actionReset_campaigns()
    {
        if (!request()->getIsAjaxRequest()) {
            $this->redirect(['misc/emergency_actions']);
        }
        Campaign::model()->updateAll(['status' => Campaign::STATUS_SENDING], 'status = :status', [':status' => Campaign::STATUS_PROCESSING]);
        $this->renderJson();
    }

    /**
     * Reset bounce servers
     *
     * @return void
     * @throws CException
     */
    public function actionReset_bounce_servers()
    {
        if (!request()->getIsAjaxRequest()) {
            $this->redirect(['misc/emergency_actions']);
        }
        BounceServer::model()->updateAll(['status' => BounceServer::STATUS_ACTIVE], 'status = :status', [':status' => BounceServer::STATUS_CRON_RUNNING]);
        $this->renderJson();
    }

    /**
     * Reset fbl servers
     *
     * @return void
     * @throws CException
     */
    public function actionReset_fbl_servers()
    {
        if (!request()->getIsAjaxRequest()) {
            $this->redirect(['misc/emergency_actions']);
        }
        FeedbackLoopServer::model()->updateAll(['status' => FeedbackLoopServer::STATUS_ACTIVE], 'status = :status', [':status' => FeedbackLoopServer::STATUS_CRON_RUNNING]);
        $this->renderJson();
    }

    /**
     * Reset email box monitors
     *
     * @return void
     * @throws CException
     */
    public function actionReset_email_box_monitors()
    {
        if (!request()->getIsAjaxRequest()) {
            $this->redirect(['misc/emergency_actions']);
        }
        EmailBoxMonitor::model()->updateAll(['status' => EmailBoxMonitor::STATUS_ACTIVE], 'status = :status', [':status' => EmailBoxMonitor::STATUS_CRON_RUNNING]);
        $this->renderJson();
    }

    /**
     * Application log
     *
     * @param string $category
     * @return void
     *
     * @throws CException
     */
    public function actionApplication_log($category = 'application')
    {
        $allowedCategories = ['application', '404'];
        if (!in_array($category, $allowedCategories)) {
            $this->redirect(['misc/application_log']);
            return;
        }

        $logFile = app()->getRuntimePath() . '/' . $category . '.log';

        if (request()->getIsPostRequest() && request()->getPost('delete') == 1) {
            if (is_file($logFile)) {
                unlink($logFile);
                notify()->addSuccess(t('app', 'The application log file has been successfully deleted!'));
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('app', 'Application log'),
            'pageHeading'     => t('app', 'Application log'),
            'pageBreadcrumbs' => [
                t('app', 'Application log'),
            ],
        ]);

        $applicationLog = '';
        if (is_file($logFile)) {
            $applicationLog = FileSystemHelper::getFileContents($logFile);
        }

        $this->render('application-log', compact('applicationLog', 'category'));
    }

    /**
     * Campaign delivery logs
     *
     * @param string $archive
     *
     * @return void
     * @throws CException
     */
    public function actionCampaigns_delivery_logs($archive = '')
    {
        $className = $archive ? CampaignDeliveryLogArchive::class : CampaignDeliveryLog::class;
        $log       = new $className('search');
        $log->unsetAttributes();

        $log->attributes = (array)request()->getQuery($log->getModelName(), []);

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('misc', 'View campaigns delivery logs'),
            'pageHeading'     => t('misc', 'View campaigns delivery logs'),
            'pageBreadcrumbs' => [
                t('misc', 'Campaigns delivery logs'),
            ],
        ]);

        $this->render('campaigns-delivery-logs', compact('log', 'archive'));
    }

    /**
     * Campaign bounce logs
     *
     * @return void
     * @throws CException
     */
    public function actionCampaigns_bounce_logs()
    {
        $log = new CampaignBounceLog('search');
        $log->unsetAttributes();

        $log->attributes = (array)request()->getQuery($log->getModelName(), []);

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('misc', 'View campaigns bounce logs'),
            'pageHeading'     => t('misc', 'View campaigns bounce logs'),
            'pageBreadcrumbs' => [
                t('misc', 'Campaigns bounce logs'),
            ],
        ]);

        $this->render('campaigns-bounce-logs', compact('log'));
    }

    /**
     * Campaigns stats
     *
     * @return void
     * @throws CException
     */
    public function actionCampaigns_stats()
    {
        $campaign = new Campaign('search');
        $campaign->unsetAttributes();
        $campaign->attributes = (array)request()->getQuery($campaign->getModelName(), []);
        $campaign->status     = [Campaign::STATUS_PENDING_SENDING, Campaign::STATUS_SENDING, Campaign::STATUS_SENT];

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('misc', 'View campaigns stats'),
            'pageHeading'     => t('misc', 'View campaigns stats'),
            'pageBreadcrumbs' => [
                t('misc', 'View campaigns stats'),
            ],
        ]);

        $this->render('campaigns-stats', compact('campaign'));
    }

    /**
     * Delivery servers usage logs
     *
     * @return void
     * @throws CException
     */
    public function actionDelivery_servers_usage_logs()
    {
        $log = new DeliveryServerUsageLog('search');
        $log->unsetAttributes();

        $log->attributes = (array)request()->getQuery($log->getModelName(), []);

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('misc', 'View delivery servers usage logs'),
            'pageHeading'     => t('misc', 'View delivery servers usage logs'),
            'pageBreadcrumbs' => [
                t('misc', 'Delivery servers usage logs'),
            ],
        ]);

        $this->render('delivery-servers-usage-logs', compact('log'));
    }

    /**
     * Delete temporary errors from campaigns delivery logs
     *
     * @return void
     */
    public function actionDelete_delivery_temporary_errors()
    {
        $criteria = new CDbCriteria();
        $criteria->select = 'campaign_id';
        $criteria->compare('status', Campaign::STATUS_SENDING);

        CampaignCollection::findAll($criteria)->each(function (Campaign $campaign) {
            CampaignDeliveryLog::model()->deleteAllByAttributes([
                'campaign_id' => $campaign->campaign_id,
                'status'      => CampaignDeliveryLog::STATUS_TEMPORARY_ERROR,
            ]);
        });

        notify()->addSuccess(t('misc', 'Delivery temporary errors were successfully deleted!'));
        $this->redirect(['misc/campaigns_delivery_logs']);
    }

    /**
     * Guest fail attempts
     *
     * @return void
     * @throws CException
     */
    public function actionGuest_fail_attempts()
    {
        $attempt = new GuestFailAttempt('search');
        $attempt->unsetAttributes();
        $attempt->attributes = (array)request()->getQuery($attempt->getModelName(), []);

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('guest_fail_attempt', 'View guest fail attempts'),
            'pageHeading'     => t('guest_fail_attempt', 'View guest fail attempts'),
            'pageBreadcrumbs' => [
                t('guest_fail_attempt', 'Guest fail attempts'),
            ],
        ]);

        $this->render('guest-fail-attempts', compact('attempt'));
    }

    /**
     * Cron jobs display list
     *
     * @return void
     */
    public function actionCron_jobs_list()
    {
        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('cronjobs', 'View cron jobs list'),
            'pageHeading'     => t('cronjobs', 'View cron jobs list'),
            'pageBreadcrumbs' => [
                t('cronjobs', 'Cron jobs list'),
            ],
        ]);

        $this->render('cron-jobs-list');
    }

    /**
     * Cron jobs display list
     *
     * @return void
     * @throws CException
     */
    public function actionCron_jobs_history()
    {
        $model = new ConsoleCommandListHistory('search');
        $model->unsetAttributes();
        $model->attributes = (array)request()->getQuery($model->getModelName(), []);

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('cronjobs', 'View cron jobs history'),
            'pageHeading'     => t('cronjobs', 'View cron jobs history'),
            'pageBreadcrumbs' => [
                t('cronjobs', 'Cron jobs history'),
            ],
        ]);

        $this->render('cron-jobs-history', compact('model'));
    }

    /**
     * Display information about the current php version
     *
     * @return void
     * @throws CException
     */
    public function actionPhpinfo()
    {
        if (request()->getQuery('show')) {
            if (CommonHelper::functionExists('phpinfo')) {
                phpinfo();
            }
            app()->end();
        }

        $phpInfoCli = t('settings', 'Please check back after the daily cron job runs and this area will contain updated info!');
        if (is_file($file = (string)Yii::getPathOfAlias('common.runtime') . '/php-info-cli.txt')) {
            $phpInfoCli = file_get_contents($file);
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('misc', 'View PHP info'),
            'pageHeading'     => t('misc', 'View PHP info'),
            'pageBreadcrumbs' => [
                t('misc', 'PHP info'),
            ],
        ]);

        $this->render('php-info', compact('phpInfoCli'));
    }

    /**
     * Change log
     *
     * @return void
     */
    public function actionChangelog()
    {
        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('app', 'Changelog'),
            'pageHeading'     => t('app', 'Changelog'),
            'pageBreadcrumbs' => [
                t('app', 'Changelog'),
            ],
        ]);

        $changeLog = FileSystemHelper::getFileContents((string)Yii::getPathOfAlias('root') . '/CHANGELOG');
        $this->render('changelog', compact('changeLog'));
    }

    /**
     * Queue tasks list
     *
     * @return void
     * @throws CException
     */
    public function actionQueue_monitor()
    {
        $model = new QueueMonitor('search');
        $model->unsetAttributes();
        $model->attributes = (array)request()->getQuery($model->getModelName(), []);

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('queue', 'View queue tasks'),
            'pageHeading'     => t('queue', 'View queue tasks'),
            'pageBreadcrumbs' => [
                t('queue', 'View queue tasks'),
            ],
        ]);

        $this->render('queue-monitor', compact('model'));
    }

    /**
     * Delete an existing queue monitor item
     *
     * @param int $id
     *
     * @return void
     * @throws CDbException
     * @throws CException
     * @throws CHttpException
     */
    public function actionDelete_queue_monitor_item($id)
    {
        $queueMonitor = QueueMonitor::model()->findByPk((string)$id);

        if (empty($queueMonitor) || !$queueMonitor->getCanBeDeleted()) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        $queueMonitor->delete();

        $redirect = null;
        if (!request()->getQuery('ajax')) {
            notify()->addSuccess(t('app', 'The item has been successfully deleted!'));
            $redirect = request()->getPost('returnUrl', ['misc/queue_monitor']);
        }

        // since 1.3.5.9
        hooks()->doAction('controller_action_delete_data', $collection = new CAttributeCollection([
            'controller' => $this,
            'model'      => $queueMonitor,
            'redirect'   => $redirect,
        ]));

        if ($collection->itemAt('redirect')) {
            $this->redirect($collection->itemAt('redirect'));
        }
    }
}
