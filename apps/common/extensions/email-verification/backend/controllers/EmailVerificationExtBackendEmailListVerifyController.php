<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * Controller file for email list verify settings.
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link http://www.mailwizz.com/
 * @copyright MailWizz EMA (http://www.mailwizz.com)
 * @license http://www.mailwizz.com/license/
 */

class EmailVerificationExtBackendEmailListVerifyController extends ExtensionController
{
    /**
     * @return string
     */
    public function getViewPath()
    {
        return $this->getExtension()->getPathOfAlias('backend.views.providers.email-list-verify');
    }

    /**
     * Common settings
     *
     * @return void
     * @throws CException
     */
    public function actionIndex()
    {
        /** @var EmailVerificationExtEmailListVerifyCommon $model */
        $model = container()->get(EmailVerificationExtEmailListVerifyCommon::class);

        if (request()->getIsPostRequest()) {
            $model->attributes = (array)request()->getPost($model->getModelName(), []);
            if ($model->save()) {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            } else {
                notify()->addError(t('app', 'Your form has a few errors, please fix them and try again!'));
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . $this->t('Email List Verify'),
            'pageHeading'     => $this->t('Email List Verify'),
            'pageBreadcrumbs' => [
                t('app', 'Extensions') => createUrl('extensions/index'),
                $this->t('Email verification') => $this->getExtension()->createUrl('providers/index'),
                $this->t('Email List Verify') => $this->getExtension()->createUrl('email_list_verify/index'),
            ],
        ]);

        $this->render('index', compact('model'));
    }
}
