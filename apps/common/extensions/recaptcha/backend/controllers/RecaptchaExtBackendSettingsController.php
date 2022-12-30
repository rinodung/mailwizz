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

class RecaptchaExtBackendSettingsController extends ExtensionController
{
    /**
     * @inheritDoc
     */
    public function init()
    {
        parent::init();

        $assetsUrl = assetManager()->publish(dirname(__FILE__) . '/../../assets/backend', false, -1, MW_DEBUG);
        clientScript()->registerScriptFile($assetsUrl . '/js/settings-form.js');
    }

    /**
     * @return string
     */
    public function getViewPath()
    {
        return $this->getExtension()->getPathOfAlias('backend.views.settings');
    }

    /**
     * Common settings
     *
     * @return void
     * @throws CException
     */
    public function actionIndex()
    {
        /** @var RecaptchaExtCommon $model */
        $model = container()->get(RecaptchaExtCommon::class);

        /** @var RecaptchaExtDomainsKeysPair $domainsKeysPair */
        $domainsKeysPair = container()->get(RecaptchaExtDomainsKeysPair::class, true);

        if (request()->getIsPostRequest()) {
            $model->attributes        = (array)request()->getPost($model->getModelName(), []);
            $model->domains_keys_pair = (array)request()->getPost($domainsKeysPair->getModelName(), []);
            if ($model->save()) {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            } else {
                notify()->addError(t('app', 'Your form has a few errors, please fix them and try again!'));
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . $this->t('Recaptcha'),
            'pageHeading'     => $this->t('Recaptcha'),
            'pageBreadcrumbs' => [
                t('app', 'Extensions') => createUrl('extensions/index'),
                $this->t('Recaptcha') => $this->getExtension()->createUrl('settings/index'),
            ],
        ]);

        $this->render('index', compact('model', 'domainsKeysPair'));
    }
}
