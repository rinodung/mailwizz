<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * Ip_blacklistController
 *
 * Handles the actions for customer ip blacklist related tasks
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.1.6
 */

class Ip_blacklistController extends Controller
{
    /**
     * @return void
     * @throws CException
     */
    public function init()
    {
        parent::init();

        $this->addPageScript(['src' => AssetsUrl::js('ip-blacklist.js')]);
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
     * List all suppressed IPs.
     *
     * @return void
     * @throws CException
     */
    public function actionIndex()
    {
        $ip = new CustomerIpBlacklist('search');
        $ip->unsetAttributes();

        // for filters.
        $ip->attributes  = (array)request()->getQuery($ip->getModelName(), []);
        $ip->customer_id = (int)customer()->getId();

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('app', 'IP blacklist'),
            'pageHeading'     => t('app', 'IP blacklist'),
            'pageBreadcrumbs' => [
                t('app', 'IP blacklist') => createUrl('ip_blacklist/index'),
                t('app', 'View all'),
            ],
        ]);

        $this->render('list', compact('ip'));
    }

    /**
     * Add a new ip in the blacklist
     *
     * @return void
     * @throws CException
     */
    public function actionCreate()
    {
        $ip = new CustomerIpBlacklist();

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($ip->getModelName(), []))) {
            $ip->attributes  = $attributes;
            $ip->customer_id = (int)customer()->getId();

            if (!$ip->save()) {
                notify()->addError(t('app', 'Your form has a few errors, please fix them and try again!'));
            } else {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller' => $this,
                'success'    => notify()->getHasSuccess(),
                'ip'         => $ip,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['ip_blacklist/index']);
                return;
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('app', 'IP blacklist'),
            'pageHeading'     => t('ip_blacklist', 'Add a new IP to blacklist'),
            'pageBreadcrumbs' => [
                t('app', 'IP blacklist') => createUrl('ip_blacklist/index'),
                t('app', 'Create new'),
            ],
        ]);

        $this->render('form', compact('ip'));
    }

    /**
     * Update an existing IP from the blacklist
     *
     * @param int $id
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionUpdate($id)
    {
        $ip = CustomerIpBlacklist::model()->findByAttributes([
            'id'          => (int)$id,
            'customer_id' => (int)customer()->getId(),
        ]);

        if (empty($ip)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($ip->getModelName(), []))) {
            $ip->attributes  = $attributes;
            $ip->customer_id = (int)customer()->getId();

            if (!$ip->save()) {
                notify()->addError(t('app', 'Your form has a few errors, please fix them and try again!'));
            } else {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller'=> $this,
                'success'   => notify()->getHasSuccess(),
                'ip'        => $ip,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['ip_blacklist/index']);
                return;
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('app', 'IP blacklist'),
            'pageHeading'     => t('ip_blacklist', 'Update blacklisted IP'),
            'pageBreadcrumbs' => [
                t('app', 'IP blacklist') => createUrl('ip_blacklist/index'),
                t('app', 'Update'),
            ],
        ]);

        $this->render('form', compact('ip'));
    }

    /**
     * Delete an ip from the blacklist.
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
        $ip = CustomerIpBlacklist::model()->findByAttributes([
            'id'          => (int)$id,
            'customer_id' => (int)customer()->getId(),
        ]);

        if (empty($ip)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        $ip->delete();

        $redirect = null;
        if (!request()->getQuery('ajax')) {
            notify()->addSuccess(t('app', 'The item has been successfully deleted!'));
            $redirect = request()->getPost('returnUrl', ['ip_blacklist/index']);
        }

        hooks()->doAction('controller_action_delete_data', $collection = new CAttributeCollection([
            'controller' => $this,
            'ip'         => $ip,
            'redirect'   => $redirect,
        ]));

        if ($collection->itemAt('redirect')) {
            $this->redirect($collection->itemAt('redirect'));
        }
    }

    /**
     * Run a bulk action against the suppressed list of IPs
     *
     * @return void
     * @throws CDbException
     * @throws CException
     */
    public function actionBulk_action()
    {
        $action  = request()->getPost('bulk_action');
        $items   = array_unique((array)request()->getPost('bulk_item', []));

        if ($action == CustomerIpBlacklist::BULK_ACTION_DELETE && count($items)) {
            $affected = 0;
            foreach ($items as $item) {
                $ip = CustomerIpBlacklist::model()->findByAttributes([
                    'id'          => (int)$item,
                    'customer_id' => (int)customer()->getId(),
                ]);

                if (empty($ip)) {
                    continue;
                }

                $ip->delete();
                $affected++;
            }
            if ($affected) {
                notify()->addSuccess(t('app', 'The action has been successfully completed!'));
            }
        }

        $defaultReturn = request()->getServer('HTTP_REFERER', ['ip_blacklist/index']);
        $this->redirect(request()->getPost('returnUrl', $defaultReturn));
    }

    /**
     * Delete all the IPs from the suppression list
     *
     * @return void
     * @throws CDbException
     * @throws CException
     */
    public function actionDelete_all()
    {
        $criteria = new CDbCriteria();
        $criteria->select = 'id, customer_id, ip_address';
        $criteria->compare('customer_id', (int)customer()->getId());
        $criteria->limit  = 500;

        $models = CustomerIpBlacklist::model()->findAll($criteria);
        while (!empty($models)) {
            foreach ($models as $model) {
                $model->delete();
            }
            $models = CustomerIpBlacklist::model()->findAll($criteria);
        }

        if (!request()->getQuery('ajax')) {
            notify()->addSuccess(t('app', 'Your items have been successfully deleted!'));
            $this->redirect(request()->getPost('returnUrl', ['ip_blacklist/index']));
        }
    }

    /**
     * Export existing suppressed IPs
     *
     * @return void
     */
    public function actionExport()
    {
        // Set the download headers
        HeaderHelper::setDownloadHeaders('ip-blacklist-' . date('Y-m-d-h-i-s') . '.csv');

        try {
            $csvWriter = League\Csv\Writer::createFromPath('php://output', 'w');
            $csvWriter->insertAll($this->getBlacklistDataForExport());
        } catch (Exception $e) {
        }

        app()->end();
    }

    /**
     * Import existing suppressed IPs
     *
     * @return void
     * @throws CException
     */
    public function actionImport()
    {
        $redirect = ['ip_blacklist/index'];

        if (!request()->getIsPostRequest()) {
            $this->redirect($redirect);
            return;
        }

        // helps for when the document has been created on a Macintosh computer
        if (!ini_get('auto_detect_line_endings')) {
            ini_set('auto_detect_line_endings', '1');
        }

        $import = new CustomerIpBlacklist('import');
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

        if (array_search('ip_address', $csvHeader) === false) {
            notify()->addError(t('app', 'Your form has a few errors, please fix them and try again!'));
            notify()->addError(t('ip_blacklist', 'Please note, the csv file must contain a header with at least the ip_address column.'));
            $this->redirect($redirect);
            return;
        }

        $totalRecords = 0;
        $totalImport  = 0;

        foreach ($csvReader->getRecords($csvHeader) as $row) {
            ++$totalRecords;

            $data = new CMap(ioFilter()->stripPurify($row));

            $model = new CustomerIpBlacklist();
            $model->customer_id = (int)customer()->getId();
            $model->ip_address  = $data->itemAt('ip_address');

            if ($model->save()) {
                $totalImport++;
            }

            unset($model, $data);
        }

        notify()->addSuccess(t('ip_blacklist', 'Your file has been successfully imported, from {count} records, {total} were imported!', [
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
        $criteria->select = 't.customer_id, t.ip_address, t.date_added';
        $criteria->compare('customer_id', (int)customer()->getId());
        $criteria->limit  = 500;
        $criteria->offset = 0;

        yield ['ip_address', 'date_added'];

        while (true) {

            /** @var CustomerIpBlacklist[] $models */
            $models = CustomerIpBlacklist::model()->findAll($criteria);
            if (empty($models)) {
                break;
            }

            foreach ($models as $model) {
                yield [$model->ip_address, $model->date_added];
            }

            $criteria->offset = $criteria->offset + $criteria->limit;
        }
    }
}
