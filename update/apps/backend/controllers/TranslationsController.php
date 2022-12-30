<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * TranslationsController
 *
 * Handles the actions for list subscribers related tasks
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.0
 */

class TranslationsController extends Controller
{
    /**
     * @return void
     */
    public function init()
    {
        $this->addPageScript(['src' => AssetsUrl::js('translations.js')]);
        parent::init();
    }

    /**
     * @return array
     */
    public function filters()
    {
        return CMap::mergeArray([
            'postOnly + save',
        ], parent::filters());
    }

    /**
     * @param int $language_id
     * @throws CException
     * @throws CHttpException
     * @return void
     */
    public function actionIndex($language_id)
    {
        /** @var Language $model */
        $model = $this->loadLanguageModel((int)$language_id);

        /** @var TranslationSourceMessage $translation */
        $translation = new TranslationSourceMessage('search');
        $translation->unsetAttributes();

        // for filters.
        $translation->attributes = (array)request()->getQuery($translation->getModelName(), []);
        $translation->language = (string)$model->getLanguageAndLocaleCode();

        notify()->addInfo(t('translations', 'Please make sure you save your changes before navigating away to a different page.'));

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('translations', '{name} translations', ['{name}' => $model->name]),
            'pageHeading'     => t('translations', '{name} translations', ['{name}' => $model->name]),
            'pageBreadcrumbs' => [
                t('languages', 'Languages') => createUrl('languages/index'),
                $model->name . ' ' => createUrl('languages/update', ['id' => $model->language_id]),
                t('translations', 'Translations') . ' ' => '',
            ],
        ]);

        $this->render('list', compact('translation', 'model'));
    }

    /**
     * @throws CException
     * @return void
     */
    public function actionSave()
    {
        if (!request()->getIsAjaxRequest() || !request()->getIsPostRequest()) {
            $this->redirect(['languages/index']);
        }

        $messages = (array)request()->getOriginalPost('TranslationMessage', []);
        $messages = (array)ioFilter()->purify($messages);
        $messages = collect($messages)->filter(function ($message) {
            return !empty($message['translation']);
        })->all();

        $affected = 0;
        foreach ($messages as $key => $message) {
            $translation = TranslationMessage::model()->findByAttributes([
                'id'       => $message['id'] ?? 0,
                'language' => $message['language'] ?? '',
            ]);

            if (empty($translation)) {
                $translation = new TranslationMessage();
            }

            $translation->attributes = $message;

            if (!$translation->save()) {
                continue;
            }
            $affected++;
        }

        if ($affected === count($messages)) {
            $this->renderJson([
                'result'  => 'success',
                'message' => t('app', 'The action has been successfully completed!'),
            ]);
        }

        $this->renderJson([
            'result'  => 'error',
            'message' => t('app', 'The action has failed for some items!'),
        ]);
    }

    /**
     * @param int $language_id
     * @return Language
     * @throws CHttpException
     */
    public function loadLanguageModel(int $language_id): Language
    {
        $model = Language::model()->findByAttributes([
            'language_id' => $language_id,
        ]);

        if ($model === null) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        return $model;
    }
}
