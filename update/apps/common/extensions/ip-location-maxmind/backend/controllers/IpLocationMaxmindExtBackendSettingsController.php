<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * Controller file for service settings.
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 */

class IpLocationMaxmindExtBackendSettingsController extends ExtensionController
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
        /** @var IpLocationMaxmindExtCommon $model */
        $model = container()->get(IpLocationMaxmindExtCommon::class);

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($model->getModelName(), []))) {
            $model->attributes = $attributes;
            if ($model->save()) {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            } else {
                notify()->addError(t('app', 'Your form has a few errors, please fix them and try again!'));
            }
        }

        // 1.4.5
        MaxmindDatabase::addNotifyErrorIfMissingDbFile();

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . $this->t('Ip location service from MaxMind.com'),
            'pageHeading'     => $this->t('Ip location service from MaxMind.com'),
            'pageBreadcrumbs' => [
                t('ip_location', 'Ip location services') => createUrl('ip_location_services/index'),
                $this->t('Ip location service from MaxMind.com'),
            ],
        ]);

        $this->render('index', compact('model'));
    }
}
