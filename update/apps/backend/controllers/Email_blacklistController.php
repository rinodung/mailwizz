<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * Email_blacklistController
 *
 * Handles the actions for blacklisted emails related tasks
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

class Email_blacklistController extends Controller
{
    /**
     * @return void
     */
    public function init()
    {
        $this->addPageScript(['src' => AssetsUrl::js('email-blacklist.js')]);
        $this->onBeforeAction = [$this, '_registerJuiBs'];
        parent::init();
    }

    /**
     * @return array
     */
    public function filters()
    {
        $filters = [
            'postOnly + delete, delete_all',
        ];

        return CMap::mergeArray($filters, parent::filters());
    }

    /**
     * List all blacklisted emails.
     * Delivery to blacklisted emails is denied
     *
     * @return void
     * @throws CException
     */
    public function actionIndex()
    {
        $blacklist = new EmailBlacklist();
        $filter    = new EmailBlacklistFilters();
        $filter->unsetAttributes();

        if ($attributes = (array)request()->getQuery('', [])) {
            $filter->attributes = CMap::mergeArray($filter->attributes, $attributes);
            $filter->hasSetFilters = true;
        }
        if ($attributes = (array)request()->getPost('', [])) {
            $filter->attributes = CMap::mergeArray($filter->attributes, $attributes);
            $filter->hasSetFilters = true;
        }

        if ($filter->hasSetFilters && !$filter->validate()) {
            notify()->addError($filter->shortErrors->getAllAsString());
            $this->redirect([$this->getRoute()]);
        }

        $criteria = new CDbCriteria();
        $criteria->compare('queue', 'backend.emailblacklist.deleteall');
        $criteria->compare('status', '<>' . QueueStatus::ACK);
        if (queue_monitor()->findByCriteria(new QueueMonitorCriteriaDatabase($criteria))) {
            notify()->addSuccess(t('app', 'You have jobs pending processing for deleting the global blacklist!'));
        }

        // the export action
        if ($filter->getIsExportAction()) {

            // Set the download headers
            HeaderHelper::setDownloadHeaders('blacklisted-emails.csv');

            try {
                $csvWriter = League\Csv\Writer::createFromPath('php://output', 'w');
                $csvWriter->insertOne(['Email', 'Reason', 'Date added']);
                /** @var EmailBlacklistFilters $model */
                foreach ($filter->getEmails() as $model) {
                    $csvWriter->insertOne([$model->email, $model->reason, $model->date_added]);
                }
            } catch (Exception $e) {
            }

            app()->end();
        }

        // the delete action
        if ($filter->getIsDeleteAction()) {
            $count    = 0;
            $emailIds = [];

            /** @var EmailBlacklistFilters $model */
            foreach ($filter->getEmails() as $model) {
                $emailIds[] = (int)$model->email_id;
                if (count($emailIds) % 1000 === 0) {
                    $count += $filter->deleteEmailsByIds($emailIds);
                    $emailIds = [];
                }
            }

            if (!empty($emailIds)) {
                $count += $filter->deleteEmailsByIds($emailIds);
            }

            notify()->addSuccess(t('email_blacklist', 'Action completed successfully, deleted {n} emails!', ['{n}' => $count]));
            $this->redirect([$this->getRoute()]);
        }

        $importUrl = ['email_blacklist/import'];
        /** @var OptionImporter $optionImporter */
        $optionImporter = container()->get(OptionImporter::class);
        if ($optionImporter->getIsEmailBlacklistCliEnabled()) {
            $importUrl = ['email_blacklist/import_queue'];
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('email_blacklist', 'Blacklisted emails'),
            'pageHeading'     => t('email_blacklist', 'Blacklisted emails'),
            'pageBreadcrumbs' => [
                t('email_blacklist', 'Blacklisted emails') => createUrl('email_blacklist/index'),
                t('app', 'View all'),
            ],
        ]);

