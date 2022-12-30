<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * Controller file
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link http://www.mailwizz.com/
 * @copyright MailWizz EMA (http://www.mailwizz.com)
 * @license http://www.mailwizz.com/license/
 */

class EmailVerificationExtCustomerBulkEmailCheckerController extends ExtensionController
{
    /**
     * @return string
     */
    public function getViewPath()
    {
        return $this->getExtension()->getPathOfAlias('customer.views.providers.bulk-email-checker');
    }

    /**
     * Common settings
     *
     * @return void
     * @throws CException
     */
    public function actionIndex()
    {
        /** @var EmailVerificationExtBulkEmailCheckerCustomer $model */
        $model = container()->get(EmailVerificationExtBulkEmailCheckerCustomer::class);

        if (request()->getIsPostRequest()) {
            $model->attributes = (array)request()->getPost($model->getModelName(), []);
            if ($model->save()) {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            } else {
                notify()->addError(t('app', 'Your form has a few errors, please fix them and try again!'));
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . $this->t('Bulk Email Checker'),
            'pageHeading'     => $this->t('Bulk Email Checker'),
            'pageBreadcrumbs' => [
                $this->t('Email verification') => $this->getExtension()->createUrl('providers/index'),
                $this->t('Bulk Email Checker') => $this->getExtension()->createUrl('bulk_email_checker/index'),
            ],
        ]);

        $this->render('index', compact('model'));
    }
}
