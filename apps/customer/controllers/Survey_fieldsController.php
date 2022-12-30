<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * Survey_fieldsController
 *
 * Handles the actions for list fields related tasks
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
class Survey_fieldsController extends Controller
{
    /**
     * @return void
     * @throws CException
     */
    public function init()
    {
        Yii::import('customer.components.survey-field-builder.*');
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
     * @throws CException
     * @throws CHttpException
     */
    public function actionIndex($survey_uid)
    {
        /** @var Survey $survey */
        $survey = $this->loadSurveyModel((string)$survey_uid);

        /** @var SurveyFieldType[] $types */
        $types = SurveyFieldType::model()->findAll();

        if (empty($types)) {
            throw new CHttpException(400, t('survey_fields', 'There is no field type defined yet, please contact the administrator.'));
        }

        /** @var CWebApplication $app */
        $app = app();

        /** @var SurveyFieldBuilderType[] $instances */
        $instances = [];

        foreach ($types as $type) {
            if (empty($type->identifier) || !is_file((string)Yii::getPathOfAlias($type->class_alias) . '.php')) {
                continue;
            }

            $component = $app->getWidgetFactory()->createWidget($this, $type->class_alias, [
                'fieldType'   => $type,
                'survey'      => $survey,
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

                // raise event
                $this->callbacks->onSurveyFieldsSave(new CEvent($this->callbacks, [
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
                $this->callbacks->onSurveyFieldsSaveSuccess(new CEvent($this->callbacks, [
                    'instances' => $instances,
                ]));

                $transaction->commit();
            } catch (Exception $e) {
                $transaction->rollback();
                notify()->addError($e->getMessage());

                // bind default save error event handler
                $this->callbacks->onResponderSaveError = [$this->callbacks, '_collectAndShowErrorMessages'];

                // raise event
                $this->callbacks->onSurveyFieldsSaveError(new CEvent($this->callbacks, [
                    'instances' => $instances,
                ]));
            }
        }

        // raise event. simply the fields are shown
        $this->callbacks->onSurveyFieldsDisplay(new CEvent($this->callbacks, [
            'fields' => &$fields,
        ]));

        // add the default sorting of fields actions and raise the event
        $this->callbacks->onSurveyFieldsSorting = [$this->callbacks, '_orderFields'];
        $this->callbacks->onSurveyFieldsSorting(new CEvent($this->callbacks, [
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
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('survey_fields', 'Your survey fields'),
            'pageHeading'     => t('survey_fields', 'Survey fields'),
            'pageBreadcrumbs' => [
                t('surveys', 'Surveys')    => createUrl('surveys/index'),
                $survey->name . ' ' => createUrl('surveys/overview', ['survey_uid' => $survey->survey_uid]),
                t('survey_fields', 'Fields'),
            ],
        ]);

        $this->render('index', compact('fieldsHtml', 'survey'));
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
            'survey_uid'    => $survey_uid,
            'customer_id'   => (int)customer()->getId(),
        ]);

        if ($model === null) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        return $model;
    }
}
