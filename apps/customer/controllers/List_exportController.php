<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * List_exportController
 *
 * Handles the actions for list export related tasks
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

class List_exportController extends Controller
{
    /**
     * @return void
     * @throws CException
     */
    public function init()
    {
        parent::init();

        /** @var OptionExporter $optionExporter */
        $optionExporter = container()->get(OptionExporter::class);

        if (!$optionExporter->getIsEnabled()) {
            $this->redirect(['lists/index']);
        }

        /** @var Customer $customer */
        $customer = customer()->getModel();

        if ($customer->getGroupOption('lists.can_export_subscribers', 'yes') != 'yes') {
            $this->redirect(['lists/index']);
        }

        // make sure the parent account has allowed access for this subaccount
        if (is_subaccount() && !subaccount()->canManageLists()) {
            $this->redirect(['dashboard/index']);
        }

        $this->addPageScript(['src' => AssetsUrl::js('list-export.js')]);
    }

    /**
     * @param string $list_uid
     *
     * @return void
     * @throws CHttpException
     */
    public function actionIndex($list_uid)
    {
        /** @var Lists $list */
        $list = $this->loadListModel((string)$list_uid);

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('list_export', 'Export subscribers from your list'),
            'pageHeading'     => t('list_export', 'Export subscribers'),
            'pageBreadcrumbs' => [
                t('lists', 'Lists') => createUrl('lists/index'),
                $list->name . ' ' => createUrl('lists/overview', ['list_uid' => $list->list_uid]),
                t('list_export', 'Export subscribers'),
            ],
        ]);

        $this->render('list', compact('list'));
    }

    /**
     * @param string $list_uid
     *
     * @return void
     * @throws CDbException
     * @throws CException
     * @throws CHttpException
     * @throws League\Csv\CannotInsertRecord
     */
    public function actionCsv($list_uid)
    {
        /** @var Lists $list */
        $list = $this->loadListModel((string)$list_uid);

        $export = new ListCsvExport();
        $export->list_id = (int)$list->list_id; // should not be assigned in attributes

        /** @var OptionExporter $exportOptions */
        $exportOptions  = container()->get(OptionExporter::class);
        $processAtOnce  = $exportOptions->getProcessAtOnce();
        $pause          = $exportOptions->getPause();

        // helps for when the document has been created on a Macintosh computer
        if (!ini_get('auto_detect_line_endings')) {
            ini_set('auto_detect_line_endings', '1');
        }

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($export->getModelName(), []))) {
            $export->attributes = $attributes;
        }

        if (!$export->count) {
            $export->count = $export->countSubscribers();
        }

        if (!request()->getIsPostRequest() || !request()->getIsAjaxRequest()) {
            $this->setData([
                'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('list_export', 'Export subscribers'),
                'pageHeading'     => t('list_export', 'Export subscribers'),
                'pageBreadcrumbs' => [
                    t('lists', 'Lists') => createUrl('lists/index'),
                    $list->name . ' ' => createUrl('lists/overview', ['list_uid' => $list->list_uid]),
                    t('list_export', 'CSV Export'),
                ],
            ]);
            $this->render('csv', compact('list', 'export', 'processAtOnce', 'pause'));
            return;
        }

        if ($export->count == 0) {
            $this->renderJson([
                'result'    => 'error',
                'message'   => t('list_export', 'Your list has no subscribers to export!'),
            ]);
            return;
        }

        $storageDir     = (string)Yii::getPathOfAlias('common.runtime.list-export');
        $csvFile        = $list->getSubscribersExportCsvFileName();
        $isFirstBatch   = $export->is_first_batch;

        if ($export->is_first_batch) {

            // old csv
            if (is_file($oldCsvFile = $storageDir . '/' . $csvFile)) {
                unlink($oldCsvFile);
            }

            if (!file_exists($storageDir) && !is_dir($storageDir) && !mkdir($storageDir, 0777, true)) {
                $this->renderJson([
                    'result'    => 'error',
                    'message'   => t('list_export', 'Cannot create the storage directory for your export!'),
                ]);
                return;
            }

            /** @var Customer $customer */
            $customer = customer()->getModel();

            /** @var CustomerActionLogBehavior $logAction */
            $logAction = $customer->getLogAction();
            $logAction->listExportStart($list, $export);

            $export->is_first_batch = 0;
        }

        $csvWriter = League\Csv\Writer::createFromPath($storageDir . '/' . $csvFile, 'a');

        $exportLog       = [];
        $hasData         = false;
        $counter         = 0;
        $startFromOffset = ($export->current_page - 1) * $processAtOnce;
        $subscribers     = $export->findSubscribers($processAtOnce, $startFromOffset);

        if (!empty($subscribers)) {
            if ($isFirstBatch) {
                $csvWriter->insertOne(array_keys($subscribers[0]));
            }

            foreach ($subscribers as $subscriberData) {
                $csvWriter->insertOne(array_values($subscriberData));
                $exportLog[] = [
                    'type'      => 'success',
                    'message'   => t('list_export', 'Successfully added the email "{email}" to the export list.', [
                        '{email}' => $subscriberData['EMAIL'],
                    ]),
                    'counter'   => true,
                ];
            }
        }

        if (!empty($subscribers)) {
            $hasData = true;
        }

        $counter += count($subscribers);

        if ($counter > 0) {
            $exportLog[] = [
                'type'      => 'info',
                'message'   => t('list_export', 'Exported {count} subscribers, from {start} to {end}.', [
                    '{count}'   => $counter,
                    '{start}'   => ($export->current_page - 1) * $processAtOnce,
                    '{end}'     => (($export->current_page - 1) * $processAtOnce) + $processAtOnce,
                ]),
            ];
        }

        // is it done ?
        if (!$hasData || ($export->current_page * $processAtOnce >= $export->count)) {

            /** @var Customer $customer */
            $customer = customer()->getModel();

            /** @var CustomerActionLogBehavior $logAction */
            $logAction = $customer->getLogAction();
            $logAction->listExportEnd($list, $export);

            $exportLog[] = [
                'type'    => 'success',
                'message' => t('list_export', 'The export is now complete, starting the packing process...'),
            ];

            $downloadUrl = createUrl('list_export/csv_download', ['list_uid' => $list_uid]);

            $this->renderJson([
                'result'        => 'success',
                'message'       => t('list_export', 'Packing done, your file will be downloaded now, please wait...'),
                'download'      => $downloadUrl,
                'export_log'    => $exportLog,
                'recordsCount'  => $export->count,
            ]);
            return;
        }

        $export->current_page++;
        $this->renderJson([
            'result'        => 'success',
            'message'       => t('list_export', 'Please wait, starting another batch...'),
            'attributes'    => $export->attributes,
            'export_log'    => $exportLog,
            'recordsCount'  => $export->count,
        ]);
    }

    /**
     * @param string $list_uid
     *
     * @return void
     * @throws CHttpException
     */
    public function actionCsv_download($list_uid)
    {
        /** @var Lists $list */
        $list = $this->loadListModel((string)$list_uid);

        $storageDir = (string)Yii::getPathOfAlias('common.runtime.list-export');
        $csvName    = $list->getSubscribersExportCsvFileName();
        $csvPath    = $storageDir . '/' . $csvName;

        if (!is_file($csvPath)) {
            notify()->addError(t('list_export', 'The export file has been deleted.'));
            $this->redirect(['list_export/index']);
        }

        // Set the download headers
        HeaderHelper::setDownloadHeaders($csvName);

        try {
            $csvReader = League\Csv\Reader::createFromPath($csvPath, 'r');
            $csvReader->setDelimiter(StringHelper::detectCsvDelimiter($csvPath));
            $csvWriter = League\Csv\Writer::createFromPath('php://output', 'w');

            $csvHeader = $csvReader->getHeader();
            if (!empty($csvHeader)) {
                $csvWriter->insertOne($csvHeader);
            }
            $csvWriter->insertAll($csvReader->getRecords());
        } catch (Exception $e) {
        }

        unlink($csvPath);
    }

    /**
     * @param string $list_uid
     *
     * @return Lists
     * @throws CHttpException
     */
    public function loadListModel(string $list_uid): Lists
    {
        $model = Lists::model()->findByAttributes([
            'list_uid'      => $list_uid,
            'customer_id'   => (int)customer()->getId(),
        ]);

        if ($model === null) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        return $model;
    }
}
