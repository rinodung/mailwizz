<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * SurveysController
 *
 * Handles the actions for surveys related tasks
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.7.8
 */

/**
 * @property SurveyControllerCallbacksBehavior $callbacks
 * @property CustomerActionLogBehavior $logAction
 */
class SurveysController extends Controller
{
    /**
     * @throws CException
     *
     * @return void
     */
    public function init()
    {
        Yii::import('customer.components.survey-field-builder.*');
        parent::init();
    }

    /**
     * @return array
     * @throws CException
     */
    public function behaviors()
    {
        return CMap::mergeArray([
            'callbacks' => [
                'class' => 'frontend.components.behaviors.SurveyControllerCallbacksBehavior',
            ],
        ], parent::behaviors());
    }

    /**
     * Subscribe a new responder to a certain survey
     *
     * @param string $survey_uid
     * @param string $subscriber_uid
     * @param string $campaign_uid
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionIndex($survey_uid, $subscriber_uid = '', $campaign_uid = '')
    {
        $survey   = $this->loadSurveyModel($survey_uid);
        $isOwner  = false;
        $viewName = 'index';

        if (!empty($survey->customer)) {
            $isOwner = (int)$survey->customer_id == customer()->getId();
            $this->setCustomerLanguage($survey->customer);
        }

        /** @var SurveyField[] $surveyFields */
        $surveyFields = SurveyField::model()->findAll([
            'condition' => 'survey_id = :lid',
            'params'    => [':lid' => (int)$survey->survey_id],
            'order'     => 'sort_order ASC',
        ]);

        if (empty($surveyFields)) {
            if (!$isOwner) {
                throw new CHttpException(404, t('app', 'The requested page does not exist.'));
            }
            throw new CHttpException(403, t('surveys', 'This survey has no fields yet.'));
        }

        // since 1.9.12
        $honeypotFieldName = sha1($survey->customer->customer_uid . ':' . $survey->survey_uid);
        if (request()->getIsPostRequest() && strlen((string)request()->getPost($honeypotFieldName, '')) > 0) {
            $this->render('thank-you');
            return;
        }
        //

        if (!$survey->getIsStarted()) {
            $message = t('surveys', 'This survey hasn\'t started yet!');
            if (!$isOwner) {
                throw new CHttpException(403, $message);
            }
            notify()->addWarning($message);
        } elseif ($survey->getIsEnded()) {
            $message = t('surveys', 'This survey has ended!');
            if (!$isOwner) {
                throw new CHttpException(403, $message);
            }
            notify()->addWarning($message);
        }

        $responder = new SurveyResponder();
        $responder->survey_id  = (int)$survey->survey_id;
        $responder->ip_address = (string)request()->getUserHostAddress();

        if (!empty($subscriber_uid) && ($subscriber = ListSubscriber::model()->findByUid($subscriber_uid))) {
            $responder->subscriber_id = (int)$subscriber->subscriber_id;
        }

        $usedTypes = [];
        foreach ($surveyFields as $field) {
            $usedTypes[] = (int)$field->type->type_id;
        }

        $criteria = new CDbCriteria();
        $criteria->addInCondition('type_id', $usedTypes);

        /** @var SurveyFieldType[] $surveyFieldTypes */
        $surveyFieldTypes = SurveyFieldType::model()->findAll($criteria);

        $instances = [];
        foreach ($surveyFieldTypes as $fieldType) {
            if (empty($fieldType->identifier) || !is_file((string)Yii::getPathOfAlias($fieldType->class_alias) . '.php')) {
                continue;
            }

            /** @var CWebApplication $app */
            $app = app();

            $component = $app->getWidgetFactory()->createWidget($this, $fieldType->class_alias, [
                'fieldType'    => $fieldType,
                'survey'       => $survey,
                'responder'    => $responder,
            ]);

            if (!($component instanceof SurveyFieldBuilderType)) {
                continue;
            }

            // run the component to hook into next events
            $component->run();

            $instances[] = $component;
        }

