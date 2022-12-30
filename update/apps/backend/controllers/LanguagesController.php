<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * LanguagesController
 *
 * Handles the actions for languages related tasks
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.1
 */

class LanguagesController extends Controller
{
    /**
     * @return array
     */
    public function filters()
    {
        $filters = [
            'postOnly + delete', // we only allow deletion via POST request
        ];

        return CMap::mergeArray($filters, parent::filters());
    }

    /**
     * List all available languages
     *
     * @return void
     * @throws CException
     */
    public function actionIndex()
    {
        $language = new Language('search');
        $language->unsetAttributes();
        $languageUpload = new LanguageUploadForm();

        // for filters.
        $language->attributes = (array)request()->getQuery($language->getModelName(), []);

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('languages', 'View available languages'),
            'pageHeading'     => t('languages', 'View available languages'),
            'pageBreadcrumbs' => [
                t('languages', 'Languages') => createUrl('languages/index'),
                t('app', 'View all'),
            ],
        ]);

        $this->render('list', compact('language', 'languageUpload'));
    }

    /**
     * Create a new language
     *
     * @return void
     * @throws CException
     */
    public function actionCreate()
    {
        $language = new Language();

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($language->getModelName(), []))) {
            $language->attributes = $attributes;
            if (!$language->validate()) {
                notify()->addError(t('app', 'Your form has a few errors, please fix them and try again!'));
            } else {
                try {
                    $locale = app()->getLocale($language->getLanguageAndLocaleCode());
                    $language->save(false);
                    notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
                } catch (Exception $e) {
                    notify()->addError(t('app', 'Your form has a few errors, please fix them and try again!'));
                    notify()->addError($e->getMessage());
                }
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller'=> $this,
                'success'   => notify()->getHasSuccess(),
                'language'  => $language,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['languages/index']);
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('languages', 'Create new language'),
            'pageHeading'     => t('languages', 'Create new language'),
            'pageBreadcrumbs' => [
                t('languages', 'Languages') => createUrl('languages/index'),
                t('app', 'Create new'),
            ],
        ]);

        $this->render('form', compact('language'));
    }

    /**
     * Create a new language
     *
     * @param int $id
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionUpdate($id)
    {
        $language = Language::model()->findByPk((int)$id);

        if (empty($language)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($language->getModelName(), []))) {
            $language->attributes = $attributes;
            if (!$language->validate()) {
                notify()->addError(t('app', 'Your form has a few errors, please fix them and try again!'));
            } else {
                try {
                    $locale = app()->getLocale($language->getLanguageAndLocaleCode());
                    $language->save(false);
                    notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
                } catch (Exception $e) {
                    notify()->addError(t('app', 'Your form has a few errors, please fix them and try again!'));
                    notify()->addError($e->getMessage());
                }
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller'=> $this,
                'success'   => notify()->getHasSuccess(),
                'language'  => $language,
            ]));
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('languages', 'Update language'),
            'pageHeading'     => t('languages', 'Update language'),
            'pageBreadcrumbs' => [
                t('languages', 'Languages') => createUrl('languages/index'),
                t('app', 'Update'),
            ],
        ]);

        $this->render('form', compact('language'));
    }

    /**
     * @throws CException
     * @return void
     */
    public function actionUpload()
    {
        $model = new LanguageUploadForm();

        if (request()->getIsPostRequest() && request()->getPost($model->getModelName())) {
            $model->archive = CUploadedFile::getInstance($model, 'archive');

            if (!$model->upload()) {
                notify()->addError(CHtml::errorSummary($model));
            } else {
                notify()->addSuccess(t('languages', 'Your language pack has been successfully uploaded!'));
            }
            $this->redirect(['languages/index']);
        }

        notify()->addError(t('languages', 'Please select a language pack archive for upload!'));
        $this->redirect(['languages/index']);
    }

    /**
     * Delete existing language
     *
     * @param int $id
     *
     * @return void
     * @throws CDbException
     * @throws CException
     * @throws CHttpException
     */
    public function actionDelete($id)
    {
        $language = Language::model()->findByPk((int)$id);

        if (empty($language)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        if ($language->is_default != Language::TEXT_YES) {
            $language->delete();
        }

        $redirect = null;
        if (!request()->getQuery('ajax')) {
            notify()->addSuccess(t('app', 'The item has been successfully deleted!'));
            $redirect = request()->getPost('returnUrl', ['languages/index']);
        }

        // since 1.3.5.9
        hooks()->doAction('controller_action_delete_data', $collection = new CAttributeCollection([
            'controller' => $this,
            'model'      => $language,
            'redirect'   => $redirect,
        ]));

        if ($collection->itemAt('redirect')) {
            $this->redirect($collection->itemAt('redirect'));
        }
    }

    /**
     * @param string $id
     * @throws CHttpException
     * @return void
     */
    public function actionExport($id)
    {
        /** @var Language|null $language */
        $language = Language::model()->findByPk((int)$id);

        if (empty($language)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        // we need the zip archive class, cannot work without.
        if (!class_exists('ZipArchive', false)) {
            notify()->addError(t('app', 'ZipArchive class required in order to unzip the file.'));
            $this->redirect(['languages/index']);
        }

        if (!app()->hasComponent('messages')) {
            notify()->addError(t('languages', 'The language export is not allowed.'));
            $this->redirect(['languages/index']);
        }

        /** @var string $languagesDir */
        $languagesDir = (string)Yii::getPathOfAlias('common.messages');

        if (!file_exists($languagesDir) || !is_dir($languagesDir)) {
            notify()->addError(t('app', 'Please make sure the folder {dirPath} exists!', ['{dirPath}' => $languagesDir]));
            $this->redirect(['languages/index']);
        }

        /** @var string $languageDir */
        $languageDir = $languagesDir . '/' . $language->getLanguageAndLocaleCode();

        if ((!file_exists($languageDir) || !is_dir($languageDir)) && !mkdir($languageDir, 0777, true)) {
            notify()->addError(t('app', 'Cannot create directory "{dirPath}". Make sure the parent directory is writable by the webserver!', ['{dirPath}' => $languageDir]));
            $this->redirect(['languages/index']);
        }

        if (!is_writable($languagesDir)) {
            notify()->addError(t('app', 'The directory "{dirPath}" is not writable by the webserver!', ['{dirPath}' => $languagesDir]));
            $this->redirect(['languages/index']);
        }

        if (app()->getComponent('messages') instanceof CDbMessageSource) {
            FileSystemHelper::deleteDirectoryContents($languageDir);

            $stub = (string)file_get_contents((string)Yii::getPathOfAlias('common.extensions.translate.common.stub') . '.php');

            $messages = $this->extractMessagesFromDb($language);
            foreach ($messages as $category => $_messages) {
                $data = [];
                $categoryFile = $languageDir . '/' . $category . '.php';
                foreach ($_messages as $messageText => $value) {
                    $data[$messageText] = $value;
                }
                $newStub = str_replace('[[category]]', $category, $stub);
                $newStub .= 'return ' . var_export($data, true) . ';' . "\n";
                $newStub = str_replace("\\\\\\'", "\\'", $newStub);
                file_put_contents($categoryFile, $newStub);
            }
        }

        $zip = new ZipArchive();

        $zipFileName = $language->getLanguageAndLocaleCode() . '.zip';
        $zipFilePath = (string)Yii::getPathOfAlias('common.runtime') . '/' . $zipFileName;

        if (is_file($zipFilePath)) {
            unlink($zipFilePath);
        }

        if (!$zip->open($zipFilePath, ZipArchive::CREATE)) {
            notify()->addError(t('app', 'Cannot open the archive file.'));
            $this->redirect(['languages/index']);
        }

        $zipFiles = FileSystemHelper::readDirectoryContents($languageDir);
        foreach ($zipFiles as $file) {
            $zip->addFile($languageDir . '/' . $file, $language->getLanguageAndLocaleCode() . '/' . $file);
        }
        $zip->close();

        HeaderHelper::setDownloadHeaders($zipFileName);

        if ($file = fopen($zipFilePath, 'rb')) {
            while (!feof($file)) {
                echo fread($file, 1024 * 8);
                ob_flush();
                flush();
            }

            fclose($file);

            unlink($zipFilePath);
        }
        app()->end();
    }

    /**
     * @param Language $language
     * @return array
     */
    protected function extractMessagesFromDb(Language $language): array
    {
        $messages = [];

        /** @var  TranslationSourceMessage[] $languageSourceMessages */
        $languageSourceMessages = TranslationSourceMessage::model()->findAll();

        foreach ($languageSourceMessages as $sourceMessage) {
            /** @var TranslationMessage|null $translation */
            $translation = TranslationMessage::model()->findByAttributes([
                'id'       => $sourceMessage->id,
                'language' => $language->getLanguageAndLocaleCode(),
            ]);

            $translationText = $sourceMessage->message;
            if (!empty($translation)) {
                $translationText = $translation->translation;
            }

            if (!isset($messages[$sourceMessage->category])) {
                $messages[$sourceMessage->category] = [];
            }
            $messages[$sourceMessage->category][$sourceMessage->message] = $translationText;
        }

        return $messages;
    }
}
