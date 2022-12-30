<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * SurveyFieldBuilderTypeMultiselectResponder
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
 * @property SurveyFieldBuilderTypeMultiselect $owner
 */
class SurveyFieldBuilderTypeMultiselectResponder extends SurveyFieldBuilderTypeResponder
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
        $typeName  = $fieldType->identifier;

        /** @var array $valueModels */
        $valueModels = $this->getValueModels();
        $fields      = [];

        if (!isset($event->params['fields'][$typeName]) || !is_array($event->params['fields'][$typeName])) {
            $event->params['fields'][$typeName] = [];
        }

        /** run validation so that fields will get the errors if any. */
        foreach ($valueModels as $models) {
            $validModels = $invalidModels = [];
            foreach ($models as $model) {
                if (!$model->validate()) {
                    $invalidModels[] = $model;
                } else {
                    $validModels[] = $model;
                }
            }
            if (count($validModels) == 0) {
                foreach ($invalidModels as $model) {
                    $this->owner->errors[] = [
                        'show'      => false,
                        'message'   => $model->shortErrors->getAllAsString(),
                    ];
                }
            } else {
                foreach ($models as $model) {
                    $model->clearErrors();
                }
            }
            unset($validModels, $invalidModels);

            $fields[] = $this->buildFieldArrayMultiValues($models);
        }

        /** make the fields available */
        $event->params['fields'][$typeName] = $fields;

        /** do the actual saving of fields if there are no errors. */
        if (empty($this->owner->errors)) {
            foreach ($valueModels as $models) {
                foreach ($models as $model) {
                    if (strlen(trim((string)$model->value)) === 0) {
                        if (!$model->isNewRecord) {
                            $model->delete();
                        }
                        continue;
                    }
                    $model->save(false);
                }
            }
        }
    }

    /**
     * @param CEvent $event
     *
     * @return void
     * @throws Exception
     */
    public function _displayFields(CEvent $event)
    {
        /** @var SurveyFieldType $fieldType */
        $fieldType = $this->owner->getFieldType();
        $typeName  = $fieldType->identifier;

        /** fields created in the save action. */
        if (isset($event->params['fields'][$typeName]) && is_array($event->params['fields'][$typeName])) {
            return;
        }

        if (!isset($event->params['fields'][$typeName]) || !is_array($event->params['fields'][$typeName])) {
            $event->params['fields'][$typeName] = [];
        }

        /** @var array $valueModels */
        $valueModels = $this->getValueModels();
        $fields      = [];

        foreach ($valueModels as $models) {
            $fields[] = $this->buildFieldArrayMultiValues($models);
        }

        $event->params['fields'][$typeName] = $fields;
    }

    /**
     * @param CModelEvent $event
     *
     * @return void
     */
    public function _setCorrectLabel(CModelEvent $event)
    {
        $event->params['labels']['value'] = $event->sender->field->label;
    }

    /**
     * @param CModelEvent $event
     *
     * @return void
     */
    public function _setCorrectValidationRules(CModelEvent $event)
    {
        /** @var CList $rules */
        $rules = $event->params['rules'];

        /** clear any other rule we have so far */
        $rules->clear();

        /** start adding new rules. */
        if ($event->sender->field->required === 'yes') {
            $rules->add(['value', 'required']);
        }

        $rules->add(['value', 'length', 'max' => 255]);
    }

    /**
     * @param CModelEvent $event
     *
     * @return void
     */
    public function _setCorrectHelpText(CModelEvent $event)
    {
        $event->params['texts']['value'] = $event->sender->field->help_text;
    }

    /**
     * @return SurveyFieldValue[]
     * @throws Exception
     */
    protected function getValueModels(): array
    {
        /** @var SurveyFieldType $fieldType */
        $fieldType = $this->owner->getFieldType();
        $survey    = $this->owner->getSurvey();
        $responder = $this->owner->getResponder();

        /** @var SurveyField[] $models */
        $models = SurveyField::model()->findAllByAttributes([
            'type_id'   => (int)$fieldType->type_id,
            'survey_id' => (int)$survey->survey_id,
        ]);

        /** @var array $valueModels */
        $valueModels = [];

        foreach ($models as $model) {

            /** @var SurveyFieldValue[] $_valueModels */
            $_valueModels = [];
            $modelOptions = !empty($model->options) ? $model->options : [];

            $defaultValue = !empty($model->default_value) ? array_map(function (string $item) use ($responder): string {
                return (string)SurveyField::parseDefaultValueTags($item, $responder);
            }, array_map('strval', explode(',', $model->default_value))) : [];
            $defaultValue = array_map('trim', $defaultValue);

            $hasOptionsSet = SurveyFieldValue::model()->countByAttributes([
                'field_id'      => (int)$model->field_id,
                'responder_id'  => (int)$responder->responder_id,
            ]);

            foreach ($modelOptions as $modelOption) {
                $valueModel = SurveyFieldValue::model()->findByAttributes([
                    'field_id'      => (int)$model->field_id,
                    'responder_id'  => (int)$responder->responder_id,
                    'value'         => $modelOption->value,
                ]);
                if (empty($valueModel)) {
                    $valueModel = new SurveyFieldValue();
                }

                $valueModel->onAttributeLabels = [$this, '_setCorrectLabel'];
                $valueModel->onRules = [$this, '_setCorrectValidationRules'];
                $valueModel->onAttributeHelpTexts = [$this, '_setCorrectHelpText'];
                $valueModel->fieldDecorator->onHtmlOptionsSetup = [$this->owner, '_addInputErrorClass'];
                $valueModel->fieldDecorator->onHtmlOptionsSetup = [$this->owner, '_addFieldNameClass'];

                $postValues  = (array)request()->getPost($model->getTag(), []);
                if (request()->getIsPostRequest()) {
                    $foundValue = false;
                    foreach ($postValues as $val) {
                        if ($val == $modelOption->value) {
                            $valueModel->value = $val;
                            $foundValue = true;
                            break;
                        }
                    }
                    if (!$foundValue) {
                        $valueModel->value = null;
                    }
                } else {
                    if (!$hasOptionsSet && in_array($modelOption->value, $defaultValue)) {
                        $valueModel->value = $modelOption->value;
                    }
                }

                /** assign props */
                $valueModel->field         = $model;
                $valueModel->field_id      = (int)$model->field_id;
                $valueModel->responder_id  = (int)$responder->responder_id;

                $_valueModels[] = $valueModel;
            }

            $valueModels[] = $_valueModels;
        }

        return $valueModels;
    }

    /**
     * @param SurveyFieldValue $model
     *
     * @return array
     */
    protected function buildFieldArray(SurveyFieldValue $model): array
    {
        return [];
    }

    /**
     * @param SurveyFieldValue[] $models
     *
     * @return array
     */
    protected function buildFieldArrayMultiValues(array $models): array
    {
        if (empty($models)) {
            return [
                'sort_order' => -100,
                'field_html' => null,
            ];
        }

        /** @var SurveyField $field */
        $field = $models[0]->field;

        $fieldHtml = '';
        $viewFile  = realpath(dirname(__FILE__) . '/../views/field-display.php');
        $options   = [];

        if ($field->required != 'yes') {
            $options[''] = t('app', 'Please choose');
        }

        if (!empty($field->options)) {

            /** @var SurveyFieldOption[] $_options */
            $_options = $field->options;

            foreach ($_options as $option) {
                $options[$option->value] = $option->name;
            }
        }

        $values = [];
        foreach ($models as $model) {
            if (strlen(trim((string)$model->value)) == 0) {
                continue;
            }
            $values[] = $model->value;
        }

        if (!$field->getVisibilityIsNone() || apps()->isAppName('customer')) {
            $visible = $field->getVisibilityIsVisible() || apps()->isAppName('customer');
            $fieldHtml = $this->owner->renderInternal($viewFile, compact('model', 'field', 'values', 'options', 'visible'), true);
        }

        return (array)hooks()->applyFilters('survey_field_builder_type_multiselect_responder_build_field_array', [
            'sort_order' => (int)$field->sort_order,
            'field_html' => $fieldHtml,
        ], $models[0], $field, $values, $options, $models);
    }
}
