<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * SurveyFieldBuilderTypeCountryResponder
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
 * @property SurveyFieldBuilderTypeCountry $owner
 */
class SurveyFieldBuilderTypeCountryResponder extends SurveyFieldBuilderTypeResponder
{
    /**
     * @param CComponent $owner
     *
     * @return void
     * @throws CException
     */
    public function attach($owner)
    {
        /** register the assets. */
        $assetsUrl = assetManager()->publish((string)realpath(dirname(__FILE__) . '/../assets/'), false, -1, MW_DEBUG);

        /** push the file into the queue. */
        clientScript()->registerScriptFile($assetsUrl . '/field-front.js');

        parent::attach($owner);
    }

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

        /** @var SurveyFieldValue[] $valueModels */
        $valueModels = $this->getValueModels();
        $fields      = [];

        if (!isset($event->params['fields'][$typeName]) || !is_array($event->params['fields'][$typeName])) {
            $event->params['fields'][$typeName] = [];
        }

        /** run validation so that fields will get the errors if any. */
        foreach ($valueModels as $model) {
            if (!$model->validate()) {
                $this->owner->errors[] = [
                    'show'      => false,
                    'message'   => $model->shortErrors->getAllAsString(),
                ];
            }
            $fields[] = $this->buildFieldArray($model);
        }

        /** make the fields available */
        $event->params['fields'][$typeName] = $fields;

        /** do the actual saving of fields if there are no errors. */
        if (empty($this->owner->errors)) {
            foreach ($valueModels as $model) {
                $model->save(false);
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

        /** @var SurveyFieldValue[] $valueModels */
        $valueModels = $this->getValueModels();
        $fields      = [];

        foreach ($valueModels as $model) {
            $fields[] = $this->buildFieldArray($model);
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

        $rules->add(['value', 'exist', 'className' => Country::class, 'attributeName' => 'name']);
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

        /** @var SurveyFieldValue[] $valueModels */
        $valueModels = [];

        foreach ($models as $model) {
            $valueModel = SurveyFieldValue::model()->findByAttributes([
                'field_id'     => (int)$model->field_id,
                'responder_id' => (int)$responder->responder_id,
            ]);

            if (empty($valueModel)) {
                $valueModel = new SurveyFieldValue();
            }

            /** setup rules and labels here. */
            $valueModel->onAttributeLabels = [$this, '_setCorrectLabel'];
            $valueModel->onRules = [$this, '_setCorrectValidationRules'];
            $valueModel->onAttributeHelpTexts = [$this, '_setCorrectHelpText'];
            $valueModel->fieldDecorator->onHtmlOptionsSetup = [$this->owner, '_addInputErrorClass'];
            $valueModel->fieldDecorator->onHtmlOptionsSetup = [$this->owner, '_addFieldNameClass'];

            /** set the correct default value. */
            $defaultValue = empty($valueModel->value) ? SurveyField::parseDefaultValueTags((string)$model->default_value, $responder) : $valueModel->value;

            /** assign props */
            $valueModel->field        = $model;
            $valueModel->field_id     = (int)$model->field_id;
            $valueModel->responder_id = (int)$responder->responder_id;
            $valueModel->value        = request()->getPost($model->getTag(), $defaultValue);

            $valueModels[] = $valueModel;
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
        /** @var SurveyField $field */
        $field = $model->field;

        $fieldHtml = '';
        $viewFile  = realpath(dirname(__FILE__) . '/../views/field-display.php');

        $countriesList = [];
        if ($field->required != SurveyField::TEXT_YES) {
            $countriesList[''] = t('app', 'Please choose');
        }
        $criteria = new CDbCriteria();
        $criteria->select = 'name';
        $criteria->order  = 'name ASC';
        $countries = Country::model()->findAll($criteria);
        foreach ($countries as $country) {
            $countriesList[$country->name] = $country->name;
        }

        if (!$field->getVisibilityIsNone() || apps()->isAppName('customer')) {
            $visible = $field->getVisibilityIsVisible() || apps()->isAppName('customer');
            $fieldHtml = $this->owner->renderInternal($viewFile, compact('model', 'field', 'countriesList', 'visible'), true);
        }

        return (array)hooks()->applyFilters('survey_field_builder_type_country_responder_build_field_array', [
            'sort_order' => (int)$field->sort_order,
            'field_html' => $fieldHtml,
        ], $model, $field, $countriesList);
    }
}
