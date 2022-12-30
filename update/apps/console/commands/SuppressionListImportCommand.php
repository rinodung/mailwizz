<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * SuppressionListImportCommand
 *
 * Handles the actions for suppression list import related tasks.
 * Most of the logic is borrowed from the web interface importer.
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.4.4
 */

class SuppressionListImportCommand extends ConsoleCommand
{
    /**
     * The folder path from where we should load files
     *
     * @var string
     */
    public $folder_path;

    /**
     * Max amount of files to process from the folder
     *
     * @var int
     */
    public $folder_process_files = 10;

    /**
     * The list where we want to import into
     *
     * @var string
     */
    public $list_uid;

    /**
     * The path where the import file is located
     *
     * @var string
     */
    public $file_path;

    /**
     * @var array
     */
    public $lastMessage = [];

    /**
     * @return int
     * @throws CException
     */
    public function actionFolder()
    {
        if (empty($this->folder_path)) {
            $this->folder_path = (string)Yii::getPathOfAlias('common.runtime.suppression-list-import-queue');
            if (!file_exists($this->folder_path) || !is_dir($this->folder_path)) {
                mkdir($this->folder_path, 0777, true);
            }
        }

        if (!is_dir($this->folder_path) || !is_readable($this->folder_path)) {
            $this->renderMessage([
                'result'  => 'error',
                'message' => t('list_import', 'Call this command with the --folder_path=XYZ param where XYZ is the full path to the folder you want to monitor.'),
            ]);
            return 1;
        }

        $this->renderMessage([
            'result'  => 'info',
            'message' => 'The folder path is: ' . $this->folder_path,
        ]);

        $files  = FileSystemHelper::readDirectoryContents($this->folder_path, true);
        $pcntl  = CommonHelper::functionExists('pcntl_fork') && CommonHelper::functionExists('pcntl_waitpid');
        $children = [];

        if ($pcntl) {
            // close the external connections
            $this->setExternalConnectionsActive(false);
        }

        if (count($files) > (int)$this->folder_process_files) {
            $files = array_slice($files, (int)$this->folder_process_files);
        }

        $this->renderMessage([
            'result'  => 'info',
            'message' => 'Found ' . count($files) . ' files (some of them might be already processing)',
        ]);

        foreach ($files as $file) {
            if (!$pcntl) {
                $this->processFile($file);
                continue;
            }

            //
            $pid = pcntl_fork();
            if ($pid == -1) {
                continue;
            }

            // Parent
            if ($pid) {
                $children[] = $pid;
            }

            // Child
            if (!$pid) {
                $this->processFile($file);
                app()->end();
            }
        }

        if ($pcntl) {
            while (count($children) > 0) {
                foreach ($children as $key => $pid) {
                    $res = pcntl_waitpid($pid, $status, WNOHANG);
                    if ($res == -1 || $res > 0) {
                        unset($children[$key]);
                    }
                }
                sleep(1);
            }
        }

        return 0;
    }

    /**
     * @return int
     * @throws CException
     */
    public function actionCsv()
    {
        hooks()->doAction('console_command_suppression_list_import_before_process', new CAttributeCollection([
            'command'    => $this,
            'importType' => 'csv',
            'listUid'    => $this->list_uid,
            'filePath'   => $this->file_path,
        ]));

        $result = $this->processCsv([
            'list_uid'    => $this->list_uid,
            'file_path'   => $this->file_path,
        ]);

        hooks()->doAction('console_command_suppression_list_import_after_process', new CAttributeCollection([
            'command'    => $this,
            'importType' => 'csv',
            'listUid'    => $this->list_uid,
            'filePath'   => $this->file_path,
        ]));

        return $result;
    }

