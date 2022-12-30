<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 */

class TranslateExtBackendSettingsController extends ExtensionController
{
    /**
     * @return string
     */
    public function getViewPath()
    {
        return $this->getExtension()->getPathOfAlias('backend.views.settings');
    }

    /**
     * Default action.
     *
     * @return void
     */
    public function actionIndex()
    {
        /** @var TranslateExtModel $model */
        $model = container()->get(TranslateExtModel::class);

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($model->getModelName(), []))) {
            $model->attributes = $attributes;
            if ($model->save()) {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            } else {
                notify()->addError(t('app', 'Your form has a few errors, please fix them and try again!'));
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . $this->t('Translate extension'),
            'pageHeading'     => $this->t('Translate extension'),
            'pageBreadcrumbs' => [
                t('extensions', 'Extensions') => createUrl('extensions/index'),
                $this->t('Translate extension'),
            ],
        ]);

        /** @var CWebApplication $app */
        $app = app();

        $messagesDir = '';
        if ($app->hasComponent('messages') && (app()->getComponent('messages') instanceof CPhpMessageSource)) {
            $messagesDir = $app->getMessages()->basePath;
        }

        if (!empty($messagesDir) && (!file_exists($messagesDir) || !is_dir($messagesDir) || !is_writable($messagesDir))) {
            notify()->addWarning($this->t('The directory {dirName} must exist and be writable by the web server in order to write the translation files.', [
                '{dirName}' => '<span class="badge">' . $messagesDir . '</span>',
            ]));
        }

        $this->render('index', compact('messagesDir', 'model'));
    }
}
