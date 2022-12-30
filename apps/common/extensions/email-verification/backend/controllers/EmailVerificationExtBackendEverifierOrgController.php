<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * Controller file for bulk email checker settings.
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link http://www.mailwizz.com/
 * @copyright MailWizz EMA (http://www.mailwizz.com)
 * @license http://www.mailwizz.com/license/
 */

class EmailVerificationExtBackendEverifierOrgController extends ExtensionController
{
    /**
     * @return string
     */
    public function getViewPath()
    {
        return $this->getExtension()->getPathOfAlias('backend.views.providers.everifier-org');
    }

    /**
     * Common settings
     *
     * @return void
     * @throws CException
     */
    public function actionIndex()
    {
        /** @var EmailVerificationExtEverifierOrgCommon $model */
        $model = container()->get(EmailVerificationExtEverifierOrgCommon::class);

        if (request()->getIsPostRequest()) {
            $model->attributes = (array)request()->getPost($model->getModelName(), []);
            if ($model->save()) {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            } else {
                notify()->addError(t('app', 'Your form has a few errors, please fix them and try again!'));
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . $this->t('Everifier.org'),
            'pageHeading'     => $this->t('Everifier.org'),
            'pageBreadcrumbs' => [
                t('app', 'Extensions') => createUrl('extensions/index'),
                $this->t('Email verification') => $this->getExtension()->createUrl('providers/index'),
                $this->t('Everifier.org') => $this->getExtension()->createUrl('everifier_org/index'),
            ],
        ]);

        $this->render('index', compact('model'));
    }
}
