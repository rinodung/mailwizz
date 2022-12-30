<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * SurveyFieldBuilderTypeCheckboxlistCrud
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
 * @property SurveyFieldBuilderTypeCheckboxlist $owner
 */
class SurveyFieldBuilderTypeCheckboxlistCrud extends SurveyFieldBuilderTypeCrud
{
    /**
     * @param CEvent $event
     *
     * @return void
     * @throws Exception
     */
    public function _saveFields(CEvent $event)
    {
        $fieldType = $this->owner->getFieldType();
        $survey    = $this->owner->getSurvey();
        $typeName  = $fieldType->identifier;

        if (!isset($event->params['fields'][$typeName]) || !is_array($event->params['fields'][$typeName])) {
            $event->params['fields'][$typeName] = [];
        }

        /** @var array $postModels */
        $postModels = (array)request()->getPost('SurveyField', []);

        /** @var array $optionPostModels */
        $optionPostModels = (array)request()->getPost('SurveyFieldOption', []);

        if (!isset($postModels[$typeName]) || !is_array($postModels[$typeName])) {
            $postModels[$typeName] = [];
        }

        if (!isset($optionPostModels[$typeName]) || !is_array($optionPostModels[$typeName])) {
            $optionPostModels[$typeName] = [];
        }

        /** @var SurveyField[] $models */
        $models = [];

        foreach ($postModels[$typeName] as $index => $attributes) {

            /** @var SurveyField|null $model */
            $model = null;

            if (!empty($attributes['field_id'])) {

                /** @var SurveyField $model */
                $model = SurveyField::model()->findByAttributes([
                    'field_id'  => (int)$attributes['field_id'],
                    'type_id'   => (int)$fieldType->type_id,
                    'survey_id' => (int)$survey->survey_id,
                ]);
            }

            if (isset($attributes['field_id'])) {
                unset($attributes['field_id']);
            }

            if (empty($model)) {
                $model = new SurveyField();
            }

            $model->attributes = $attributes;
            $model->type_id    = (int)$fieldType->type_id;
            $model->survey_id  = (int)$survey->survey_id;

            if (!empty($optionPostModels[$typeName][$index]) && is_array($optionPostModels[$typeName][$index])) {
                foreach ($optionPostModels[$typeName][$index] as $attrs) {

                    /** @var SurveyFieldOption|null $mdl */
                    $mdl = null;

                    if (isset($attrs['option_id'], $attrs['field_id'])) {

                        /** @var SurveyFieldOption|null $mdl */
                        $mdl = SurveyFieldOption::model()->findByAttributes([
                            'option_id' => (int)$attrs['option_id'],
                            'field_id'  => (int)$attrs['field_id'],
                        ]);
                    }

                    if (isset($attrs['option_id'])) {
                        unset($attrs['option_id']);
                    }

                    if (isset($attrs['field_id'])) {
                        unset($attrs['field_id']);
                    }

                    if (empty($mdl)) {
                        $mdl = new SurveyFieldOption();
                    }

                    $mdl->attributes = $attrs;
                    $model->addRelatedRecord('options', $mdl, true);
                }
            }
            $models[] = $model;
        }

        /** @var int[] $modelsToKeep */
        $modelsToKeep = [];

        /** @var int[] $optionModelsToKeep */
        $optionModelsToKeep = [];

        foreach ($models as $model) {
            if (!$model->save()) {
                $this->owner->errors[] = [
                    'show'      => false,
                    'message'   => $model->shortErrors->getAllAsString(),
                ];
                continue;
            }

            if (!empty($model->options)) {

                /** @var SurveyFieldOption[] $options */
                $options = $model->options;

                foreach ($options as $option) {
                    $option->field_id = (int)$model->field_id;
                    if (!$option->save()) {
                        $this->owner->errors[] = [
                            'show'      => false,
                            'message'   => $option->shortErrors->getAllAsString(),
                        ];
                    } else {
                        $optionModelsToKeep[] = (int)$option->option_id;
                    }
                }
            }
            $modelsToKeep[] = (int)$model->field_id;
        }

        if (empty($this->owner->errors)) {
            $criteria = new CDbCriteria();
            $criteria->compare('survey_id', $survey->survey_id);
            $criteria->compare('type_id', $fieldType->type_id);
            $criteria->addNotInCondition('field_id', $modelsToKeep);
            SurveyField::model()->deleteAll($criteria);

            // delete options
            $criteria = new CDbCriteria();
            $criteria->addInCondition('field_id', $modelsToKeep);
            $criteria->addNotInCondition('option_id', $optionModelsToKeep);
            SurveyFieldOption::model()->deleteAll($criteria);
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

        /** @var SurveyField[] $models */
        $models = SurveyField::model()->findAllByAttributes([
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
        $model     = new SurveyField();
        $option    = new SurveyFieldOption();
        $fieldType = $this->owner->getFieldType();
        $survey    = $this->owner->getSurvey();

        /** default view file. */
        $viewFile = realpath(dirname(__FILE__) . '/../views/field-tpl-js.php');

        /** and render. */
        $this->owner->renderInternal($viewFile, compact('model', 'option', 'fieldType', 'survey'));
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
        $fieldType = $this->owner->getFieldType();
        $survey    = $this->owner->getSurvey();

        /** so that it increments properly. */
        $index = $this->owner->getIndex();

        $viewFile = realpath(dirname(__FILE__) . '/../views/field-tpl.php');
        $model->fieldDecorator->onHtmlOptionsSetup = [$this->owner, '_addInputErrorClass'];

        return [
            'sort_order' => (int)$model->sort_order,
            'field_html' => $this->owner->renderInternal($viewFile, compact('model', 'index', 'fieldType', 'survey'), true),
        ];
    }
}
