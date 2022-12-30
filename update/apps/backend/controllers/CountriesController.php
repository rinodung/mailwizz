<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * CountriesController
 *
 * Handles the actions for countries related tasks
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.4.5
 */

class CountriesController extends Controller
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
     * List all available countries
     *
     * @return void
     * @throws CException
     */
    public function actionIndex()
    {
        $country = new Country('search');
        $country->unsetAttributes();
        $country->attributes = (array)request()->getQuery($country->getModelName(), []);

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('countries', 'View countries'),
            'pageHeading'     => t('articles', 'View countries'),
            'pageBreadcrumbs' => [
                t('countries', 'Countries') => createUrl('countries/index'),
                t('app', 'View all'),
            ],
        ]);

        $this->render('list', compact('country'));
    }

    /**
     * Create a new country
     *
     * @return void
     * @throws CException
     */
    public function actionCreate()
    {
        $country = new Country();

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($country->getModelName(), []))) {
            $country->attributes = $attributes;
            if (!$country->save()) {
                notify()->addError(t('app', 'Your form has a few errors, please fix them and try again!'));
            } else {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller'=> $this,
                'success'   => notify()->getHasSuccess(),
                'country'   => $country,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['countries/index']);
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('countries', 'Create new country'),
            'pageHeading'     => t('countries', 'Create new country'),
            'pageBreadcrumbs' => [
                t('countries', 'Countries') => createUrl('countries/index'),
                t('app', 'Create new'),
            ],
        ]);

        $this->render('form', compact('country'));
    }

    /**
     * Update existing country
     *
     * @param int $id
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionUpdate($id)
    {
        $country = Country::model()->findByPk((int)$id);

        if (empty($country)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($country->getModelName(), []))) {
            $country->attributes = $attributes;
            if (!$country->save()) {
                notify()->addError(t('app', 'Your form has a few errors, please fix them and try again!'));
            } else {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller'=> $this,
                'success'   => notify()->getHasSuccess(),
                'country'   => $country,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['countries/index']);
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('countries', 'Update country'),
            'pageHeading'     => t('countries', 'Update country'),
            'pageBreadcrumbs' => [
                t('countries', 'Countries') => createUrl('countries/index'),
                t('app', 'Update'),
            ],
        ]);

        $this->render('form', compact('country'));
    }

    /**
     * Delete existing country
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
        $country = Country::model()->findByPk((int)$id);

        if (empty($country)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        if (request()->getIsPostRequest()) {
            $country->delete();

            notify()->addSuccess(t('app', 'Your item has been successfully deleted!'));
            $redirect = request()->getPost('returnUrl', ['countries/index']);

            // since 1.3.5.9
            hooks()->doAction('controller_action_delete_data', $collection = new CAttributeCollection([
                'controller' => $this,
                'model'      => $country,
                'redirect'   => $redirect,
            ]));

            if ($collection->itemAt('redirect')) {
                $this->redirect($collection->itemAt('redirect'));
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('countries', 'Confirm country removal'),
            'pageHeading'     => t('countries', 'Confirm country removal'),
            'pageBreadcrumbs' => [
                t('countries', 'Countries') => createUrl('countries/index'),
                $country->name . ' ' => createUrl('countries/update', ['id' => $country->country_id]),
                t('countries', 'Confirm country removal'),
            ],
        ]);

        $this->render('delete', compact('country'));
    }

    /**
     * Show existing country zones
     *
     * @param int $country_id
     *
     * @return void
     * @throws CException
     */
    public function actionZones($country_id)
    {
        $this->renderJson(['zones' => Zone::getAsDropdownOptionsByCountryId((int)$country_id)]);
    }
}
