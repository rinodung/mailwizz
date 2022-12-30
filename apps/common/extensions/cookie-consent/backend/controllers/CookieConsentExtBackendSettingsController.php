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

class CookieConsentExtBackendSettingsController extends ExtensionController
{
    /**
     * @return void
     */
    public function init()
    {
        $this->addPageStyle(['src'  => apps()->getAppUrl('frontend', 'assets/js/colorpicker/css/bootstrap-colorpicker.css', false, true)]);
        $this->addPageScript(['src' => apps()->getAppUrl('frontend', 'assets/js/colorpicker/js/bootstrap-colorpicker.min.js', false, true)]);

        /** @var CookieConsentExt $extension */
        $extension = $this->getExtension();

        $this->addPageScript(['src' => $extension->getAssetsUrl() . '/js/settings-form.js']);
        parent::init();
    }
    /**
     * @return string
     */
    public function getViewPath()
    {
        return $this->getExtension()->getPathOfAlias('backend.views.settings');
    }

    /**
     * Default action
     *
     * @return void
     * @throws CException
     */
    public function actionIndex()
    {
        /** @var CookieConsentExtCommon $model */
        $model = container()->get(CookieConsentExtCommon::class);

        if (request()->getIsPostRequest()) {
            $model->attributes = (array)request()->getPost($model->getModelName(), []);
            if ($model->save()) {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            } else {
                notify()->addError(t('app', 'Your form has a few errors, please fix them and try again!'));
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . $this->t('Cookie consent'),
            'pageHeading'     => $this->t('Cookie consent'),
            'pageBreadcrumbs' => [
                t('app', 'Extensions') => createUrl('extensions/index'),
                $this->t('Cookie consent'),
            ],
        ]);

        $this->render('index', compact('model'));
    }
}
