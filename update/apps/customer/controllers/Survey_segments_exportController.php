<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * Survey_segments_exportController
 *
 * Handles the actions for list segments export related tasks
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.7.8
 */

class Survey_segments_exportController extends Controller
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
            $this->redirect(['surveys/index']);
            return;
        }

        // make sure the parent account has allowed access for this subaccount
        if (is_subaccount() && !subaccount()->canManageSurveys()) {
            $this->redirect(['surveys/index']);
            return;
        }

        $this->addPageScript(['src' => AssetsUrl::js('survey-segments-export.js')]);
    }

    /**
     * @param string $survey_uid
     * @param string $segment_uid
     *
     * @return void
     * @throws CHttpException
     */
    public function actionIndex($survey_uid, $segment_uid)
    {
        /** @var Survey $survey */
        $survey = $this->loadSurveyModel((string)$survey_uid);

        /** @var SurveySegment $segment */
        $segment = $this->loadSegmentModel((int)$survey->survey_id, (string)$segment_uid);

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('survey_export', 'Export responders from your survey segment'),
            'pageHeading'     => t('survey_export', 'Export responders'),
            'pageBreadcrumbs' => [
                t('surveys', 'Surveys') => createUrl('surveys/index'),
                $survey->name . ' ' => createUrl('surveys/overview', ['survey_uid' => $survey->survey_uid]),
                t('surveys', 'Segments') => createUrl('survey_segments/index', ['survey_uid' => $survey->survey_uid]),
                $segment->name . ' ' => createUrl('survey_segments/update', ['survey_uid' => $survey->survey_uid, 'segment_uid' => $segment->segment_uid]),
                t('survey_export', 'Export responders'),
            ],
        ]);

        $this->render('list', compact('survey', 'segment'));
    }

    /**
     * @param string $survey_uid
     * @param string $segment_uid
     *
     * @return void
     * @throws CDbException
     * @throws CException
     * @throws CHttpException
     * @throws League\Csv\CannotInsertRecord
     */
    public function actionCsv($survey_uid, $segment_uid)
    {
        /** @var Survey $survey */
        $survey = $this->loadSurveyModel((string)$survey_uid);

        /** @var SurveySegment $segment */
        $segment = $this->loadSegmentModel((int)$survey->survey_id, (string)$segment_uid);

        $export = new SurveySegmentCsvExport();
        $export->survey_id  = (int)$survey->survey_id; // should not be assigned in attributes
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
            $export->count = $export->countResponders();
        }

        if (!request()->getIsPostRequest() || !request()->getIsAjaxRequest()) {
            $this->setData([
                'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('survey_export', 'Export responders'),
                'pageHeading'     => t('survey_export', 'Export responders'),
                'pageBreadcrumbs' => [
                    t('surveys', 'Surveys') => createUrl('surveys/index'),
                    $survey->name . ' ' => createUrl('surveys/overview', ['survey_uid' => $survey->survey_uid]),
                    t('surveys', 'Segments') => createUrl('survey_segments/index', ['survey_uid' => $survey->survey_uid]),
                    $segment->name . ' ' => createUrl('survey_segments/update', ['survey_uid' => $survey->survey_uid, 'segment_uid' => $segment->segment_uid]),
                    t('survey_export', 'Export responders') => createUrl('survey_segments_export/index', ['survey_uid' => $survey->survey_uid, 'segment_uid' => $segment->segment_uid]),
                    t('survey_export', 'CSV Export'),
                ],
            ]);
            $this->render('csv', compact('survey', 'segment', 'export', 'processAtOnce', 'pause'));
            return;
        }

        if ($export->count == 0) {
            $this->renderJson([
                'result'    => 'error',
                'message'   => t('survey_export', 'Your survey has no responders to export!'),
            ]);
            return;
        }

        $storageDir     = (string)Yii::getPathOfAlias('common.runtime.survey-segment-export');
        $csvFile        = $segment->getRespondersExportCsvFileName();
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
                    'message'   => t('survey_export', 'Cannot create the storage directory for your export!'),
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
        $responders      = $export->findResponders($processAtOnce, $startFromOffset);
        $responder       = new SurveyResponder();

        if (!empty($responders)) {
            if ($isFirstBatch) {
                $csvWriter->insertOne(array_keys($responders[0]));
            }

            foreach ($responders as $responderData) {
                $csvWriter->insertOne(array_values($responderData));
                $exportLog[] = [
                    'type'      => 'success',
                    'message'   => t('survey_export', 'Successfully added the IP "{ip}" to the export survey.', [
                        '{ip}' => $responderData[$responder->getAttributeLabel('ip_address')],
                    ]),
                    'counter'   => true,
                ];
            }
        }

        if (!empty($responders)) {
            $hasData = true;
        }

        $counter += count($responders);

        if ($counter > 0) {
            $exportLog[] = [
                'type'      => 'info',
                'message'   => t('survey_export', 'Exported {count} responders, from {start} to {end}.', [
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
                'message'   => t('survey_export', 'The export is now complete, starting the packing process...'),
            ];

            $downloadUrl = createUrl('survey_segments_export/csv_download', ['survey_uid' => $survey_uid, 'segment_uid' => $segment_uid]);

            $this->renderJson([
                'result'        => 'success',
                'message'       => t('survey_export', 'Packing done, your file will be downloaded now, please wait...'),
                'download'      => $downloadUrl,
                'export_log'    => $exportLog,
                'recordsCount'  => $export->count,
            ]);
            return;
        }

        $export->current_page++;
        $this->renderJson([
            'result'        => 'success',
            'message'       => t('survey_export', 'Please wait, starting another batch...'),
            'attributes'    => $export->attributes,
            'export_log'    => $exportLog,
            'recordsCount'  => $export->count,
        ]);
    }

    /**
     * @param string $survey_uid
     * @param string $segment_uid
     *
     * @return void
     * @throws CHttpException
     */
    public function actionCsv_download($survey_uid, $segment_uid)
    {
        /** @var Survey $survey */
        $survey = $this->loadSurveyModel((string)$survey_uid);

        /** @var SurveySegment $segment */
        $segment = $this->loadSegmentModel((int)$survey->survey_id, (string)$segment_uid);

        $storageDir = (string)Yii::getPathOfAlias('common.runtime.survey-segment-export');
        $csvName    = $segment->getRespondersExportCsvFileName();
        $csvPath    = $storageDir . '/' . $csvName;

        if (!is_file($csvPath)) {
            notify()->addError(t('survey_export', 'The export file has been deleted.'));
            $this->redirect(createUrl('survey_segments_export/index', ['survey_uid' => $survey->survey_uid, 'segment_uid' => $segment->segment_uid]));
            return;
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
     * @param string $survey_uid
     *
     * @return Survey
     * @throws CHttpException
     */
    public function loadSurveyModel(string $survey_uid): Survey
    {
        $model = Survey::model()->findByAttributes([
            'survey_uid'    => $survey_uid,
            'customer_id'   => (int)customer()->getId(),
        ]);

        if ($model === null) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        return $model;
    }

    /**
     * @param int $survey_id
     * @param string $segment_uid
     *
     * @return SurveySegment
     * @throws CHttpException
     */
    public function loadSegmentModel(int $survey_id, string $segment_uid): SurveySegment
    {
        $model = SurveySegment::model()->findByAttributes([
            'survey_id'   => $survey_id,
            'segment_uid' => $segment_uid,
        ]);

        if ($model === null) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        return $model;
    }
}
