<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * TaxesController
 *
 * Handles the actions for taxes related tasks
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.4.5
 */

class TaxesController extends Controller
{
    /**
     * @return void
     */
    public function init()
    {
        $this->addPageScript(['src' => AssetsUrl::js('taxes.js')]);
        parent::init();
    }

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
     * List all available taxes
     *
     * @return void
     * @throws CException
     */
    public function actionIndex()
    {
        $tax = new Tax('search');
        $tax->unsetAttributes();
        $tax->attributes = (array)request()->getQuery($tax->getModelName(), []);

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('taxes', 'View taxes'),
            'pageHeading'     => t('taxes', 'View taxes'),
            'pageBreadcrumbs' => [
                t('taxes', 'Taxes') => createUrl('taxes/index'),
                t('app', 'View all'),
            ],
        ]);

        $this->render('list', compact('tax'));
    }

    /**
     * Create a new tax
     *
     * @return void
     * @throws CException
     */
    public function actionCreate()
    {
        $tax = new Tax();

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($tax->getModelName(), []))) {
            $tax->attributes = $attributes;
            if (!$tax->save()) {
                notify()->addError(t('app', 'Your form has a few errors, please fix them and try again!'));
            } else {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller'=> $this,
                'success'   => notify()->getHasSuccess(),
                'tax'  => $tax,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['taxes/index']);
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('taxes', 'Create new tax'),
            'pageHeading'     => t('taxes', 'Create new tax'),
            'pageBreadcrumbs' => [
                t('taxes', 'Taxes') => createUrl('taxes/index'),
                t('app', 'Create new'),
            ],
        ]);

        $this->render('form', compact('tax'));
    }

    /**
     * Update existing tax
     *
     * @param int $id
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionUpdate($id)
    {
        $tax = Tax::model()->findByPk((int)$id);

        if (empty($tax)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($tax->getModelName(), []))) {
            $tax->attributes = $attributes;
            if (!$tax->save()) {
                notify()->addError(t('app', 'Your form has a few errors, please fix them and try again!'));
            } else {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller'=> $this,
                'success'   => notify()->getHasSuccess(),
                'tax'  => $tax,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['taxes/index']);
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('taxes', 'Update tax'),
            'pageHeading'     => t('taxes', 'Update tax'),
            'pageBreadcrumbs' => [
                t('taxes', 'Taxes') => createUrl('taxes/index'),
                t('app', 'Update'),
            ],
        ]);

        $this->render('form', compact('tax'));
    }

    /**
     * Delete existing tax
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
        $tax = Tax::model()->findByPk((int)$id);

        if (empty($tax)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        $tax->delete();

        $redirect = null;
        if (!request()->getQuery('ajax')) {
            notify()->addSuccess(t('app', 'The item has been successfully deleted!'));
            $redirect = request()->getPost('returnUrl', ['taxes/index']);
        }

        // since 1.3.5.9
        hooks()->doAction('controller_action_delete_data', $collection = new CAttributeCollection([
            'controller' => $this,
            'model'      => $tax,
            'redirect'   => $redirect,
        ]));

        if ($collection->itemAt('redirect')) {
            $this->redirect($collection->itemAt('redirect'));
        }
    }

    /**
     * Display country zones
     *
     * @return void
     * @throws CException
     */
    public function actionZones_by_country()
    {
        $criteria = new CDbCriteria();
        $criteria->select = 'zone_id, name';
        $criteria->compare('country_id', (int) request()->getQuery('country_id'));

        $this->renderJson([
            'zones' => ZoneCollection::findAll($criteria)->map(function (Zone $model) {
                return ['zone_id' => $model->zone_id, 'name' => $model->name];
            })->all(),
        ]);
    }
}