        // since 1.3.9.7
        if (!request()->getIsPostRequest()) {
            foreach ($surveyFields as $surveyField) {
                if ($tagValue = request()->getQuery($surveyField->getTag())) {
                    $_POST[$surveyField->getTag()] = $tagValue;
                }
            }
        }

        $fields = [];

        // if the fields are saved
        if (request()->getIsPostRequest()) {

            // last submission
            $criteria = new CDbCriteria();
            $criteria->compare('ip_address', $responder->ip_address);
            $criteria->addCondition('date_added > DATE_SUB(NOW(), INTERVAL 1 MINUTE)');
            $criteria->order = 'responder_id DESC';
            $criteria->limit = 1;
            if (SurveyResponder::model()->find($criteria)) {
                throw new CHttpException(403, t('surveys', 'We detected too many submissions from your IP address in a short period of time, please slow down!'));
            }
            //

            // since 1.3.5.6
            hooks()->doAction('frontend_survey_respond_before_transaction', $this);

            $transaction = db()->beginTransaction();

            try {

                // since 1.3.5.8
                hooks()->doAction('frontend_survey_respond_at_transaction_start', $this);

                $maxRespondersPerSurvey = (int)$survey->customer->getGroupOption('surveys.max_responders_per_survey', -1);
                $maxResponders          = (int)$survey->customer->getGroupOption('surveys.max_responders', -1);

                if ($maxResponders > -1 || $maxRespondersPerSurvey > -1) {
                    $criteria = new CDbCriteria();

                    if ($maxResponders > -1 && ($surveysIds = $survey->customer->getAllSurveysIds())) {
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

                if (!$responder->save()) {
                    if ($responder->hasErrors()) {
                        throw new Exception($responder->shortErrors->getAllAsString());
                    }
                    throw new Exception(t('app', 'Temporary error, please contact us if this happens too often!'));
                }

                // raise event
                $this->callbacks->onResponderSave(new CEvent($this->callbacks, [
                    'fields' => &$fields,
                    'action' => 'respond',
                ]));

                // if no exception thrown but still there are errors in any of the instances, stop.
                foreach ($instances as $instance) {
                    if (!empty($instance->errors)) {
                        throw new Exception(t('app', 'Your form has a few errors. Please fix them and try again!'));
                    }
                }

                // raise event. at this point everything seems to be fine.
                $this->callbacks->onResponderSaveSuccess(new CEvent($this->callbacks, [
                    'instances' => $instances,
                    'responder' => $responder,
                    'survey'    => $survey,
                    'action'    => 'respond',
                ]));

                // since 1.8.2
                $survey->customer->logAction->responderCreated($responder);

                $transaction->commit();

                $viewName = 'thank-you';

                // since 1.3.5.8
                hooks()->doAction('frontend_survey_respond_at_transaction_end', $this);

                if (!empty($survey->finish_redirect)) {
                    $this->redirect($survey->finish_redirect);
                    return;
                }
            } catch (Exception $e) {
                $transaction->rollback();

                if (($message = $e->getMessage())) {
                    notify()->addError($message);
                }

                // bind default save error event handler
                $this->callbacks->onResponderSaveError = [$this->callbacks, '_collectAndShowErrorMessages'];

                // raise event
                $this->callbacks->onResponderSaveError(new CEvent($this->callbacks, [
                    'instances' => $instances,
                    'responder' => $responder,
                    'survey'    => $survey,
                    'action'    => 'respond',
                ]));
            }

            // since 1.3.5.6
            hooks()->doAction('frontend_survey_respond_after_transaction', $this);
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
        $fields = !empty($fields) && is_array($fields) ? $fields : []; // @phpstan-ignore-line

        // and build the html for the fields.
        $fieldsHtml = '';

        foreach ($fields as $field) {
            $fieldsHtml .= $field['field_html'];
        }

        // since 1.9.12
        $fieldsHtml .= sprintf('
		<div style="position: absolute; left: -5000px;" aria-hidden="true">
			<input type="text" name="%s" tabindex="-1" autocomplete="%s" value=""/>
		</div>', $honeypotFieldName, $honeypotFieldName);

        // embed output
        if (request()->getQuery('output') == 'embed') {
            $width  = (string)request()->getQuery('width', 400);
            $height = (string)request()->getQuery('height', 400);
            $width  = substr($width, -1)  == '%' ? (int)substr($width, 0, strlen($width) - 1) . '%' : (int)$width . 'px';
            $height = substr($height, -1) == '%' ? (int)substr($height, 0, strlen($height) - 1) . '%' : (int)$height . 'px';

            $attributes = [
                'width'  => $width,
                'height' => $height,
                'target' => request()->getQuery('target'),
            ];
            $this->layout = 'embed';
            $this->setData('attributes', $attributes);
        }

        $this->render($viewName, compact('survey', 'fieldsHtml'));
    }

    /**
     * Responds to the ajax calls from the country survey fields
     *
     * @return void
     * @throws CException
     */
    public function actionFields_country_states_by_country_name()
    {
        if (!request()->getIsAjaxRequest()) {
            $this->redirect(['site/index']);
            return;
        }

        $countryName = request()->getQuery('country');
        $country = Country::model()->findByAttributes(['name' => $countryName]);
        if (empty($country)) {
            $this->renderJson([]);
            return;
        }

        $statesList = [];
        $states     = !empty($country->zones) ? $country->zones : [];

        foreach ($states as $state) {
            $statesList[$state->name] = $state->name;
        }

        $this->renderJson($statesList);
    }

    /**
     * Responds to the ajax calls from the state survey fields
     *
     * @return void
     * @throws CException
     */
    public function actionFields_country_by_zone()
    {
        if (!request()->getIsAjaxRequest()) {
            $this->redirect(['dashboard/index']);
            return;
        }

        $zone = Zone::model()->findByAttributes([
            'name' => request()->getQuery('zone'),
        ]);

        if (empty($zone)) {
            $this->renderJson([]);
            return;
        }

        $this->renderJson([
            'country' => [
                'name' => $zone->country->name,
                'code' => $zone->country->code,
            ],
        ]);
    }

    /**
     * @param string $survey_uid
     *
     * @return Survey
     * @throws CHttpException
     */
    public function loadSurveyModel(string $survey_uid): Survey
    {
        $criteria = new CDbCriteria();
        $criteria->compare('survey_uid', $survey_uid);
        $criteria->addNotInCondition('status', [Survey::STATUS_PENDING_DELETE]);
        $model = Survey::model()->find($criteria);

        if ($model === null) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        return $model;
    }

    /**
     * @param string $responder_uid
     * @param int $survey_id
     *
     * @return SurveyResponder
     * @throws CHttpException
     */
    public function loadResponderModel(string $responder_uid, int $survey_id): SurveyResponder
    {
        $model = SurveyResponder::model()->findByAttributes([
            'responder_uid'    => $responder_uid,
            'survey_id'        => (int)$survey_id,
        ]);

        if ($model === null) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        return $model;
    }

    /**
     * Helper method to set the language for this customer.
     *
     * @param Customer $customer
     *
     * @return SurveysController
     * @throws CException
     */
    public function setCustomerLanguage(Customer $customer)
    {
        if (empty($customer->language_id)) {
            return $this;
        }

        // 1.5.3 - language has been forced already at init
        if (($langCode = (string)request()->getQuery('lang', '')) && strlen($langCode) <= 5) {
            return $this;
        }

        /** @var OptionCommon $common */
        $common = container()->get(OptionCommon::class);

        // multilanguage is available since 1.1 and the Language class does not exist prior to that version
        if (!version_compare($common->version, '1.1', '>=')) {
            return $this;
        }

        if (!empty($customer->language)) {
            app()->setLanguage($customer->language->getLanguageAndLocaleCode());
        }

        return $this;
    }
}