    /**
     * @param string $file
     * @return int
     */
    protected function processFile($file)
    {
        $this->renderMessage([
            'result'  => 'info',
            'message' => 'Processing: ' . $file,
        ]);

        $lockName = sha1($file);
        if (!mutex()->acquire($lockName, 5)) {
            $this->renderMessage([
                'result'  => 'info',
                'message' => 'Cannot acquire lock for processing: ' . $file,
            ]);
            return 1;
        }

        if (!is_file($file)) {
            $this->renderMessage([
                'result'  => 'info',
                'message' => 'The file: "' . $file . '" was removed by another process!',
            ]);
            return 1;
        }

        try {
            $fileName  = basename($file);
            $extension = pathinfo($fileName, PATHINFO_EXTENSION);
            $listName  = substr(trim(basename($fileName, $extension), '.'), 0, 13); // maybe uid-1.csv uid-2.txt

            hooks()->doAction('console_command_suppression_list_import_before_process', new CAttributeCollection([
                'command'    => $this,
                'importType' => $extension,
                'listUid'    => $listName,
                'filePath'   => $file,
            ]));

            if ($extension === 'csv') {
                $this->processCsv([
                    'list_uid'    => $listName,
                    'file_path'   => $file,
                ]);
            }

            hooks()->doAction('console_command_suppression_list_import_after_process', new CAttributeCollection([
                'command'    => $this,
                'importType' => $extension,
                'listUid'    => $listName,
                'filePath'   => $file,
            ]));

            // @phpstan-ignore-next-line
            if (in_array($extension, ['csv']) && is_file($file)) {

                // remove the file
                unlink($file);

                // 1.4.4
                $list = CustomerSuppressionList::model()->findByAttributes([
                    'list_uid' => $listName,
                ]);

                // since 1.7.9
                if (!empty($list)) {
                    $list->touchLastUpdated();
                }

                if (!empty($list) && ($server = DeliveryServer::pickServer())) {
                    /** @var OptionUrl $optionUrl */
                    $optionUrl = container()->get(OptionUrl::class);
                    $listOverviewUrl = $optionUrl->getCustomerUrl('suppression-lists/' . $list->list_uid . '/emails/index');

                    $emailParams = CommonEmailTemplate::getAsParamsArrayBySlug(
                        'suppression-list-import-finished',
                        [
                            'to'      => [$list->customer->email => $list->customer->email],
                            'subject' => t('list_import', 'Suppression list import has finished!'),
                        ],
                        [
                            '[CUSTOMER_NAME]'   => $list->customer->getFullName(),
                            '[LIST_NAME]'       => $list->name,
                            '[OVERVIEW_URL]'    => $listOverviewUrl,
                        ]
                    );

                    $server->sendEmail($emailParams);
                }
                //
            }
        } catch (Exception $e) {
            $this->stdout(__LINE__ . ': ' . $e->getMessage());
            Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
        }

        mutex()->release($lockName);

        $this->renderMessage([
            'result'  => 'info',
            'message' => 'The file: "' . $file . '" was processed!',
        ]);

        return 0;
    }

    /**
     * @param array $params
     *
     * @return int
     * @throws Exception
     */
    protected function processCsv(array $params)
    {
        if (empty($params['list_uid'])) {
            $this->renderMessage([
                'result'  => 'error',
                'message' => t('list_import', 'Call this command with the --list_uid=XYZ param where XYZ is the 13 chars unique list id.'),
            ]);
            return 1;
        }

        $list = CustomerSuppressionList::model()->findByUid($params['list_uid']);
        if (empty($list)) {
            $this->renderMessage([
                'result'  => 'error',
                'message' => t('list_import', 'The list with the uid {uid} was not found in database.', [
                    '{uid}' => $params['list_uid'],
                ]),
            ]);
            return 1;
        }

        if (empty($params['file_path']) || !is_file($params['file_path'])) {
            $this->renderMessage([
                'result'  => 'error',
                'message' => t('list_import', 'Call this command with the --file_path=/some/file.csv param where /some/file.csv is the full path to the csv file to be imported.'),
            ]);
            return 1;
        }

        // helps for when the document has been created on a Macintosh computer
        if (!ini_get('auto_detect_line_endings')) {
            ini_set('auto_detect_line_endings', '1');
        }

        $csvReader = League\Csv\Reader::createFromPath($params['file_path'], 'r');
        $csvReader->setDelimiter(StringHelper::detectCsvDelimiter($params['file_path']));
        $csvReader->setHeaderOffset(0);

        $csvHeader = array_map('strtolower', array_map('trim', $csvReader->getHeader()));
        if (array_search('email', $csvHeader) === false) {
            $this->renderMessage([
                'result'  => 'error',
                'message' => t('list_import', 'Cannot find the "email" column in your file!'),
            ]);
            return 1;
        }

        /** @var OptionImporter $optionImporter */
        $optionImporter = container()->get(OptionImporter::class);

        $totalFileRecords = $csvReader->count();
        $importAtOnce     = $optionImporter->getImportAtOnce();
        $insert           = [];
        $mainCounter      = 0;
        $count            = 0;

        // reset
        $csvReader->setHeaderOffset(0);

        /** @var array $row */
        foreach ($csvReader->getRecords($csvHeader) as $row) {

            // clean the data
            $row = (array)ioFilter()->stripPurify($row);

            $mainCounter++;
            $percent = round(($mainCounter / $totalFileRecords) * 100);

            $this->renderMessage([
                'type'    => 'info',
                'message' => '[' . $percent . '%] - ' . t('list_import', 'Processing the email: "{email}"', [
                        '{email}' => $row['email'],
                    ]),
                'counter' => false,
            ]);

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
            CustomerSuppressionListEmail::insertMultipleUnique($insert);

            $count  = 0;
            $insert = [];
        }

        CustomerSuppressionListEmail::insertMultipleUnique($insert);

        $this->renderMessage([
            'result'  => 'success',
            'message' => t('list_import', 'The import process has finished!'),
            'finished'=> true,
        ]);
        return 0;
    }

    /**
     * @param array $data
     * @return void
     */
    protected function renderMessage($data = []): void
    {
        if (isset($data['type']) && in_array($data['type'], ['success', 'error'])) {
            $this->lastMessage = $data;
        }

        if (isset($data['message']) && $this->verbose) {
            $out = '[' . date('Y-m-d H:i:s') . '] - ';
            if (isset($data['type'])) {
                $out .= '[' . strtoupper((string)$data['type']) . '] - ';
            }
            $out .= strip_tags(str_replace(['<br />', '<br/>', '<br>'], PHP_EOL, $data['message'])) . PHP_EOL;
            echo $out;
        }
    }
}
