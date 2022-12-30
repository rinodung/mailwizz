<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * Tracking_domainsController
 *
 * Handles the actions for tracking domains related tasks
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.4.6
 */

class Tracking_domainsController extends Controller
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

        if ($customer->getGroupOption('tracking_domains.can_manage_tracking_domains', 'no') != 'yes') {
            $this->redirect(['dashboard/index']);
            return;
        }

        // make sure the parent account has allowed access for this subaccount
        if (is_subaccount() && !subaccount()->canManageDomains()) {
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
     * @return void
     * @throws CException
     */
    public function actionIndex()
    {
        $domain = new TrackingDomain('search');
        $domain->unsetAttributes();

        $domain->attributes  = (array)request()->getQuery($domain->getModelName(), []);
        $domain->customer_id = customer()->getId();

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('tracking_domains', 'View tracking domains'),
            'pageHeading'     => t('tracking_domains', 'View tracking domains'),
            'pageBreadcrumbs' => [
                t('tracking_domains', 'Tracking domains') => createUrl('tracking_domains/index'),
                t('app', 'View all'),
            ],
        ]);

        $this->render('list', compact('domain'));
    }

    /**
     * @return void
     * @throws CException
     */
    public function actionCreate()
    {
        $domain = new TrackingDomain();

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($domain->getModelName(), []))) {
            $domain->attributes  = $attributes;
            $domain->customer_id = customer()->getId();
            if (!$domain->save()) {
                notify()->addError(t('app', 'Your form has a few errors, please fix them and try again!'));
            } else {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller'=> $this,
                'success'   => notify()->getHasSuccess(),
                'domain'    => $domain,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['tracking_domains/update', 'id' => $domain->domain_id]);
                return;
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('tracking_domains', 'Create new tracking domain'),
            'pageHeading'     => t('tracking_domains', 'Create new tracking domain'),
            'pageBreadcrumbs' => [
                t('tracking_domains', 'Tracking domains') => createUrl('tracking_domains/index'),
                t('app', 'Create new'),
            ],
        ]);

        $this->render('form', compact('domain'));
    }

    /**
     * @param int $id
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionUpdate($id)
    {
        $domain = TrackingDomain::model()->findByAttributes([
            'domain_id'   => (int)$id,
            'customer_id' => customer()->getId(),
        ]);

        if (empty($domain)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($domain->getModelName(), []))) {
            $domain->attributes  = $attributes;
            $domain->customer_id = customer()->getId();
            if (!$domain->save()) {
                notify()->addError(t('app', 'Your form has a few errors, please fix them and try again!'));
            } else {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller'=> $this,
                'success'   => notify()->getHasSuccess(),
                'domain'    => $domain,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['tracking_domains/update', 'id' => $domain->domain_id]);
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('tracking_domains', 'Update tracking domain'),
            'pageHeading'     => t('tracking_domains', 'Update tracking domain'),
            'pageBreadcrumbs' => [
                t('tracking_domains', 'Tracking domains') => createUrl('tracking_domains/index'),
                t('app', 'Update'),
            ],
        ]);

        $this->render('form', compact('domain'));
    }

    /**
     * @param int $id
     *
     * @return void
     * @throws CDbException
     * @throws CException
     * @throws CHttpException
     */
    public function actionDelete($id)
    {
        $domain = TrackingDomain::model()->findByAttributes([
            'domain_id'   => (int)$id,
            'customer_id' => customer()->getId(),
        ]);

        if (empty($domain)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        $domain->delete();

        $redirect = null;
        if (!request()->getQuery('ajax')) {
            notify()->addSuccess(t('app', 'The item has been successfully deleted!'));
            $redirect = request()->getPost('returnUrl', ['tracking_domains/index']);
        }

        // since 1.3.5.9
        hooks()->doAction('controller_action_delete_data', $collection = new CAttributeCollection([
            'controller' => $this,
            'model'      => $domain,
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
        $models = TrackingDomain::model()->findAllByAttributes([
            'customer_id' => (int)customer()->getId(),
        ]);

        if (empty($models)) {
            notify()->addError(t('app', 'There is no item available for export!'));
            $this->redirect(['index']);
            return;
        }

        // Set the download headers
        HeaderHelper::setDownloadHeaders('tracking-domains.csv');

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
