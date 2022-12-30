<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * Email_blacklistController
 *
 * Handles the actions for customer email blacklist related tasks
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.6.2
 */

class Email_blacklistController extends Controller
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
            return;
        }

        $this->addPageScript(['src' => AssetsUrl::js('email-blacklist.js')]);
    }

    /**
     * @return array
     * @throws CException
     */
    public function filters()
    {
        $filters = [
            'postOnly + delete, delete_all',
        ];

        return CMap::mergeArray($filters, parent::filters());
    }

    /**
     * List all suppressed emails.
     * Delivery to suppressed emails is denied
     *
     * @return void
     * @throws CException
     */
    public function actionIndex()
    {
        $email = new CustomerEmailBlacklist('search');
        $email->unsetAttributes();

        // for filters.
        $email->attributes  = (array)request()->getQuery($email->getModelName(), []);
        $email->customer_id = (int)customer()->getId();

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('email_blacklist', 'Blacklist'),
            'pageHeading'     => t('email_blacklist', 'Blacklist'),
            'pageBreadcrumbs' => [
                t('email_blacklist', 'Blacklist') => createUrl('email_blacklist/index'),
                t('app', 'View all'),
            ],
        ]);

        $this->render('list', compact('email'));
    }

    /**
     * Add a new email in the blacklist
     *
     * @return void
     * @throws CException
     */
    public function actionCreate()
    {
        $email = new CustomerEmailBlacklist();

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($email->getModelName(), []))) {
            $email->attributes  = $attributes;
            $email->customer_id = (int)customer()->getId();

            if (!$email->save()) {
                notify()->addError(t('app', 'Your form has a few errors, please fix them and try again!'));
            } else {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller' => $this,
                'success'    => notify()->getHasSuccess(),
                'email'      => $email,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['email_blacklist/index']);
                return;
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('email_blacklist', 'Blacklist'),
            'pageHeading'     => t('email_blacklist', 'Add a new email address to blacklist.'),
            'pageBreadcrumbs' => [
                t('email_blacklist', 'Blacklist') => createUrl('email_blacklist/index'),
                t('app', 'Create new'),
            ],
        ]);

        $this->render('form', compact('email'));
    }

    /**
     * Update an existing email from the blacklist
     *
     * @param string $email_uid
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionUpdate($email_uid)
    {
        $email = CustomerEmailBlacklist::model()->findByAttributes([
            'email_uid'   => $email_uid,
            'customer_id' => (int)customer()->getId(),
        ]);

        if (empty($email)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($email->getModelName(), []))) {
            $email->attributes  = $attributes;
            $email->customer_id = (int)customer()->getId();
            if (!$email->save()) {
                notify()->addError(t('app', 'Your form has a few errors, please fix them and try again!'));
            } else {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller'=> $this,
                'success'   => notify()->getHasSuccess(),
                'email'     => $email,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['email_blacklist/index']);
                return;
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('email_blacklist', 'Blacklist'),
            'pageHeading'     => t('email_blacklist', 'Update blacklisted email address.'),
            'pageBreadcrumbs' => [
                t('email_blacklist', 'Blacklist') => createUrl('email_blacklist/index'),
                t('app', 'Update'),
            ],
        ]);

        $this->render('form', compact('email'));
    }

    /**
     * Delete an email from the blacklist.
     *
     * @param string $email_uid
     *
     * @return void
     * @throws CDbException
     * @throws CException
     * @throws CHttpException
     */
    public function actionDelete($email_uid)
    {
        $email = CustomerEmailBlacklist::model()->findByAttributes([
            'email_uid'   => $email_uid,
            'customer_id' => (int)customer()->getId(),
        ]);

        if (empty($email)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        $email->delete();

        $redirect = null;
        if (!request()->getQuery('ajax')) {
            notify()->addSuccess(t('app', 'The item has been successfully deleted!'));
            $redirect = request()->getPost('returnUrl', ['email_blacklist/index']);
        }

        hooks()->doAction('controller_action_delete_data', $collection = new CAttributeCollection([
            'controller' => $this,
            'email'      => $email,
            'redirect'   => $redirect,
        ]));

        if ($collection->itemAt('redirect')) {
            $this->redirect($collection->itemAt('redirect'));
        }
    }

    /**
     * Run a bulk action against the suppressed list of emails
     *
     * @return void
     * @throws CDbException
     * @throws CException
     */
    public function actionBulk_action()
    {
        $action  = request()->getPost('bulk_action');
        $items   = array_unique((array)request()->getPost('bulk_item', []));

        if ($action == CustomerEmailBlacklist::BULK_ACTION_DELETE && count($items)) {
            $affected = 0;
            foreach ($items as $item) {
                $email = CustomerEmailBlacklist::model()->findByAttributes([
                    'email_uid'   => $item,
                    'customer_id' => (int)customer()->getId(),
                ]);

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
     * Delete all the emails from the suppression list
     *
     * @return void
     * @throws CDbException
     * @throws CException
     */
    public function actionDelete_all()
    {
        $criteria = new CDbCriteria();
        $criteria->select = 'email_id, customer_id, email';
        $criteria->compare('customer_id', (int)customer()->getId());
        $criteria->limit  = 500;

        $models = CustomerEmailBlacklist::model()->findAll($criteria);
        while (!empty($models)) {
            foreach ($models as $model) {
                $model->delete();
            }
            $models = CustomerEmailBlacklist::model()->findAll($criteria);
        }

        if (!request()->getQuery('ajax')) {
            notify()->addSuccess(t('app', 'Your items have been successfully deleted!'));
            $this->redirect(request()->getPost('returnUrl', ['email_blacklist/index']));
        }
    }

    /**
     * Export existing suppressed emails
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
     * Import existing suppressed emails
     *
     * @return void
     * @throws CException
     */
    public function actionImport()
    {
        $redirect = ['email_blacklist/index'];

        if (!request()->getIsPostRequest()) {
            $this->redirect($redirect);
            return;
        }

        // helps for when the document has been created on a Macintosh computer
        if (!ini_get('auto_detect_line_endings')) {
            ini_set('auto_detect_line_endings', '1');
        }

        $import = new CustomerEmailBlacklist('import');
        $import->file = CUploadedFile::getInstance($import, 'file');

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
            notify()->addError(t('email_blacklist', 'Your file does not contain the header with the fields title!'));
            $this->redirect($redirect);
            return;
        }

        $totalRecords = 0;
        $totalImport  = 0;

        foreach ($csvReader->getRecords($csvHeader) as $row) {
            ++$totalRecords;

            $data = new CMap(ioFilter()->stripPurify($row));

            $model = new CustomerEmailBlacklist();
            $model->customer_id = (int)customer()->getId();
            $model->email       = $data->itemAt('email');
            $model->reason      = $data->itemAt('reason');

            if ($model->save()) {
                $totalImport++;
            }

            unset($model, $data);
        }

        notify()->addSuccess(t('email_blacklist', 'Your file has been successfuly imported, from {count} records, {total} were imported!', [
            '{count}'   => $totalRecords,
            '{total}'   => $totalImport,
        ]));

        $this->redirect($redirect);
    }

    /**
     * @return Generator
     */
    protected function getBlacklistDataForExport(): Generator
    {
        $criteria = new CDbCriteria();
        $criteria->select = 't.customer_id, t.email, t.reason, t.date_added';
        $criteria->compare('customer_id', (int)customer()->getId());
        $criteria->limit  = 500;
        $criteria->offset = 0;

        yield ['Email', 'Reason', 'Date added'];

        while (true) {

            /** @var CustomerEmailBlacklist[] $models */
            $models = CustomerEmailBlacklist::model()->findAll($criteria);
            if (empty($models)) {
                break;
            }

            foreach ($models as $model) {
                yield [$model->getDisplayEmail(), $model->reason, $model->date_added];
            }

            $criteria->offset = $criteria->offset + $criteria->limit;
        }
    }
}
