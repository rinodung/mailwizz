<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * ListImportCommand
 *
 * Handles the actions for list import related tasks.
 * Most of the logic is borrowed from the web interface importer.
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.5.9
 */

class ListImportCommand extends ConsoleCommand
{
    /**
     * The folder path from where we should load files
     *
     * @var string
     */
    public $folder_path = '';

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
    public $list_uid = '';

    /**
     * The path where the import file is located
     *
     * @var string
     */
    public $file_path = '';

    /**
     * @var int maximum number of records allowed per file.
     * Above this number, files will be split into smaller files
     */
    public $max_records_per_file_split = 10000;

    /**
     * Is verbose
     *
     * @var int
     */
    public $verbose = 0;

    /**
     * For external access maybe?
     *
     * @var array
     */
    public $lastMessage = [];

    /**
     * Collect error messages to send them in the email overview
     *
     * @var array
     */
    public $errorMessages = [];

    /**
     * @return int
     * @throws CException
     */
    public function actionFolder()
    {
        if (empty($this->folder_path)) {
            $this->folder_path = (string)Yii::getPathOfAlias('common.runtime.list-import-queue');
        }

        if ((!is_dir($this->folder_path) && mkdir($this->folder_path, 0777, true)) || !is_readable($this->folder_path)) {
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
            $files = array_slice($files, 0, (int)$this->folder_process_files);
        }

        $this->renderMessage([
            'result'  => 'info',
            'message' => 'Found ' . count($files) . ' files (some of them might be already processing)',
        ]);

        foreach ($files as $file) {
            for ($i = 0; $i < 5; $i++) {
                $this->stdout('.', false, '');
                sleep(1);
            }

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
     * @return int|void
     * @throws CException
     * @throws League\Csv\Exception
     */
    public function actionCsv()
    {
        hooks()->doAction('console_command_list_import_before_process', new CAttributeCollection([
            'command'    => $this,
            'importType' => 'csv',
            'listUid'    => $this->list_uid,
            'filePath'   => $this->file_path,
        ]));

        $result = $this->processCsv([
            'list_uid'    => $this->list_uid,
            'file_path'   => $this->file_path,
        ]);

        hooks()->doAction('console_command_list_import_after_process', new CAttributeCollection([
            'command'    => $this,
            'importType' => 'csv',
            'listUid'    => $this->list_uid,
            'filePath'   => $this->file_path,
        ]));

        return $result;
    }

    /**
     * @return int
     */
    public function actionUrl()
    {
        $this->stdout('Starting to fetch data from remote urls...');

        $mutexKey = sha1(__METHOD__);
        if (!mutex()->acquire($mutexKey)) {
            $this->stdout('Seems this process is already running... aborting for now!');
            return 1;
        }

        try {
            $storagePath = (string)Yii::getPathOfAlias('common.runtime.list-import-url');
            if (!file_exists($storagePath) || !is_dir($storagePath)) {
                if (!mkdir($storagePath)) {
                    throw new Exception('Unable to create: ' . $storagePath);
                }
            }

            $storagePath = (string)Yii::getPathOfAlias('common.runtime.list-import-queue');
            if (!file_exists($storagePath) || !is_dir($storagePath)) {
                if (!mkdir($storagePath)) {
                    throw new Exception('Unable to create: ' . $storagePath);
                }
            }

            $models = ListUrlImport::model()->findAllByAttributes([
                'status' => ListUrlImport::STATUS_ACTIVE,
            ]);

            foreach ($models as $model) {
                $this->stdout('Processing the url: ' . $model->url);

                if (!in_array($model->getExtension(), ['.csv'])) {
                    $model->failures = PHP_INT_MAX; // force inactive
                    $model->save(false);
                    $this->stdout('The url processing failed!');
                    if (is_file($model->getDownloadPath())) {
                        unlink($model->getDownloadPath());
                    }
                    continue;
                }

                if (!$model->getIsUrlValid() || !$model->download()) {
                    $model->failures++;
                    $model->save(false);
                    $this->stdout('The url processing failed!');
                    if (is_file($model->getDownloadPath())) {
                        unlink($model->getDownloadPath());
                    }
                    continue;
                }

                if (!is_file($model->getDownloadPath())) {
                    $this->stdout('The url processing failed!');
                    continue;
                }

                $fileSize = filesize($model->getDownloadPath());
                if ((int)$fileSize == 0) {
                    unlink($model->getDownloadPath());
                    $this->stdout('The contents of the url returned 0 bytes!');
                    continue;
                }

                $fileNumber      = 1;
                $basePath        = (string)Yii::getPathOfAlias('common.runtime.list-import-queue') . '/' . $model->list->list_uid . '-';
                $destinationPath = $basePath . $fileNumber . $model->getExtension();
                while (is_file($destinationPath)) {
                    $fileNumber++;
                    $destinationPath = $basePath . $fileNumber . $model->getExtension();
                }

                $this->stdout('Copy ' . $model->getDownloadPath() . ' to ' . $destinationPath);
                copy($model->getDownloadPath(), $destinationPath);

                $this->stdout('Deleting ' . $model->getDownloadPath());
                unlink($model->getDownloadPath());

                $this->stdout('Done processing ' . $model->url);
            }

            $this->stdout('Done fetching data from remote urls...');
        } catch (Exception $e) {
            $this->stdout(__LINE__ . ': ' . $e->getMessage());
            Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
        }

        mutex()->release($mutexKey);
        return 0;
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
            $fileName  = basename($file);
            $extension = pathinfo($fileName, PATHINFO_EXTENSION);
            $listName  = substr(trim(basename($fileName, $extension), '.'), 0, 13); // maybe uid-1.csv uid-2.txt

            hooks()->doAction('console_command_list_import_before_process', new CAttributeCollection([
                'command'    => $this,
                'importType' => $extension,
                'listUid'    => $listName,
                'filePath'   => $file,
            ]));

            if ($extension == 'csv') {
                $this->processCsv([
                    'list_uid'    => $listName,
                    'file_path'   => $file,
                ]);
            }

            hooks()->doAction('console_command_list_import_after_process', new CAttributeCollection([
                'command'    => $this,
                'importType' => $extension,
                'listUid'    => $listName,
                'filePath'   => $file,
            ]));

            // @phpstan-ignore-next-line
            if (in_array($extension, ['csv', 'txt']) && is_file($file)) {

                // 1.4.4
                $list = Lists::model()->findByAttributes([
                    'list_uid' => $listName,
                ]);

                // 1.7.6
                $canSendEmail = false;
                if (!empty($list)) {
                    $accessKey = sha1(__METHOD__ . ':access_key:' . $list->list_uid);
                    if (mutex()->acquire($accessKey, 30)) {

                        // remove the file
                        unlink($file);

                        // [LIST_UID]-1.(csv) | [LIST_UID]-part-1.(csv)
                        if (!glob(dirname($file) . '/' . $list->list_uid . '-*.' . $extension)) {
                            $canSendEmail = true;
                        }

                        mutex()->release($accessKey);
                    }
                }
                //

                // 1.7.7 - remove the file
                if (is_file($file)) {
                    unlink($file);
                }

                if ($canSendEmail && ($server = DeliveryServer::pickServer())) {
                    /** @var OptionUrl $optionUrl */
                    $optionUrl = container()->get(OptionUrl::class);
                    $listOverviewUrl = $optionUrl->getCustomerUrl('lists/' . $list->list_uid . '/overview');

                    if (empty($this->errorMessages)) {
                        $errorsSummary = t('list_import', 'List import finished without errors.');
                    } else {
                        $errors = array_slice($this->errorMessages, 0, 10);
                        $errors = CMap::mergeArray($errors, ['[...]']);
                        $errors = CMap::mergeArray($errors, array_slice($this->errorMessages, -10, 10));
                        $errorsSummary = t('list_import', 'List import finished with errors, here is a small excerpt: {errors}', [
                            '{errors}' => sprintf('<br /> %s', implode('<br />', $errors)),
                        ]);
                    }

                    $emailParams = CommonEmailTemplate::getAsParamsArrayBySlug(
                        'list-import-finished',
                        [
                            'to'      => [$list->customer->email => $list->customer->email],
                            'subject' => t('list_import', 'List import has finished!'),
                        ],
                        [
                            '[CUSTOMER_NAME]'   => $list->customer->getFullName(),
                            '[LIST_NAME]'       => $list->name,
                            '[OVERVIEW_URL]'    => $listOverviewUrl,
                            '[ERRORS_SUMMARY]'  => $errorsSummary,
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
     * @return int|void
     * @throws CException
     * @throws League\Csv\Exception
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

        $list = Lists::model()->findByAttributes([
            'list_uid'  => $params['list_uid'],
            'status'    => Lists::STATUS_ACTIVE,
        ]);
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

        /** @var OptionImporter $optionImporter */
        $optionImporter = container()->get(OptionImporter::class);

        /** @var int $importAtOnce */
        $importAtOnce = $optionImporter->getImportAtOnce();

        // helps for when the document has been created on a Macintosh computer
        if (!ini_get('auto_detect_line_endings')) {
            ini_set('auto_detect_line_endings', '1');
        }

        $csvReader = League\Csv\Reader::createFromPath($params['file_path'], 'r');
        $csvReader->setDelimiter(StringHelper::detectCsvDelimiter($params['file_path']));
        $totalFileRecords = $csvReader->count() - 1;
        $csvReader->setHeaderOffset(0);

        /** @var array $columns */
        $columns = (array)ioFilter()->stripPurify((array)$csvReader->getHeader());
        $columns = array_map('strtolower', array_map('trim', $columns));

        // set a default
        if ((int)$this->max_records_per_file_split < 10000) {
            $this->max_records_per_file_split = 10000;
        }

        // this is max number of rows per file
        $maxRecordsPerFile = (int)$this->max_records_per_file_split;

        // we need to split in multiple smaller files
        if ($totalFileRecords > $maxRecordsPerFile) {
            $this->stdout('The file is too large, we are splitting it into multiple smaller files');

            $fileCounter  = 0;
            $lockName     = '';
            $lockNames    = [];
            $smallerFiles = [];
            $totalRecords = 0;

            /** @var League\Csv\Writer $csvWriter */
            $csvWriter = null;

            try {
                /** @var array $row */
                foreach ($csvReader->getRecords($columns) as $row) {
                    if ($totalRecords == 0 || $totalRecords % $maxRecordsPerFile === 0) {
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
                        $csvWriter->insertOne($columns);

                        $smallerFiles[] = $smallerFile;
                        $this->stdout('Adding into file: ' . $smallerFile);
                    }

                    $csvWriter->insertOne((array)$row);

                    $totalRecords++;
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
                'message' => t('list_import', 'The file is too large, and it has been splitted into multiple smaller files!'),
            ]);
            return 1;
        }

        $customer              = $list->customer;
        $totalSubscribersCount = 0;
        $listSubscribersCount  = 0;
        $maxSubscribersPerList = (int)$customer->getGroupOption('lists.max_subscribers_per_list', -1);
        $maxSubscribers        = (int)$customer->getGroupOption('lists.max_subscribers', -1);

        if ($maxSubscribers > -1 || $maxSubscribersPerList > -1) {
            $criteria = new CDbCriteria();
            $criteria->select = 'COUNT(DISTINCT(t.email)) as counter';

            if ($maxSubscribers > -1 && ($listsIds = $customer->getAllListsIds())) {
                $criteria->addInCondition('t.list_id', $listsIds);
                $totalSubscribersCount = ListSubscriber::model()->count($criteria);
                if ($totalSubscribersCount >= $maxSubscribers) {
                    $this->renderMessage([
                        'result'  => 'error',
                        'message' => t('list_import', 'You have reached the maximum number of allowed subscribers.'),
                    ]);
                    return 1;
                }
            }

            if ($maxSubscribersPerList > -1) {
                $criteria->compare('t.list_id', (int)$list->list_id);
                $listSubscribersCount = ListSubscriber::model()->count($criteria);
                if ($listSubscribersCount >= $maxSubscribersPerList) {
                    $this->renderMessage([
                        'result'  => 'error',
                        'message' => t('list_import', 'You have reached the maximum number of allowed subscribers into this list.'),
                    ]);
                    return 1;
                }
            }
        }

        $criteria = new CDbCriteria();
        $criteria->select = 'field_id, label, tag';
        $criteria->compare('list_id', $list->list_id);
        $criteria->order = 'sort_order ASC';
        $fields = ListField::model()->findAll($criteria);

        $searchReplaceTags = [
            'E_MAIL'        => 'EMAIL',
            'EMAIL_ADDRESS' => 'EMAIL',
            'EMAILADDRESS'  => 'EMAIL',
        ];
        foreach ($fields as $field) {
            if ($field->tag == 'FNAME') {
                $searchReplaceTags['F_NAME']     = 'FNAME';
                $searchReplaceTags['FIRST_NAME'] = 'FNAME';
                $searchReplaceTags['FIRSTNAME']  = 'FNAME';
                continue;
            }
            if ($field->tag == 'LNAME') {
                $searchReplaceTags['L_NAME']    = 'LNAME';
                $searchReplaceTags['LAST_NAME'] = 'LNAME';
                $searchReplaceTags['LASTNAME']  = 'LNAME';
                continue;
            }
        }

        $foundTags = [];
        foreach ($columns as $value) {
            $tagName     = StringHelper::getTagFromString($value);
            $tagName     = (string)str_replace(array_keys($searchReplaceTags), array_values($searchReplaceTags), $tagName);
            $foundTags[] = $tagName;
        }

        // empty tags, not allowed
        if (count($foundTags) !== count(array_filter($foundTags))) {
            $this->renderMessage([
                'result'  => 'error',
                'message' => t('list_import', 'Empty column names are not allowed!'),
            ]);
            return 1;
        }

        $foundEmailTag = false;
        foreach ($foundTags as $tagName) {
            if ($tagName === 'EMAIL') {
                $foundEmailTag = true;
                break;
            }
        }

        if (!$foundEmailTag) {
            $this->renderMessage([
                'result'  => 'error',
                'message' => t('list_import', 'Cannot find the "email" column in your file!'),
            ]);
            return 1;
        }

        $foundReservedColumns = [];
        foreach ($columns as $columnName) {
            $columnName     = StringHelper::getTagFromString($columnName);
            $columnName     = (string)str_replace(array_keys($searchReplaceTags), array_values($searchReplaceTags), $columnName);
            $tagIsReserved  = TagRegistry::model()->findByAttributes(['tag' => '[' . $columnName . ']']);
            if (!empty($tagIsReserved)) {
                $foundReservedColumns[] = $columnName;
            }
        }

        if (!empty($foundReservedColumns)) {
            $this->renderMessage([
                'result'  => 'error',
                'message' => t('list_import', 'Your list contains the columns: "{columns}" which are system reserved. Please update your file and change the column names!', [
                    '{columns}' => implode(', ', $foundReservedColumns),
                ]),
            ]);
            return 1;
        }

        // since 1.9.10
        $list->setSubscribersCountCacheEnabled(false)
            ->flushSubscribersCountCacheOnEndRequest();

        $rounds      = $totalFileRecords > $importAtOnce ? ceil($totalFileRecords / $importAtOnce) : 1;
        $mainCounter = 0;

        for ($rCount = 1; $rCount <= $rounds; $rCount++) {
            if ($rCount == 1) {
                $this->renderMessage([
                    'message' => t('list_import', 'Found the following column names: {columns}', [
                        '{columns}' => implode(', ', $columns),
                    ]),
                ]);
            }

            $offset = (int)($importAtOnce * ($rCount - 1));
            if ($offset >= $totalFileRecords) {
                $this->renderMessage([
                    'result'  => 'success',
                    'message' => t('list_import', 'The import process has finished!'),
                    'finished'=> true,
                ]);
                return 0;
            }

            // back to 0 otherwise League\Csv\Statement::offset will jump rows
            $csvReader->setHeaderOffset(0);

            $statement = (new League\Csv\Statement())->offset($offset)->limit($importAtOnce);
            $records   = $statement->process($csvReader, $columns);

            /** @var array $csvData */
            $csvData = array_map([ioFilter(), 'stripPurify'], iterator_to_array($records->getRecords()));

            $fieldType = ListFieldType::model()->findByAttributes([
                'identifier' => 'text',
            ]);

            $data = [];
            foreach ($csvData as $row) {
                $rowData = [];
                foreach ($row as $name => $value) {
                    $tagName = StringHelper::getTagFromString((string)$name);
                    $tagName = (string)str_replace(array_keys($searchReplaceTags), array_values($searchReplaceTags), $tagName);

                    $rowData[] = [
                        'name'      => ucwords((string)str_replace('_', ' ', (string)$name)),
                        'tagName'   => trim($tagName),
                        'tagValue'  => trim((string)$value),
                    ];
                }
                $data[] = $rowData;
            }

            if (count($data) === 0) {
                if ($rCount == 1) {
                    $this->renderMessage([
                        'result'  => 'error',
                        'message' => t('list_import', 'Your file does not contain enough data to be imported!'),
                    ]);
                    return 1;
                }
                $this->renderMessage([
                        'result'  => 'success',
                        'message' => t('list_import', 'The import process has finished!'),
                        'finished'=> true,
                    ]);
                return 0;
            }

            $tagToModel = [];
            foreach ($data[0] as $sample) {
                if ($rCount == 1) {
                    $this->renderMessage([
                        'type'    => 'info',
                        'message' => t('list_import', 'Checking to see if the tag "{tag}" is defined in your list fields...', [
                            '{tag}' => html_encode($sample['tagName']),
                        ]),
                        'counter' => false,
                    ]);
                }

                $model = ListField::model()->findByAttributes([
                    'list_id' => $list->list_id,
                    'tag'     => $sample['tagName'],
                ]);

                if (!empty($model)) {
                    if ($rCount == 1) {
                        $this->renderMessage([
                            'type'    => 'info',
                            'message' => t('list_import', 'The tag "{tag}" is already defined in your list fields.', [
                                '{tag}' => html_encode($sample['tagName']),
                            ]),
                            'counter' => false,
                        ]);
                    }

                    $tagToModel[$sample['tagName']] = $model;
                    continue;
                }

                if ($rCount == 1) {
                    $this->renderMessage([
                        'type'    => 'info',
                        'message' => t('list_import', 'The tag "{tag}" is not defined in your list fields, we will try to create it.', [
                            '{tag}' => html_encode($sample['tagName']),
                        ]),
                        'counter' => false,
                    ]);
                }

                $model = new ListField();
                $model->type_id     = (int)$fieldType->type_id;
                $model->list_id     = (int)$list->list_id;
                $model->label       = $sample['name'];
                $model->tag         = $sample['tagName'];
                $model->visibility  = $list->customer->getGroupOption('lists.custom_fields_default_visibility', ListField::VISIBILITY_VISIBLE);

                if ($model->save()) {
                    if ($rCount == 1) {
                        $this->renderMessage([
                            'type'    => 'success',
                            'message' => t('list_import', 'The tag "{tag}" has been successfully created.', [
                                '{tag}' => html_encode($sample['tagName']),
                            ]),
                            'counter' => false,
                        ]);
                    }

                    $tagToModel[$sample['tagName']] = $model;
                } else {
                    if ($rCount == 1) {
                        $this->renderMessage([
                            'type'    => 'error',
                            'message' => t('list_import', 'The tag "{tag}" cannot be saved, reason: {reason}', [
                                '{tag}'    => html_encode($sample['tagName']),
                                '{reason}' => '<br />' . $model->shortErrors->getAllAsString(),
                            ]),
                            'counter' => false,
                        ]);
                    }
                }
            }

            // since 1.3.5.9
            $bulkEmails = [];
            foreach ($data as $fields) {
                foreach ($fields as $detail) {
                    if ($detail['tagName'] == 'EMAIL' && !empty($detail['tagValue'])) {
                        $email = $detail['tagValue'];
                        if (!EmailBlacklist::getFromStore($email)) {
                            $bulkEmails[$email] = false;
                        }
                        break;
                    }
                }
            }
            /** @var array $failures */
            $failures = (array)hooks()->applyFilters('list_import_data_bulk_check_failures', [], (array)$bulkEmails);
            foreach ($failures as $email => $message) {
                EmailBlacklist::addToBlacklist($email, $message);
            }
            // end 1.3.5.9

            $finished    = false;
            $importCount = 0;
            $importLog   = [];

            // since 1.3.5.9
            hooks()->doAction('list_import_before_processing_data', $collection = new CAttributeCollection([
                'data'        => $data,
                'list'        => $list,
                'importLog'   => $importLog,
                'finished'    => $finished,
                'importCount' => $importCount,
                'failures'    => $failures,
                'importType'  => 'csv',
            ]));

            $data        = (array)$collection->itemAt('data');
            $importCount = (int)$collection->itemAt('importCount');
            $finished    = (bool)$collection->itemAt('finished');
            $failures    = (array)$collection->itemAt('failures');
            //

            /** @var array $fields */
            foreach ($data as $fields) {
                $email = '';
                foreach ($fields as $detail) {
                    if ($detail['tagName'] == 'EMAIL' && !empty($detail['tagValue'])) {
                        $email = (string)$detail['tagValue'];
                        break;
                    }
                }

                if (empty($email)) {
                    continue;
                }

                // Since 1.9.19 - Insert the IP from the imported source into the list_subscriber table
                $ip = '';
                foreach ($fields as $detail) {
                    if ($detail['tagName'] == 'IP_ADDRESS' && !empty($detail['tagValue']) && FilterVarHelper::ip($detail['tagValue'])) {
                        $ip = $detail['tagValue'];
                        break;
                    }
                }

                $mainCounter++;
                $percent = round(($mainCounter / $totalFileRecords) * 100);

                $this->renderMessage([
                    'type'    => 'info',
                    'message' => '[' . $percent . '%] - ' . t('list_import', 'Checking the list for the email: "{email}"', [
                            '{email}' => html_encode($email),
                        ]),
                    'counter' => false,
                ]);

                if (!empty($failures[$email])) {
                    $this->renderMessage([
                        'type'    => 'error',
                        'message' => '[' . $percent . '%] - ' . t('list_import', 'Failed to save the email "{email}", reason: {reason}', [
                                '{email}'  => html_encode($email),
                                '{reason}' => '<br />' . $failures[$email],
                            ]),
                        'counter' => true,
                    ]);
                    continue;
                }

                $subscriber = ListSubscriber::model()->findByAttributes([
                    'list_id' => $list->list_id,
                    'email'   => $email,
                ]);

                try {

                    // since 1.9.26
                    $canUpdateGeoLocation = false;

                    if (empty($subscriber)) {
                        $this->renderMessage([
                            'type'    => 'info',
                            'message' => '[' . $percent . '%] - ' . t('list_import', 'The email "{email}" was not found, we will try to create it...', [
                                    '{email}' => html_encode($email),
                                ]),
                            'counter' => false,
                        ]);

                        $subscriber = new ListSubscriber();
                        $subscriber->list_id    = (int)$list->list_id;
                        $subscriber->email      = $email;
                        $subscriber->source     = ListSubscriber::SOURCE_IMPORT;
                        $subscriber->status     = ListSubscriber::STATUS_CONFIRMED;
                        $subscriber->ip_address = $ip;

                        $validator = new CEmailValidator();
                        $validator->allowEmpty  = false;
                        $validator->validateIDN = true;

                        $validEmail = $validator->validateValue($email);

                        if (!$validEmail) {
                            $subscriber->addError('email', t('list_import', 'Invalid email address!'));
                        } else {
                            $blacklisted = $subscriber->getIsBlacklisted(['checkZone' => EmailBlacklist::CHECK_ZONE_LIST_IMPORT]);
                            if (!empty($blacklisted)) {
                                $subscriber->addError('email', t('list_import', 'This email address is blacklisted!'));
                            }
                        }

                        if (!$validEmail || $subscriber->hasErrors() || !$subscriber->save()) {
                            $this->renderMessage([
                                'type'    => 'error',
                                'message' => '[' . $percent . '%] - ' . t('list_import', 'Failed to save the email "{email}", reason: {reason}', [
                                        '{email}'  => html_encode($email),
                                        '{reason}' => '<br />' . $subscriber->shortErrors->getAllAsString(),
                                    ]),
                                'counter' => true,
                            ]);
                            continue;
                        }

                        // since 1.9.26
                        $canUpdateGeoLocation = true;

                        $listSubscribersCount++;
                        $totalSubscribersCount++;

                        if ($maxSubscribersPerList > -1 && $listSubscribersCount >= $maxSubscribersPerList) {
                            $finished = t('lists', 'You have reached the maximum number of allowed subscribers into this list.');
                            break;
                        }

                        if ($maxSubscribers > -1 && $totalSubscribersCount >= $maxSubscribers) {
                            $finished = t('lists', 'You have reached the maximum number of allowed subscribers.');
                            break;
                        }

                        // 1.5.2
                        $subscriber->takeListSubscriberAction(ListSubscriberAction::ACTION_SUBSCRIBE);

                        $this->renderMessage([
                            'type'    => 'success',
                            'message' => '[' . $percent . '%] - ' . t('list_import', 'The email "{email}" has been successfully saved.', [
                                    '{email}' => html_encode($email),
                                ]),
                            'counter' => true,
                        ]);
                    } else {
                        $this->renderMessage([
                            'type'    => 'info',
                            'message' => '[' . $percent . '%] - ' . t('list_import', 'The email "{email}" has been found, we will update it.', [
                                    '{email}' => html_encode($email),
                                ]),
                            'counter' => true,
                        ]);
                    }

                    /** @var array $detail */
                    foreach ($fields as $detail) {
                        if (!isset($tagToModel[$detail['tagName']])) {
                            continue;
                        }
                        $fieldModel = $tagToModel[$detail['tagName']];
                        $valueModel = ListFieldValue::model()->findByAttributes([
                            'field_id'      => $fieldModel->field_id,
                            'subscriber_id' => $subscriber->subscriber_id,
                        ]);
                        if (empty($valueModel)) {
                            $valueModel = new ListFieldValue();
                            $valueModel->field_id      = (int)$fieldModel->field_id;
                            $valueModel->subscriber_id = (int)$subscriber->subscriber_id;
                        }
                        $valueModel->value = (string)$detail['tagValue'];
                        $valueModel->save();
                    }

                    // since 1.9.26
                    if ($canUpdateGeoLocation) {
                        $subscriber->updateGeoLocationFields();
                    }

                    // since 2.1.4
                    $subscriber->handleFieldsDefaultValues();

                    ++$importCount;

                    if ($finished) {
                        break;
                    }
                } catch (Exception $e) {
                    Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
                }
            }

            if ($finished) {
                $this->renderMessage([
                    'result'  => 'error',
                    'message' => $finished,
                ]);
                return 0;
            }
        }

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

        if (
            (isset($data['type']) && in_array($data['type'], ['error'])) ||
            (isset($data['result']) && in_array($data['result'], ['error']))
        ) {
            $out = '';
            if (isset($data['type'])) {
                $out .= '[' . strtoupper((string)$data['type']) . '] - ';
            }
            $out .= $data['message'];
            $this->errorMessages[] = $out;
        }

        if (isset($data['message']) && $this->verbose) {
            $out = '[' . date('Y-m-d H:i:s') . '] - ';
            if (isset($data['type'])) {
                $out .= '[' . strtoupper((string)$data['type']) . '] - ';
            }
            $out .= strip_tags((string)str_replace(['<br />', '<br/>', '<br>'], PHP_EOL, $data['message'])) . PHP_EOL;
            echo $out;
        }
    }
}
