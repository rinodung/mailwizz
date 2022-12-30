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

class SurveysController extends Controller
{
    /**
     * @return void
     * @throws CException
     */
    public function init()
    {
        /** @var Customer|null $customer */
        $customer = customer()->getModel();

        if (empty($customer)) {
            $this->redirect(['guest/index']);
            return;
        }

        if ((int)$customer->getGroupOption('surveys.max_surveys', -1) == 0) {
            $this->redirect(['dashboard/index']);
            return;
        }

        // make sure the parent account has allowed access for this subaccount
        if (is_subaccount() && !subaccount()->canManageSurveys()) {
            $this->redirect(['dashboard/index']);
            return;
        }

        $this->addPageStyle(['src' => AssetsUrl::js('datetimepicker/css/bootstrap-datetimepicker.min.css')]);
        $this->addPageScript(['src' => AssetsUrl::js('datetimepicker/js/bootstrap-datetimepicker.min.js')]);

        $languageCode = LanguageHelper::getAppLanguageCode();

        /** @var CWebApplication $app */
        $app = app();

        if ($app->getLanguage() != $app->sourceLanguage && is_file(AssetsPath::js($languageFile = 'datetimepicker/js/locales/bootstrap-datetimepicker.' . $languageCode . '.js'))) {
            $this->addPageScript(['src' => AssetsUrl::js($languageFile)]);
        }

        $this->addPageScript(['src' => AssetsUrl::js('surveys.js')]);

        parent::init();
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
     * @return void
     * @throws CException
     */
    public function actionIndex()
    {
        $survey  = new Survey('search');
        $survey->unsetAttributes();
        $survey->attributes  = (array)request()->getQuery($survey->getModelName(), []);
        $survey->customer_id = (int)customer()->getId();

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('surveys', 'Your surveys'),
            'pageHeading'     => t('surveys', 'Surveys'),
            'pageBreadcrumbs' => [
                t('surveys', 'Surveys') => createUrl('surveys/index'),
                t('app', 'View all'),
            ],
        ]);

