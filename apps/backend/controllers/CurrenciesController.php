<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * CurrenciesController
 *
 * Handles the actions for currencies related tasks
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.4.5
 */

class CurrenciesController extends Controller
{
    /**
     * @return array
     */
    public function filters()
    {
        $filters = [
            'postOnly + delete, reset_sending_quota',
        ];

        return CMap::mergeArray($filters, parent::filters());
    }

    /**
     * List all available currencies
     *
     * @return void
     * @throws CException
     */
    public function actionIndex()
    {
        $currency = new Currency('search');
        $currency->unsetAttributes();
        $currency->attributes = (array)request()->getQuery($currency->getModelName(), []);

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('currencies', 'View currencies'),
            'pageHeading'     => t('currencies', 'View currencies'),
            'pageBreadcrumbs' => [
                t('currencies', 'Currencies') => createUrl('currencies/index'),
                t('app', 'View all'),
            ],
        ]);

        $this->render('list', compact('currency'));
    }

    /**
     * Create a new currency
     *
     * @return void
     * @throws CException
     */
    public function actionCreate()
    {
        $currency = new Currency();

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($currency->getModelName(), []))) {
            $currency->attributes = $attributes;
            if (!$currency->save()) {
                notify()->addError(t('app', 'Your form has a few errors, please fix them and try again!'));
            } else {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller'=> $this,
                'success'   => notify()->getHasSuccess(),
                'currency'  => $currency,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['currencies/index']);
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('currencies', 'Create new currency'),
            'pageHeading'     => t('currencies', 'Create new currency'),
            'pageBreadcrumbs' => [
                t('currencies', 'Currencies') => createUrl('currencies/index'),
                t('app', 'Create new'),
            ],
        ]);

        $this->render('form', compact('currency'));
    }

    /**
     * Update existing currency
     *
     * @param int $id
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionUpdate($id)
    {
        $currency = Currency::model()->findByPk((int)$id);

        if (empty($currency)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($currency->getModelName(), []))) {
            $currency->attributes = $attributes;
            if (!$currency->save()) {
                notify()->addError(t('app', 'Your form has a few errors, please fix them and try again!'));
            } else {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller'=> $this,
                'success'   => notify()->getHasSuccess(),
                'currency'  => $currency,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['currencies/index']);
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('currencies', 'Update currency'),
            'pageHeading'     => t('currencies', 'Update currency'),
            'pageBreadcrumbs' => [
                t('currencies', 'Currencies') => createUrl('currencies/index'),
                t('app', 'Update'),
            ],
        ]);

        $this->render('form', compact('currency'));
    }

    /**
     * Delete existing currency
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
        $currency = Currency::model()->findByPk((int)$id);

        if (empty($currency)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        if ($currency->getIsRemovable()) {
            $currency->delete();
        }

        $redirect = null;
        if (!request()->getQuery('ajax')) {
            notify()->addSuccess(t('app', 'The item has been successfully deleted!'));
            $redirect = request()->getPost('returnUrl', ['currencies/index']);
        }

        // since 1.3.5.9
        hooks()->doAction('controller_action_delete_data', $collection = new CAttributeCollection([
            'controller' => $this,
            'model'      => $currency,
            'redirect'   => $redirect,
        ]));

        if ($collection->itemAt('redirect')) {
            $this->redirect($collection->itemAt('redirect'));
        }
    }
}
