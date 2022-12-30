<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * Survey_segmentsController
 *
 * Handles the actions for survey segments related tasks
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.7.8
 */

class Survey_segmentsController extends Controller
{
    /**
     * @return void
     * @throws CException
     */
    public function init()
    {
        $this->addPageScript(['src' => AssetsUrl::js('survey-segments.js')]);
        parent::init();

        /** @var Customer $customer */
        $customer = customer()->getModel();

        if (!($customer->getGroupOption('surveys.can_segment_surveys', 'yes') == 'yes')) {
            $this->redirect(['surveys/index']);
            return;
        }

        // make sure the parent account has allowed access for this subaccount
        if (is_subaccount() && !subaccount()->canManageSurveys()) {
            $this->redirect(['surveys/index']);
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
     * @param string $survey_uid
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionIndex($survey_uid)
    {
        /** @var Survey $survey */
        $survey = $this->loadSurveyModel((string)$survey_uid);

        $segment = new SurveySegment('search');
        $segment->attributes = (array)request()->getQuery($segment->getModelName(), []);
        $segment->survey_id  = (int)$survey->survey_id;

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('survey_segments', 'Your mail survey segments'),
            'pageHeading'     => t('survey_segments', 'Survey segments'),
            'pageBreadcrumbs' => [
                t('surveys', 'Surveys') => createUrl('surveys/index'),
                $survey->name . ' ' => createUrl('surveys/overview', ['survey_uid' => $survey->survey_uid]),
                t('survey_segments', ' Survey segments') => createUrl('survey_segments/index', ['survey_uid' => $survey->survey_uid]),
                t('app', 'View all'),
            ],
        ]);

        $this->render('list', compact('survey', 'segment'));
    }

    /**
     * @param string $survey_uid
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionCreate($survey_uid)
    {
        /** @var Survey $survey */
        $survey = $this->loadSurveyModel((string)$survey_uid);

        $segment = new SurveySegment();
        $segment->survey_id = (int)$survey->survey_id;

        $condition   = new SurveySegmentCondition();
        $conditions  = [];
        $canContinue = true;

