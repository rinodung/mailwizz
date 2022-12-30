<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * Promo_codesController
 *
 * Handles the actions for promo codes related tasks
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.4.3
 */

class Promo_codesController extends Controller
{
    /**
     * @return void
     */
    public function init()
    {
        $this->onBeforeAction = [$this, '_registerJuiBs'];
        parent::init();
    }

    /**
     * @return array
     */
    public function filters()
    {
        $filters = [
            'postOnly + delete',
        ];

        return CMap::mergeArray($filters, parent::filters());
    }

    /**
     * List all available promo codes
     *
     * @return void
     * @throws CException
     */
    public function actionIndex()
    {
        $promoCode = new PricePlanPromoCode('search');
        $promoCode->unsetAttributes();
        $promoCode->attributes = (array)ioFilter()->xssClean((array)request()->getOriginalQuery($promoCode->getModelName(), []));

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('promo_codes', 'View promo codes'),
            'pageHeading'     => t('promo_codes', 'Promo codes'),
            'pageBreadcrumbs' => [
                t('promo_codes', 'Promo codes') => createUrl('promo_codes/index'),
                t('app', 'View all'),
            ],
        ]);

        $this->render('list', compact('promoCode'));
    }

    /**
     * Create a new promo code
     *
     * @return void
     * @throws CException
     */
    public function actionCreate()
    {
        $promoCode = new PricePlanPromoCode();

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($promoCode->getModelName(), []))) {
            $promoCode->attributes = $attributes;
            if (!$promoCode->save()) {
                notify()->addError(t('app', 'Your form has a few errors, please fix them and try again!'));
            } else {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            }

            hooks()->doAction('controller_save_data', $collection = new CAttributeCollection([
                'controller'=> $this,
                'success'   => notify()->getHasSuccess(),
                'promoCode' => $promoCode,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['promo_codes/index']);
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('promo_codes', 'Create new promo code'),
            'pageHeading'     => t('promo_codes', 'Create new promo code'),
            'pageBreadcrumbs' => [
                t('promo_codes', 'Promo codes') => createUrl('promo_codes/index'),
                t('app', 'Create new'),
            ],
        ]);

        $this->render('form', compact('promoCode'));
    }

    /**
     * Update existing promo code
     *
     * @param int $id
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionUpdate($id)
    {
        $promoCode = PricePlanPromoCode::model()->findByPk((int)$id);

        if (empty($promoCode)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($promoCode->getModelName(), []))) {
            $promoCode->attributes = $attributes;
            if (!$promoCode->save()) {
                notify()->addError(t('app', 'Your form has a few errors, please fix them and try again!'));
            } else {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            }

            hooks()->doAction('controller_save_data', $collection = new CAttributeCollection([
                'controller'=> $this,
                'success'   => notify()->getHasSuccess(),
                'promoCode' => $promoCode,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['promo_codes/index']);
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('promo_codes', 'Update promo code'),
            'pageHeading'     => t('promo_codes', 'Update promo code'),
            'pageBreadcrumbs' => [
                t('promo_codes', 'Promo codes') => createUrl('promo_codes/index'),
                t('app', 'Update'),
            ],
        ]);

        $this->render('form', compact('promoCode'));
    }

    /**
     * Delete existing promo code
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
        $promoCode = PricePlanPromoCode::model()->findByPk((int)$id);

        if (empty($promoCode)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        $promoCode->delete();

        $redirect = null;
        if (!request()->getQuery('ajax')) {
            notify()->addSuccess(t('app', 'The item has been successfully deleted!'));
            $redirect = request()->getPost('returnUrl', ['promo_codes/index']);
        }

        // since 1.3.5.9
        hooks()->doAction('controller_action_delete_data', $collection = new CAttributeCollection([
            'controller' => $this,
            'model'      => $promoCode,
            'redirect'   => $redirect,
        ]));

        if ($collection->itemAt('redirect')) {
            $this->redirect($collection->itemAt('redirect'));
        }
    }

    /**
     * Autocomplete for promo codes
     *
     * @param string $term
     *
     * @return void
     * @throws CException
     */
    public function actionAutocomplete($term)
    {
        if (!request()->getIsAjaxRequest()) {
            $this->redirect(['customers/index']);
        }

        $criteria = new CDbCriteria();
        $criteria->select = 'promo_code_id, code';
        $criteria->compare('code', $term, true);
        $criteria->limit = 10;

        $this->renderJson(PricePlanPromoCodeCollection::findAll($criteria)->map(function (PricePlanPromoCode $model) {
            return ['promo_code_id' => $model->promo_code_id, 'value' => $model->code];
        })->all());
    }

    /**
     * @param CEvent $event
     *
     * @return void
     */
    public function _registerJuiBs(CEvent $event)
    {
        if (in_array($event->params['action']->id, ['index', 'create', 'update'])) {
            $this->addPageStyles([
                ['src' => apps()->getBaseUrl('assets/css/jui-bs/jquery-ui-1.10.3.custom.css'), 'priority' => -1001],
            ]);
        }
    }
}
