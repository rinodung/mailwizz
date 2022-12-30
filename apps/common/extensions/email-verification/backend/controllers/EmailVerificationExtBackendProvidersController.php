<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * Controller file for email providers providers.
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link http://www.mailwizz.com/
 * @copyright MailWizz EMA (http://www.mailwizz.com)
 * @license http://www.mailwizz.com/license/
 */

class EmailVerificationExtBackendProvidersController extends ExtensionController
{
    /**
     * @return string
     */
    public function getViewPath()
    {
        return $this->getExtension()->getPathOfAlias('backend.views.providers');
    }

    /**
     * Common providers
     *
     * @return void
     */
    public function actionIndex()
    {
        $model = new EmailVerificationProvidersHandler($this->getExtension());
        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . $this->t('Email verification provider providers'),
            'pageHeading'     => $this->t('Email verification provider providers'),
            'pageBreadcrumbs' => [
                t('app', 'Extensions') => createUrl('extensions/index'),
                $this->t('Email verification') => $this->getExtension()->createUrl('providers/index'),
            ],
        ]);

        $this->render('index', compact('model'));
    }
}
