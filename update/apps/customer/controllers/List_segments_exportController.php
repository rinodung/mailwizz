<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * List_segments_exportController
 *
 * Handles the actions for list segments export related tasks
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.4.8
 */

class List_segments_exportController extends Controller
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

        if ($customer->getGroupOption('lists.can_import_subscribers', 'yes') != 'yes') {
            $this->redirect(['lists/index']);
        }

        // make sure the parent account has allowed access for this subaccount
        if (is_subaccount() && !subaccount()->canManageLists()) {
            $this->redirect(['dashboard/index']);
        }

        $this->addPageScript(['src' => AssetsUrl::js('list-segments-export.js')]);
    }

    /**
     * @param string $list_uid
     * @param string $segment_uid
     *
     * @return void
     * @throws CHttpException
     */
    public function actionIndex($list_uid, $segment_uid)
    {
        /** @var Lists $list */
        $list = $this->loadListModel((string)$list_uid);

        /** @var ListSegment $segment */
        $segment = $this->loadSegmentModel((int)$list->list_id, (string)$segment_uid);

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('list_export', 'Export subscribers from your list segment'),
            'pageHeading'     => t('list_export', 'Export subscribers'),
            'pageBreadcrumbs' => [
                t('lists', 'Lists')     => createUrl('lists/index'),
                $list->name . ' '       => createUrl('lists/overview', ['list_uid' => $list->list_uid]),
                t('lists', 'Segments')  => createUrl('list_segments/index', ['list_uid' => $list->list_uid]),
                $segment->name . ' '    => createUrl('list_segments/update', ['list_uid' => $list->list_uid, 'segment_uid' => $segment->segment_uid]),
                t('list_export', 'Export subscribers'),
            ],
        ]);

        $this->render('list', compact('list', 'segment'));
    }

    /**
     * @param string $list_uid
     * @param string $segment_uid
     *
     * @return void
     * @throws CDbException
     * @throws CException
     * @throws CHttpException
     * @throws League\Csv\CannotInsertRecord
     */
    public function actionCsv($list_uid, $segment_uid)
    {
        /** @var Lists $list */
        $list = $this->loadListModel((string)$list_uid);

        /** @var ListSegment $segment */
        $segment = $this->loadSegmentModel((int)$list->list_id, (string)$segment_uid);

        $export = new ListSegmentCsvExport();
        $export->list_id    = (int)$list->list_id; // should not be assigned in attributes
        $export->segment_id = (int)$segment->segment_id; // should not be assigned in attributes

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
                    t('lists', 'Segments') => createUrl('list_segments/index', ['list_uid' => $list->list_uid]),
                    $segment->name . ' ' => createUrl('list_segments/update', ['list_uid' => $list->list_uid, 'segment_uid' => $segment->segment_uid]),
                    t('list_export', 'Export subscribers') => createUrl('list_segments_export/index', ['list_uid' => $list->list_uid, 'segment_uid' => $segment->segment_uid]),
                    t('list_export', 'CSV Export'),
                ],
            ]);
            $this->render('csv', compact('list', 'segment', 'export', 'processAtOnce', 'pause'));
            return;
        }

        if ($export->count == 0) {
            $this->renderJson([
                'result'    => 'error',
                'message'   => t('list_export', 'Your list has no subscribers to export!'),
            ]);
            return;
        }

        $storageDir     = (string)Yii::getPathOfAlias('common.runtime.list-segment-export');
        $csvFile        = $segment->getSubscribersExportCsvFileName();
        $isFirstBatch   = $export->is_first_batch;

        if ($export->is_first_batch) {

            // old csv
            if (is_file($oldCsvFile = $storageDir . '/' . $csvFile)) {
                unlink($oldCsvFile);
            }

            // new ones
            if (!file_exists($storageDir) && !is_dir($storageDir) && !mkdir($storageDir, 0777, true)) {
                $this->renderJson([
                    'result'    => 'error',
                    'message'   => t('list_export', 'Cannot create the storage directory for your export!'),
                ]);
                return;
            }

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
            $exportLog[] = [
                'type'      => 'success',
                'message'   => t('list_export', 'The export is now complete, starting the packing process...'),
            ];

            $downloadUrl = createUrl('list_segments_export/csv_download', ['list_uid' => $list_uid, 'segment_uid' => $segment_uid]);

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
     * @param string $segment_uid
     *
     * @return void
     * @throws CHttpException
     */
    public function actionCsv_download($list_uid, $segment_uid)
    {
        /** @var Lists $list */
        $list = $this->loadListModel((string)$list_uid);

        /** @var ListSegment $segment */
        $segment = $this->loadSegmentModel((int)$list->list_id, (string)$segment_uid);

        $storageDir = (string)Yii::getPathOfAlias('common.runtime.list-segment-export');
        $csvName    = $segment->getSubscribersExportCsvFileName();
        $csvPath    = $storageDir . '/' . $csvName;

        if (!is_file($csvPath)) {
            notify()->addError(t('list_export', 'The export file has been deleted.'));
            $this->redirect(createUrl('list_segments_export/index', ['list_uid' => $list->list_uid, 'segment_uid' => $segment->segment_uid]));
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

        app()->end();
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

    /**
     * @param int $list_id
     * @param string $segment_uid
     *
     * @return ListSegment
     * @throws CHttpException
     */
    public function loadSegmentModel(int $list_id, string $segment_uid): ListSegment
    {
        $model = ListSegment::model()->findByAttributes([
            'list_id'     => $list_id,
            'segment_uid' => $segment_uid,
        ]);

        if ($model === null) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        return $model;
    }
}
