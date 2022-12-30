<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * SurveyFieldBuilderTypeTextCrud
 *
 * The save action is running inside an active transaction.
 * For fatal errors, an exception must be thrown, otherwise the errors array must be populated.
 * If an exception is thrown, or the errors array is populated, the transaction is rolled back.
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.7.8
 */

/**
 * @property SurveyFieldBuilderTypeText $owner
 */
class SurveyFieldBuilderTypeTextCrud extends SurveyFieldBuilderTypeCrud
{
    /**
     * @param CEvent $event
     *
     * @return void
     * @throws Exception
     */
    public function _saveFields(CEvent $event)
    {
        /** @var SurveyFieldType $fieldType */
        $fieldType = $this->owner->getFieldType();
        $survey    = $this->owner->getSurvey();
        $typeName  = $fieldType->identifier;

        if (!isset($event->params['fields'][$typeName]) || !is_array($event->params['fields'][$typeName])) {
            $event->params['fields'][$typeName] = [];
        }

        /** @var array $postModels */
        $postModels = (array)request()->getPost('SurveyFieldText', []);
        if (!isset($postModels[$typeName]) || !is_array($postModels[$typeName])) {
            $postModels[$typeName] = [];
        }

        /** @var SurveyField[] $models */
        $models = [];

        foreach ($postModels[$typeName] as $attributes) {

            /** @var SurveyField|null $model */
            $model = null;

            if (!empty($attributes['field_id'])) {
                $model = SurveyFieldText::model()->findByAttributes([
                    'field_id'  => (int)$attributes['field_id'],
                    'type_id'   => (int)$fieldType->type_id,
                    'survey_id' => (int)$survey->survey_id,
                ]);
            }

            if (isset($attributes['field_id'])) {
                unset($attributes['field_id']);
            }

            if (empty($model)) {
                $model = new SurveyFieldText();
            }

            $model->attributes = $attributes;
            $model->type_id    = (int)$fieldType->type_id;
            $model->survey_id  = (int)$survey->survey_id;

            $models[] = $model;
        }

        /** @var int[] $modelsToKeep */
        $modelsToKeep = [];
        foreach ($models as $model) {
            if (!$model->save()) {
                $this->owner->errors[] = [
                    'show'      => false,
                    'message'   => $model->shortErrors->getAllAsString(),
                ];
            } else {
                $modelsToKeep[] = (int)$model->field_id;
            }
        }

        if (empty($this->owner->errors)) {
            $criteria = new CDbCriteria();
            $criteria->compare('survey_id', $survey->survey_id);
            $criteria->compare('type_id', $fieldType->type_id);
            $criteria->addNotInCondition('field_id', $modelsToKeep);
            SurveyFieldText::model()->deleteAll($criteria);
        }

        $fields = [];
        foreach ($models as $model) {
            $fields[] = $this->buildFieldArray($model);
        }

        $event->params['fields'][$typeName] = $fields;
    }

    /**
     * @param CEvent $event
     *
     * @return void
     * @throws CException
     */
    public function _displayFields(CEvent $event)
    {
        /** @var SurveyFieldType $fieldType */
        $fieldType = $this->owner->getFieldType();
        $survey    = $this->owner->getSurvey();
        $typeName  = $fieldType->identifier;

        /** register the add button. */
        hooks()->addAction('customer_controller_survey_fields_render_buttons', [$this, '_renderAddButton']);

        /** register the javascript template. */
        hooks()->addAction('customer_controller_survey_fields_after_form', [$this, '_registerJavascriptTemplate']);

        /** register the assets. */
        $assetsUrl = assetManager()->publish((string)realpath(dirname(__FILE__) . '/../assets/'), false, -1, MW_DEBUG);

        /** push the file into the queue. */
        clientScript()->registerScriptFile($assetsUrl . '/field.js');

        /** fields created in the save action. */
        if (isset($event->params['fields'][$typeName]) && is_array($event->params['fields'][$typeName])) {
            return;
        }

        if (!isset($event->params['fields'][$typeName]) || !is_array($event->params['fields'][$typeName])) {
            $event->params['fields'][$typeName] = [];
        }

        /** @var SurveyFieldText[] $models */
        $models = SurveyFieldText::model()->findAllByAttributes([
            'type_id'   => (int)$fieldType->type_id,
            'survey_id' => (int)$survey->survey_id,
        ]);

        $fields = [];
        foreach ($models as $model) {
            $fields[] = $this->buildFieldArray($model);
        }

        $event->params['fields'][$typeName] = $fields;
    }

    /**
     * @return void
     * @throws Exception
     */
    public function _registerJavascriptTemplate()
    {
        $model = new SurveyFieldText();

        /** @var SurveyFieldType $fieldType */
        $fieldType = $this->owner->getFieldType();
        $survey    = $this->owner->getSurvey();

        /** default view file. */
        $viewFile = realpath(dirname(__FILE__) . '/../views/field-tpl-js.php');

        /** and render. */
        $this->owner->renderInternal($viewFile, compact('model', 'fieldType', 'survey'));
    }

    /**
     * @param CEvent $event
     *
     * @return void
     */
    public function _addReadOnlyAttributes(CEvent $event)
    {
    }

    /**
     * @param SurveyField $model
     *
     * @return array
     * @throws Exception
     */
    protected function buildFieldArray(SurveyField $model): array
    {
        /** @var SurveyFieldType $fieldType */
        $fieldType = $this->owner->getFieldType();

        /** so that it increments properly. */
        $index = $this->owner->getIndex();

        $viewFile = realpath(dirname(__FILE__) . '/../views/field-tpl.php');
        $model->fieldDecorator->onHtmlOptionsSetup = [$this->owner, '_addInputErrorClass'];

        return [
            'sort_order' => (int)$model->sort_order,
            'field_html' => $this->owner->renderInternal($viewFile, compact('model', 'index', 'fieldType'), true),
        ];
    }
}
