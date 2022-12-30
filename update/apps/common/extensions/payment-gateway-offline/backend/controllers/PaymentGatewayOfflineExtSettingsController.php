<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * Controller file for gateway settings.
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 */

class PaymentGatewayOfflineExtSettingsController extends ExtensionController
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
        /** @var PaymentGatewayOfflineExtCommon $model */
        $model = container()->get(PaymentGatewayOfflineExtCommon::class);

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($model->getModelName(), []))) {
            $model->attributes = $attributes;
            if ($model->save()) {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            } else {
                notify()->addError(t('app', 'Your form has a few errors, please fix them and try again!'));
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . $this->t('Offline payment gateway'),
            'pageHeading'     => $this->t('Offline payment gateway'),
            'pageBreadcrumbs' => [
                t('payment_gateways', 'Payment gateways') => createUrl('payment_gateways/index'),
                $this->t('Offline payments'),
            ],
        ]);

        $this->render('index', compact('model'));
    }
}
