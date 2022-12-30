<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * Bounce_serversController
 *
 * Handles the actions for bounce servers related tasks
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.4
 */

class Bounce_serversController extends Controller
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
        if (!((int)$customer->getGroupOption('servers.max_bounce_servers', 0))) {
            $this->redirect(['dashboard/index']);
            return;
        }

        // make sure the parent account has allowed access for this subaccount
        if (is_subaccount() && !subaccount()->canManageServers()) {
            $this->redirect(['dashboard/index']);
            return;
        }

        $this->addPageScript(['src' => AssetsUrl::js('bounce-fbl-servers.js')]);
    }

    /**
     * @return array
     * @throws CException
     */
    public function filters()
    {
        $filters = [
            'postOnly + delete, copy, enable, disable',
        ];

        return CMap::mergeArray($filters, parent::filters());
    }

    /**
     * List available bounce servers
     *
     * @return void
     * @throws CException
     */
    public function actionIndex()
    {
        /** @var Customer $customer */
        $customer = customer()->getModel();

        $server = new BounceServer('search');
        $server->unsetAttributes();

        $server->attributes = (array)request()->getQuery($server->getModelName(), []);
        $server->customer_id= (int)$customer->customer_id;

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('servers', 'View bounce servers'),
            'pageHeading'     => t('servers', 'View bounce servers'),
            'pageBreadcrumbs' => [
                t('servers', 'Bounce servers') => createUrl('bounce_servers/index'),
                t('app', 'View all'),
            ],
        ]);

        $csvImport = new BounceServerCsvImport();

        $this->render('list', compact('server', 'csvImport'));
    }

    /**
     * Create a new bounce server
     *
     * @return void
     * @throws CException
     */
    public function actionCreate()
    {
        /** @var Customer $customer */
        $customer = customer()->getModel();

        $server = new BounceServer();
        $server->customer_id = (int)$customer->customer_id;

        if (($limit = (int)$customer->getGroupOption('servers.max_bounce_servers', 0)) > -1) {
            $count = BounceServer::model()->countByAttributes(['customer_id' => (int)$customer->customer_id]);
            if ($count >= $limit) {
                notify()->addWarning(t('servers', 'You have reached the maximum number of allowed servers!'));
                $this->redirect(['bounce_servers/index']);
                return;
            }
        }

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($server->getModelName(), []))) {
            if (!$server->isNewRecord && empty($attributes['password']) && isset($attributes['password'])) {
                unset($attributes['password']);
            }
            $server->attributes  = $attributes;
            $server->customer_id = (int)$customer->customer_id;
            if (!$server->testConnection() || !$server->save()) {
                notify()->addError(t('app', 'Your form has a few errors, please fix them and try again!'));
            } else {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
                notify()->addSuccess(t('servers', 'Please do not forget to associate this server with a delivery server!'));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller'=> $this,
                'success'   => notify()->getHasSuccess(),
                'server'    => $server,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['bounce_servers/update', 'id' => $server->server_id]);
                return;
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('servers', 'Create new server'),
            'pageHeading'     => t('servers', 'Create new bounce server'),
            'pageBreadcrumbs' => [
                t('servers', 'Bounce servers') => createUrl('bounce_servers/index'),
                t('app', 'Create new'),
            ],
        ]);

        $this->render('form', compact('server'));
    }

    /**
     * Update existing bounce server
     *
     * @param int $id
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionUpdate($id)
    {
        /** @var Customer $customer */
        $customer = customer()->getModel();

        $server = BounceServer::model()->findByAttributes([
            'server_id'   => (int)$id,
            'customer_id' => (int)$customer->customer_id,
        ]);

        if (empty($server)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        if (!$server->getCanBeUpdated()) {
            $this->redirect(['bounce_servers/index']);
        }

        if ($server->getIsLocked()) {
            notify()->addWarning(t('servers', 'This server is locked, you cannot update, enable, disable, copy or delete it!'));
            $this->redirect(['bounce_servers/index']);
        }

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($server->getModelName(), []))) {
            if (!$server->isNewRecord && empty($attributes['password']) && isset($attributes['password'])) {
                unset($attributes['password']);
            }

            $server->attributes  = $attributes;
            $server->customer_id = (int)$customer->customer_id;

            if (!$server->testConnection() || !$server->save()) {
                notify()->addError(t('app', 'Your form has a few errors, please fix them and try again!'));
            } else {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
                $deliveryServers = $server->deliveryServers;
                if (empty($deliveryServers)) {
                    notify()->addSuccess(t('servers', 'Please do not forget to associate this server with a delivery server!'));
                } elseif ($server->settingsChanged) {
                    $servers = [];
                    foreach ($deliveryServers as $srv) {
                        $servers[] = CHtml::link('&raquo; ' . $srv->hostname, createUrl('delivery_servers/update', ['type' => $srv->type, 'id' => $srv->server_id]), ['target' => '_blank']);
                    }
                    $prefix = '<br />' . str_repeat('&nbsp;', 5);
                    $message = t('servers', 'Following associated servers were marked as inactive and you need to verify them again: ');
                    $message .= $prefix . implode(', ' . $prefix, $servers);
                    notify()->addSuccess($message);
                }
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller'=> $this,
                'success'   => notify()->getHasSuccess(),
                'server'    => $server,
            ]));
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('servers', 'Update server'),
            'pageHeading'     => t('servers', 'Update bounce server'),
            'pageBreadcrumbs' => [
                t('servers', 'Bounce servers') => createUrl('bounce_servers/index'),
                t('app', 'Update'),
            ],
        ]);

        $this->render('form', compact('server'));
    }

    /**
     * Delete existing bounce server
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
        /** @var Customer $customer */
        $customer = customer()->getModel();

        $server = BounceServer::model()->findByAttributes([
            'server_id'   => (int)$id,
            'customer_id' => (int)$customer->customer_id,
        ]);

        if (empty($server)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        if ($server->getIsLocked()) {
            notify()->addWarning(t('servers', 'This server is locked, you cannot update, enable, disable, copy or delete it!'));
            if (!request()->getIsAjaxRequest()) {
                $this->redirect(request()->getPost('returnUrl', ['bounce_servers/index']));
            }
            app()->end();
        }

        if ($server->getCanBeDeleted()) {
            $server->delete();
        }

        $redirect = null;
        if (!request()->getQuery('ajax')) {
            notify()->addSuccess(t('app', 'The item has been successfully deleted!'));
            $redirect = request()->getPost('returnUrl', ['bounce_servers/index']);
        }

        // since 1.3.5.9
        hooks()->doAction('controller_action_delete_data', $collection = new CAttributeCollection([
            'controller' => $this,
            'model'      => $server,
            'redirect'   => $redirect,
        ]));

        if ($collection->itemAt('redirect')) {
            $this->redirect($collection->itemAt('redirect'));
        }
    }

    /**
     * Create a copy of an existing bounce server!
     *
     * @param int $id
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionCopy($id)
    {
        /** @var Customer $customer */
        $customer = customer()->getModel();

        $server = BounceServer::model()->findByAttributes([
            'server_id'   => (int)$id,
            'customer_id' => (int)$customer->customer_id,
        ]);

        if (empty($server)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        /** @var Customer $customer */
        $customer = customer()->getModel();

        if ($server->getIsLocked()) {
            notify()->addWarning(t('servers', 'This server is locked, you cannot update, enable, disable, copy or delete it!'));
            if (!request()->getIsAjaxRequest()) {
                $this->redirect(request()->getPost('returnUrl', ['bounce_servers/index']));
            }
            app()->end();
        }

        if (($limit = (int)$customer->getGroupOption('servers.max_bounce_servers', 0)) > -1) {
            $count = BounceServer::model()->countByAttributes(['customer_id' => (int)$customer->customer_id]);
            if ($count >= $limit) {
                notify()->addWarning(t('servers', 'You have reached the maximum number of allowed servers!'));
                if (!request()->getIsAjaxRequest()) {
                    $this->redirect(request()->getPost('returnUrl', ['bounce_servers/index']));
                }
                app()->end();
            }
        }

        if ($server->copy()) {
            notify()->addSuccess(t('servers', 'Your server has been successfully copied!'));
        } else {
            notify()->addError(t('servers', 'Unable to copy the server!'));
        }

        if (!request()->getIsAjaxRequest()) {
            $this->redirect(request()->getPost('returnUrl', ['bounce_servers/index']));
        }
    }

    /**
     * Enable a server that has been previously disabled.
     *
     * @param int $id
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionEnable($id)
    {
        /** @var Customer $customer */
        $customer = customer()->getModel();

        $server = BounceServer::model()->findByAttributes([
            'server_id'   => (int)$id,
            'customer_id' => (int)$customer->customer_id,
        ]);

        if (empty($server)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        if ($server->getIsLocked()) {
            notify()->addWarning(t('servers', 'This server is locked, you cannot update, enable, disable, copy or delete it!'));
            if (!request()->getIsAjaxRequest()) {
                $this->redirect(request()->getPost('returnUrl', ['bounce_servers/index']));
            }
            app()->end();
        }

        if ($server->getIsDisabled()) {
            $server->enable();
            notify()->addSuccess(t('servers', 'Your server has been successfully enabled!'));
        } else {
            notify()->addError(t('servers', 'The server must be disabled in order to enable it!'));
        }

        if (!request()->getIsAjaxRequest()) {
            $this->redirect(request()->getPost('returnUrl', ['bounce_servers/index']));
        }
    }

    /**
     * Disable a server that has been previously verified.
     *
     * @param int $id
     *
     * @return void
     * @throws CHttpException
     * @throws CException
     */
    public function actionDisable($id)
    {
        /** @var Customer $customer */
        $customer = customer()->getModel();

        $server = BounceServer::model()->findByAttributes([
            'server_id'   => (int)$id,
            'customer_id' => (int)$customer->customer_id,
        ]);

        if (empty($server)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        if ($server->getIsLocked()) {
            notify()->addWarning(t('servers', 'This server is locked, you cannot update, enable, disable, copy or delete it!'));
            if (!request()->getIsAjaxRequest()) {
                $this->redirect(request()->getPost('returnUrl', ['bounce_servers/index']));
            }
            app()->end();
        }

        if ($server->getIsActive()) {
            $server->disable();
            notify()->addSuccess(t('servers', 'Your server has been successfully disabled!'));
        } else {
            notify()->addError(t('servers', 'The server must be active in order to disable it!'));
        }

        if (!request()->getIsAjaxRequest()) {
            $this->redirect(request()->getPost('returnUrl', ['bounce_servers/index']));
        }
    }

    /**
     * Export
     *
     * @return void
     */
    public function actionExport()
    {
        $models = BounceServer::model()->findAllByAttributes([
            'customer_id' => (int)customer()->getId(),
        ]);

        if (empty($models)) {
            notify()->addError(t('app', 'There is no item available for export!'));
            $this->redirect(['index']);
        }

        // Set the download headers
        HeaderHelper::setDownloadHeaders('bounce-servers.csv');

        try {
            $csvWriter = League\Csv\Writer::createFromPath('php://output', 'w');

            $attributes = AttributeHelper::removeSpecialAttributes($models[0]->attributes, ['customer_id', 'password']);

            $csvWriter->insertOne(array_keys($attributes));

            foreach ($models as $model) {
                $attributes = AttributeHelper::removeSpecialAttributes($model->attributes, ['customer_id', 'password']);
                $csvWriter->insertOne(array_values($attributes));
            }
        } catch (Exception $e) {
        }

        app()->end();
    }

    /**
     * Import new bounce servers
     *
     * @return void
     * @throws \League\Csv\Exception
     * @throws CException
     */
    public function actionImport()
    {
        $redirect = ['bounce_servers/index'];

        if (!request()->getIsPostRequest()) {
            $this->redirect($redirect);
        }

        // helps for when the document has been created on a Macintosh computer
        if (!ini_get('auto_detect_line_endings')) {
            ini_set('auto_detect_line_endings', '1');
        }

        /** @var Customer $customer */
        $customer = customer()->getModel();

        $import = new BounceServerCsvImport('import');
        $import->file = CUploadedFile::getInstance($import, 'file');

        if (!$import->validate() || $import->file === null) {
            notify()->addError(t('app', 'Your form has a few errors, please fix them and try again!'));
            notify()->addError($import->shortErrors->getAllAsString());
            $this->redirect($redirect);
        }

        $csvReader = League\Csv\Reader::createFromPath($import->file->tempName, 'r');
        $csvReader->setDelimiter(StringHelper::detectCsvDelimiter($import->file->tempName));
        $csvReader->setHeaderOffset(0);
        $csvHeader = array_map('strtolower', array_map('trim', $csvReader->getHeader()));

        $totalRecords  = 0;
        $totalImport   = 0;
        $errorMessages = [];

        /** @var array $row */
        foreach ($csvReader->getRecords($csvHeader) as $row) {
            $row = (array)ioFilter()->stripPurify($row);

            ++$totalRecords;

            $model = BounceServer::createFromArray($row, $customer);

            if ($model->hasErrors()) {
                $errorMessages[] = t('servers', 'Server configuration "{customer} - {hostname} - {username}" has the following errors: {errors}', [
                    '{customer}' => $customer->getFullName(),
                    '{hostname}' => $model->hostname,
                    '{username}' => $model->username,
                    '{errors}'   => $model->shortErrors->getAllAsString(),
                ]);
                continue;
            }

            $totalImport++;
        }

        notify()->addSuccess(t('servers', 'Your file has been successfully imported, from {count} records, {total} were imported!', [
            '{count}' => $totalRecords,
            '{total}' => $totalImport,
        ]));

        if (!empty($errorMessages)) {
            notify()->addError($errorMessages);
        }

        $this->redirect($redirect);
    }
}
