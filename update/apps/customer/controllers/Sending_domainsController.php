<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * Sending_domainsController
 *
 * Handles the actions for sending domains related tasks
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.4.7
 */

class Sending_domainsController extends Controller
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
        if ($customer->getGroupOption('sending_domains.can_manage_sending_domains', 'no') != 'yes') {
            $this->redirect(['dashboard/index']);
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
     * List all available sending domains
     *
     * @return void
     * @throws CException
     */
    public function actionIndex()
    {
        $domain  = new SendingDomain('search');
        $domain->unsetAttributes();

        $domain->attributes = (array)request()->getQuery($domain->getModelName(), []);
        $domain->customer_id = customer()->getId();

        if ($domain->getRequirementsErrors()) {
            $this->redirect('dashboard/index');
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('sending_domains', 'View sending domains'),
            'pageHeading'     => t('sending_domains', 'View sending domains'),
            'pageBreadcrumbs' => [
                t('sending_domains', 'Sending domains') => createUrl('sending_domains/index'),
                t('app', 'View all'),
            ],
        ]);

        $this->render('list', compact('domain'));
    }

    /**
     * Create a new sending domain
     *
     * @return void
     * @throws CException
     */
    public function actionCreate()
    {
        $domain = new SendingDomain();

        if ($domain->getRequirementsErrors()) {
            $this->redirect('dashboard/index');
        }

        /** @var Customer $customer */
        $customer = customer()->getModel();
        if (($limit = (int)$customer->getGroupOption('sending_domains.max_sending_domains', -1)) > -1) {
            $count = SendingDomain::model()->countByAttributes(['customer_id' => (int)$customer->customer_id]);
            if ($count >= $limit) {
                notify()->addWarning(t('sending_domains', 'You have reached the maximum number of allowed sending domains!'));
                $this->redirect(['sending_domains/index']);
            }
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
                $this->redirect(['sending_domains/update', 'id' => $domain->domain_id]);
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('sending_domains', 'Create new sending domain'),
            'pageHeading'     => t('sending_domains', 'Create new sending domain'),
            'pageBreadcrumbs' => [
                t('sending_domains', 'Sending domains') => createUrl('sending_domains/index'),
                t('app', 'Create new'),
            ],
        ]);

        $this->render('form', compact('domain'));
    }

    /**
     * Update existing sending domain
     *
     * @param int $id
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionUpdate($id)
    {
        $domain = SendingDomain::model()->findByAttributes([
            'domain_id'     => (int)$id,
            'customer_id'   => (int)customer()->getId(),
        ]);

        if (empty($domain)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        if ($domain->getRequirementsErrors()) {
            $this->redirect('dashboard/index');
        }

        if ($domain->getIsLocked()) {
            notify()->addWarning(t('servers', 'This domain is locked, you cannot change or delete it!'));
            $this->redirect(['sending_domains/index']);
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
                $this->redirect(['sending_domains/update', 'id' => $domain->domain_id]);
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('sending_domains', 'Update sending domain'),
            'pageHeading'     => t('sending_domains', 'Update sending domain'),
            'pageBreadcrumbs' => [
                t('sending_domains', 'Sending domains') => createUrl('sending_domains/index'),
                t('app', 'Update'),
            ],
        ]);

        $this->render('form', compact('domain'));
    }

    /**
     * Verify sending domain
     *
     * @return void
     * @param int $id
     * @throws CHttpException
     */
    public function actionVerify($id)
    {
        $domain = SendingDomain::model()->findByAttributes([
            'domain_id'     => (int)$id,
            'customer_id'   => (int)customer()->getId(),
        ]);
        if (empty($domain)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        $found = false;
        try {
            $found = $domain->hasValidDNSTxtRecord();
        } catch (Net_DNS2_Exception $e) {
            Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
        }

        if (!$found) {
            notify()->addError(t('sending_domains', 'Unable to find proper TXT record for your domain name, if you just added the records please wait for them to propagate.'));
            $this->redirect(['sending_domains/update', 'id' => $id]);
        }

        $domain->verified = SendingDomain::TEXT_YES;
        $domain->save(false);

        notify()->addSuccess(t('sending_domains', 'Your domain has been successfully verified.'));
        $this->redirect(['sending_domains/update', 'id' => $id]);
    }

    /**
     * Delete existing sending domain
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
        $domain = SendingDomain::model()->findByAttributes([
            'domain_id'     => (int)$id,
            'customer_id'   => (int)customer()->getId(),
        ]);

        if (empty($domain)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        if (!$domain->getIsLocked()) {
            $domain->delete();
        }

        $redirect = null;
        if (!request()->getQuery('ajax')) {
            notify()->addSuccess(t('app', 'The item has been successfully deleted!'));
            $redirect = request()->getPost('returnUrl', ['sending_domains/index']);
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
        $models = SendingDomain::model()->findAllByAttributes([
            'customer_id' => (int)customer()->getId(),
        ]);

        if (empty($models)) {
            notify()->addError(t('app', 'There is no item available for export!'));
            $this->redirect(['index']);
        }

        // Set the download headers
        HeaderHelper::setDownloadHeaders('sending-domains.csv');

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