        /** @var Customer $customer */
        $customer = customer()->getModel();

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($segment->getModelName(), []))) {
            $postConditions         = (array)request()->getPost($condition->getModelName(), []);
            $maxAllowedConditions   = (int)$customer->getGroupOption('surveys.max_segment_conditions', 3);
            if (!empty($postConditions) && count($postConditions) > $maxAllowedConditions) {
                notify()->addWarning(t('survey_segments', 'You are only allowed to add {num} segment conditions.', ['{num}' => $maxAllowedConditions]));
                $canContinue = false;
            }
        }

        if ($canContinue && request()->getIsPostRequest() && ($attributes = (array)request()->getPost($segment->getModelName(), []))) {
            $postConditions = (array)request()->getPost($condition->getModelName(), []);
            if (!empty($postConditions)) {
                $hashedConditions = [];
                /** @var array $conditionAttributes */
                foreach ($postConditions as $conditionAttributes) {
                    $cond = new SurveySegmentCondition();
                    $cond->attributes = (array)$conditionAttributes;

                    $hashKey = sha1($cond->field_id . $cond->operator_id . $cond->value);
                    if (isset($hashedConditions[$hashKey])) {
                        continue;
                    }
                    $hashedConditions[$hashKey] = true;

                    $conditions[] = $cond;
                }
            }
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

                $timeNow = time();
                try {
                    $segment->countResponders();
                } catch (Exception $e) {
                }

                if ((time() - $timeNow) > (int)$customer->getGroupOption('surveys.max_segment_wait_timeout', 5)) {
                    throw new Exception(t('survey_segments', 'Current segmentation is too deep and loads too slow, please revise your segment conditions!'));
                }

                $transaction->commit();

                /** @var CustomerActionLogBehavior $logAction */
                $logAction = $customer->getLogAction();
                $logAction->surveySegmentCreated($segment);

                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            } catch (Exception $e) {
                notify()->addError($e->getMessage());
                $transaction->rollback();
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller' => $this,
                'success'    => notify()->getHasSuccess(),
                'survey'     => $survey,
                'segment'    => $segment,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['survey_segments/update', 'survey_uid' => $survey->survey_uid, 'segment_uid' => $segment->segment_uid]);
                return;
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('survey_segments', 'Your survey segments'),
            'pageHeading'     => t('survey_segments', 'Create a new survey segment'),
            'pageBreadcrumbs' => [
                t('surveys', 'Surveys') => createUrl('surveys/index'),
                $survey->name . ' ' => createUrl('surveys/overview', ['survey_uid' => $survey->survey_uid]),
                t('survey_segments', 'Segments') => createUrl('survey_segments/index', ['survey_uid' => $survey->survey_uid]),
                t('app', 'Create'),
            ],
        ]);

        // since 1.3.5
        $conditionValueTags = SurveySegmentCondition::getValueTags();

        $this->render('form', compact('survey', 'segment', 'condition', 'conditions', 'conditionValueTags'));
    }

    /**
     * @param string $survey_uid
     * @param string $segment_uid
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionUpdate($survey_uid, $segment_uid)
    {
        /** @var Survey $survey */
        $survey = $this->loadSurveyModel((string)$survey_uid);

        /** @var SurveySegment|null $segment */
        $segment = SurveySegment::model()->findByAttributes([
            'segment_uid'   => (string)$segment_uid,
            'survey_id'     => (int)$survey->survey_id,
        ]);

        if (empty($segment)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        $condition  = new SurveySegmentCondition();
        $conditions = SurveySegmentCondition::model()->findAllByAttributes([
            'segment_id' => (int)$segment->segment_id,
        ]);

        /** @var Customer $customer */
        $customer = customer()->getModel();

        $canContinue = true;
        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($segment->getModelName(), []))) {
            $postConditions = (array)request()->getPost($condition->getModelName(), []);
            $maxAllowedConditions = (int)$customer->getGroupOption('surveys.max_segment_conditions', 3);
            if (!empty($postConditions) && count($postConditions) > $maxAllowedConditions) {
                notify()->addWarning(t('survey_segments', 'You are only allowed to add {num} segment conditions.', ['{num}' => $maxAllowedConditions]));
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
                    $cond = new SurveySegmentCondition();
                    $cond->attributes = (array)$conditionAttributes;

                    $hashKey = sha1($cond->field_id . $cond->operator_id . $cond->value);
                    if (isset($hashedConditions[$hashKey])) {
                        continue;
                    }
                    $hashedConditions[$hashKey] = true;

                    $conditions[] = $cond;
                }
            }

            $segment->attributes = $attributes;
            $transaction = db()->beginTransaction();

            try {
                if (!$segment->save()) {
                    throw new Exception(t('app', 'Your form has a few errors, please fix them and try again!'));
                }

                SurveySegmentCondition::model()->deleteAllByAttributes([
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

                $timeNow = time();
                try {
                    $segment->countResponders();
                } catch (Exception $e) {
                }

                if ((time() - $timeNow) > (int)$customer->getGroupOption('surveys.max_segment_wait_timeout', 5)) {
                    throw new Exception(t('survey_segments', 'Current segmentation is too deep and loads too slow, please revise your segment conditions!'));
                }

                $transaction->commit();

                /** @var CustomerActionLogBehavior $logAction */
                $logAction = $customer->getLogAction();
                $logAction->surveySegmentUpdated($segment);

                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            } catch (Exception $e) {
                notify()->addError($e->getMessage());
                $transaction->rollback();
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller' => $this,
                'success'    => notify()->getHasSuccess(),
                'survey'     => $survey,
                'segment'    => $segment,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['survey_segments/update', 'survey_uid' => $survey->survey_uid, 'segment_uid' => $segment->segment_uid]);
                return;
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('survey_segments', 'Your survey segments'),
            'pageHeading'     => t('survey_segments', 'Update survey segment'),
            'pageBreadcrumbs' => [
                t('surveys', 'Surveys') => createUrl('surveys/index'),
                $survey->name . ' ' => createUrl('surveys/overview', ['survey_uid' => $survey->survey_uid]),
                t('survey_segments', 'Segments') => createUrl('survey_segments/index', ['survey_uid' => $survey->survey_uid]),
                t('app', 'Update'),
            ],
        ]);

        // since 1.3.5
        $conditionValueTags = SurveySegmentCondition::getValueTags();

        // since 1.3.8.8
        $canExport = $customer->getGroupOption('surveys.can_export_responders', 'yes') == 'yes';

        $this->render('form', compact('survey', 'segment', 'condition', 'conditions', 'conditionValueTags', 'canExport'));
    }

    /**
     * @param string $survey_uid
     * @param string $segment_uid
     *
     * @return void
     * @throws CDbException
     * @throws CException
     * @throws CHttpException
     */
    public function actionResponders($survey_uid, $segment_uid)
    {
        if (!request()->getIsAjaxRequest()) {
            $this->redirect(['surveys/index']);
            return;
        }

        /** @var Survey $survey */
        $survey = $this->loadSurveyModel((string)$survey_uid);

        /** @var SurveySegment|null $segment */
        $segment = SurveySegment::model()->findByAttributes([
            'segment_uid' => (string)$segment_uid,
            'survey_id'   => (int)$survey->survey_id,
        ]);

        if (empty($segment)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        /** @var int $count */
        $count = $segment->countResponders();

        $pages = new CPagination($count);
        $pages->pageSize = (int)$segment->paginationOptions->getPageSize();

        /** @var SurveyResponder[] $responders */
        $responders = $segment->findResponders($pages->getOffset(), $pages->getLimit());
        $responder  = new SurveyResponder();

        $columns = [
            [
                'label'    => $responder->getAttributeLabel('ip_address'),
                'field_id' => null,
                'value'    => '',
            ],
        ];
        $rows = [];

        $criteria = new CDbCriteria();
        $criteria->compare('t.survey_id', $survey->survey_id);
        $criteria->order = 't.sort_order ASC';

        $fields = SurveyField::model()->findAll($criteria);

        foreach ($fields as $field) {
            $columns[] = [
                'label'     => $field->label,
                'field_id'  => $field->field_id,
                'value'     => '',
            ];
        }

        foreach ($responders as $responder) {
            $responderRow = ['columns' => [
                $responder->ip_address,
            ]];
            foreach ($fields as $field) {
                $criteria = new CDbCriteria();
                $criteria->select = 't.value';
                $criteria->compare('field_id', $field->field_id);
                $criteria->compare('responder_id', $responder->responder_id);
                $values = SurveyFieldValue::model()->findAll($criteria);

                $value = [];
                foreach ($values as $val) {
                    $value[] = $val->value;
                }

                $responderRow['columns'][] = ioFilter()->xssClean(implode(', ', $value));
            }

            if (count($responderRow['columns']) == count($columns)) {
                $rows[] = $responderRow;
            }
        }

        $this->renderPartial('_responders', compact('survey', 'columns', 'rows', 'pages', 'count'));
    }

    /**
     * @param string $survey_uid
     * @param string $segment_uid
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionCopy($survey_uid, $segment_uid)
    {
        /** @var Survey $survey */
        $survey = $this->loadSurveyModel((string)$survey_uid);

        /** @var SurveySegment|null $segment */
        $segment = SurveySegment::model()->findByAttributes([
            'segment_uid' => (string)$segment_uid,
            'survey_id'   => (int)$survey->survey_id,
        ]);

        if (empty($segment)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        if ($segment->copy()) {
            notify()->addSuccess(t('survey_segments', 'Your survey segment was successfully copied!'));
        }

        if (!request()->getIsAjaxRequest()) {
            $this->redirect(request()->getPost('returnUrl', ['survey_segments/index', 'survey_uid' => $survey->survey_uid]));
        }
    }

    /**
     * @param string $survey_uid
     * @param string $segment_uid
     *
     * @return void
     * @throws CDbException
     * @throws CException
     * @throws CHttpException
     */
    public function actionDelete($survey_uid, $segment_uid)
    {
        /** @var Survey $survey */
        $survey = $this->loadSurveyModel((string)$survey_uid);

        /** @var SurveySegment|null $segment */
        $segment = SurveySegment::model()->findByAttributes([
            'segment_uid' => (string)$segment_uid,
            'survey_id'   => (int)$survey->survey_id,
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
            $logAction->surveySegmentDeleted($segment);

            notify()->addSuccess(t('app', 'Your item has been successfully deleted!'));
            $redirect = request()->getPost('returnUrl', ['survey_segments/index', 'survey_uid' => $survey_uid]);

            // since 1.3.5.9
            hooks()->doAction('controller_action_delete_data', $collection = new CAttributeCollection([
                'controller' => $this,
                'survey'     => $survey,
                'segment'    => $segment,
                'redirect'   => $redirect,
            ]));

            if ($collection->itemAt('redirect')) {
                $this->redirect($collection->itemAt('redirect'));
                return;
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('surveys', 'Confirm survey segment removal'),
            'pageHeading'     => t('surveys', 'Confirm survey segment removal'),
            'pageBreadcrumbs' => [
                t('surveys', 'Surveys') => createUrl('surveys/index'),
                $survey->name . ' ' => createUrl('surveys/overview', ['survey_uid' => $survey->survey_uid]),
                t('surveys', 'Segments') => createUrl('survey_segments/index', ['survey_uid' => $survey->survey_uid]),
                $segment->name . ' ' => createUrl('survey_segments/update', ['survey_uid' => $survey->survey_uid, 'segment_uid' => $segment->segment_uid]),
                t('surveys', 'Confirm survey segment removal'),
            ],
        ]);

        $this->render('delete', compact('survey', 'segment'));
    }

    /**
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
     * @param string $survey_uid
     *
     * @return Survey
     * @throws CHttpException
     */
    public function loadSurveyModel(string $survey_uid): Survey
    {
        $model = Survey::model()->findByAttributes([
            'survey_uid'  => $survey_uid,
            'customer_id' => (int)customer()->getId(),
        ]);

        if ($model === null) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        return $model;
    }
}
