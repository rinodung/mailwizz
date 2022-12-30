<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * List_fieldsController
 *
 * Handles the actions for list fields related tasks
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

/**
 * @property ListFieldsControllerCallbacksBehavior $callbacks
 */
class List_fieldsController extends Controller
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

        Yii::import('customer.components.list-field-builder.*');
        parent::init();
    }

    /**
     * List of behaviors attached to this controller
     * The behaviors are merged with the one from parent implementation
     *
     * @return array
     * @throws CException
     */
    public function behaviors()
    {
        return CMap::mergeArray([
            'callbacks' => [
                'class' => 'customer.components.behaviors.ListFieldsControllerCallbacksBehavior',
            ],
        ], parent::behaviors());
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

        /** @var ListFieldType[] $types */
        $types = ListFieldType::model()->findAll();

        if (empty($types)) {
            throw new CHttpException(400, t('list_fields', 'There is no field type defined yet, please contact the administrator.'));
        }

        /** @var ListFieldBuilderType[] $instances */
        $instances = [];

        /** @var CWebApplication $app */
        $app = app();

        foreach ($types as $type) {
            if (empty($type->identifier) || !is_file((string)Yii::getPathOfAlias($type->class_alias) . '.php')) {
                continue;
            }

            $component = $app->getWidgetFactory()->createWidget($this, $type->class_alias, [
                'fieldType' => $type,
                'list'      => $list,
            ]);

            if (!($component instanceof ListFieldBuilderType)) {
                continue;
            }

            // run the component to hook into next events
            $component->run();

            $instances[] = $component;
        }

        $fields  = [];

        // if the fields are saved
        if (request()->getIsPostRequest()) {
            $transaction = db()->beginTransaction();
            $hasErrors   = false;

            try {

                // raise event
                $this->callbacks->onListFieldsSave(new CEvent($this->callbacks, [
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
                $this->callbacks->onListFieldsSaveSuccess(new CEvent($this->callbacks, [
                    'instances' => $instances,
                ]));

                $transaction->commit();
            } catch (Exception $e) {
                $transaction->rollback();
                notify()->addError($e->getMessage());

                // bind default save error event handler
                $this->callbacks->onSubscriberSaveError = [$this->callbacks, '_collectAndShowErrorMessages'];

                // raise event
                $this->callbacks->onListFieldsSaveError(new CEvent($this->callbacks, [
                    'instances' => $instances,
                ]));

                $hasErrors = true;
            }

            // 1.3.8.7

            /** @var  OptionCronProcessSubscribers $optionCronProcessSubscribers */
            $optionCronProcessSubscribers = container()->get(OptionCronProcessSubscribers::class);

            if (!$hasErrors && $optionCronProcessSubscribers->getSyncCustomFieldsValues()) {
                notify()->addInfo(t('list_fields', 'Please note that it will take a while to synchronize the existing subscribers with the new custom fields defaults!'));
            }
        }

        // raise event. simply the fields are shown
        $this->callbacks->onListFieldsDisplay(new CEvent($this->callbacks, [
            'fields' => &$fields,
        ]));

        // add the default sorting of fields actions and raise the event
        $this->callbacks->onListFieldsSorting = [$this->callbacks, '_orderFields'];
        $this->callbacks->onListFieldsSorting(new CEvent($this->callbacks, [
            'fields' => &$fields,
        ]));

        /** @var array $fields */
        $fields = !empty($fields) && is_array($fields) ? $fields : []; // @phpstan-ignore-line

        // and build the html for the fields.
        $fieldsHtml = '';

        foreach ($fields as $field) {
            $fieldsHtml .= $field['field_html'];
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('list_fields', 'Your mail lists custom fields'),
            'pageHeading'     => t('list_fields', 'List custom fields'),
            'pageBreadcrumbs' => [
                t('lists', 'Lists')    => createUrl('lists/index'),
                $list->name . ' '           => createUrl('lists/overview', ['list_uid' => $list->list_uid]),
                t('list_fields', 'Custom fields'),
            ],
        ]);

        $this->render('index', compact('fieldsHtml', 'list'));
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
