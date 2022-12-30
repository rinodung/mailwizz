<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * List_toolsController
 *
 * Handles the actions for lists related tasks
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.4.3
 */

class List_toolsController extends Controller
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

        $this->addPageScript(['src' => AssetsUrl::js('list-tools.js')]);
        parent::init();
    }

    /**
     * @param string $list_uid
     *
     * @return void
     * @throws CHttpException
     */
    public function actionIndex($list_uid)
    {
        /** @var Lists $list */
        $list = $this->loadListModel((string)$list_uid);

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('lists', 'List tools'),
            'pageHeading'     => t('lists', 'List tools'),
            'pageBreadcrumbs' => [
                t('lists', 'Lists') => createUrl('lists/index'),
                $list->name . ' ' => createUrl('lists/overview', ['list_uid' => $list->list_uid]),
                t('lists', 'List tools'),
            ],
        ]);

        /** @var Customer $customer */
        $customer = customer()->getModel();

        /** @var OptionImporter $optionImporter */
        $optionImporter = container()->get(OptionImporter::class);

        /** @var OptionExporter $optionExporter */
        $optionExporter = container()->get(OptionExporter::class);

        $canSegmentLists = $customer->getGroupOption('lists.can_segment_lists', 'yes') == 'yes';
        $importerEnabled = $optionImporter->getIsEnabled();
        $exporterEnabled = $optionExporter->getIsEnabled();
        $subscriber      = new ListSubscriber();

        $canImport = $importerEnabled && $customer->getGroupOption('lists.can_import_subscribers', 'yes') == 'yes';
        $canExport = $exporterEnabled && $customer->getGroupOption('lists.can_export_subscribers', 'yes') == 'yes';
        $canCopy   = $customer->getGroupOption('lists.can_copy_subscribers', 'yes') == 'yes';

        $this->render('index', compact('canImport', 'canExport', 'canCopy', 'list', 'canSegmentLists', 'subscriber'));
    }

    /**
     * @param string $list_uid
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionCopy_subscribers_ajax($list_uid)
    {
        if (!request()->getIsAjaxRequest()) {
            $this->redirect(['list_tools/index', 'list_uid' => (string)$list_uid]);
        }

        /** @var Lists $list */
        $list = $this->loadListModel((string)$list_uid);

        $listId = (int)request()->getQuery('list_id');
        $data   = [];

        // load all lists
        if (empty($listId)) {
            $criteria = new CDbCriteria();
            $criteria->select = 'list_id, name';
            $criteria->compare('customer_id', (int)customer()->getId());
            $criteria->compare('status', Lists::STATUS_ACTIVE);
            $criteria->addCondition('list_id != :lid');
            $criteria->order = 'list_id DESC';
            $criteria->params[':lid'] = (int)$list->list_id;

            $data['lists'] = ListsCollection::findAll($criteria)->map(function (Lists $list) {
                return [
                    'list_id' => $list->list_id,
                    'name'    => $list->name,
                ];
            })->all();
        }

        // load all segments
        if (!empty($listId)) {
            $data['segments'] = ListSegmentCollection::findAllByListId($listId)->map(function (ListSegment $segment) {
                return [
                    'segment_id' => $segment->segment_id,
                    'name'       => $segment->name,
                ];
            })->all();
        }

        $this->renderJson(['result' => 'success', 'data' => $data]);
    }

    /**
     * Handle the copy of subscribers from another list
     *
     * @param string $list_uid
     *
     * @return void
     * @throws CDbException
     * @throws CException
     * @throws CHttpException
     */
    public function actionCopy_subscribers($list_uid)
    {
        /** @var Lists $list */
        $list = $this->loadListModel((string)$list_uid);

        $listId       = (int)request()->getPost('copy_list_id');
        $segmentId    = (int)request()->getPost('copy_segment_id');
        $status       = (array)request()->getPost('copy_status', []);
        $status       = empty($status) ? [ListSubscriber::STATUS_CONFIRMED] : $status;
        $statusAction = (int)request()->getPost('copy_status_action', 0);

        /** @var Customer $customer */
        $customer = customer()->getModel();
        $canCopy  = $customer->getGroupOption('lists.can_copy_subscribers', 'yes') == 'yes';

        if (!request()->getIsPostRequest() || empty($listId) || !$canCopy) {
            $this->redirect(['lists/tools', 'list_uid' => $list->list_uid]);
            return;
        }

        /** @var Lists|null $fromList */
        $fromList = Lists::model()->findByAttributes([
            'list_id'     => $listId,
            'customer_id' => (int)customer()->getId(),
        ]);

        if (empty($fromList)) {
            $this->redirect(['lists/tools', 'list_uid' => $list->list_uid]);
            return;
        }

        $fromSegment = null;
        if (!empty($segmentId)) {
            $fromSegment = ListSegment::model()->findByAttributes([
                'list_id'    => $fromList->list_id,
                'segment_id' => $segmentId,
            ]);

            if (empty($fromSegment)) {
                $this->redirect(['lists/tools', 'list_uid' => $list->list_uid]);
                return;
            }
        }

        if (!empty($fromSegment)) {
            $count = (int)$fromSegment->countSubscribers(null, [
                'status' => $status,
            ]);
        } else {
            $criteria = new CDbCriteria();
            $criteria->compare('list_id', (int)$listId);
            $criteria->addInCondition('status', $status);
            $count = (int)ListSubscriber::model()->count($criteria);
        }

        $fromText = t('lists', 'Copy {count} subscribers from "{fromList}" list into the "{intoList}" list', [
            '{count}'    => formatter()->formatNumber($count),
            '{fromList}' => $fromList->name,
            '{intoList}' => $list->name,
        ]);
        if (!empty($fromSegment)) {
            $fromText = t('lists', 'Copy {count} subscribers from "{fromList}" list using the "{fromSegment}" segment into the "{intoList}" list', [
                '{count}'       => formatter()->formatNumber($count),
                '{fromList}'    => $fromList->name,
                '{fromSegment}' => $fromSegment->name,
                '{intoList}'    => $list->name,
            ]);
        }

        $limit  = (int)$customer->getGroupOption('lists.copy_subscribers_at_once', 100);
        $pages  = $count <= $limit ? 1 : ceil($count / $limit);
        $page   = (int)request()->getPost('page', 1);
        $page   = $page < 1 ? 1 : $page;
        $offset = ($page - 1) * $limit;

        $attributes = [
            'total'             => $count,
            'processed_total'   => 0,
            'processed_success' => 0,
            'processed_error'   => 0,
            'percentage'        => 0,
            'progress_text'     => t('lists', 'The copy process is starting, please wait...'),
            'post_url'          => createUrl('list_tools/copy_subscribers', ['list_uid' => $list->list_uid]),
            'list_id'           => (int)$listId,
            'segment_id'        => (int)$segmentId,
            'status'            => (array)$status,
            'status_action'     => $statusAction,
            'page'              => $page,
        ];

        $jsonAttributes = json_encode($attributes);

        if (!request()->getIsAjaxRequest()) {
            $this->setData([
                'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('lists', 'Copy subscribers'),
                'pageHeading'     => t('lists', 'Copy subscribers'),
                'pageBreadcrumbs' => [
                    t('lists', 'Lists') => createUrl('lists/index'),
                    $list->name . ' '        => createUrl('lists/overview', ['list_uid' => $list->list_uid]),
                    t('lists', 'Tools') => createUrl('list_tools/index', ['list_uid' => $list->list_uid]),
                    t('lists', 'Copy from "{from}" list', ['{from}' => $fromList->name]),
                ],
            ]);
            $this->render('copy-subscribers', compact('list', 'fromList', 'fromSegment', 'fromText', 'jsonAttributes'));
            return;
        }

        $totalSubscribersCount   = 0;
        $listSubscribersCount    = 0;
        $maxSubscribersPerList   = (int)$customer->getGroupOption('lists.max_subscribers_per_list', -1);

        if ($maxSubscribersPerList > -1) {
            $criteria = new CDbCriteria();
            $criteria->select = 'COUNT(DISTINCT(t.email)) as counter';
            $criteria->compare('t.list_id', (int)$list->list_id);
            $listSubscribersCount = ListSubscriber::model()->count($criteria);
            if ($listSubscribersCount >= $maxSubscribersPerList) {
                $this->renderJson([
                    'finished'      => true,
                    'progress_text' => t('lists', 'You have reached the maximum number of allowed subscribers into this list.'),
                ]);
                return;
            }
        }

        if (!empty($fromSegment)) {
            $criteria = new CDbCriteria();
            $criteria->select = 't.*';
            $subscribers = $fromSegment->findSubscribers($offset, $limit, $criteria, [
                'status' => $status,
            ]);
        } else {
            $criteria = new CDbCriteria();
            $criteria->compare('list_id', (int)$listId);
            $criteria->addInCondition('status', $status);
            $criteria->limit  = $limit;
            $criteria->offset = $offset;
            $subscribers = ListSubscriber::model()->findAll($criteria);
        }

        if (empty($subscribers)) {
            $this->renderJson([
                'finished'      => true,
                'progress_text' => t('lists', 'The copy process is done.'),
            ]);
            return;
        }

        $processedTotal   = (int)request()->getPost('processed_total', 0);
        $processedSuccess = (int)request()->getPost('processed_success', 0);
        $processedError   = (int)request()->getPost('processed_error', 0);
        $progressText     = t('lists', 'The copy process is running, please wait...');
        $finished         = false;

        $transaction = db()->beginTransaction();

        try {
            foreach ($subscribers as $subscriber) {
                if ($maxSubscribersPerList > -1 && $listSubscribersCount >= $maxSubscribersPerList) {
                    $progressText = t('lists', 'You have reached the maximum number of allowed subscribers into this list.');
                    $finished = true;
                    break;
                }
                $processedTotal++;
                if ($statusAction == 1) {
                    $subscriber->status = ListSubscriber::STATUS_CONFIRMED;
                }
                if ($newSubscriber = $subscriber->copyToList((int)$list->list_id, false)) {
                    $processedSuccess++;
                    if ($newSubscriber->subscriber_id != $subscriber->subscriber_id) {
                        $totalSubscribersCount++;
                        $listSubscribersCount++;
                    }
                } else {
                    $processedError++;
                }
            }

            $transaction->commit();
        } catch (Exception $e) {
            $transaction->rollback();
        }

        $percentage   = round((($processedTotal / $count) * 100), 2);

        $this->renderJson(array_merge($attributes, [
            'processed_total'   => $processedTotal,
            'processed_success' => $processedSuccess,
            'processed_error'   => $processedError,
            'percentage'        => $percentage,
            'page'              => $page + 1,
            'progress_text'     => $progressText,
            'finished'          => $finished,
        ]));
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
            'list_uid'      => $list_uid,
            'customer_id'   => (int)customer()->getId(),
        ]);

        if ($model === null) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        return $model;
    }
}
