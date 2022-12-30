<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * Survey_respondersController
 *
 * Handles the actions for survey responders related tasks
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.7.8
 */

/**
 * @property SurveyFieldsControllerCallbacksBehavior $callbacks
 */
class Survey_respondersController extends Controller
{
    /**
     * @return void
     * @throws CException
     */
    public function init()
    {
        Yii::import('customer.components.survey-field-builder.*');

        $this->addPageScript(['src' => AssetsUrl::js('responders.js')]);
        parent::init();

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
            'postOnly + delete',
        ], parent::filters());
    }

    /**
     * @return array
     * @throws CException
     */
    public function behaviors()
    {
        return CMap::mergeArray([
            'callbacks' => [
                'class' => 'customer.components.behaviors.SurveyFieldsControllerCallbacksBehavior',
            ],
        ], parent::behaviors());
    }

    /**
     * @param string $survey_uid
     *
     * @return void
     * @throws CDbException
     * @throws CException
     * @throws CHttpException
     */
    public function actionIndex($survey_uid)
    {
        /** @var Survey $survey */
        $survey = $this->loadSurveyModel((string)$survey_uid);

        $postFilter = (array)request()->getPost('filter', []);
        $responder  = new SurveyResponder();

        /** @var array $responderStatusesList */
        $responderStatusesList = $responder->getStatusesList();

        /**
         * NOTE:
         * Following criteria will use filesort and create a temp table because of the group by condition.
         * So far, beside subqueries this is the only optimal way i have found to work fine.
         * Needs optimization in the future if will cause problems.
         */
        $criteria = new CDbCriteria();
        $criteria->with = [];
        $criteria->select = 'COUNT(DISTINCT t.responder_id) as counter';
        $criteria->compare('t.survey_id', $survey->survey_id);
        $criteria->order = 't.responder_id DESC';

        $criteria->with['subscriber'] = [
            'joinType' => 'LEFT JOIN',
            'together' => false,
            'select'   => 'subscriber.list_id, subscriber.subscriber_uid, subscriber.email',
        ];

        foreach ($postFilter as $field_id => $value) {
            if (empty($value)) {
                unset($postFilter[$field_id]);
                continue;
            }

            if (is_numeric($field_id)) {
                $model = SurveyField::model()->findByAttributes([
                    'field_id'  => $field_id,
                    'survey_id'   => $survey->survey_id,
                ]);
                if (empty($model)) {
                    unset($postFilter[$field_id]);
                }
            }
        }

        if (!empty($postFilter['status']) && in_array($postFilter['status'], array_keys($responderStatusesList))) {
            $criteria->compare('status', $postFilter['status']);
        }

        if (!empty($postFilter['uid']) && strlen((string)$postFilter['uid']) == 13) {
            $criteria->compare('responder_uid', $postFilter['uid']);
        }

        if (!empty($postFilter)) {
            $with = [];
            foreach ($postFilter as $field_id => $value) {
                if (!is_numeric($field_id)) {
                    continue;
                }

                $i = (int)$field_id;
                $with['fieldValues' . $i] = [
                    'select'    => false,
                    'together'  => true,
                    'joinType'  => 'INNER JOIN',
                    'condition' => '`fieldValues' . $i . '`.`field_id` = :field_id' . $i . ' AND `fieldValues' . $i . '`.`value` LIKE :value' . $i,
                    'params'    => [
                        ':field_id' . $i  => (int)$field_id,
                        ':value' . $i     => '%' . $value . '%',
                    ],
                ];
            }

            $md = $responder->getMetaData();
            foreach ($postFilter as $field_id => $value) {
                if (!is_numeric($field_id)) {
                    continue;
                }
                if ($md->hasRelation('fieldValues' . $field_id)) {
                    continue;
                }
                $md->addRelation('fieldValues' . $field_id, [SurveyResponder::HAS_MANY, 'SurveyFieldValue', 'responder_id']);
            }

            if (!empty($with)) {
                $criteria->with = $with;
            }
        }

        /** @var int $count */
        $count = $responder->count($criteria);

        // instantiate the pagination and apply the limit statement to the query
        $pages = new CPagination($count);
        $pages->pageSize = (int)$responder->paginationOptions->getPageSize();
        $pages->applyLimit($criteria);

        // load the required models
        $criteria->select = 't.survey_id, t.responder_id, t.responder_uid, t.subscriber_id, t.ip_address, t.status, t.date_added';
        $criteria->group = 't.responder_id';
        $responders = $responder->findAll($criteria);

        // 1.3.8.8
        $modelName  = sprintf('%s_survey_%d', get_class($responder), $survey->survey_id);
        $optionKey  = sprintf('%s:%s:%s', $modelName, $this->getId(), $this->getAction()->getId());
        $customerId = (int)customer()->getId();
        $optionKey  = sprintf('system.views.grid_view_columns.customers.%d.%s', $customerId, $optionKey);

        /** @var array $storedToggleColumns */
        $storedToggleColumns      = (array)options()->get($optionKey, []);
        $storedToggleColumnsEmpty = empty($storedToggleColumns);
        $displayToggleColumns     = [];
        //

        // now, we need to know what columns this survey has, that is, all the tags available for this survey.
        $columns = [];
        $rows = [];

        $criteria = new CDbCriteria();
        $criteria->compare('t.survey_id', $survey->survey_id);
        $criteria->order = 't.sort_order ASC';

        $fields = SurveyField::model()->findAll($criteria);

        $columns[] = [
            'label'         => t('app', 'Options'),
            'field_type'    => null,
            'field_id'      => null,
            'value'         => null,
            'htmlOptions'   => ['class' => 'empty-options-header options'],
        ];

        $columns[] = [
            'label'     => t('survey_responders', 'Unique ID'),
            'field_type'=> 'text',
            'field_id'  => 'uid',
            'value'     => isset($postFilter['uid']) ? html_encode((string)$postFilter['uid']) : null,
        ];

        $columns[] = [
            'label'         => t('app', 'Date added'),
            'field_type'    => null,
            'field_id'      => 'date_added',
            'value'         => null,
            'htmlOptions'   => ['class' => 'responder-date-added'],
        ];

        $columns[] = [
            'label'         => t('app', 'Ip address'),
            'field_type'    => null,
            'field_id'      => 'ip_address',
            'value'         => null,
            'htmlOptions'   => ['class' => 'responder-date-added'],
        ];

        $columns[] = [
            'label'         => t('survey_responders', 'Subscriber'),
            'field_type'    => null,
            'field_id'      => 'subscriber',
            'value'         => null,
        ];

        $columns[] = [
            'label'     => t('app', 'Status'),
            'field_type'=> 'select',
            'field_id'  => 'status',
            'value'     => isset($postFilter['status']) ? html_encode((string)$postFilter['status']) : null,
            'options'   => CMap::mergeArray(['' => t('app', 'Choose')], $responderStatusesList),
        ];

        foreach ($fields as $field) {
            $columns[] = [
                'label'     => $field->label,
                'field_type'=> 'text',
                'field_id'  => $field->field_id,
                'value'     => isset($postFilter[$field->field_id]) ? html_encode((string)$postFilter[$field->field_id]) : null,
            ];
        }

        // 1.3.8.8
        foreach ($columns as $index => $column) {
            if (empty($column['field_id'])) {
                continue;
            }
            $displayToggleColumns[] = $column;
            if ($storedToggleColumnsEmpty) {
                $storedToggleColumns[] = $column['field_id'];
                continue;
            }
            if (array_search($column['field_id'], $storedToggleColumns) === false) {
                unset($columns[$index]);
                continue;
            }
        }

        foreach ($responders as $responder) {
            $responderRow = ['columns' => []];

            $actions = [];

            if ($responder->getCanBeEdited()) {
                $actions[] = CHtml::link(IconHelper::make('update'), ['survey_responders/update', 'survey_uid' => $survey->survey_uid, 'responder_uid' => $responder->responder_uid], ['title' => t('app', 'Update'), 'class' => 'btn btn-primary btn-flat btn-xs']);
            }

            if ($responder->getCanBeDeleted()) {
                $actions[] = CHtml::link(IconHelper::make('glyphicon-remove-circle'), ['survey_responders/delete', 'survey_uid' => $survey->survey_uid, 'responder_uid' => $responder->responder_uid], ['class' => 'btn btn-danger btn-flat delete', 'title' => t('app', 'Delete'), 'data-message' => t('app', 'Are you sure you want to delete this item? There is no coming back after you do it.')]);
            }

            $responderRow['columns'][] = $this->renderPartial('_options-column', compact('actions'), true);

            if (in_array('uid', $storedToggleColumns)) {
                $responderRow['columns'][] = CHtml::link($responder->responder_uid, createUrl('survey_responders/update', ['survey_uid' => $survey->survey_uid, 'responder_uid' => $responder->responder_uid]));
            }
            if (in_array('date_added', $storedToggleColumns)) {
                $responderRow['columns'][] = $responder->dateTimeFormatter->getDateAdded();
            }
            if (in_array('ip_address', $storedToggleColumns)) {
                $responderRow['columns'][] = $responder->ip_address;
            }
            if (in_array('subscriber', $storedToggleColumns)) {
                $responderRow['columns'][] = !empty($responder->subscriber_id) && !empty($responder->subscriber) ? CHtml::link($responder->subscriber->getDisplayEmail(), createUrl('list_subscribers/update', ['list_uid' => $responder->subscriber->list->list_uid, 'subscriber_uid' => $responder->subscriber->subscriber_uid])) : '';
            }
            if (in_array('status', $storedToggleColumns)) {
                $responderRow['columns'][] = $responder->getStatusName();
            }

            foreach ($fields as $field) {
                if (!in_array($field->field_id, $storedToggleColumns)) {
                    continue;
                }

                $criteria = new CDbCriteria();
                $criteria->select = 't.value';
                $criteria->compare('field_id', $field->field_id);
                $criteria->compare('responder_id', $responder->responder_id);
                $values = SurveyFieldValue::model()->findAll($criteria);

                $value = [];
                foreach ($values as $val) {
                    $value[] = $val->value;
                }

                $responderRow['columns'][] = html_encode((string)implode(', ', $value));
            }

            if (count($responderRow['columns']) == count($columns)) {
                $rows[] = $responderRow;
            }
        }

        if (request()->getIsPostRequest() && request()->getIsAjaxRequest()) {
            $this->renderPartial('_list', compact('survey', 'responder', 'columns', 'rows', 'pages', 'count'));
            return;
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('survey_responders', 'Your survey responders'),
            'pageHeading'     => t('survey_responders', 'Survey responders'),
            'pageBreadcrumbs' => [
                t('surveys', 'Surveys') => createUrl('surveys/index'),
                $survey->name . ' ' => createUrl('surveys/overview', ['survey_uid' => $survey->survey_uid]),
                t('survey_responders', 'Responders') => createUrl('survey_responders/index', ['survey_uid' => $survey->survey_uid]),
                t('app', 'View all'),
            ],
        ]);

        $this->render('index', compact('survey', 'responder', 'columns', 'rows', 'pages', 'count', 'displayToggleColumns'));
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

        /** @var SurveyField[] $surveyFields */
        $surveyFields = SurveyField::model()->findAll([
            'condition' => 'survey_id = :lid',
            'params'    => [':lid' => (int)$survey->survey_id],
            'order'     => 'sort_order ASC',
        ]);

        if (empty($surveyFields)) {
            throw new CHttpException(404, t('survey_fields', 'Your survey does not have any field defined.'));
        }

        $usedTypes = [];
        foreach ($surveyFields as $field) {
            $usedTypes[] = $field->type->type_id;
        }
        $criteria = new CDbCriteria();
        $criteria->addInCondition('type_id', $usedTypes);

        /** @var SurveyFieldType[] $types */
        $types = SurveyFieldType::model()->findAll($criteria);

        $responder = new SurveyResponder();
        $responder->survey_id = (int)$survey->survey_id;

        /** @var SurveyFieldBuilderType[] $instances */
        $instances = [];

        /** @var CWebApplication $app */
        $app = app();

        foreach ($types as $type) {
            if (empty($type->identifier) || !is_file((string)Yii::getPathOfAlias($type->class_alias) . '.php')) {
                continue;
            }

            $component = $app->getWidgetFactory()->createWidget($this, $type->class_alias, [
                'fieldType' => $type,
                'survey'    => $survey,
                'responder' => $responder,
            ]);

            if (!($component instanceof SurveyFieldBuilderType)) {
                continue;
            }

            // run the component to hook into next events
            $component->run();

            $instances[] = $component;
        }

        /** @var array $fields */
        $fields = [];

        // if the fields are saved
        if (request()->getIsPostRequest()) {
            $transaction = db()->beginTransaction();

            try {
                /** @var Customer $customer */
                $customer = $survey->customer;

                $maxRespondersPerSurvey = (int)$customer->getGroupOption('surveys.max_responders_per_survey', -1);
                $maxResponders          = (int)$customer->getGroupOption('surveys.max_responders', -1);

                if ($maxResponders > -1 || $maxRespondersPerSurvey > -1) {
                    $criteria = new CDbCriteria();

                    if ($maxResponders > -1 && ($surveysIds = $customer->getAllSurveysIds())) {
                        $criteria->addInCondition('t.survey_id', $surveysIds);
                        $totalRespondersCount = SurveyResponder::model()->count($criteria);
                        if ($totalRespondersCount >= $maxResponders) {
                            throw new Exception(t('surveys', 'The maximum number of allowed responders has been reached.'));
                        }
                    }

                    if ($maxRespondersPerSurvey > -1) {
                        $criteria->compare('t.survey_id', (int)$survey->survey_id);
                        $surveyRespondersCount = SurveyResponder::model()->count($criteria);
                        if ($surveyRespondersCount >= $maxRespondersPerSurvey) {
                            throw new Exception(t('surveys', 'The maximum number of allowed responders for this survey has been reached.'));
                        }
                    }
                }

                $attributes = (array)request()->getPost($responder->getModelName(), []);
                if (empty($responder->ip_address)) {
                    $responder->ip_address = (string)request()->getUserHostAddress();
                }
                if (isset($attributes['status']) && in_array($attributes['status'], array_keys($responder->getStatusesList()))) {
                    $responder->status = (string)$attributes['status'];
                } else {
                    $responder->status = SurveyResponder::STATUS_ACTIVE;
                }

                if (!$responder->save()) {
                    if ($responder->hasErrors()) {
                        throw new Exception($responder->shortErrors->getAllAsString());
                    }
                    throw new Exception(t('app', 'Temporary error, please contact us if this happens too often!'));
                }

                // raise event
                $this->callbacks->onResponderSave(new CEvent($this->callbacks, [
                    'fields' => &$fields,
                ]));

                // if no error thrown but still there are errors in any of the instances, stop.
                foreach ($instances as $instance) {
                    if (!empty($instance->errors)) {
                        throw new Exception(t('app', 'Your form has a few errors. Please fix them and try again!'));
                    }
                }

                // add the default success message
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));

                // raise event. at this point everything seems to be fine.
                $this->callbacks->onResponderSaveSuccess(new CEvent($this->callbacks, [
                    'instances' => $instances,
                    'responder' => $responder,
                    'survey'    => $survey,
                ]));

                // since 1.8.2
                $customer->logAction->responderCreated($responder);

                $transaction->commit();
            } catch (Exception $e) {
                $transaction->rollback();
                notify()->addError($e->getMessage());

                // bind default save error event handler
                $this->callbacks->onResponderSaveError = [$this->callbacks, '_collectAndShowErrorMessages'];

                // raise event
                $this->callbacks->onResponderSaveError(new CEvent($this->callbacks, [
                    'instances' => $instances,
                    'responder' => $responder,
                    'survey'    => $survey,
                ]));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller' => $this,
                'success'    => notify()->getHasSuccess(),
                'responder'  => $responder,
            ]));

            if ($collection->itemAt('success')) {
                if (request()->getPost('next_action') && request()->getPost('next_action') == 'create-new') {
                    $this->redirect(['survey_responders/create', 'survey_uid' => $responder->survey->survey_uid]);
                }
                $this->redirect(['survey_responders/update', 'survey_uid' => $responder->survey->survey_uid, 'responder_uid' => $responder->responder_uid]);
            }
        }

        // raise event. simply the fields are shown
        $this->callbacks->onResponderFieldsDisplay(new CEvent($this->callbacks, [
            'fields' => &$fields,
        ]));

        // add the default sorting of fields actions and raise the event
        $this->callbacks->onResponderFieldsSorting = [$this->callbacks, '_orderFields'];
        $this->callbacks->onResponderFieldsSorting(new CEvent($this->callbacks, [
            'fields' => &$fields,
        ]));

        /** @var array $fields */
        $fields = !empty($fields) && is_array($fields) ? $fields : [];

        // and build the html for the fields.
        $fieldsHtml = '';

        foreach ($fields as $field) {
            $fieldsHtml .= $field['field_html'];
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('survey_responders', 'Add a new responder to your survey.'),
            'pageHeading'     => t('survey_responders', 'Add a new responder to your survey.'),
            'pageBreadcrumbs' => [
                t('surveys', 'Surveys') => createUrl('surveys/index'),
                $survey->name . ' ' => createUrl('surveys/overview', ['survey_uid' => $survey->survey_uid]),
                t('survey_responders', 'Responders') => createUrl('survey_responders/index', ['survey_uid' => $survey->survey_uid]),
                t('app', 'Create new'),
            ],
        ]);

        $this->render('form', compact('fieldsHtml', 'survey', 'responder'));
    }

    /**
     * @param string $survey_uid
     * @param string $responder_uid
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionUpdate($survey_uid, $responder_uid)
    {
        /** @var Survey $survey */
        $survey = $this->loadSurveyModel((string)$survey_uid);

        /** @var SurveyResponder $responder */
        $responder = $this->loadResponderModel((int)$survey->survey_id, (string)$responder_uid);

        if ($survey->customer->getGroupOption('surveys.can_edit_own_responders', 'yes') != 'yes') {
            notify()->addError(t('survey_responders', 'You are not allowed to edit responders at this time!'));
            $this->redirect(['survey_responders/index', 'survey_uid' => $survey->survey_uid]);
        }

        /** @var SurveyField[] $surveyFields */
        $surveyFields = SurveyField::model()->findAll([
            'condition' => 'survey_id = :sid',
            'params'    => [':sid' => $survey->survey_id],
            'order'     => 'sort_order ASC',
        ]);

        if (empty($surveyFields)) {
            throw new CHttpException(404, t('survey', 'Your survey does not have any field defined.'));
        }

        $usedTypes = [];
        foreach ($surveyFields as $field) {
            $usedTypes[] = $field->type_id;
        }
        $criteria = new CDbCriteria();
        $criteria->addInCondition('type_id', $usedTypes);

        /** @var SurveyFieldType[] $types */
        $types = SurveyFieldType::model()->findAll($criteria);

        /** @var SurveyFieldBuilderType[] $instances */
        $instances = [];

        /** @var CWebApplication $app */
        $app = app();

        foreach ($types as $type) {
            if (empty($type->identifier) || !is_file((string)Yii::getPathOfAlias($type->class_alias) . '.php')) {
                continue;
            }

            $component = $app->getWidgetFactory()->createWidget($this, $type->class_alias, [
                'fieldType' => $type,
                'survey'    => $survey,
                'responder' => $responder,
            ]);

            if (!($component instanceof SurveyFieldBuilderType)) {
                continue;
            }

            // run the component to hook into next events
            $component->run();

            $instances[] = $component;
        }

        /** @var array $fields */
        $fields = [];

        // if the fields are saved
        if (request()->getIsPostRequest()) {
            $transaction = db()->beginTransaction();

            try {
                $attributes = (array)request()->getPost($responder->getModelName(), []);
                if (empty($responder->ip_address)) {
                    $responder->ip_address = (string)request()->getUserHostAddress();
                }

                if (isset($attributes['status']) && in_array($attributes['status'], array_keys($responder->getStatusesList()))) {
                    $responder->status = (string)$attributes['status'];
                } else {
                    $responder->status = SurveyResponder::STATUS_ACTIVE;
                }

                if (!$responder->save()) {
                    if ($responder->hasErrors()) {
                        throw new Exception($responder->shortErrors->getAllAsString());
                    }
                    throw new Exception(t('app', 'Temporary error, please contact us if this happens too often!'));
                }

                // raise event
                $this->callbacks->onResponderSave(new CEvent($this->callbacks, [
                    'fields' => &$fields,
                ]));

                // if no error thrown but still there are errors in any of the instances, stop.
                foreach ($instances as $instance) {
                    if (!empty($instance->errors)) {
                        throw new Exception(t('app', 'Your form has a few errors. Please fix them and try again!'));
                    }
                }

                // add the default success message
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));

                // raise event. at this point everything seems to be fine.
                $this->callbacks->onResponderSaveSuccess(new CEvent($this->callbacks, [
                    'instances' => $instances,
                    'responder' => $responder,
                    'survey'    => $survey,
                ]));

                // since 1.8.2
                $survey->customer->logAction->responderUpdated($responder);

                $transaction->commit();
            } catch (Exception $e) {
                $transaction->rollback();
                notify()->addError($e->getMessage());

                // bind default save error event handler
                $this->callbacks->onResponderSaveError = [$this->callbacks, '_collectAndShowErrorMessages'];

                // raise event
                $this->callbacks->onResponderSaveError(new CEvent($this->callbacks, [
                    'instances' => $instances,
                    'responder' => $responder,
                    'survey'    => $survey,
                ]));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller' => $this,
                'success'    => notify()->getHasSuccess(),
                'responder'  => $responder,
            ]));

            if ($collection->itemAt('success')) {
                if (request()->getPost('next_action') && request()->getPost('next_action') == 'create-new') {
                    $this->redirect(['survey_responders/create', 'survey_uid' => $responder->survey->survey_uid]);
                }
            }
        }

        // raise event. simply the fields are shown
        $this->callbacks->onResponderFieldsDisplay(new CEvent($this->callbacks, [
            'fields' => &$fields,
        ]));

        // add the default sorting of fields actions and raise the event
        $this->callbacks->onResponderFieldsSorting = [$this->callbacks, '_orderFields'];
        $this->callbacks->onResponderFieldsSorting(new CEvent($this->callbacks, [
            'fields' => &$fields,
        ]));

        /** @var array $fields */
        $fields = !empty($fields) && is_array($fields) ? $fields : [];

        // and build the html for the fields.
        $fieldsHtml = '';

        foreach ($fields as $field) {
            $fieldsHtml .= $field['field_html'];
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('survey_responders', 'Update existing survey responder.'),
            'pageHeading'     => t('survey_responders', 'Update existing survey responder.'),
            'pageBreadcrumbs' => [
                t('surveys', 'Surveys') => createUrl('surveys/index'),
                $survey->name . ' ' => createUrl('surveys/overview', ['survey_uid' => $survey->survey_uid]),
                t('survey_responders', 'Responders') => createUrl('survey_responders/index', ['survey_uid' => $survey->survey_uid]),
                t('app', 'Update'),
            ],
        ]);

        $this->render('form', compact('fieldsHtml', 'survey', 'responder'));
    }

    /**
     * @param string $survey_uid
     * @param string $responder_uid
     *
     * @return void
     * @throws CDbException
     * @throws CException
     * @throws CHttpException
     */
    public function actionDelete($survey_uid, $responder_uid)
    {
        /** @var Survey $survey */
        $survey = $this->loadSurveyModel((string)$survey_uid);

        /** @var SurveyResponder $responder */
        $responder = $this->loadResponderModel((int)$survey->survey_id, (string)$responder_uid);

        if ($responder->getCanBeDeleted()) {
            $responder->delete();

            // since 1.8.2
            $survey->customer->logAction->responderDeleted($responder);
        }

        $redirect = null;
        if (!request()->getIsAjaxRequest()) {
            notify()->addSuccess(t('survey_responders', 'Your survey responder was successfully deleted!'));
            $redirect = request()->getPost('returnUrl', ['survey_responders/index', 'survey_uid' => $survey->survey_uid]);
        }

        // since 1.3.5.9
        hooks()->doAction('controller_action_delete_data', $collection = new CAttributeCollection([
            'controller' => $this,
            'survey'     => $survey,
            'responder'  => $responder,
            'redirect'   => $redirect,
        ]));

        if ($collection->itemAt('redirect')) {
            $this->redirect($collection->itemAt('redirect'));
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

    /**
     * @param int $survey_id
     * @param string $responder_uid
     *
     * @return SurveyResponder
     * @throws CHttpException
     */
    public function loadResponderModel(int $survey_id, string $responder_uid): SurveyResponder
    {
        $model = SurveyResponder::model()->findByAttributes([
            'responder_uid' => $responder_uid,
            'survey_id'     => (int)$survey_id,
        ]);

        if ($model === null) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        return $model;
    }
}
