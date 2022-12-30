<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * Email_box_monitorsController
 *
 * Handles the actions for incoming servers monitors related tasks
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.4.5
 */

class Email_box_monitorsController extends Controller
{
    /**
     * @return void
     */
    public function init()
    {
        $this->addPageScript(['src' => AssetsUrl::js('email-box-monitors.js')]);
        $this->onBeforeAction = [$this, '_registerJuiBs'];
        parent::init();
    }

    /**
     * @return array
     */
    public function filters()
    {
        $filters = [
            'postOnly + delete, copy, enable, disable',
        ];

        return CMap::mergeArray($filters, parent::filters());
    }

    /**
     * List available incoming servers monitors
     *
     * @return void
     * @throws CException
     */
    public function actionIndex()
    {
        $server = new EmailBoxMonitor('search');
        $server->unsetAttributes();
        $server->attributes = (array)request()->getQuery($server->getModelName(), []);

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('servers', 'View email box monitors'),
            'pageHeading'     => t('servers', 'View email box monitors'),
            'pageBreadcrumbs' => [
                t('servers', 'Email box monitors') => createUrl('email_box_monitors/index'),
                t('app', 'View all'),
            ],
        ]);

        $csvImport = new EmailBoxMonitorCsvImport();

        $this->render('list', compact('server', 'csvImport'));
    }

    /**
     * Create a new incoming server monitor
     *
     * @return void
     * @throws CException
     */
    public function actionCreate()
    {
        $server = new EmailBoxMonitor();

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($server->getModelName(), []))) {
            if (!$server->isNewRecord && empty($attributes['password']) && isset($attributes['password'])) {
                unset($attributes['password']);
            }
            $server->attributes = $attributes;

            if (!$server->testConnection() || !$server->save()) {
                notify()->addError(t('app', 'Your form has a few errors, please fix them and try again!'));
            } else {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller'=> $this,
                'success'   => notify()->getHasSuccess(),
                'server'    => $server,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['email_box_monitors/update', 'id' => $server->server_id]);
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('servers', 'Create new email box monitor'),
            'pageHeading'     => t('servers', 'Create new email box monitor'),
            'pageBreadcrumbs' => [
                t('servers', 'Email box monitors') => createUrl('email_box_monitors/index'),
                t('app', 'Create new'),
            ],
        ]);

        $this->render('form', compact('server'));
    }

    /**
     * Update existing incoming server monitor
     *
     * @param int $id
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionUpdate($id)
    {
        $server = EmailBoxMonitor::model()->findByPk((int)$id);
        if (empty($server)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        if (!$server->getCanBeUpdated()) {
            $this->redirect(['email_box_monitors/index']);
        }

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($server->getModelName(), []))) {
            if (!$server->isNewRecord && empty($attributes['password']) && isset($attributes['password'])) {
                unset($attributes['password']);
            }
            $server->attributes = $attributes;
            if (!$server->testConnection() || !$server->save()) {
                notify()->addError(t('app', 'Your form has a few errors, please fix them and try again!'));
            } else {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller' => $this,
                'success'    => notify()->getHasSuccess(),
                'server'     => $server,
            ]));
        }

        if ($server->getConditionsContainEmailList()) {
            $message = [
                t('servers', 'This monitor includes email lists in it\'s conditions which are not shown here!'),
                t('servers', 'Saving it will remove the conditions which contain email lists.'),
                t('servers', 'Login as this customer and edit the monitor from his account if you want the conditions saved!'),
            ];
            notify()->addWarning($message);
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('servers', 'Update email box monitor'),
            'pageHeading'     => t('servers', 'Update email box monitor'),
            'pageBreadcrumbs' => [
                t('servers', 'Email box monitors') => createUrl('email_box_monitors/index'),
                t('app', 'Update'),
            ],
        ]);

        $this->render('form', compact('server'));
    }

    /**
     * Delete existing incoming server monitor
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
        $server = EmailBoxMonitor::model()->findByPk((int)$id);
        if (empty($server)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        if ($server->getCanBeDeleted()) {
            $server->delete();
        }

        $redirect = null;
        if (!request()->getQuery('ajax')) {
            notify()->addSuccess(t('app', 'The item has been successfully deleted!'));
            $redirect = request()->getPost('returnUrl', ['email_box_monitors/index']);
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
     * Run a bulk action against the servers
     *
     * @return void
     * @throws CDbException
     * @throws CException
     */
    public function actionBulk_action()
    {
        $action = request()->getPost('bulk_action');
        $items  = array_unique(array_map('intval', (array)request()->getPost('bulk_item', [])));

        if ($action == EmailBoxMonitor::BULK_ACTION_DELETE && count($items)) {
            $affected = 0;
            foreach ($items as $item) {
                $server = EmailBoxMonitor::model()->findByPk((int)$item);
                if (empty($server)) {
                    continue;
                }

                if (!$server->getCanBeDeleted()) {
                    continue;
                }

                $server->delete();
                $affected++;
            }
            if ($affected) {
                notify()->addSuccess(t('app', 'The action has been successfully completed!'));
            }
        }

        $defaultReturn = request()->getServer('HTTP_REFERER', ['email_box_monitors/index']);
        $this->redirect(request()->getPost('returnUrl', $defaultReturn));
    }

    /**
     * Create a copy of an existing server
     *
     * @param int $id
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionCopy($id)
    {
        $server = EmailBoxMonitor::model()->findByPk((int)$id);
        if (empty($server)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        if ($server->copy()) {
            notify()->addSuccess(t('servers', 'Your server has been successfully copied!'));
        } else {
            notify()->addError(t('servers', 'Unable to copy the server!'));
        }

        if (!request()->getIsAjaxRequest()) {
            $this->redirect(request()->getPost('returnUrl', ['email_box_monitors/index']));
        }
    }

    /**
     * Enable a server that has been previously disabled
     *
     * @param int $id
     *
     * @return void
     * @throws CHttpException
     */
    public function actionEnable($id)
    {
        $server = EmailBoxMonitor::model()->findByPk((int)$id);
        if (empty($server)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        if ($server->getIsDisabled()) {
            $server->enable();
            notify()->addSuccess(t('servers', 'Your server has been successfully enabled!'));
        } else {
            notify()->addError(t('servers', 'The server must be disabled in order to enable it!'));
        }

        if (!request()->getIsAjaxRequest()) {
            $this->redirect(request()->getPost('returnUrl', ['email_box_monitors/index']));
        }
    }

    /**
     * Disable a server that has been previously verified
     *
     * @param int $id
     *
     * @return void
     * @throws CHttpException
     */
    public function actionDisable($id)
    {
        $server = EmailBoxMonitor::model()->findByPk((int)$id);
        if (empty($server)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        if ($server->getIsActive()) {
            $server->disable();
            notify()->addSuccess(t('servers', 'Your server has been successfully disabled!'));
        } else {
            notify()->addError(t('servers', 'The server must be active in order to disable it!'));
        }

        if (!request()->getIsAjaxRequest()) {
            $this->redirect(request()->getPost('returnUrl', ['email_box_monitors/index']));
        }
    }

    /**
     * Export
     *
     * @return void
     */
    public function actionExport()
    {
        $models = EmailBoxMonitor::model()->findAll();

        if (empty($models)) {
            notify()->addError(t('app', 'There is no item available for export!'));
            $this->redirect(['index']);
        }

        // Set the download headers
        HeaderHelper::setDownloadHeaders('email-box-monitors.csv');

        try {
            $csvWriter = League\Csv\Writer::createFromPath('php://output', 'w');

            $attributes = AttributeHelper::removeSpecialAttributes($models[0]->attributes, ['customer_id', 'password']);

            $csvWriter->insertOne(array_merge(array_keys($attributes), ['conditions', 'identify_subscribers_by']));

            foreach ($models as $model) {
                $attributes = AttributeHelper::removeSpecialAttributes($model->attributes, ['customer_id', 'password']);

                $csvWriter->insertOne(array_merge(array_values($attributes), [json_encode($model->getConditions()), $model->getIdentifySubscribersBy()]));
            }
        } catch (Exception $e) {
        }

        app()->end();
    }

    /**
     * Import new email box monitors
     *
     * @return void
     * @throws \League\Csv\Exception
     * @throws CException
     */
    public function actionImport()
    {
        $redirect = ['email_box_monitors/index'];

        if (!request()->getIsPostRequest()) {
            $this->redirect($redirect);
        }

        // helps for when the document has been created on a Macintosh computer
        if (!ini_get('auto_detect_line_endings')) {
            ini_set('auto_detect_line_endings', '1');
        }

        $import = new EmailBoxMonitorCsvImport('import');
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

            $model = EmailBoxMonitor::createFromArray($row);

            if ($model->hasErrors()) {
                $errorMessages[] = t('servers', 'Server configuration "{customer} - {hostname} - {username}" has the following errors: {errors}', [
                    '{customer}' => !empty($model->customer) ? $model->customer->getFullName() : t('app', 'System'),
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

    /**
     * @param CEvent $event
     *
     * @return void
     */
    public function _registerJuiBs(CEvent $event)
    {
        if (in_array($event->params['action']->id, ['create', 'update'])) {
            $this->addPageStyles([
                ['src' => apps()->getBaseUrl('assets/css/jui-bs/jquery-ui-1.10.3.custom.css'), 'priority' => -1001],
            ]);
        }
    }
}
