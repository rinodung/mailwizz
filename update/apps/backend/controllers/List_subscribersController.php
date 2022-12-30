<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * List_subscribersController
 *
 * Handles the actions for list subscribers related tasks
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.5.3
 */

class List_subscribersController extends Controller
{
    /**
     * @return array
     */
    public function filters()
    {
        return CMap::mergeArray([
            'postOnly + delete, subscribe, unsubscribe, disable',
        ], parent::filters());
    }

    /**
     * Campaigns sent to this subscriber
     *
     * @param string $list_uid
     * @param string $subscriber_uid
     *
     * @return void
     * @throws CHttpException
     */
    public function actionCampaigns($list_uid, $subscriber_uid)
    {
        $list       = $this->loadListModel($list_uid);
        $subscriber = $this->loadSubscriberModel($subscriber_uid);

        $model = new CampaignDeliveryLog('search');
        $model->campaign_id   = -1;
        $model->subscriber_id = (int)$subscriber->subscriber_id;
        $model->status        = '';

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('list_subscribers', 'Subscriber campaigns'),
            'pageHeading'     => t('list_subscribers', 'Subscriber campaigns'),
            'pageBreadcrumbs' => [
                t('lists', 'Lists') => createUrl('lists/index'),
                $list->name . ' ' => createUrl('lists/overview', ['list_uid' => $list->list_uid]),
                t('list_subscribers', 'Subscribers') => createUrl('list_subscribers/index', ['list_uid' => $list->list_uid]),
                t('list_subscribers', 'Campaigns') => createUrl('list_subscribers/campaigns', ['list_uid' => $list_uid, 'subscriber_uid' => $subscriber_uid]),
                t('app', 'View all'),
            ],
        ]);

        $this->render('campaigns', compact('model', 'list', 'subscriber'));
    }

    /**
     * Delete existing list subscriber
     *
     * @param string $subscriber_uid
     *
     * @return void
     * @throws Exception
     */
    public function actionDelete($subscriber_uid)
    {
        /** @var ListSubscriber $subscriber */
        $subscriber = $this->loadSubscriberModel($subscriber_uid);

        if ($subscriber->getCanBeDeleted()) {
            $subscriber->delete();

            /** @var Customer $customer */
            $customer = $subscriber->list->customer;

            /** @var CustomerActionLogBehavior $logAction */
            $logAction = $customer->getLogAction();
            $logAction->subscriberDeleted($subscriber);
        }

        $redirect = null;
        if (!request()->getIsAjaxRequest()) {
            notify()->addSuccess(t('list_subscribers', 'Your list subscriber was successfully deleted!'));
            $redirect = request()->getPost('returnUrl', ['lists/all_subscribers']);
        }

        // since 1.3.5.9
        hooks()->doAction('controller_action_delete_data', $collection = new CAttributeCollection([
            'controller' => $this,
            'subscriber' => $subscriber,
            'redirect'   => $redirect,
        ]));

        if ($collection->itemAt('redirect')) {
            $this->redirect($collection->itemAt('redirect'));
        }
    }

    /**
     * Disable existing list subscriber
     *
     * @param string $subscriber_uid
     *
     * @return void
     * @throws CHttpException
     */
    public function actionDisable($subscriber_uid)
    {
        $subscriber = $this->loadSubscriberModel($subscriber_uid);

        if ($subscriber->getCanBeDisabled()) {
            $subscriber->saveStatus(ListSubscriber::STATUS_DISABLED);
        }

        if (!request()->getIsAjaxRequest()) {
            notify()->addSuccess(t('list_subscribers', 'Your list subscriber was successfully disabled!'));
            $this->redirect(request()->getPost('returnUrl', ['lists/all_subscribers']));
        }
    }

    /**
     * Unsubscribe existing list subscriber
     *
     * @param string $subscriber_uid
     *
     * @return void
     * @throws CHttpException
     */
    public function actionUnsubscribe($subscriber_uid)
    {
        $subscriber = $this->loadSubscriberModel($subscriber_uid);

        if ($subscriber->getCanBeUnsubscribed()) {
            $subscriber->saveStatus(ListSubscriber::STATUS_UNSUBSCRIBED);
        }

        if (!request()->getIsAjaxRequest()) {
            notify()->addSuccess(t('list_subscribers', 'Your list subscriber was successfully unsubscribed!'));
            $this->redirect(request()->getPost('returnUrl', ['lists/all_subscribers']));
        }
    }

    /**
     * Subscribe existing list subscriber
     *
     * @param string $subscriber_uid
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     * @throws Throwable
     */
    public function actionSubscribe($subscriber_uid)
    {
        $subscriber = $this->loadSubscriberModel($subscriber_uid);
        $oldStatus  = $subscriber->status;

        if ($subscriber->getCanBeApproved()) {
            $subscriber->saveStatus(ListSubscriber::STATUS_CONFIRMED);
            $subscriber->handleApprove(true)->handleWelcome(true);
        } elseif ($subscriber->getCanBeConfirmed()) {
            $subscriber->saveStatus(ListSubscriber::STATUS_CONFIRMED);
        }

        if (!request()->getIsAjaxRequest()) {
            if ($oldStatus == ListSubscriber::STATUS_UNSUBSCRIBED) {
                notify()->addSuccess(t('list_subscribers', 'Your list unsubscriber was successfully subscribed back!'));
            } elseif ($oldStatus == ListSubscriber::STATUS_UNAPPROVED) {
                notify()->addSuccess(t('list_subscribers', 'Your list subscriber has been approved and notified!'));
            } else {
                notify()->addSuccess(t('list_subscribers', 'Your list subscriber has been confirmed!'));
            }
            $this->redirect(request()->getPost('returnUrl', ['lists/all_subscribers']));
        }
    }

    /**
     * Return profile info
     *
     * @param string $subscriber_uid
     *
     * @return void
     * @throws CException
     */
    public function actionProfile($subscriber_uid)
    {
        if (!request()->getIsAjaxRequest()) {
            $this->redirect(['lists/all_subscribers']);
        }

        $subscriber = ListSubscriber::model()->findByAttributes([
            'subscriber_uid' => $subscriber_uid,
        ]);

        if (empty($subscriber)) {
            return;
        }

        $this->renderPartial('_profile-in-modal', [
            'list'          => $subscriber->list,
            'subscriber'    => $subscriber,
            'subscriberName'=> $subscriber->getFullName(),
            'optinHistory'  => !empty($subscriber->optinHistory) ? $subscriber->optinHistory : null,
            'optoutHistory' => $subscriber->status == ListSubscriber::STATUS_UNSUBSCRIBED && !empty($subscriber->optoutHistory) ? $subscriber->optoutHistory : null,
        ]);
    }

    /**
     * Export profile info
     *
     * @param string $subscriber_uid
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionProfile_export($subscriber_uid)
    {
        $subscriber = $this->loadSubscriberModel($subscriber_uid);
        $data       = $subscriber->getFullData();

        // Set the download headers
        HeaderHelper::setDownloadHeaders('subscriber-profile.csv');

        try {
            $csvWriter = League\Csv\Writer::createFromPath('php://output', 'w');
            $csvWriter->insertOne(array_keys($data));
            $csvWriter->insertOne(array_values($data));
        } catch (Exception $e) {
        }

        app()->end();
    }

    /**
     * @param string $list_uid
     *
     * @return Lists
     * @throws CHttpException
     */
    public function loadListModel(string $list_uid): Lists
    {
        $model = Lists::model()->findByAttributes([
            'list_uid' => $list_uid,
        ]);

        if ($model === null) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        return $model;
    }

    /**
     * @param string $subscriber_uid
     *
     * @return ListSubscriber
     * @throws CHttpException
     */
    public function loadSubscriberModel(string $subscriber_uid): ListSubscriber
    {
        $model = ListSubscriber::model()->findByAttributes([
            'subscriber_uid' => $subscriber_uid,
        ]);

        if ($model === null) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        return $model;
    }
}
