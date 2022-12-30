<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * Suppression_list_emailsController
 *
 * Handles the actions for customer email blacklist related tasks
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.4.4
 */

class Suppression_list_emailsController extends Controller
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
     * @param string $list_uid
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionIndex($list_uid)
    {
        /** @var CustomerSuppressionList $list */
        $list = $this->loadListModel((string)$list_uid);

        $email = new CustomerSuppressionListEmail('search');
        $email->unsetAttributes();

        // for filters.
        $email->attributes = (array)request()->getQuery($email->getModelName(), []);
        $email->list_id    = (int)$list->list_id;

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('suppression_lists', 'Suppression list emails'),
            'pageHeading'     => t('suppression_lists', 'Suppression list emails'),
            'pageBreadcrumbs' => [
                t('suppression_lists', 'Suppression lists') => createUrl('suppression_lists/index'),
                $list->name . ' ' => createUrl('suppression_list_emails/index', ['list_uid' => $list->list_uid]),
                t('app', 'View all'),
            ],
        ]);

        $importUrl = ['suppression_list_emails/import', 'list_uid' => $list->list_uid];

        /** @var OptionImporter $optionImporter */
        $optionImporter = container()->get(OptionImporter::class);
        if ($optionImporter->getIsSuppressionListCliEnabled()) {
            $importUrl = ['suppression_list_emails/import_queue', 'list_uid' => $list->list_uid];
        }

        $this->render('list', compact('email', 'list', 'importUrl'));
    }

    /**
     * @param string $list_uid
     *
     * @return void
     * @throws CDbException
     * @throws CException
     * @throws CHttpException
     */
    public function actionCreate($list_uid)
    {
        /** @var CustomerSuppressionList $list */
        $list = $this->loadListModel((string)$list_uid);

        $email = new CustomerSuppressionListEmail();

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($email->getModelName(), []))) {
            $email->attributes = $attributes;
            $email->list_id    = (int)$list->list_id;

            if (!$email->save()) {
                notify()->addError(t('app', 'Your form has a few errors, please fix them and try again!'));
            } else {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller' => $this,
                'success'    => notify()->getHasSuccess(),
                'list'       => $list,
                'email'      => $email,
            ]));

            if ($collection->itemAt('success')) {

                // since 1.8.0
                $list->touchLastUpdated();

                $this->redirect(['suppression_list_emails/index', 'list_uid' => $list->list_uid]);
                return;
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('suppression_lists', 'Suppression list emails'),
            'pageHeading'     => t('suppression_lists', 'Create new'),
            'pageBreadcrumbs' => [
                t('suppression_lists', 'Suppression lists') => createUrl('suppression_lists/index'),
                $list->name . ' ' => createUrl('suppression_list_emails/index', ['list_uid' => $list->list_uid]),
                t('app', 'Create new'),
            ],
        ]);

        $this->render('form', compact('email', 'list'));
    }

    /**
     * @param string $list_uid
     * @param string $email_id
     *
     * @return void
     * @throws CDbException
     * @throws CException
     * @throws CHttpException
     */
    public function actionUpdate($list_uid, $email_id)
    {
        /** @var CustomerSuppressionList $list */
        $list = $this->loadListModel((string)$list_uid);

        $email = CustomerSuppressionListEmail::model()->findByAttributes([
            'email_id' => $email_id,
            'list_id'  => $list->list_id,
        ]);

        if (empty($email)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($email->getModelName(), []))) {
            $email->attributes  = $attributes;
            $email->list_id     = (int)$list->list_id;
            if (!$email->save()) {
                notify()->addError(t('app', 'Your form has a few errors, please fix them and try again!'));
            } else {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller'=> $this,
                'success'   => notify()->getHasSuccess(),
                'list'      => $list,
                'email'     => $email,
            ]));

            if ($collection->itemAt('success')) {

                // since 1.8.0
                $list->touchLastUpdated();

                $this->redirect(['suppression_list_emails/index', 'list_uid' => $list->list_uid]);
                return;
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('suppression_lists', 'Suppression list emails'),
            'pageHeading'     => t('suppression_lists', 'Update'),
            'pageBreadcrumbs' => [
                t('suppression_lists', 'Suppression lists') => createUrl('suppression_lists/index'),
                $list->name . ' ' => createUrl('suppression_list_emails/index', ['list_uid' => $list->list_uid]),
                t('app', 'Update'),
            ],
        ]);

        $this->render('form', compact('email', 'list'));
    }

    /**
     * @param string $list_uid
     * @param string $email_id
     *
     * @return void
     * @throws CDbException
     * @throws CException
     * @throws CHttpException
     */
    public function actionDelete($list_uid, $email_id)
    {
        /** @var CustomerSuppressionList $list */
        $list = $this->loadListModel((string)$list_uid);

        $email = CustomerSuppressionListEmail::model()->findByAttributes([
            'email_id' => $email_id,
            'list_id'  => $list->list_id,
        ]);

        if (empty($email)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        $email->delete();

        $redirect = null;
        if (!request()->getQuery('ajax')) {
            notify()->addSuccess(t('app', 'The item has been successfully deleted!'));
            $redirect = request()->getPost('returnUrl', ['suppression_list_emails/index', 'list_uid' => $list->list_uid]);
        }

        hooks()->doAction('controller_action_delete_data', $collection = new CAttributeCollection([
            'controller' => $this,
            'list'       => $list,
            'email'      => $email,
            'redirect'   => $redirect,
        ]));

        // since 1.8.0
        $list->touchLastUpdated();

        if ($collection->itemAt('redirect')) {
            $this->redirect($collection->itemAt('redirect'));
        }
    }

    /**
     * @param string $list_uid
     *
     * @return void
     * @throws CDbException
     * @throws CException
     * @throws CHttpException
     */
    public function actionBulk_action($list_uid)
    {
        /** @var CustomerSuppressionList $list */
        $list = $this->loadListModel((string)$list_uid);

        $action = request()->getPost('bulk_action', '');
        $items  = array_unique((array)request()->getPost('bulk_item', []));

        if ($action == CustomerSuppressionListEmail::BULK_ACTION_DELETE && count($items)) {
            $affected = 0;
            foreach ($items as $item) {
                $email = CustomerSuppressionListEmail::model()->findByAttributes([
                    'email_id' => $item,
                    'list_id'  => $list->list_id,
                ]);

                if (empty($email)) {
                    continue;
                }

                $email->delete();
                $affected++;
            }

            if ($affected) {

                // since 1.8.0
                $list->touchLastUpdated();

                notify()->addSuccess(t('app', 'The action has been successfully completed!'));
            }
        }

        $defaultReturn = request()->getServer('HTTP_REFERER', ['suppression_list_emails/index', 'list_uid' => $list->list_uid]);
        $this->redirect(request()->getPost('returnUrl', $defaultReturn));
    }

    /**
     * @param string $list_uid
     *
     * @return void
     * @throws CHttpException
     */
    public function actionExport($list_uid)
    {
        /** @var CustomerSuppressionList $list */
        $list = $this->loadListModel((string)$list_uid);

        // set the right download headers
        HeaderHelper::setDownloadHeaders('email-suppression-list-' . $list->list_uid . '.csv');

        $csvWriter = League\Csv\Writer::createFromPath('php://output', 'w');
        $csvWriter->insertAll($this->getDataForExport($list));

        app()->end();
    }

    /**
     * @param string $list_uid
     *
     * @return void
     * @throws CDbException
     * @throws CHttpException
     */
    public function actionImport($list_uid)
    {
        /** @var CustomerSuppressionList $list */
        $list = $this->loadListModel((string)$list_uid);

        $redirect = ['suppression_list_emails/index', 'list_uid' => $list->list_uid];

        if (!request()->getIsPostRequest()) {
            $this->redirect($redirect);
            return;
        }

        // helps for when the document has been created on a Macintosh computer
        if (!ini_get('auto_detect_line_endings')) {
            ini_set('auto_detect_line_endings', '1');
        }

        $import = new CustomerSuppressionListEmail('import');
        $import->file    = CUploadedFile::getInstance($import, 'file');
        $import->list_id = (int)$list->list_id;

        if (!$import->validate()) {
            notify()->addError(t('app', 'Your form has a few errors, please fix them and try again!'));
            notify()->addError($import->shortErrors->getAllAsString());
            $this->redirect($redirect);
            return;
        }

        $csvReader = League\Csv\Reader::createFromPath($import->file->tempName, 'r');
        $csvReader->setDelimiter(StringHelper::detectCsvDelimiter($import->file->tempName));
        $csvReader->setHeaderOffset(0);

        $csvHeader = array_map('strtolower', array_map('trim', $csvReader->getHeader()));
        if (array_search('email', $csvHeader) === false) {
            notify()->addError(t('app', 'Your form has a few errors, please fix them and try again!'));
            notify()->addError(t('suppression_lists', 'Your file does not contain the header with the fields title!'));
            $this->redirect($redirect);
            return;
        }

        /** @var OptionImporter $optionImporter */
        $optionImporter = container()->get(OptionImporter::class);

        $importAtOnce = $optionImporter->getImportAtOnce();
        $totalRecords = $csvReader->count();
        $insert       = [];
        $totalImport  = 0;
        $count        = 0;

        // reset
        $csvReader->setHeaderOffset(0);

        /** @var array $row */
        foreach ($csvReader->getRecords($csvHeader) as $row) {

            // clean the data
            /** @var array $row */
            $row = (array)ioFilter()->stripPurify($row);

            $_insert = [
                'list_id' => $list->list_id,
            ];

            if (StringHelper::isMd5((string)$row['email'])) {
                $_insert['email_md5'] = $row['email'];
            } else {
                $_insert['email']     = $row['email'];
                $_insert['email_md5'] = md5((string)$row['email']);
            }

            $insert[] = $_insert;

            $count++;
            if ($count < $importAtOnce) {
                continue;
            }

            try {
                $importCount = CustomerSuppressionListEmail::insertMultipleUnique($insert);
                $totalImport += $importCount;
            } catch (Exception $e) {
            }

            $count  = 0;
            $insert = [];
        }

        try {
            $importCount = CustomerSuppressionListEmail::insertMultipleUnique($insert);
            $totalImport += $importCount;
        } catch (Exception $e) {
        }

        // since 1.7.9
        $list->touchLastUpdated();

        notify()->addSuccess(t('suppression_lists', 'Your file has been successfuly imported, from {count} records, {total} were imported!', [
            '{count}'   => $totalRecords,
            '{total}'   => $totalImport,
        ]));

        $this->redirect($redirect);
    }

    /**
     * @param string $list_uid
     *
     * @return void
     * @throws CHttpException
     */
    public function actionImport_queue($list_uid)
    {
        /** @var CustomerSuppressionList $list */
        $list = $this->loadListModel((string)$list_uid);

        $redirect = ['suppression_list_emails/index', 'list_uid' => $list->list_uid];

        if (!request()->getIsPostRequest()) {
            $this->redirect($redirect);
        }

        // helps for when the document has been created on a Macintosh computer
        if (!ini_get('auto_detect_line_endings')) {
            ini_set('auto_detect_line_endings', '1');
        }

        $import          = new CustomerSuppressionListEmail('import');
        $import->file    = CUploadedFile::getInstance($import, 'file');
        $import->list_id = (int)$list->list_id;

        if (!$import->validate()) {
            notify()->addError(t('app', 'Your form has a few errors, please fix them and try again!'));
            notify()->addError($import->shortErrors->getAllAsString());
            $this->redirect($redirect);
        }

        $savePath = (string)Yii::getPathOfAlias('common.runtime.suppression-list-import-queue');
        if (!file_exists($savePath) || !is_dir($savePath) || !is_writable($savePath)) {
            mkdir($savePath, 0777, true);
        }

        $counter = 0;
        $file    = $savePath . '/' . $list->list_uid . '-' . $counter . '.csv';
        while (is_file($file)) {
            $counter++;
            $file = $savePath . '/' . $list->list_uid . '-' . $counter . '.csv';
        }

        if (!$import->file->saveAs($file)) {
            notify()->addError(t('app', 'Your form has a few errors, please fix them and try again!'));
            notify()->addError(t('suppression_lists', 'Unable to move the uploaded file!'));
            $this->redirect($redirect);
        }

        notify()->addSuccess(t('suppression_lists', 'Your file has been successfully queued for processing and you will be notified when processing is done!'));
        $this->redirect($redirect);
    }

    /**
     * @param CustomerSuppressionList $list
     *
     * @return Generator
     */
    protected function getDataForExport(CustomerSuppressionList $list): Generator
    {
        $limit  = 1000;
        $lastID = 0;

        yield [
            t('suppression_lists', 'Email'),
        ];

        while (true) {
            $criteria = new CDbCriteria();
            $criteria->select = 'email_id, email, email_md5';
            $criteria->compare('email_id', '>' . (int)$lastID);
            $criteria->compare('list_id', (int)$list->list_id);
            $criteria->order = 'email_id ASC';
            $criteria->limit = (int)$limit;

            $models = CustomerSuppressionListEmail::model()->findAll($criteria);
            if (empty($models)) {
                break;
            }
            $lastID = $models[(is_countable($models) ? count($models) : 1) - 1]->email_id;

            foreach ($models as $model) {
                yield [
                    $model->getDisplayEmail(),
                ];
            }
        }
    }

    /**
     * @param string $list_uid
     *
     * @return CustomerSuppressionList
     * @throws CHttpException
     */
    protected function loadListModel(string $list_uid)
    {
        $model = CustomerSuppressionList::model()->findByAttributes([
            'list_uid'    => $list_uid,
            'customer_id' => (int)customer()->getId(),
        ]);

        if ($model === null) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        return $model;
    }
}
