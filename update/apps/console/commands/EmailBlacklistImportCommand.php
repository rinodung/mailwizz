<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * EmailBlacklistImportCommand
 *
 * Handles the actions for email blacklist import related tasks.
 * Most of the logic is borrowed from the web interface importer.
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.5.2
 */

class EmailBlacklistImportCommand extends ConsoleCommand
{
    /**
     * @var string the folder path from where we should load files
     */
    public $folder_path = '';

    /**
     * @var int max amount of files to process from the folder
     */
    public $folder_process_files = 10;

    /**
     * @var int maximum number of records allowed per file.
     * Above this number, files will be split into smaller files
     */
    public $max_records_per_file_split = 10000;

    /**
     * @var string the path where the import file is located
     */
    public $file_path = '';

    /**
     * @var int is verbose
     */
    public $verbose = 0;

    /**
     * @var array for external access maybe?
     */
    public $lastMessage = [];

    /**
     * @return int
     * @throws CException
     */
    public function actionFolder()
    {
        if (empty($this->folder_path)) {
            $this->folder_path = (string)Yii::getPathOfAlias('common.runtime.email-blacklist-import-queue');
        }

        if ((!is_dir($this->folder_path) && mkdir($this->folder_path, 0777, true)) || !is_readable($this->folder_path)) {
            $this->renderMessage([
                'result'  => 'error',
                'message' => t('email_blacklist', 'Call this command with the --folder_path=XYZ param where XYZ is the full path to the folder you want to monitor.'),
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
            $usableFiles = [];
            foreach ($files as $file) {
                $lockName = sha1($file);
                if (mutex()->acquire($lockName)) {
                    $usableFiles[] = $file;
                    mutex()->release($lockName);
                }
            }
            $files = array_slice($usableFiles, 0, (int)$this->folder_process_files);
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
            sleep(5); // this allows the files to get a start ahead of each other
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
        hooks()->doAction('console_command_email_blacklist_import_before_process', new CAttributeCollection([
            'command'    => $this,
            'importType' => 'csv',
        ]));

        $result = $this->processCsv([
            'file_path' => $this->file_path,
        ]);

        hooks()->doAction('console_command_email_blacklist_import_after_process', new CAttributeCollection([
            'command'    => $this,
            'importType' => 'csv',
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
            mutex()->release($lockName);
            $this->renderMessage([
                'result'  => 'info',
                'message' => 'The file: "' . $file . '" was removed by another process!',
            ]);
            return 1;
        }

        try {
            $fileName  = $initialFilename = basename($file);
            $extension = pathinfo($fileName, PATHINFO_EXTENSION);

            hooks()->doAction('console_command_email_blacklist_import_before_process', new CAttributeCollection([
                'command'    => $this,
                'importType' => $extension,
                'filePath'   => $file,
            ]));

            $this->processCsv([
                'file_path' => $file,
            ]);

            hooks()->doAction('console_command_email_blacklist_import_after_process', new CAttributeCollection([
                'command'    => $this,
                'importType' => $extension,
                'filePath'   => $file,
            ]));

            // @phpstan-ignore-next-line
            if (in_array($extension, ['csv']) && is_file($file)) {
                $canSendEmail = false;
                $accessKey = sha1(__METHOD__ . ':access_key:' . $fileName);
                if (mutex()->acquire($accessKey, 30)) {
                    // remove the file
                    unlink($file);

                    // [FILENAME]-part-1.(csv) | [FILENAME]-part-2.(csv)
                    $initialFilename = (string)preg_replace('/(-part-\d+)(?!.*(-part-\d+))/', '', $fileName);
                    if (!glob(dirname($file) . '/' . basename($initialFilename, '.' . $extension) . '-part-*.' . $extension)) {
                        $canSendEmail = true;
                    }
                    mutex()->release($accessKey);
                }

                if (is_file($file)) {
                    unlink($file);
                }
                //

                if ($canSendEmail && ($server = DeliveryServer::pickServer())) {
                    /** @var OptionUrl $optionUrl */
                    $optionUrl = container()->get(OptionUrl::class);
                    $overviewUrl = $optionUrl->getBackendUrl('email-blacklist/index');
                    $users = User::model()->findAllByAttributes([
                        'removable' => User::TEXT_NO,
                    ]);
                    foreach ($users as $user) {
                        $emailParams  = CommonEmailTemplate::getAsParamsArrayBySlug(
                            'email-blacklist-import-finished',
                            [
                                'to'      => [$user->email => $user->email],
                                'subject' => t('email_blacklist', 'Email blacklist import has finished!'),
                            ],
                            [
                                '[USER_NAME]'    => $user->getFullName(),
                                '[FILE_NAME]'    => $initialFilename,
                                '[OVERVIEW_URL]' => $overviewUrl,
                            ]
                        );
                        $server->sendEmail($emailParams);
                    }
                }
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
     * @return int
     * @throws CException
     */
    protected function processCsv(array $params)
    {
        if (empty($params['file_path']) || !is_file($params['file_path'])) {
            $this->renderMessage([
                'result'  => 'error',
                'message' => t('email_blacklist', 'Call this command with the --file_path=/some/file.csv param where /some/file.csv is the full path to the csv file to be imported.'),
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
                'message' => t('email_blacklist', 'Your file does not contain the header with the fields title!'),
            ]);
            return 1;
        }

        $this->stdout('Counting all file lines...');
        $totalFileRecords = $csvReader->count();
        $this->stdout('Found ' . $totalFileRecords . ' total lines...');

        // reset
        $csvReader->setHeaderOffset(0);

        // set a default
        if ((int)$this->max_records_per_file_split < 10000) {
            $this->max_records_per_file_split = 10000;
        }

        // this is max number of rows per file
        $maxRecordsPerFile = (int)$this->max_records_per_file_split;

        // we need to split in multiple smaller files
        if ($totalFileRecords > $maxRecordsPerFile) {
            $this->stdout('The file is too large, we are splitting it into multiple smaller files');

            $totalRecords = 0;
            $fileCounter  = 0;
            $lockName     = '';
            $lockNames    = [];
            $smallerFiles = [];

            /** @var League\Csv\Writer $csvWriter */
            $csvWriter = null;

            try {
                $batchInsert      = [];
                $batchInsertCount = 0;
                foreach ($csvReader->getRecords($csvHeader) as $row) {

                    // create the new file
                    if ($totalRecords === 0 || $totalRecords % $maxRecordsPerFile === 0) {
                        $smallerFile = $this->folder_path . '/' . basename($params['file_path'], '.csv') . '-part-' . $fileCounter . '.csv';

                        if (!empty($lockName)) {
                            mutex()->release($lockName);
                            if (isset($lockNames[$lockName])) {
                                unset($lockNames[$lockName]);
                            }
                        }

                        $lockName = sha1($smallerFile);
                        if (!mutex()->acquire($lockName, 5)) {
                            throw new Exception('Unable to acquire lock for smaller file!');
                        }
                        $lockNames[$lockName] = $lockName;

                        $fileCounter++;

                        /** @var League\Csv\Writer $csvWriter */
                        $csvWriter = League\Csv\Writer::createFromPath($smallerFile, 'w');
                        $csvWriter->insertOne($csvHeader);

                        $smallerFiles[] = $smallerFile;
                        $this->stdout('Adding into file: ' . $smallerFile);
                    }

                    $batchInsert[] = $row;
                    $batchInsertCount++;
                    $totalRecords++;

                    if ($batchInsertCount < 1000) {
                        continue;
                    }

                    $csvWriter->insertAll($batchInsert);
                    $batchInsert      = [];
                    $batchInsertCount = 0;
                }

                // anything left from above
                if (!empty($batchInsert)) {
                    $csvWriter->insertAll($batchInsert);
                    unset($batchInsert, $batchInsertCount);
                }
            } catch (Exception $e) {
                foreach ($smallerFiles as $smallerFile) {
                    if (is_file($smallerFile)) {
                        unlink($smallerFile);
                    }
                }
            }

            // this prevents email sending
            unlink($params['file_path']);

            // release any lock
            $lockNames = array_values($lockNames);
            foreach ($lockNames as $lockName) {
                mutex()->release($lockName);
            }

            $this->renderMessage([
                'result'  => 'error',
                'message' => t('email_blacklist', 'The file is too large, and it has been splitted into multiple smaller files!'),
            ]);
            return 1;
        }

        /** @var OptionImporter $optionImporter */
        $optionImporter = container()->get(OptionImporter::class);

        $importAtOnce = $optionImporter->getImportAtOnce();
        $insert       = [];
        $mainCounter  = 0;
        $count        = 0;

        /** @var array $row */
        foreach ($csvReader->getRecords($csvHeader) as $row) {

            /** @var array $row */
            $row = (array)ioFilter()->stripPurify($row);

            $mainCounter++;
            $percent = round(($mainCounter / $totalFileRecords) * 100);
            $this->stdout(sprintf('[%s] Processing the email: %s', $percent . '%', $row['email']));

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

            EmailBlacklist::insertMultipleUnique($insert);

            $count  = 0;
            $insert = [];
        }

        EmailBlacklist::insertMultipleUnique($insert);

        $this->renderMessage([
            'result'  => 'success',
            'message' => t('email_blacklist', 'The import process has finished!'),
            'finished'=> true,
        ]);
        return 0;
    }

    /**
     * @param array $data
     *
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
