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
     */
    public function init()
    {
        $this->addPageScript(['src' => AssetsUrl::js('surveys.js')]);
        parent::init();
    }

    /**
     * Show available surveys
     *
     * @return void
     * @throws CException
     */
    public function actionIndex()
    {
        $survey = new Survey('search');
        $survey->unsetAttributes();
        $survey->attributes = (array)request()->getQuery($survey->getModelName(), []);

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('surveys', 'Surveys'),
            'pageHeading'     => t('surveys', 'Surveys'),
            'pageBreadcrumbs' => [
                t('surveys', 'Surveys') => createUrl('surveys/index'),
                t('app', 'View all'),
            ],
        ]);

        $this->render('list', compact('survey'));
    }

    /**
     * Display survey overview
     * This is a page containing shortcuts to the most important survey features.
     *
     * @param string $survey_uid
     *
     * @return void
     * @throws CHttpException
     */
    public function actionOverview($survey_uid)
    {
        $survey = $this->loadModel((string)$survey_uid);

        if ($survey->getIsPendingDelete()) {
            $this->redirect(['surveys/index']);
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('surveys', 'Survey overview'),
            'pageHeading'     => t('surveys', 'Survey overview'),
            'pageBreadcrumbs' => [
                t('surveys', 'Surveys') => createUrl('surveys/index'),
                $survey->name . ' ' => createUrl('surveys/overview', ['survey_uid' => $survey->survey_uid]),
                t('surveys', 'Overview'),
            ],
        ]);

        $respondersCount   = $survey->respondersCount;
        $segmentsCount     = $survey->activeSegmentsCount;
        $customFieldsCount = $survey->fieldsCount;

        $this->render('overview', compact(
            'survey',
            'respondersCount',
            'segmentsCount',
            'customFieldsCount'
        ));
    }

    /**
     * Delete existing survey
     *
     * @param string $survey_uid
     *
     * @return void
     * @throws CDbException
     * @throws CException
     * @throws CHttpException
     */
    public function actionDelete($survey_uid)
    {
        $survey = $this->loadModel((string)$survey_uid);

        if (!$survey->getIsRemovable()) {
            $this->redirect(['surveys/index']);
        }

        if (request()->getIsPostRequest()) {
            $survey->delete();

            /** @var Customer $customer */
            $customer = $survey->customer;

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
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('surveys', 'Confirm survey removal'),
            'pageHeading'     => t('surveys', 'Confirm survey removal'),
            'pageBreadcrumbs' => [
                t('surveys', 'Survey') => createUrl('surveys/index'),
                $survey->name . ' ' => createUrl('surveys/overview', ['survey_uid' => $survey->survey_uid]),
                t('surveys', 'Confirm survey removal'),
            ],
        ]);

        $this->render('delete', compact('survey'));
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
        $criteria->addNotInCondition('status', [Survey::STATUS_PENDING_DELETE]);

        /** @var Survey|null $model */
        $model = Survey::model()->find($criteria);

        if (empty($model)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        if ($model->getIsPendingDelete()) {
            $this->redirect(['surveys/index']);
        }

        return $model;
    }
}