        $this->render('list', compact('blacklist', 'filter', 'importUrl'));
    }

    /**
     * Add a new email in the blacklist
     *
     * @return void
     * @throws CException
     */
    public function actionCreate()
    {
        $blacklist  = new EmailBlacklist();

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($blacklist->getModelName(), []))) {
            $blacklist->attributes = $attributes;
            if (!$blacklist->save()) {
                notify()->addError(t('app', 'Your form has a few errors, please fix them and try again!'));
            } else {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller'=> $this,
                'success'   => notify()->getHasSuccess(),
                'blacklist' => $blacklist,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['email_blacklist/index']);
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('email_blacklist', 'Blacklisted emails'),
            'pageHeading'     => t('email_blacklist', 'Add a new email address to blacklist.'),
            'pageBreadcrumbs' => [
                t('email_blacklist', 'Blacklisted emails') => createUrl('email_blacklist/index'),
                t('app', 'Add new'),
            ],
        ]);

        $this->render('form', compact('blacklist'));
    }

    /**
     * Update an existing email from the blacklist
     *
     * @param int $id
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionUpdate($id)
    {
        $blacklist = EmailBlacklist::model()->findByPk((int)$id);

        if (empty($blacklist)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($blacklist->getModelName(), []))) {
            $blacklist->attributes = $attributes;
            if (!$blacklist->save()) {
                notify()->addError(t('app', 'Your form has a few errors, please fix them and try again!'));
            } else {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller'=> $this,
                'success'   => notify()->getHasSuccess(),
                'blacklist' => $blacklist,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['email_blacklist/index']);
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('email_blacklist', 'Blacklisted emails'),
            'pageHeading'     => t('email_blacklist', 'Update blacklisted email address.'),
            'pageBreadcrumbs' => [
                t('email_blacklist', 'Blacklisted emails') => createUrl('email_blacklist/index'),
                t('app', 'Update'),
            ],
        ]);

        $this->render('form', compact('blacklist'));
    }

    /**
     * Delete an email from the blacklist.
     * Once removed from the blacklist, the delivery servers will be able to deliver the email to the removed address
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
        $blacklist = EmailBlacklist::model()->findByPk((int)$id);

        if (empty($blacklist)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        $blacklist->delete();

        $redirect = null;
        if (!request()->getQuery('ajax')) {
            notify()->addSuccess(t('app', 'The item has been successfully deleted!'));
            $redirect = request()->getPost('returnUrl', ['email_blacklist/index']);
        }

        // since 1.3.5.9
        hooks()->doAction('controller_action_delete_data', $collection = new CAttributeCollection([
            'controller' => $this,
            'model'      => $blacklist,
            'redirect'   => $redirect,
        ]));

        if ($collection->itemAt('redirect')) {
            $this->redirect($collection->itemAt('redirect'));
        }
    }

    /**
     * Run a bulk action against the email blacklist
     *
     * @return void
     * @throws CDbException
     * @throws CException
     */
    public function actionBulk_action()
    {
        $action = request()->getPost('bulk_action');
        $items  = array_unique(array_map('intval', (array)request()->getPost('email_id', [])));

        if ($action == EmailBlacklist::BULK_ACTION_DELETE && count($items)) {
            $affected = 0;
            foreach ($items as $item) {
                $email = EmailBlacklist::model()->findByPk((int)$item);
                if (empty($email)) {
                    continue;
                }

                $email->delete();
                $affected++;
            }
            if ($affected) {
                notify()->addSuccess(t('app', 'The action has been successfully completed!'));
            }
        }

        $defaultReturn = request()->getServer('HTTP_REFERER', ['email_blacklist/index']);
        $this->redirect(request()->getPost('returnUrl', $defaultReturn));
    }

    /**
     * Delete all the emails from the blacklist
     *
     * @return void
     * @throws CException
     */
    public function actionDelete_all()
    {
        // the index action shows the message
        $criteria = new CDbCriteria();
        $criteria->compare('queue', 'backend.emailblacklist.deleteall');
        $criteria->compare('status', '<>' . QueueStatus::ACK);
        if (queue_monitor()->findByCriteria(new QueueMonitorCriteriaDatabase($criteria))) {
            if (!request()->getIsAjaxRequest()) {
                $this->redirect(request()->getPost('returnUrl', ['email_blacklist/index']));
            }
            app()->end();
        }

        queue_send('backend.emailblacklist.deleteall', ['user_id' => user()->getId()]);

        notify()->addSuccess(t('app', 'Your request has been successfully queued, you will be notified once it is completed!'));

        if (!request()->getIsAjaxRequest()) {
            $this->redirect(request()->getPost('returnUrl', ['email_blacklist/index']));
        }
    }

    /**
     * Export blacklisted emails
     *
     * @return void
     */
    public function actionExport()
    {
        // Set the download headers
        HeaderHelper::setDownloadHeaders('email-blacklist-' . date('Y-m-d-h-i-s') . '.csv');

        try {
            $csvWriter = League\Csv\Writer::createFromPath('php://output', 'w');
            $csvWriter->insertAll($this->getBlacklistDataForExport());
        } catch (Exception $e) {
        }

        app()->end();
    }

    /**
     * Import blacklisted emails
     *
     * @return void
     */
    public function actionImport()
    {
        $redirect   = ['email_blacklist/index'];

        if (!request()->getIsPostRequest()) {
            $this->redirect($redirect);
        }

        // helps for when the document has been created on a Macintosh computer
        if (!ini_get('auto_detect_line_endings')) {
            ini_set('auto_detect_line_endings', '1');
        }

        $import = new EmailBlacklist('import');
        $import->file = CUploadedFile::getInstance($import, 'file');

        if (!$import->validate()) {
            notify()->addError(t('app', 'Your form has a few errors, please fix them and try again!'));
            notify()->addError($import->shortErrors->getAllAsString());
            $this->redirect($redirect);
        }

        $csvReader = League\Csv\Reader::createFromPath($import->file->tempName, 'r');
        $csvReader->setDelimiter(StringHelper::detectCsvDelimiter($import->file->tempName));
        $csvReader->setHeaderOffset(0);

        $csvHeader = array_map('strtolower', array_map('trim', $csvReader->getHeader()));
        if (array_search('email', $csvHeader) === false) {
            notify()->addError(t('app', 'Your form has a few errors, please fix them and try again!'));
            notify()->addError(t('email_blacklist', 'Your file does not contain the header with the fields title!'));
            $this->redirect($redirect);
            return;
        }

        /** @var OptionImporter $optionImporter */
        $optionImporter = container()->get(OptionImporter::class);
        $importAtOnce   = $optionImporter->getImportAtOnce();
        $totalRecords   = $csvReader->count();
        $insert         = [];
        $totalImport    = 0;
        $count          = 0;

        // reset
        $csvReader->setHeaderOffset(0);

        /** @var array $row */
        foreach ($csvReader->getRecords($csvHeader) as $row) {

            // clean the data
            $row = (array)ioFilter()->stripPurify($row);

            $insert[] = [
                'email'         => $row['email'],
                'reason'        => $row['reason'] ?? '',
                'date_added'    => MW_DATETIME_NOW,
                'last_updated'  => MW_DATETIME_NOW,
            ];

            $count++;
            if ($count < $importAtOnce) {
                continue;
            }

            try {
                $importCount = EmailBlacklist::insertMultipleUnique($insert);
                $totalImport += $importCount;
            } catch (Exception $e) {
            }

            $count  = 0;
            $insert = [];
        }

        try {
            $importCount = EmailBlacklist::insertMultipleUnique($insert);
            $totalImport += $importCount;
        } catch (Exception $e) {
        }

        notify()->addSuccess(t('email_blacklist', 'Your file has been successfuly imported, from {count} records, {total} were imported!', [
            '{count}'   => $totalRecords,
            '{total}'   => $totalImport,
        ]));

        $this->redirect($redirect);
    }

    /**
     * Import into the queue existing suppressed emails
     *
     * @return void
     */
    public function actionImport_queue()
    {
        $redirect = ['email_blacklist/index'];

        if (!request()->getIsPostRequest()) {
            $this->redirect($redirect);
        }

        // helps for when the document has been created on a Macintosh computer
        if (!ini_get('auto_detect_line_endings')) {
            ini_set('auto_detect_line_endings', '1');
        }

        $import       = new EmailBlacklist('import');
        $import->file = CUploadedFile::getInstance($import, 'file');

        if (!$import->validate()) {
            notify()->addError(t('app', 'Your form has a few errors, please fix them and try again!'));
            notify()->addError($import->shortErrors->getAllAsString());
            $this->redirect($redirect);
        }

        $savePath = (string)Yii::getPathOfAlias('common.runtime.email-blacklist-import-queue');
        if (!file_exists($savePath) || !is_dir($savePath) || !is_writable($savePath)) {
            mkdir($savePath, 0777, true);
        }

        $file = $savePath . '/' . $import->file->name;
        if (!$import->file->saveAs($file)) {
            notify()->addError(t('app', 'Your form has a few errors, please fix them and try again!'));
            notify()->addError(t('email_blacklist', 'Unable to move the uploaded file!'));
            $this->redirect($redirect);
        }

        notify()->addSuccess(t('email_blacklist', 'Your file has been successfully queued for processing and you will be notified when processing is done!'));
        $this->redirect($redirect);
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

    /**
     * @return Generator
     */
    protected function getBlacklistDataForExport(): Generator
    {
        $criteria = new CDbCriteria();
        $criteria->select = 't.email, t.reason, t.date_added';
        $criteria->limit  = 500;
        $criteria->offset = 0;

        yield ['Email', 'Reason', 'Date added'];

        while (true) {

            /** @var EmailBlacklist[] $models */
            $models = EmailBlacklist::model()->findAll($criteria);
            if (empty($models)) {
                break;
            }

            foreach ($models as $model) {
                yield [$model->email, $model->reason, $model->date_added];
            }

            $criteria->offset = $criteria->offset + $criteria->limit;
        }
    }
}
