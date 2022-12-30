<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * Lists_toolsController
 *
 * Handles the actions for lists related tasks
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.4.5
 */

class Lists_toolsController extends Controller
{
    /**
     * @return void
     * @throws CException
     */
    public function init()
    {
        // make sure the parent account has allowed access for this subaccount
        if (is_subaccount() && !subaccount()->canManageLists()) {
            $this->redirect(['dashboard/index']);
        }

        $this->addPageScript(['src' => AssetsUrl::js('lists-tools.js')]);
        parent::init();
    }

    /**
     * Display list available tools
     *
     * @return void
     */
    public function actionIndex()
    {
        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('lists', 'Tools'),
            'pageHeading'     => t('lists', 'Tools'),
            'pageBreadcrumbs' => [
                t('lists', 'Lists') => ['lists/index'],
                t('lists', 'Tools'),
            ],
        ]);

        /** @var Customer $customer */
        $customer = customer()->getModel();

        $syncTool = new ListsSyncTool();
        $syncTool->customer_id = (int)$customer->customer_id;

        $splitTool = new ListSplitTool();
        $splitTool->customer_id = (int)$customer->customer_id;

        $this->render('index', compact('syncTool', 'splitTool'));
    }

    /**
     * @return void
     * @throws CException
     */
    public function actionSync()
    {
        if (!request()->getIsPostRequest()) {
            $this->redirect(['lists_tools/index']);
        }

        /** @var Customer $customer */
        $customer = customer()->getModel();

        $syncTool = new ListsSyncTool();
        $syncTool->attributes = (array)request()->getPost($syncTool->getModelName(), []);
        $syncTool->customer_id = (int)$customer->customer_id;

        if (!$syncTool->validate()) {
            $message = t('lists', 'Unable to validate your sync data!');
            if (request()->getIsAjaxRequest()) {
                $syncTool->progress_text = $message;
                $syncTool->finished      = 1;
                $this->renderJson([
                    'attributes'           => $syncTool->attributes,
                    'formatted_attributes' => $syncTool->getFormattedAttributes(),
                ]);
                return;
            }
            notify()->addError($message);
            $this->redirect(['lists_tools/index']);
        }

        if ($syncTool->primary_list_id == $syncTool->secondary_list_id) {
            $message = t('lists', 'The primary list and the secondary list cannot be the same!');
            if (request()->getIsAjaxRequest()) {
                $syncTool->progress_text = $message;
                $syncTool->finished      = 1;
                $this->renderJson([
                    'attributes'           => $syncTool->attributes,
                    'formatted_attributes' => $syncTool->getFormattedAttributes(),
                ]);
                return;
            }
            notify()->addError($message);
            $this->redirect(['lists_tools/index']);
        }

        $noAction = empty($syncTool->missing_subscribers_action);
        $noAction = $noAction && empty($syncTool->distinct_status_action);
        $noAction = $noAction && empty($syncTool->duplicate_subscribers_action);
        if ($noAction) {
            $message = t('lists', 'You need to select an action against one of the lists subscribers!');
            if (request()->getIsAjaxRequest()) {
                $syncTool->progress_text = $message;
                $syncTool->finished      = 1;
                $this->renderJson([
                    'attributes'           => $syncTool->attributes,
                    'formatted_attributes' => $syncTool->getFormattedAttributes(),
                ]);
                return;
            }
            notify()->addError($message);
            $this->redirect(['lists_tools/index']);
        }

        /** @var Lists|null $primaryList */
        $primaryList = $syncTool->getPrimaryList();

        if (empty($primaryList)) {
            $message = t('lists', 'The primary list cannot be found!');
            if (request()->getIsAjaxRequest()) {
                $syncTool->progress_text = $message;
                $syncTool->finished      = 1;
                $this->renderJson([
                    'attributes'           => $syncTool->attributes,
                    'formatted_attributes' => $syncTool->getFormattedAttributes(),
                ]);
                return;
            }
            notify()->addError($message);
            $this->redirect(['lists_tools/index']);
            return;
        }

        /** @var Lists|null $secondaryList */
        $secondaryList = $syncTool->getSecondaryList();

        if (empty($secondaryList)) {
            $message = t('lists', 'The secondary list cannot be found!');
            if (request()->getIsAjaxRequest()) {
                $syncTool->progress_text = $message;
                $syncTool->finished      = 1;
                $this->renderJson([
                    'attributes'           => $syncTool->attributes,
                    'formatted_attributes' => $syncTool->getFormattedAttributes(),
                ]);
                return;
            }
            notify()->addError($message);
            $this->redirect(['lists_tools/index']);
            return;
        }

        $syncTool->count  = (int)$primaryList->subscribersCount;
        $syncTool->limit  = (int)$customer->getGroupOption('lists.copy_subscribers_at_once', 100);

        $jsonAttributes = json_encode([
            'attributes'           => $syncTool->attributes,
            'formatted_attributes' => $syncTool->getFormattedAttributes(),
        ]);

        if (!request()->getIsAjaxRequest()) {
            $this->setData([
                'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('lists', 'Sync lists'),
                'pageHeading'     => t('lists', 'Sync lists'),
                'pageBreadcrumbs' => [
                    t('lists', 'Tools') => createUrl('lists_tools/index'),
                    t('lists', 'Sync "{primary}" list with "{secondary}" list', ['{primary}' => $primaryList->name, '{secondary}' => $secondaryList->name]),
                ],
                'fromText' => t('lists', 'Sync "{primary}" list with "{secondary}" list', ['{primary}' => $primaryList->name, '{secondary}' => $secondaryList->name]),
            ]);
            $this->render('sync-lists', compact('syncTool', 'jsonAttributes'));
            return;
        }

        $criteria = new CDbCriteria();
        $criteria->compare('list_id', (int)$primaryList->list_id);
        $criteria->limit  = $syncTool->limit;
        $criteria->offset = $syncTool->offset;
        $subscribers = ListSubscriber::model()->findAll($criteria);

        if (empty($subscribers)) {
            $syncTool->progress_text = t('lists', 'The sync process is done.');
            $syncTool->finished      = 1;
            $this->renderJson([
                'attributes'           => $syncTool->attributes,
                'formatted_attributes' => $syncTool->getFormattedAttributes(),
            ]);
            return;
        }

        $syncTool->progress_text = t('lists', 'The sync process is running, please wait...');
        $syncTool->finished      = 0;

        $transaction = db()->beginTransaction();

        try {
            foreach ($subscribers as $subscriber) {
                $syncTool->processed_total++;
                $syncTool->processed_success++;

                $exists = ListSubscriber::model()->findByAttributes([
                    'list_id' => $secondaryList->list_id,
                    'email'   => $subscriber->email,
                ]);

                if (empty($exists) && $syncTool->missing_subscribers_action == ListsSyncTool::MISSING_SUBSCRIBER_ACTION_CREATE_SECONDARY) {
                    $subscriber->copyToList((int)$secondaryList->list_id, false);
                    continue;
                }

                if (!empty($exists)) {
                    if ($syncTool->duplicate_subscribers_action == ListsSyncTool::DUPLICATE_SUBSCRIBER_ACTION_DELETE_SECONDARY) {
                        $exists->delete();
                        continue;
                    }
                }

                if (!empty($exists) && $subscriber->status != $exists->status) {
                    if ($syncTool->distinct_status_action == ListsSyncTool::DISTINCT_STATUS_ACTION_UPDATE_PRIMARY) {
                        $subscriber->status = $exists->status;
                        $subscriber->save(false);
                        continue;
                    }
                    if ($syncTool->distinct_status_action == ListsSyncTool::DISTINCT_STATUS_ACTION_UPDATE_SECONDARY) {
                        $exists->status = $subscriber->status;
                        $exists->save(false);
                        continue;
                    }
                    if ($syncTool->distinct_status_action == ListsSyncTool::DISTINCT_STATUS_ACTION_DELETE_SECONDARY) {
                        $exists->delete();
                        continue;
                    }
                }
            }

            $transaction->commit();
        } catch (Exception $e) {
            $transaction->rollback();
        }

        $syncTool->percentage = (int)round((((int)$syncTool->processed_total / (int)$syncTool->count) * 100), 0);
        $syncTool->offset     = (int)$syncTool->offset + (int)$syncTool->limit;

        $this->renderJson([
            'attributes'           => $syncTool->attributes,
            'formatted_attributes' => $syncTool->getFormattedAttributes(),
        ]);
    }

    /**
     * @return void
     * @throws CException
     */
    public function actionSplit()
    {
        if (!request()->getIsPostRequest()) {
            $this->redirect(['lists_tools/index']);
        }

        /** @var Customer $customer */
        $customer = customer()->getModel();

        $splitTool = new ListSplitTool();
        $splitTool->attributes  = (array)request()->getPost($splitTool->getModelName(), []);
        $splitTool->customer_id = (int)$customer->customer_id;

        if (!$splitTool->validate()) {
            $message = $splitTool->shortErrors->getAllAsString();
            if (request()->getIsAjaxRequest()) {
                $splitTool->progress_text = $message;
                $splitTool->finished      = 1;
                $this->renderJson([
                    'attributes'           => $splitTool->attributes,
                    'formatted_attributes' => $splitTool->getFormattedAttributes(),
                ]);
                return;
            }
            notify()->addError($message);
            $this->redirect(['lists_tools/index']);
        }

        /** @var Lists|null $list */
        $list = $splitTool->getList();

        if (empty($list)) {
            $message = t('lists', 'Invalid list selection!');
            if (request()->getIsAjaxRequest()) {
                $splitTool->progress_text = $message;
                $splitTool->finished      = 1;
                $this->renderJson([
                    'attributes'           => $splitTool->attributes,
                    'formatted_attributes' => $splitTool->getFormattedAttributes(),
                ]);
                return;
            }
            notify()->addError($message);
            $this->redirect(['lists_tools/index']);
            return;
        }

        if (!request()->getIsAjaxRequest()) {
            $criteria = new CDbCriteria();
            $criteria->compare('list_id', $list->list_id);
            $criteria->addInCondition('status', [Campaign::STATUS_PROCESSING, Campaign::STATUS_SENDING, Campaign::STATUS_PENDING_SENDING]);
            $criteria->limit = 1;

            /** @var Campaign[] $campaigns */
            $campaigns = Campaign::model()->findAll($criteria);

            if (!empty($campaigns)) {
                notify()->addError(t('lists', 'It seems that you have ongoing campaigns using this list. Please pause them before running this action.'));
                $this->redirect(['lists_tools/index']);
            }

            $splitTool->count = (int)ListSubscriber::model()->countByAttributes(['list_id' => $list->list_id]);
            if ((int)$splitTool->count < (int)$splitTool->sublists) {
                $splitTool->sublists = (int)$splitTool->count;
            }

            // no sub? we're done
            if (empty($splitTool->count)) {
                notify()->addError(t('lists', 'It seems that the list you try to split has no subscribers.'));
                $this->redirect(['lists_tools/index']);
            }

            $splitTool->per_list = (int)floor($splitTool->count / $splitTool->sublists);
            if ((int)$splitTool->limit > (int)$splitTool->per_list) {
                $splitTool->limit = (int)$splitTool->per_list;
            }
        }

        $jsonAttributes = json_encode([
            'attributes'           => $splitTool->attributes,
            'formatted_attributes' => $splitTool->getFormattedAttributes(),
        ]);

        if (!request()->getIsAjaxRequest()) {
            $this->setData([
                'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('lists', 'Split list'),
                'pageHeading'     => t('lists', 'Split list'),
                'pageBreadcrumbs' => [
                    t('lists', 'Tools') => createUrl('lists_tools/index'),
                    t('lists', 'Split list'),
                ],
            ]);
            $this->render('split-list', compact('splitTool', 'jsonAttributes'));
            return;
        }

        if ((int)$splitTool->page >= ((int)$splitTool->sublists - 1)) {
            $splitTool->progress_text = t('lists', 'The split process is done.');
            $splitTool->finished      = 1;
            $this->renderJson([
                'attributes'           => $splitTool->attributes,
                'formatted_attributes' => $splitTool->getFormattedAttributes(),
            ]);
        }

        /** @var Lists|null $copyList */
        $copyList = $list->copy();

        if (empty($copyList)) {
            $splitTool->progress_text = t('lists', 'Unable to create a copy from the initial list.');
            $splitTool->finished      = 1;
            $this->renderJson([
                'attributes'           => $splitTool->attributes,
                'formatted_attributes' => $splitTool->getFormattedAttributes(),
            ]);
            return;
        }

        $copyList->name = (string)preg_replace('/\#(\d+)$/', '#' . ((int)$splitTool->page + 1), $copyList->name);
        $copyList->save(false);
        $counter = 0;

        $db   = db();
        $rows = $db->createCommand()
            ->select('subscriber_id')
            ->from('{{list_subscriber}}')
            ->where('list_id = :lid', [':lid' => $list->list_id])
            ->order('subscriber_id DESC')
            ->limit($splitTool->limit)
            ->queryAll();

        // 1.6.4
        $list->flushSubscribersCountCache();
        $copyList->flushSubscribersCountCache();
        //

        while (!empty($rows)) {
            $subscriberIDS = [];
            foreach ($rows as $row) {
                $subscriberIDS[] = (int)$row['subscriber_id'];
            }

            try {
                $condition = 'list_id = ' . (int)$list->list_id . ' AND subscriber_id IN(' . implode(',', $subscriberIDS) . ')';
                $db->createCommand()->update('{{list_subscriber}}', ['list_id' => (int)$copyList->list_id], $condition);

                foreach ($copyList->copyListFieldsMap as $oldFieldId => $newFieldId) {
                    $condition = 'field_id = ' . (int)$oldFieldId . ' AND subscriber_id IN(' . implode(',', $subscriberIDS) . ')';
                    $db->createCommand()->update('{{list_field_value}}', ['field_id' => (int)$newFieldId], $condition);
                }
            } catch (Exception $e) {
                $splitTool->progress_text = $e->getMessage();
                $splitTool->finished      = 1;
                $this->renderJson([
                    'attributes'           => $splitTool->attributes,
                    'formatted_attributes' => $splitTool->getFormattedAttributes(),
                ]);
                return;
            }

            $counter += $splitTool->limit;
            if ($counter >= $splitTool->per_list) {
                break;
            }

            $rows = $db->createCommand()
                ->select('subscriber_id')
                ->from('{{list_subscriber}}')
                ->where('list_id = :lid', [':lid' => $list->list_id])
                ->order('subscriber_id DESC')
                ->limit($splitTool->limit)
                ->queryAll();
        }

        $splitTool->page++;
        $splitTool->progress_text = t('lists', 'Successfully created and moved subscribers into {name} list. Going further, please wait...', ['{name}' => $copyList->name]);
        $splitTool->percentage    = (int)round((($splitTool->page / (($splitTool->sublists - 1))) * 100), 0);

        $this->renderJson([
            'attributes'           => $splitTool->attributes,
            'formatted_attributes' => $splitTool->getFormattedAttributes(),
        ]);
    }
}
