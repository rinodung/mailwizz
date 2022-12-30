<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * List_segmentsController
 *
 * Handles the actions for list segments related tasks
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

class List_segmentsController extends Controller
{
    /**
     * @return void
     * @throws CException
     */
    public function init()
    {
        $this->addPageScript(['src' => AssetsUrl::js('segments.js')]);
        parent::init();

        /** @var Customer $customer */
        $customer = customer()->getModel();

        if (!($customer->getGroupOption('lists.can_segment_lists', 'yes') == 'yes')) {
            $this->redirect(['lists/index']);
            return;
        }

        // make sure the parent account has allowed access for this subaccount
        if (is_subaccount() && !subaccount()->canManageLists()) {
            $this->redirect(['dashboard/index']);
        }
    }

    /**
     * @return array
     * @throws CException
     */
    public function filters()
    {
        return CMap::mergeArray([
            'postOnly + copy',
        ], parent::filters());
    }

    /**
     * @param string $list_uid
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionIndex($list_uid)
    {
        /** @var Lists $list */
        $list = $this->loadListModel((string)$list_uid);

        $segment = new ListSegment('search');
        $segment->attributes = (array)request()->getQuery($segment->getModelName(), []);
        $segment->list_id = (int)$list->list_id;

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('list_segments', 'Your mail list segments'),
            'pageHeading'     => t('list_segments', 'List segments'),
            'pageBreadcrumbs' => [
                t('lists', 'Lists') => createUrl('lists/index'),
                $list->name . ' ' => createUrl('lists/overview', ['list_uid' => $list->list_uid]),
                t('list_segments', ' List segments') => createUrl('list_segments/index', ['list_uid' => $list->list_uid]),
                t('app', 'View all'),
            ],
        ]);

        $this->render('list', compact('list', 'segment'));
    }

    /**
     * @param string $list_uid
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionCreate($list_uid)
    {
        /** @var Lists $list */
        $list = $this->loadListModel((string)$list_uid);

        $segment = new ListSegment();
        $segment->list_id = (int)$list->list_id;

        $condition   = new ListSegmentCondition();
        $conditions  = [];

        // since 1.9.12
        $campaignCondition  = new ListSegmentCampaignCondition();
        $campaignConditions = [];
        //

        $canContinue = true;
        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($segment->getModelName(), []))) {
            $postConditions = (array)request()->getPost($condition->getModelName(), []);
            // since 1.9.12
            $postCampaignConditions = (array)request()->getPost($campaignCondition->getModelName(), []);
            $conditionsCount        = count($postConditions) + count($postCampaignConditions);
            //

            /** @var Customer $customer */
            $customer = customer()->getModel();

            $maxAllowedConditions = (int)$customer->getGroupOption('lists.max_segment_conditions', 3);
            if ($conditionsCount > $maxAllowedConditions) {
                notify()->addWarning(t('list_segments', 'You are only allowed to add {num} segment conditions.', ['{num}' => $maxAllowedConditions]));
                $canContinue = false;
            }
        }

        if ($canContinue && request()->getIsPostRequest() && ($attributes = (array)request()->getPost($segment->getModelName(), []))) {
            $postConditions = (array)request()->getPost($condition->getModelName(), []);
            if (!empty($postConditions)) {
                $hashedConditions = [];
                /** @var array $conditionAttributes */
                foreach ($postConditions as $conditionAttributes) {
                    $cond = new ListSegmentCondition();
                    $cond->attributes = $conditionAttributes;

                    $hashKey = sha1($cond->field_id . $cond->operator_id . $cond->value);
                    if (isset($hashedConditions[$hashKey])) {
                        continue;
                    }
                    $hashedConditions[$hashKey] = true;

                    $conditions[] = $cond;
                }
            }
            // since 1.9.12
            $postCampaignConditions = (array)request()->getPost($campaignCondition->getModelName(), []);
            if (!empty($postCampaignConditions)) {
                $campaignConditions = [];
                $hashedConditions = [];
                /** @var array $conditionAttributes */
                foreach ($postCampaignConditions as $conditionAttributes) {
                    $cond = new ListSegmentCampaignCondition();
                    $cond->attributes = $conditionAttributes;

                    $hashKey = sha1($cond->action . $cond->campaign_id . $cond->time_comparison_operator . $cond->time_value . $cond->time_unit);
                    if (isset($hashedConditions[$hashKey])) {
                        continue;
                    }
                    $hashedConditions[$hashKey] = true;

                    $campaignConditions[] = $cond;
                }
            }
            //
            $segment->attributes = $attributes;
            $transaction = db()->beginTransaction();
            try {
                if (!$segment->save()) {
                    throw new Exception(t('app', 'Your form has a few errors, please fix them and try again!'));
                }

                $conditionError = false;
                foreach ($conditions as $cond) {
                    $cond->segment_id = (int)$segment->segment_id;
                    $cond->fieldDecorator->onHtmlOptionsSetup = [$this, '_addInputErrorClass'];
                    if (!$cond->save()) {
                        $conditionError = true;
                    }
                }
                if ($conditionError) {
                    throw new Exception(t('app', 'Your form has a few errors, please fix them and try again!'));
                }

                // since 1.9.12
                ListSegmentCampaignCondition::model()->deleteAllByAttributes([
                    'segment_id' => $segment->segment_id,
                ]);
                $campaignConditionError = false;
                foreach ($campaignConditions as $cond) {
                    $cond->segment_id = (int)$segment->segment_id;
                    $cond->fieldDecorator->onHtmlOptionsSetup = [$this, '_addInputErrorClass'];
                    if (!$cond->save()) {
                        $campaignConditionError = true;
                    }
                }
                if ($campaignConditionError) {
                    throw new Exception(t('app', 'Your form has a few errors, please fix them and try again!'));
                }
                //

                $timeNow = time();
                try {
                    $segment->countSubscribers();
                } catch (Exception $e) {
                }

                /** @var Customer $customer */
                $customer = customer()->getModel();

                if ((time() - $timeNow) > (int)$customer->getGroupOption('lists.max_segment_wait_timeout', 5)) {
                    throw new Exception(t('list_segments', 'Current segmentation is too deep and loads too slow, please revise your segment conditions!'));
                }

                $transaction->commit();

                /** @var Customer $customer */
                $customer = customer()->getModel();

                /** @var CustomerActionLogBehavior $logAction */
                $logAction = $customer->getLogAction();
                $logAction->segmentCreated($segment);

                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            } catch (Exception $e) {
                notify()->addError($e->getMessage());
                $transaction->rollback();
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller'=> $this,
                'success'   => notify()->getHasSuccess(),
                'list'      => $list,
                'segment'   => $segment,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['list_segments/update', 'list_uid' => $list->list_uid, 'segment_uid' => $segment->segment_uid]);
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('list_segments', 'Your mail list segments'),
            'pageHeading'     => t('list_segments', 'Create a new list segment'),
            'pageBreadcrumbs' => [
                t('lists', 'Lists') => createUrl('lists/index'),
                $list->name . ' ' => createUrl('lists/overview', ['list_uid' => $list->list_uid]),
                t('list_segments', 'Segments') => createUrl('list_segments/index', ['list_uid' => $list->list_uid]),
                t('app', 'Create'),
            ],
        ]);

        // since 1.3.5
        $conditionValueTags = ListSegmentCondition::getValueTags();

        $this->render('form', compact('list', 'segment', 'condition', 'conditions', 'conditionValueTags', 'campaignCondition', 'campaignConditions'));
    }

    /**
     * @param string $list_uid
     * @param string $segment_uid
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionUpdate($list_uid, $segment_uid)
    {
        /** @var Lists $list */
        $list = $this->loadListModel((string)$list_uid);

        $segment = ListSegment::model()->findByAttributes([
            'segment_uid'   => $segment_uid,
            'list_id'       => $list->list_id,
        ]);

        if (empty($segment)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        $condition = new ListSegmentCondition();
        $conditions = ListSegmentCondition::model()->findAllByAttributes([
            'segment_id' => $segment->segment_id,
        ]);

        // since 1.9.12
        $campaignCondition  = new ListSegmentCampaignCondition();
        $campaignConditions = ListSegmentCampaignCondition::model()->findAllByAttributes([
            'segment_id' => $segment->segment_id,
        ]);
        //

        $canContinue = true;
        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($segment->getModelName(), []))) {
            $postConditions = (array)request()->getPost($condition->getModelName(), []);
            // since 1.9.12
            $postCampaignConditions = (array)request()->getPost($campaignCondition->getModelName(), []);
            $conditionsCount        = count($postConditions) + count($postCampaignConditions);
            //

            /** @var Customer $customer */
            $customer = customer()->getModel();

            $maxAllowedConditions = (int)$customer->getGroupOption('lists.max_segment_conditions', 3);
            if ($conditionsCount > $maxAllowedConditions) {
                notify()->addWarning(t('list_segments', 'You are only allowed to add {num} segment conditions.', ['{num}' => $maxAllowedConditions]));
                $canContinue = false;
            }
        }

        if ($canContinue && request()->getIsPostRequest() && ($attributes = (array)request()->getPost($segment->getModelName(), []))) {
            $postConditions = (array)request()->getPost($condition->getModelName(), []);
            if (!empty($postConditions)) {
                $conditions = [];
                $hashedConditions = [];
                /** @var array $conditionAttributes */
                foreach ($postConditions as $conditionAttributes) {
                    $cond = new ListSegmentCondition();
                    $cond->attributes = $conditionAttributes;

                    $hashKey = sha1($cond->field_id . $cond->operator_id . $cond->value);
                    if (isset($hashedConditions[$hashKey])) {
                        continue;
                    }
                    $hashedConditions[$hashKey] = true;

                    $conditions[] = $cond;
                }
            }

            // since 1.9.12
            $postCampaignConditions = (array)request()->getPost($campaignCondition->getModelName(), []);
            if (!empty($postCampaignConditions)) {
                $campaignConditions = [];
                $hashedConditions = [];
                /** @var array $conditionAttributes */
                foreach ($postCampaignConditions as $conditionAttributes) {
                    $cond = new ListSegmentCampaignCondition();
                    $cond->attributes = $conditionAttributes;

                    $hashKey = sha1($cond->action . $cond->campaign_id . $cond->time_comparison_operator . $cond->time_value . $cond->time_unit);
                    if (isset($hashedConditions[$hashKey])) {
                        continue;
                    }
                    $hashedConditions[$hashKey] = true;

                    $campaignConditions[] = $cond;
                }
            }
            //

            $segment->attributes = $attributes;
            $transaction = db()->beginTransaction();
            try {
                if (!$segment->save()) {
                    throw new Exception(t('app', 'Your form has a few errors, please fix them and try again!'));
                }

                ListSegmentCondition::model()->deleteAllByAttributes([
                    'segment_id' => $segment->segment_id,
                ]);

                $conditionError = false;
                foreach ($conditions as $cond) {
                    $cond->segment_id = (int)$segment->segment_id;
                    $cond->fieldDecorator->onHtmlOptionsSetup = [$this, '_addInputErrorClass'];
                    if (!$cond->save()) {
                        $conditionError = true;
                    }
                }
                if ($conditionError) {
                    throw new Exception(t('app', 'Your form has a few errors, please fix them and try again!'));
                }

                // since 1.9.12
                ListSegmentCampaignCondition::model()->deleteAllByAttributes([
                    'segment_id' => $segment->segment_id,
                ]);
                $campaignConditionError = false;
                foreach ($campaignConditions as $cond) {
                    $cond->segment_id = $segment->segment_id;
                    $cond->fieldDecorator->onHtmlOptionsSetup = [$this, '_addInputErrorClass'];
                    if (!$cond->save()) {
                        $campaignConditionError = true;
                    }
                }
                if ($campaignConditionError) {
                    throw new Exception(t('app', 'Your form has a few errors, please fix them and try again!'));
                }
                //

                $timeNow = time();
                try {
                    $segment->countSubscribers();
                } catch (Exception $e) {
                }

                /** @var Customer $customer */
                $customer = customer()->getModel();

                if ((time() - $timeNow) > (int)$customer->getGroupOption('lists.max_segment_wait_timeout', 5)) {
                    throw new Exception(t('list_segments', 'Current segmentation is too deep and loads too slow, please revise your segment conditions!'));
                }

                $transaction->commit();

                /** @var Customer $customer */
                $customer = customer()->getModel();

                /** @var CustomerActionLogBehavior $logAction */
                $logAction = $customer->getLogAction();
                $logAction->segmentUpdated($segment);

                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            } catch (Exception $e) {
                notify()->addError($e->getMessage());
                $transaction->rollback();
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller'=> $this,
                'success'   => notify()->getHasSuccess(),
                'list'      => $list,
                'segment'   => $segment,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['list_segments/update', 'list_uid' => $list->list_uid, 'segment_uid' => $segment->segment_uid]);
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('list_segments', 'Your mail list segments'),
            'pageHeading'     => t('list_segments', 'Update list segment'),
            'pageBreadcrumbs' => [
                t('lists', 'Lists') => createUrl('lists/index'),
                $list->name . ' ' => createUrl('lists/overview', ['list_uid' => $list->list_uid]),
                t('list_segments', 'Segments') => createUrl('list_segments/index', ['list_uid' => $list->list_uid]),
                t('app', 'Update'),
            ],
        ]);

        // since 1.3.5
        $conditionValueTags = ListSegmentCondition::getValueTags();

        /** @var Customer $customer */
        $customer = customer()->getModel();

        // since 1.3.8.8
        $canExport = $customer->getGroupOption('lists.can_export_subscribers', 'yes') == 'yes';

        $this->render('form', compact('list', 'segment', 'condition', 'conditions', 'conditionValueTags', 'campaignCondition', 'campaignConditions', 'canExport'));
    }

    /**
     * @param string $list_uid
     * @param string $segment_uid
     *
     * @return void
     * @throws CDbException
     * @throws CException
     * @throws CHttpException
     */
    public function actionSubscribers($list_uid, $segment_uid)
    {
        if (!request()->getIsAjaxRequest()) {
            $this->redirect(['lists/index']);
        }

        /** @var Lists $list */
        $list = $this->loadListModel((string)$list_uid);

        /** @var ListSegment|null $segment */
        $segment = ListSegment::model()->findByAttributes([
            'segment_uid'    => $segment_uid,
            'list_id'        => $list->list_id,
        ]);

        if (empty($segment)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        $count = $segment->countSubscribers();

        $pages = new CPagination($count);
        $pages->pageSize = (int)$segment->paginationOptions->getPageSize();

        $subscribers = $segment->findSubscribers($pages->getOffset(), $pages->getLimit());

        $columns = [];
        $rows    = [];

        $criteria = new CDbCriteria();
        $criteria->compare('t.list_id', $list->list_id);
        $criteria->order = 't.sort_order ASC';

        $fields = ListField::model()->findAll($criteria);

        foreach ($fields as $field) {
            $columns[] = [
                'label'     => $field->label,
                'field_id'  => $field->field_id,
                'value'     => null,
            ];
        }

        foreach ($subscribers as $subscriber) {
            $subscriberRow = ['columns' => []];
            foreach ($fields as $field) {
                if ($field->tag == 'EMAIL') {
                    $value = $subscriber->getDisplayEmail();
                    $subscriberRow['columns'][] = html_encode($value);
                    continue;
                }

                $criteria = new CDbCriteria();
                $criteria->select = 't.value';
                $criteria->compare('field_id', $field->field_id);
                $criteria->compare('subscriber_id', $subscriber->subscriber_id);
                $values = ListFieldValue::model()->findAll($criteria);

                $value = [];
                foreach ($values as $val) {
                    $value[] = $val->value;
                }

                $subscriberRow['columns'][] = ioFilter()->xssClean(implode(', ', $value));
            }

            if (count($subscriberRow['columns']) == count($columns)) {
                $rows[] = $subscriberRow;
            }
        }

        $this->renderPartial('_subscribers', compact('list', 'columns', 'rows', 'pages', 'count'));
    }

    /**
     * @param string $list_uid
     * @param string $segment_uid
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionCopy($list_uid, $segment_uid)
    {
        /** @var Lists $list */
        $list = $this->loadListModel((string)$list_uid);

        $segment = ListSegment::model()->findByAttributes([
            'segment_uid'    => $segment_uid,
            'list_id'        => $list->list_id,
        ]);

        if (empty($segment)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        if ($segment->copy()) {
            notify()->addSuccess(t('list_segments', 'Your list segment was successfully copied!'));
        }

        if (!request()->getIsAjaxRequest()) {
            $this->redirect(request()->getPost('returnUrl', ['list_segments/index', 'list_uid' => $list->list_uid]));
        }
    }

    /**
     * @param string $list_uid
     * @param string $segment_uid
     *
     * @return void
     * @throws CDbException
     * @throws CException
     * @throws CHttpException
     */
    public function actionDelete($list_uid, $segment_uid)
    {
        /** @var Lists $list */
        $list = $this->loadListModel((string)$list_uid);

        $segment = ListSegment::model()->findByAttributes([
            'segment_uid' => $segment_uid,
            'list_id'     => $list->list_id,
        ]);

        if (empty($segment)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        if (request()->getIsPostRequest()) {
            $segment->delete();

            /** @var Customer $customer */
            $customer = customer()->getModel();

            /** @var CustomerActionLogBehavior $logAction */
            $logAction = $customer->getLogAction();
            $logAction->segmentDeleted($segment);

            notify()->addSuccess(t('app', 'Your item has been successfully deleted!'));
            $redirect = request()->getPost('returnUrl', ['list_segments/index', 'list_uid' => $list_uid]);

            // since 1.3.5.9
            hooks()->doAction('controller_action_delete_data', $collection = new CAttributeCollection([
                'controller' => $this,
                'list'       => $list,
                'segment'    => $segment,
                'redirect'   => $redirect,
            ]));

            if ($collection->itemAt('redirect')) {
                $this->redirect($collection->itemAt('redirect'));
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('lists', 'Confirm list segment removal'),
            'pageHeading'     => t('lists', 'Confirm list segment removal'),
            'pageBreadcrumbs' => [
                t('lists', 'Lists') => createUrl('lists/index'),
                $list->name . ' ' => createUrl('lists/overview', ['list_uid' => $list->list_uid]),
                t('lists', 'Segments') => createUrl('list_segments/index', ['list_uid' => $list->list_uid]),
                $segment->name . ' ' => createUrl('list_segments/update', ['list_uid' => $list->list_uid, 'segment_uid' => $segment->segment_uid]),
                t('lists', 'Confirm list segment removal'),
            ],
        ]);

        $campaign = new Campaign();
        $campaign->unsetAttributes();
        $campaign->attributes  = (array)request()->getQuery($campaign->getModelName(), []);
        $campaign->list_id     = (int)$list->list_id;
        $campaign->segment_id  = (int)$segment->segment_id;
        $campaign->customer_id = (int)customer()->getId();

        $campaignsCount = Campaign::model()->countByAttributes([
            'segment_id' => $segment->segment_id,
        ]);

        $this->render('delete', compact('list', 'segment', 'campaign', 'campaignsCount'));
    }

    /**
     * Callback method to add attribute error class to the AR model
     *
     * @param CEvent $event
     *
     * @return void
     */
    public function _addInputErrorClass(CEvent $event)
    {
        if ($event->sender->owner->hasErrors($event->params['attribute'])) {
            $event->params['htmlOptions']['class'] .= ' error';
        }
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
