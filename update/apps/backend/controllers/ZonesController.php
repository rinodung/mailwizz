<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * ZonesController
 *
 * Handles the actions for zones related tasks
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.4.5
 */

class ZonesController extends Controller
{
    /**
     * @return array
     */
    public function filters()
    {
        $filters = [];
        return CMap::mergeArray($filters, parent::filters());
    }

    /**
     * List all available zones
     *
     * @throws CException
     * @return void
     */
    public function actionIndex()
    {
        $zone = new Zone('search');
        $zone->unsetAttributes();

        $zone->attributes = (array)request()->getQuery($zone->getModelName(), []);

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('zones', 'View zones'),
            'pageHeading'     => t('articles', 'View zones'),
            'pageBreadcrumbs' => [
                t('zones', 'Zones') => createUrl('zones/index'),
                t('app', 'View all'),
            ],
        ]);

        $this->render('list', compact('zone'));
    }

    /**
     * Create a new zone
     *
     * @throws CException
     * @return void
     */
    public function actionCreate()
    {
        $zone = new Zone();

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($zone->getModelName(), []))) {
            $zone->attributes = $attributes;
            if (!$zone->save()) {
                notify()->addError(t('app', 'Your form has a few errors, please fix them and try again!'));
            } else {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller'=> $this,
                'success'   => notify()->getHasSuccess(),
                'zone'      => $zone,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['zones/index']);
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('zones', 'Create new zone'),
            'pageHeading'     => t('zones', 'Create new zone'),
            'pageBreadcrumbs' => [
                t('zones', 'Zones') => createUrl('zones/index'),
                t('app', 'Create new'),
            ],
        ]);

        $this->render('form', compact('zone'));
    }

    /**
     * Update existing zone
     *
     * @param int $id
     *
     * @throws CException
     * @throws CHttpException
     * @return void
     */
    public function actionUpdate($id)
    {
        $zone = Zone::model()->findByPk((int)$id);

        if (empty($zone)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($zone->getModelName(), []))) {
            $zone->attributes = $attributes;
            if (!$zone->save()) {
                notify()->addError(t('app', 'Your form has a few errors, please fix them and try again!'));
            } else {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller'=> $this,
                'success'   => notify()->getHasSuccess(),
                'zone'      => $zone,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['zones/index']);
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('zones', 'Update zone'),
            'pageHeading'     => t('zones', 'Update zone'),
            'pageBreadcrumbs' => [
                t('zones', 'Zones') => createUrl('zones/index'),
                t('app', 'Update'),
            ],
        ]);

        $this->render('form', compact('zone'));
    }

    /**
     * Delete existing zone
     *
     * @param int $id
     *
     * @throws CDbException
     * @throws CException
     * @throws CHttpException
     * @return void
     */
    public function actionDelete($id)
    {
        $zone = Zone::model()->findByPk((int)$id);

        if (empty($zone)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        if (request()->getIsPostRequest()) {
            $zone->delete();

            notify()->addSuccess(t('app', 'Your item has been successfully deleted!'));
            $redirect = request()->getPost('returnUrl', ['zones/index']);

            // since 1.3.5.9
            hooks()->doAction('controller_action_delete_data', $collection = new CAttributeCollection([
                'controller' => $this,
                'model'      => $zone,
                'redirect'   => $redirect,
            ]));

            if ($collection->itemAt('redirect')) {
                $this->redirect($collection->itemAt('redirect'));
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('zones', 'Confirm zone removal'),
            'pageHeading'     => t('zones', 'Confirm zone removal'),
            'pageBreadcrumbs' => [
                t('zones', 'Zones') => createUrl('zones/index'),
                $zone->name . ' ' => createUrl('zones/update', ['id' => $zone->zone_id]),
                t('zones', 'Confirm zone removal'),
            ],
        ]);

        $this->render('delete', compact('zone'));
    }

    /**
     * Ajax search for zones
     *
     * @throws CException
     * @return void
     */
    public function actionAjax_search()
    {
        $zone = new Zone('search');
        $zone->unsetAttributes();
        $zone->attributes = (array)request()->getQuery($zone->getModelName(), []);

        $this->renderJson(collect($zone->search()->getData())->map(function (Zone $zone) {
            return $zone->getAttributes(['zone_id', 'country_id', 'name', 'code']);
        })->toArray());
    }
}
