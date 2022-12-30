<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * ListFieldBuilderTypeConsentCheckboxCrud
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
 * @since 1.5.5
 */

/**
 * @property ListFieldBuilderTypeConsentCheckbox $owner
 */
class ListFieldBuilderTypeConsentCheckboxCrud extends ListFieldBuilderTypeCrud
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
        $list      = $this->owner->getList();
        $typeName  = $fieldType->identifier;

        if (!isset($event->params['fields'][$typeName]) || !is_array($event->params['fields'][$typeName])) {
            $event->params['fields'][$typeName] = [];
        }

        /** @var array $postModels */
        $postModels = request()->getPost('ListFieldConsentCheckbox', []);

        if (!isset($postModels[$typeName]) || !is_array($postModels[$typeName])) {
            $postModels[$typeName] = [];
        }

        /** @var ListField[] $models */
        $models = [];
        foreach ($postModels[$typeName] as $attributes) {

            /** @var ListField|null $model */
            $model = null;

            if (!empty($attributes['field_id'])) {

                /** @var ListFieldConsentCheckbox $model */
                $model = ListFieldConsentCheckbox::model()->findByAttributes([
                    'field_id'  => (int)$attributes['field_id'],
                    'type_id'   => (int)$fieldType->type_id,
                    'list_id'   => (int)$list->list_id,
                ]);
            }

            if (isset($attributes['field_id'])) {
                unset($attributes['field_id']);
            }

            if (empty($model)) {
                $model = new ListFieldConsentCheckbox();
            }

            $model->attributes = $attributes;
            $model->type_id    = (int)$fieldType->type_id;
            $model->list_id    = (int)$list->list_id;

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
            $criteria->compare('list_id', $list->list_id);
            $criteria->compare('type_id', $fieldType->type_id);
            $criteria->addNotInCondition('field_id', $modelsToKeep);
            ListFieldConsentCheckbox::model()->deleteAll($criteria);
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
        $list      = $this->owner->getList();
        $typeName  = $fieldType->identifier;

        /** register the add button. */
        hooks()->addAction('customer_controller_list_fields_render_buttons', [$this, '_renderAddButton']);

        /** register the javascript template. */
        hooks()->addAction('customer_controller_list_fields_after_form', [$this, '_registerJavascriptTemplate']);

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

        $models = ListFieldConsentCheckbox::model()->findAllByAttributes([
            'type_id' => (int)$fieldType->type_id,
            'list_id' => (int)$list->list_id,
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
        $model     = new ListFieldConsentCheckbox();
        $fieldType = $this->owner->getFieldType();
        $list      = $this->owner->getList();

        // since 1.9.29
        $model->list_id    = (int)$list->list_id;
        $model->visibility = ListField::VISIBILITY_VISIBLE;

        $companyName = !empty($list->company->name) ? $list->company->name : '...';
        $model->consent_text = (string)str_replace('[COMPANY_NAME]', $companyName, $model->consent_text);

        /** default view file */
        $viewFile = realpath(dirname(__FILE__) . '/../views/field-tpl-js.php');

        /** and render */
        $this->owner->renderInternal($viewFile, compact('model', 'fieldType', 'list'));
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
     * @param ListField $model
     *
     * @return array
     * @throws Exception
     */
    protected function buildFieldArray(ListField $model): array
    {
        $fieldType = $this->owner->getFieldType();
        $list      = $this->owner->getList();

        $companyName = !empty($list->company->name) ? $list->company->name : '...';

        /** @var ListFieldConsentCheckbox $consentModel */
        $consentModel = $model;

        $consentModel->consent_text = (string)str_replace('[COMPANY_NAME]', $companyName, $consentModel->consent_text);

        /** so that it increments properly! */
        $index = $this->owner->getIndex();

        $viewFile = realpath(dirname(__FILE__) . '/../views/field-tpl.php');
        $consentModel->fieldDecorator->onHtmlOptionsSetup = [$this->owner, '_addInputErrorClass'];
        $consentModel->fieldDecorator->onHtmlOptionsSetup = [$this, '_addReadOnlyAttributes'];

        return [
            'sort_order' => (int)$consentModel->sort_order,
            'field_html' => $this->owner->renderInternal($viewFile, [
                'model'     => $consentModel,
                'index'     => $index,
                'fieldType' => $fieldType,
                'list'      => $list,
            ], true),
        ];
    }
}
