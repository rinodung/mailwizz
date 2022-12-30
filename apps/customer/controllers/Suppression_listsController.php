<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * Suppression_listsController
 *
 * Handles the actions for customer suppression lists related tasks
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.4.4
 */

class Suppression_listsController extends Controller
{
    /**
     * @return void
     * @throws CException
     */
    public function init()
    {
        parent::init();

        /** @var Customer $customer */
        $customer = customer()->getModel();
        if ($customer->getGroupOption('lists.can_use_own_blacklist', 'no') != 'yes') {
            $this->redirect(['dashboard/index']);
            return;
        }

        // make sure the parent account has allowed access for this subaccount
        if (is_subaccount() && !subaccount()->canManageBlacklists()) {
            $this->redirect(['dashboard/index']);
        }
    }

    /**
     * @return array
     * @throws CException
     */
    public function filters()
    {
        $filters = [
            'postOnly + delete',
        ];

        return CMap::mergeArray($filters, parent::filters());
    }

    /**
     * List all suppressions lists
     *
     * @return void
     * @throws CException
     */
    public function actionIndex()
    {
        $list = new CustomerSuppressionList('search');
        $list->unsetAttributes();

        // for filters.
        $list->attributes  = (array)request()->getQuery($list->getModelName(), []);
        $list->customer_id = (int)customer()->getId();

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('suppression_lists', 'Suppression lists'),
            'pageHeading'     => t('suppression_lists', 'Suppression lists'),
            'pageBreadcrumbs' => [
                t('suppression_lists', 'Suppression lists') => createUrl('suppression_lists/index'),
                t('app', 'View all'),
            ],
        ]);

        $this->render('list', compact('list'));
    }

    /**
     * Create a new suppression list
     *
     * @return void
     * @throws CException
     */
    public function actionCreate()
    {
        $list = new CustomerSuppressionList();

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($list->getModelName(), []))) {
            $list->attributes  = $attributes;
            $list->customer_id = (int)customer()->getId();

            if (!$list->save()) {
                notify()->addError(t('app', 'Your form has a few errors, please fix them and try again!'));
            } else {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller' => $this,
                'success'    => notify()->getHasSuccess(),
                'email'      => $list,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['suppression_lists/index']);
                return;
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('suppression_lists', 'Suppression lists'),
            'pageHeading'     => t('suppression_lists', 'Create new'),
            'pageBreadcrumbs' => [
                t('suppression_lists', 'Suppression lists') => createUrl('suppression_lists/index'),
                t('app', 'Create new'),
            ],
        ]);

        $this->render('form', compact('list'));
    }

    /**
     * Update an existing suppression list
     *
     * @param string $list_uid
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionUpdate($list_uid)
    {
        $list = CustomerSuppressionList::model()->findByAttributes([
            'list_uid'    => $list_uid,
            'customer_id' => (int)customer()->getId(),
        ]);

        if (empty($list)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($list->getModelName(), []))) {
            $list->attributes  = $attributes;
            $list->customer_id = (int)customer()->getId();
            if (!$list->save()) {
                notify()->addError(t('app', 'Your form has a few errors, please fix them and try again!'));
            } else {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller'=> $this,
                'success'   => notify()->getHasSuccess(),
                'list'      => $list,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['suppression_lists/index']);
                return;
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('suppression_lists', 'Suppression lists'),
            'pageHeading'     => t('suppression_lists', 'Update'),
            'pageBreadcrumbs' => [
                t('suppression_lists', 'Suppression lists') => createUrl('suppression_lists/index'),
                t('app', 'Update'),
            ],
        ]);

        $this->render('form', compact('list'));
    }

    /**
     * Delete a suppression list
     *
     * @param string $list_uid
     *
     * @return void
     * @throws CDbException
     * @throws CException
     * @throws CHttpException
    */
    public function actionDelete($list_uid)
    {
        $list = CustomerSuppressionList::model()->findByAttributes([
            'list_uid'    => $list_uid,
            'customer_id' => (int)customer()->getId(),
        ]);

        if (empty($list)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        $list->delete();

        $redirect = null;
        if (!request()->getQuery('ajax')) {
            notify()->addSuccess(t('app', 'The item has been successfully deleted!'));
            $redirect = request()->getPost('returnUrl', ['suppression_lists/index']);
        }

        hooks()->doAction('controller_action_delete_data', $collection = new CAttributeCollection([
            'controller' => $this,
            'list'       => $list,
            'redirect'   => $redirect,
        ]));

        if ($collection->itemAt('redirect')) {
            $this->redirect($collection->itemAt('redirect'));
        }
    }

    /**
     * Export
     *
     * @return void
     */
    public function actionExport()
    {
        $models = CustomerSuppressionList::model()->findAllByAttributes([
            'customer_id' => (int)customer()->getId(),
        ]);

        if (empty($models)) {
            notify()->addError(t('app', 'There is no item available for export!'));
            $this->redirect(['index']);
            return;
        }

        // Set the download headers
        HeaderHelper::setDownloadHeaders('suppression-lists.csv');

        try {
            $csvWriter  = League\Csv\Writer::createFromPath('php://output', 'w');
            $attributes = AttributeHelper::removeSpecialAttributes($models[0]->attributes);

            /** @var callable $callback */
            $callback   = [$models[0], 'getAttributeLabel'];
            $attributes = array_map($callback, array_keys($attributes));

            $csvWriter->insertOne($attributes);

            foreach ($models as $model) {
                $attributes = AttributeHelper::removeSpecialAttributes($model->attributes);
                $csvWriter->insertOne(array_values($attributes));
            }
        } catch (Exception $e) {
        }

        app()->end();
    }
}