        $this->render('list', compact('survey'));
    }

    /**
     * @return void
     * @throws CException
     */
    public function actionCreate()
    {
        /** @var Customer $customer */
        $customer = customer()->getModel();

        if (($maxSurveys = (int)$customer->getGroupOption('surveys.max_surveys', -1)) > -1) {
            $criteria = new CDbCriteria();
            $criteria->compare('customer_id', (int)$customer->customer_id);
            $criteria->addNotInCondition('status', [Survey::STATUS_PENDING_DELETE]);

            $surveysCount = Survey::model()->count($criteria);
            if ($surveysCount >= $maxSurveys) {
                notify()->addWarning(t('surveys', 'You have reached the maximum number of allowed surveys.'));
                $this->redirect(['surveys/index']);
                return;
            }
        }

        $survey = new Survey();
        $survey->customer_id = (int)$customer->customer_id;

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($survey->getModelName(), []))) {
            /** @var array $post */
            $post = (array)request()->getOriginalPost('', []);

            $survey->attributes = $attributes;

            if (isset($post[$survey->getModelName()]['description'])) {
                $survey->description = (string)ioFilter()->purify((string)$post[$survey->getModelName()]['description']);
            }

            if (!$survey->save()) {
                notify()->addError(t('app', 'Your form has a few errors, please fix them and try again!'));
            } else {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));

                /** @var CustomerActionLogBehavior $logAction */
                $logAction = $customer->getLogAction();
                $logAction->surveyCreated($survey);
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller' => $this,
                'success'    => notify()->getHasSuccess(),
                'survey'     => $survey,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['surveys/fields', 'survey_uid' => $survey->survey_uid]);
                return;
            }
        }

        $survey->fieldDecorator->onHtmlOptionsSetup = [$this, '_setupEditorOptions'];

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('surveys', 'Create new survey'),
            'pageHeading'     => t('surveys', 'Create new survey'),
            'pageBreadcrumbs' => [
                t('surveys', 'Surveys') => createUrl('surveys/index'),
                t('app', 'Create new'),
            ],
        ]);

        $this->render('form', compact(
            'survey'
        ));
    }

    /**
     * @param string $survey_uid
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionUpdate($survey_uid)
    {
        /** @var Survey $survey */
        $survey = $this->loadModel((string)$survey_uid);

        if (!$survey->getEditable()) {
            $this->redirect(['surveys/index']);
            return;
        }

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($survey->getModelName(), []))) {
            /** @var array $post */
            $post = (array)request()->getOriginalPost('', []);

            $survey->attributes = $attributes;
            if (isset($post[$survey->getModelName()]['description'])) {
                $survey->description = (string)ioFilter()->purify((string)$post[$survey->getModelName()]['description']);
            }

            if (!$survey->save()) {
                notify()->addError(t('app', 'Your form has a few errors, please fix them and try again!'));
            } else {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));

                /** @var Customer $customer */
                $customer = customer()->getModel();

                /** @var CustomerActionLogBehavior $logAction */
                $logAction = $customer->getLogAction();
                $logAction->surveyUpdated($survey);
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller' => $this,
                'success'    => notify()->getHasSuccess(),
                'survey'     => $survey,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['surveys/update', 'survey_uid' => $survey->survey_uid]);
                return;
            }
        }

        $survey->fieldDecorator->onHtmlOptionsSetup = [$this, '_setupEditorOptions'];

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('surveys', 'Update survey'),
            'pageHeading'     => t('surveys', 'Update survey'),
            'pageBreadcrumbs' => [
                t('surveys', 'Surveys') => createUrl('surveys/index'),
                $survey->name . ' ' => createUrl('surveys/overview', ['survey_uid' => $survey->survey_uid]),
                t('app', 'Update'),
            ],
        ]);

        $this->render('form', compact(
            'survey'
        ));
    }

    /**
     * @param string $survey_uid
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionCopy($survey_uid)
    {
        /** @var Survey $survey */
        $survey = $this->loadModel((string)$survey_uid);

        /** @var Customer $customer */
        $customer = $survey->customer;

        /** @var bool $canCopy */
        $canCopy = true;

        if ($survey->getIsPendingDelete()) {
            $this->redirect(['surveys/index']);
            return;
        }

        if (($maxSurveys = $customer->getGroupOption('surveys.max_surveys', -1)) > -1) {
            $criteria = new CDbCriteria();
            $criteria->compare('customer_id', (int)$customer->customer_id);
            $criteria->addNotInCondition('status', [Survey::STATUS_PENDING_DELETE]);

            $surveysCount = Survey::model()->count($criteria);
            if ($surveysCount >= $maxSurveys) {
                notify()->addWarning(t('surveys', 'You have reached the maximum number of allowed surveys.'));
                $canCopy = false;
            }
        }

        if ($canCopy && $survey->copy()) {
            notify()->addSuccess(t('surveys', 'Your survey was successfully copied!'));
        }

        if (!request()->getIsAjaxRequest()) {
            $this->redirect(request()->getPost('returnUrl', ['surveys/index']));
        }
    }

    /**
     * @param string $survey_uid
     *
     * @return void
     * @throws CDbException
     * @throws CException
     * @throws CHttpException
     */
    public function actionDelete($survey_uid)
    {
        /** @var Survey $survey */
        $survey = $this->loadModel((string)$survey_uid);

        if (!$survey->getIsRemovable()) {
            $this->redirect(['surveys/index']);
            return;
        }

        if (request()->getIsPostRequest()) {
            $survey->delete();

            /** @var Customer $customer */
            $customer = customer()->getModel();

            /** @var CustomerActionLogBehavior $logAction */
            $logAction = $customer->getLogAction();
            $logAction->surveyDeleted($survey);

            notify()->addSuccess(t('app', 'Your item has been successfully deleted!'));
            $redirect = request()->getPost('returnUrl', ['surveys/index']);

            // since 1.3.5.9
            hooks()->doAction('controller_action_delete_data', $collection = new CAttributeCollection([
                'controller' => $this,
                'model'      => $survey,
                'redirect'   => $redirect,
            ]));

            if ($collection->itemAt('redirect')) {
                $this->redirect($collection->itemAt('redirect'));
                return;
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('surveys', 'Confirm survey removal'),
            'pageHeading'     => t('surveys', 'Confirm survey removal'),
            'pageBreadcrumbs' => [
                t('surveys', 'Surveys') => createUrl('surveys/index'),
                $survey->name . ' ' => createUrl('surveys/overview', ['survey_uid' => $survey->survey_uid]),
                t('surveys', 'Confirm survey removal'),
            ],
        ]);

        $this->render('delete', compact('survey'));
    }

    /**
     * @param string $survey_uid
     *
     * @return void
     * @throws CHttpException
     */
    public function actionOverview($survey_uid)
    {
        /** @var Survey $survey */
        $survey = $this->loadModel((string)$survey_uid);

        if ($survey->getIsPendingDelete()) {
            $this->redirect(['surveys/index']);
            return;
        }

        $this->addPageScripts([
            ['src' => apps()->getBaseUrl('assets/js/flot/jquery.flot.min.js')],
            ['src' => apps()->getBaseUrl('assets/js/flot/jquery.flot.resize.min.js')],
            ['src' => apps()->getBaseUrl('assets/js/flot/jquery.flot.categories.min.js')],
            ['src' => AssetsUrl::js('survey-overview.js')],
        ]);

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('surveys', 'Survey overview'),
            'pageHeading'     => t('surveys', 'Survey overview'),
            'pageBreadcrumbs' => [
                t('surveys', 'Surveys') => createUrl('surveys/index'),
                $survey->name . ' ' => createUrl('surveys/overview', ['survey_uid' => $survey->survey_uid]),
                t('surveys', 'Overview'),
            ],
        ]);

        /** @var Customer $customer */
        $customer = customer()->getModel();

        /** @var bool $canSegmentSurveys */
        $canSegmentSurveys = $customer->getGroupOption('surveys.can_segment_surveys', 'yes') == 'yes';

        /** @var int $respondersCount */
        $respondersCount = $survey->respondersCount;

        /** @var int $segmentsCount */
        $segmentsCount = $survey->activeSegmentsCount;

        /** @var int $customFieldsCount */
        $customFieldsCount = $survey->fieldsCount;

        $this->render('overview', compact(
            'survey',
            'canSegmentSurveys',
            'respondersCount',
            'segmentsCount',
            'customFieldsCount'
        ));
    }

    /**
     * @return void
     * @throws CException
     */
    public function actionFields_country_states_by_country_name()
    {
        if (!request()->getIsAjaxRequest()) {
            $this->redirect(['dashboard/index']);
            return;
        }

        /** @var string $countryName */
        $countryName = (string)request()->getQuery('country', '');

        /** @var Country|null $country */
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
     * @return void
     * @throws CException
     */
    public function actionFields_country_by_zone()
    {
        if (!request()->getIsAjaxRequest()) {
            $this->redirect(['dashboard/index']);
            return;
        }

        /** @var Zone|null $zone */
        $zone = Zone::model()->findByAttributes([
            'name' => request()->getQuery('zone', ''),
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
    public function loadModel(string $survey_uid): Survey
    {
        $criteria = new CDbCriteria();
        $criteria->compare('survey_uid', $survey_uid);
        $criteria->compare('customer_id', (int)customer()->getId());
        $criteria->addNotInCondition('status', [Survey::STATUS_PENDING_DELETE]);

        $model = Survey::model()->find($criteria);

        if ($model === null) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        if ($model->getIsPendingDelete()) {
            $this->redirect(['surveys/index']);
        }

        return $model;
    }

    /**
     * @param CEvent $event
     *
     * @return void
     */
    public function _setupEditorOptions(CEvent $event)
    {
        if (!in_array($event->params['attribute'], ['description'])) {
            return;
        }

        $options = [];
        if ($event->params['htmlOptions']->contains('wysiwyg_editor_options')) {
            $options = (array)$event->params['htmlOptions']->itemAt('wysiwyg_editor_options');
        }

        $options['id']     = CHtml::activeId($event->sender->owner, $event->params['attribute']);
        $options['height'] = 100;

        $event->params['htmlOptions']->add('wysiwyg_editor_options', $options);
    }
}
