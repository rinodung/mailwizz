<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * Email_blacklist_monitorsController
 *
 * Handles the actions for blacklist monitors related tasks
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.6.9
 */

class Email_blacklist_monitorsController extends Controller
{
    /**
     * @return void
     */
    public function init()
    {
        $this->addPageScript(['src' => AssetsUrl::js('email-blacklist-monitors.js')]);
        parent::init();
    }

    /**
     * @return array
     */
    public function filters()
    {
        $filters = [
            'postOnly + delete',
        ];

        return CMap::mergeArray($filters, parent::filters());
    }

    /**
     * List all blacklist monitors.
     *
     * @return void
     * @throws CException
     */
    public function actionIndex()
    {
        $monitor = new EmailBlacklistMonitor('search');
        $monitor->unsetAttributes();
        $monitor->attributes = (array)request()->getQuery($monitor->getModelName(), []);

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('email_blacklist', 'Blacklist monitors'),
            'pageHeading'     => t('email_blacklist', 'Blacklist monitors'),
            'pageBreadcrumbs' => [
                t('email_blacklist', 'Blacklist monitors') => createUrl('email_blacklist_monitors/index'),
                t('app', 'View all'),
            ],
        ]);

        $this->render('list', compact('monitor'));
    }

    /**
     * Add a new blacklist monitor
     *
     * @return void
     * @throws CException
     */
    public function actionCreate()
    {
        $monitor = new EmailBlacklistMonitor();

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($monitor->getModelName(), []))) {
            $monitor->attributes = $attributes;
            if (!$monitor->save()) {
                notify()->addError(t('app', 'Your form has a few errors, please fix them and try again!'));
            } else {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller'=> $this,
                'success'   => notify()->getHasSuccess(),
                'monitor'   => $monitor,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['email_blacklist_monitors/index']);
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('email_blacklist', 'Blacklist monitors'),
            'pageHeading'     => t('email_blacklist', 'Create a new blacklist monitor.'),
            'pageBreadcrumbs' => [
                t('email_blacklist', 'Blacklist monitors') => createUrl('email_blacklist_monitors/index'),
                t('app', 'Create new'),
            ],
        ]);

        $this->render('form', compact('monitor'));
    }

    /**
     * Update an existing blacklist monitor
     *
     * @param int $id
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionUpdate($id)
    {
        $monitor = EmailBlacklistMonitor::model()->findByPk((int)$id);

        if (empty($monitor)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($monitor->getModelName(), []))) {
            $monitor->attributes = $attributes;
            if (!$monitor->save()) {
                notify()->addError(t('app', 'Your form has a few errors, please fix them and try again!'));
            } else {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller'=> $this,
                'success'   => notify()->getHasSuccess(),
                'monitor'   => $monitor,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['email_blacklist_monitors/index']);
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('email_blacklist', 'Blacklist monitors'),
            'pageHeading'     => t('email_blacklist', 'Update blacklist monitor.'),
            'pageBreadcrumbs' => [
                t('email_blacklist', 'Blacklist monitors') => createUrl('email_blacklist_monitors/index'),
                t('app', 'Update'),
            ],
        ]);

        $this->render('form', compact('monitor'));
    }

    /**
     * Delete a blacklist monitor.
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
        $monitor = EmailBlacklistMonitor::model()->findByPk((int)$id);

        if (empty($monitor)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        $monitor->delete();

        $redirect = null;
        if (!request()->getQuery('ajax')) {
            notify()->addSuccess(t('app', 'The item has been successfully deleted!'));
            $redirect = request()->getPost('returnUrl', ['email_blacklist_monitors/index']);
        }

        // since 1.3.5.9
        hooks()->doAction('controller_action_delete_data', $collection = new CAttributeCollection([
            'controller' => $this,
            'model'      => $monitor,
            'redirect'   => $redirect,
        ]));

        if ($collection->itemAt('redirect')) {
            $this->redirect($collection->itemAt('redirect'));
        }
    }

    /**
     * Delete all the emails from the blacklist
     *
     * @return void
     * @throws CException
     */
    public function actionDelete_all()
    {
        EmailBlacklistMonitor::model()->deleteAll();

        if (!request()->getQuery('ajax')) {
            notify()->addSuccess(t('app', 'Your items have been successfully deleted!'));
            $this->redirect(request()->getPost('returnUrl', ['email_blacklist_monitors/index']));
        }
    }

    /**
     * Export blacklist monitors
     *
     * @return void
     */
    public function actionExport()
    {
        // Set the download headers
        HeaderHelper::setDownloadHeaders('email-blacklist-monitors-' . date('Y-m-d-h-i-s') . '.csv');

        try {
            $csvWriter = League\Csv\Writer::createFromPath('php://output', 'w');
            $csvWriter->insertAll($this->getBlacklistMonitorsForExport());
        } catch (Exception $e) {
        }

        app()->end();
    }

    /**
     * Import blacklist monitors
     *
     * @return void
     */
    public function actionImport()
    {
        $redirect = ['email_blacklist_monitors/index'];

        if (!request()->getIsPostRequest()) {
            $this->redirect($redirect);
        }

        // helps for when the document has been created on a Macintosh computer
        if (!ini_get('auto_detect_line_endings')) {
            ini_set('auto_detect_line_endings', '1');
        }

        $import = new EmailBlacklistMonitor('import');
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

        $totalRecords = 0;
        $totalImport  = 0;

        foreach ($csvReader->getRecords($csvHeader) as $row) {
            ++$totalRecords;

            $model = new EmailBlacklistMonitor();
            $model->attributes = (array)ioFilter()->stripPurify($row);

            if ($model->save()) {
                $totalImport++;
            }
        }

        notify()->addSuccess(t('email_blacklist', 'Your file has been successfuly imported, from {count} records, {total} were imported!', [
            '{count}'   => ($totalRecords-1),
            '{total}'   => $totalImport,
        ]));

        $this->redirect($redirect);
    }

    /**
     * @return Generator
     */
    protected function getBlacklistMonitorsForExport(): Generator
    {
        $attributes = (new EmailBlacklistMonitor())->attributes;
        unset($attributes['monitor_id']);
        yield array_keys($attributes);

        $criteria = new CDbCriteria();
        $criteria->limit  = 100;
        $criteria->offset = 0;

        while (true) {

            /** @var EmailBlacklistMonitor[] $models */
            $models = EmailBlacklistMonitor::model()->findAll($criteria);
            if (empty($models)) {
                break;
            }

            foreach ($models as $model) {
                $attributes = $model->attributes;
                unset($attributes['monitor_id']);
                yield array_values($attributes);
            }

            $criteria->offset = $criteria->offset + $criteria->limit;
        }
    }
}
