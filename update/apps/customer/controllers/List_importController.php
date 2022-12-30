<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * List_importController
 *
 * Handles the actions for list import related tasks
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

class List_importController extends Controller
{
    /**
     * @return void
     * @throws CException
     */
    public function init()
    {
        parent::init();

        /** @var OptionImporter $optionImporter */
        $optionImporter = container()->get(OptionImporter::class);

        if (!$optionImporter->getIsEnabled()) {
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

        $this->addPageScript(['src' => AssetsUrl::js('list-import.js')]);
    }

    /**
     * @return array
     * @throws CException
     */
    public function filters()
    {
        return CMap::mergeArray(parent::filters(), [
            'postOnly + csv, database, url',
        ]);
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

        $importCsv  = new ListCsvImport('upload');
        $importDb   = new ListDatabaseImport();

        $importUrl = ListUrlImport::model()->findByAttributes([
            'list_id' => $list->list_id,
        ]);
        if (empty($importUrl)) {
            $importUrl = new ListUrlImport();
        }

        /** @var OptionImporter $importOptions */
        $importOptions = container()->get(OptionImporter::class);
        $cliEnabled = $importOptions->getIsCliEnabled();
        $webEnabled = $importOptions->getIsWebEnabled();
        $urlEnabled = $importOptions->getIsUrlEnabled();

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('list_import', 'Import subscribers into your list'),
            'pageHeading'     => t('list_import', 'Import subscribers'),
            'pageBreadcrumbs' => [
                t('lists', 'Lists') => createUrl('lists/index'),
                $list->name . ' ' => createUrl('lists/overview', ['list_uid' => $list->list_uid]),
                t('list_import', 'Import subscribers'),
            ],
        ]);

        $maxUploadSize = $importOptions->getFileSizeLimit() / 1024 / 1024;
        $this->render('list', compact('list', 'importCsv', 'importDb', 'importUrl', 'maxUploadSize', 'cliEnabled', 'webEnabled', 'urlEnabled'));
    }

    /**
     * Handle the CSV import and place it in queue
     *
     * @param string $list_uid
     *
     * @return void
     * @throws CHttpException
     */
    public function actionCsv_queue($list_uid)
    {
        $this->handleQueuePlacement($list_uid);
    }

    /**
     * @param string $list_uid
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionCsv($list_uid)
    {
        /** @var Lists $list */
        $list = $this->loadListModel((string)$list_uid);

        $importLog = [];
        $filePath  = (string)Yii::getPathOfAlias('common.runtime.list-import') . '/';

        /** @var OptionImporter $importOptions */
        $importOptions = container()->get(OptionImporter::class);

        $importAtOnce = $importOptions->getImportAtOnce();
        $pause        = $importOptions->getPause();

        // helps for when the document has been created on a Macintosh computer
        if (!ini_get('auto_detect_line_endings')) {
            ini_set('auto_detect_line_endings', '1');
        }

        $import = new ListCsvImport('upload');
        $import->file_size_limit = $importOptions->getFileSizeLimit();
        $import->attributes      = (array)request()->getPost($import->getModelName(), []);
        $import->file            = CUploadedFile::getInstance($import, 'file');

        if (!empty($import->file)) {
            if (!$import->upload()) {
                notify()->addError(t('app', 'Your form has a few errors, please fix them and try again!'));
                notify()->addError($import->shortErrors->getAllAsString());
                $this->redirect(['list_import/index', 'list_uid' => $list->list_uid]);
            }

            $this->setData([
                'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('list_import', 'Import subscribers'),
                'pageHeading'     => t('list_import', 'Import subscribers'),
                'pageBreadcrumbs' => [
                    t('lists', 'Lists') => createUrl('lists/index'),
                    $list->name . ' '   => createUrl('lists/overview', ['list_uid' => $list->list_uid]),
                    t('list_import', 'CSV Import'),
                ],
            ]);

            $this->render('csv', compact('list', 'import', 'importAtOnce', 'pause'));
            return;
        }

        // only ajax from now on.
        if (!request()->getIsAjaxRequest()) {
            $this->redirect(['list_import/index', 'list_uid' => $list->list_uid]);
        }

        try {
            if (!is_file($filePath . $import->file_name)) {
                $this->renderJson([
                    'result'  => 'error',
                    'message' => t('list_import', 'The import file does not exist anymore!'),
                ]);
                return;
            }

            $csvReader = League\Csv\Reader::createFromPath($filePath . $import->file_name, 'r');
            $csvReader->setDelimiter(StringHelper::detectCsvDelimiter($filePath . $import->file_name));
            $csvReader->setHeaderOffset(0);

            /** @var array<string> $csvHeader */
            $csvHeader = array_map('strtolower', array_map('trim', $csvReader->getHeader()));

            /** @var array<string> $csvHeader */
            $csvHeader = (array)ioFilter()->stripPurify($csvHeader);

            if ($import->is_first_batch) {
                $totalFileRecords   = $csvReader->count();
                $import->rows_count = $totalFileRecords;
                $csvReader->setHeaderOffset(1);
            } else {
                $totalFileRecords = $import->rows_count;
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
                        $this->renderJson([
                            'result'  => 'error',
                            'message' => t('lists', 'You have reached the maximum number of allowed subscribers.'),
                        ]);
                        return;
                    }
                }

                if ($maxSubscribersPerList > -1) {
                    $criteria->compare('t.list_id', (int)$list->list_id);
                    $listSubscribersCount = ListSubscriber::model()->count($criteria);
                    if ($listSubscribersCount >= $maxSubscribersPerList) {
                        $this->renderJson([
                            'result'  => 'error',
                            'message' => t('lists', 'You have reached the maximum number of allowed subscribers into this list.'),
                        ]);
                        return;
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
            /** @var string $value */
            foreach ($csvHeader as $value) {
                $tagName     = StringHelper::getTagFromString((string)$value);
                $tagName     = (string)str_replace(array_keys($searchReplaceTags), array_values($searchReplaceTags), $tagName);
                $foundTags[] = $tagName;
            }

            // empty tags, not allowed
            if (count($foundTags) !== count(array_filter($foundTags))) {
                unlink($filePath . $import->file_name);
                $this->renderJson([
                    'result'  => 'error',
                    'message' => t('list_import', 'Empty column names are not allowed!'),
                ]);
                return;
            }

            $foundEmailTag = false;
            foreach ($foundTags as $tagName) {
                if ($tagName === 'EMAIL') {
                    $foundEmailTag = true;
                    break;
                }
            }

            if (!$foundEmailTag) {
                unlink($filePath . $import->file_name);
                $this->renderJson([
                    'result'  => 'error',
                    'message' => t('list_import', 'Cannot find the "email" column in your file!'),
                ]);
                return;
            }

            $foundReservedColumns = [];
            /** @var string $columnName */
            foreach ($csvHeader as $columnName) {
                $columnName     = StringHelper::getTagFromString((string)$columnName);
                $columnName     = (string)str_replace(array_keys($searchReplaceTags), array_values($searchReplaceTags), $columnName);
                $tagIsReserved  = TagRegistry::model()->findByAttributes(['tag' => '[' . $columnName . ']']);
                if (!empty($tagIsReserved)) {
                    $foundReservedColumns[] = $columnName;
                }
            }

            if (!empty($foundReservedColumns)) {
                unlink($filePath . $import->file_name);
                $this->renderJson([
                    'result'  => 'error',
                    'message' => t('list_import', 'Your list contains the columns: "{columns}" which are system reserved. Please update your file and change the column names!', [
                        '{columns}' => implode(', ', $foundReservedColumns),
                    ]),
                ]);
                return;
            }

            if ($import->is_first_batch) {

                /** @var Customer $customer */
                $customer = customer()->getModel();

                /** @var CustomerActionLogBehavior $logAction */
                $logAction = $customer->getLogAction();
                $logAction->listImportStart($list, $import);

                $importLog[] = [
                    'type'    => 'info',
                    'message' => t('list_import', 'Found the following column names: {columns}', [
                        '{columns}' => implode(', ', $csvHeader),
                    ]),
                    'counter' => false,
                ];
            }

            $offset = (int)($importAtOnce * ($import->current_page - 1));
            if ($offset >= $totalFileRecords) {
                // @phpstan-ignore-next-line
                if (is_file($filePath . $import->file_name)) {
                    unlink($filePath . $import->file_name);
                }
                $this->renderJson([
                    'result'  => 'success',
                    'message' => t('list_import', 'The import process has finished!'),
                    'finished'=> true,
                ]);
                return;
            }

            // back to 0 otherwise League\Csv\Statement::offset will jump rows
            $csvReader->setHeaderOffset(0);

            /** @var League\Csv\Statement $statement */
            $statement = (new League\Csv\Statement())->offset($offset)->limit($importAtOnce);
            $records   = $statement->process($csvReader, $csvHeader);

            /** @var array $csvData */
            $csvData = array_map([ioFilter(), 'stripPurify'], iterator_to_array($records->getRecords()));

            $fieldType = ListFieldType::model()->findByAttributes([
                'identifier' => 'text',
            ]);

            $data = [];

            /** @var array $row */
            foreach ($csvData as $row) {
                $rowData = [];
                /**
                 * @var string $name
                 * @var string $value
                 */
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
            unset($csvData);

            if (count($data) === 0) {
                unlink($filePath . $import->file_name);

                /** @var Customer $customer */
                $customer = customer()->getModel();

                /** @var CustomerActionLogBehavior $logAction */
                $logAction = $customer->getLogAction();
                $logAction->listImportEnd($list, $import);

                if ($import->is_first_batch) {
                    $this->renderJson([
                        'result'  => 'error',
                        'message' => t('list_import', 'Your file does not contain enough data to be imported!'),
                    ]);
                } else {
                    $this->renderJson([
                        'result'  => 'success',
                        'message' => t('list_import', 'The import process has finished!'),
                        'finished'=> true,
                    ]);
                }
                return;
            }

            $tagToModel = [];
            foreach ($data[0] as $sample) {
                if ($import->is_first_batch) {
                    $importLog[] = [
                        'type'    => 'info',
                        'message' => t('list_import', 'Checking to see if the tag "{tag}" is defined in your list fields...', [
                            '{tag}' => html_encode($sample['tagName']),
                        ]),
                        'counter' => false,
                    ];
                }

                $model = ListField::model()->findByAttributes([
                    'list_id' => $list->list_id,
                    'tag'     => $sample['tagName'],
                ]);

                if (!empty($model)) {
                    if ($import->is_first_batch) {
                        $importLog[] = [
                            'type'    => 'info',
                            'message' => t('list_import', 'The tag "{tag}" is already defined in your list fields.', [
                                '{tag}' => html_encode($sample['tagName']),
                            ]),
                            'counter' => false,
                        ];
                    }

                    $tagToModel[$sample['tagName']] = $model;
                    continue;
                }

                if ($import->is_first_batch) {
                    $importLog[] = [
                        'type'    => 'info',
                        'message' => t('list_import', 'The tag "{tag}" is not defined in your list fields, we will try to create it.', [
                            '{tag}' => html_encode($sample['tagName']),
                        ]),
                        'counter' => false,
                    ];
                }

                $model = new ListField();
                $model->type_id     = (int)$fieldType->type_id;
                $model->list_id     = (int)$list->list_id;
                $model->label       = (string)$sample['name'];
                $model->tag         = (string)$sample['tagName'];
                $model->visibility  = (string)$list->customer->getGroupOption('lists.custom_fields_default_visibility', ListField::VISIBILITY_VISIBLE);

                if ($model->save()) {
                    if ($import->is_first_batch) {
                        $importLog[] = [
                            'type'    => 'success',
                            'message' => t('list_import', 'The tag "{tag}" has been successfully created.', [
                                '{tag}' => html_encode($sample['tagName']),
                            ]),
                            'counter' => false,
                        ];
                    }

                    $tagToModel[$sample['tagName']] = $model;
                } else {
                    if ($import->is_first_batch) {
                        $importLog[] = [
                            'type'    => 'error',
                            'message' => t('list_import', 'The tag "{tag}" cannot be saved, reason: {reason}', [
                                '{tag}'    => html_encode($sample['tagName']),
                                '{reason}' => '<br />' . $model->shortErrors->getAllAsString(),
                            ]),
                            'counter' => false,
                        ];
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
            $failures = (array)hooks()->applyFilters('list_import_data_bulk_check_failures', [], (array)$bulkEmails);
            /**
             * @var string $email
             * @var string $message
             */
            foreach ($failures as $email => $message) {
                EmailBlacklist::addToBlacklist($email, $message);
            }
            // end 1.3.5.9

            $finished    = false;
            $importCount = 0;

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

            /** @var array $data */
            $data = (array)$collection->itemAt('data');

            /** @var array $importLog */
            $importLog = (array)$collection->itemAt('importLog');

            /** @var int $importCount */
            $importCount = (int)$collection->itemAt('importCount');

            /** @var bool $finished */
            $finished = (bool)$collection->itemAt('finished');

            /** @var array $failures */
            $failures = (array)$collection->itemAt('failures');
            //

            // since 1.9.10
            $list->setSubscribersCountCacheEnabled(false)
                ->flushSubscribersCountCacheOnEndRequest();

            // since 1.9.12
            // The transaction will improve the speed of below code by 50% for inserts and by 75% for updates
            // for now this only applies to web csv import and it is under active supervision.
            // If it proves to behave properly, we will do the change for all import types.
            /** @var CDbTransaction $transaction */
            $transaction = db()->beginTransaction();

            try {
                /**
                 * @var int $index
                 * @var array $fields
                 */
                foreach ($data as $index => $fields) {
                    $email = '';
                    /** @var array $detail */
                    foreach ($fields as $detail) {
                        if ($detail['tagName'] == 'EMAIL' && !empty($detail['tagValue'])) {
                            $email = (string)$detail['tagValue'];
                            break;
                        }
                    }

                    if (empty($email)) {
                        unset($data[$index]);
                        continue;
                    }

                    // Since 1.9.19 - Insert the IP from the imported source into the list_subscriber table
                    $ip = '';
                    /** @var array $detail */
                    foreach ($fields as $detail) {
                        if ($detail['tagName'] == 'IP_ADDRESS' && !empty($detail['tagValue']) && FilterVarHelper::ip($detail['tagValue'])) {
                            $ip = (string)$detail['tagValue'];
                            break;
                        }
                    }

                    $importLog[] = [
                        'type'    => 'info',
                        'message' => t('list_import', 'Checking the list for the email: "{email}"', [
                            '{email}' => html_encode($email),
                        ]),
                        'counter' => false,
                    ];

                    if (!empty($failures[$email])) {
                        $importLog[] = [
                            'type'    => 'error',
                            'message' => t('list_import', 'Failed to save the email "{email}", reason: {reason}', [
                                '{email}'  => html_encode($email),
                                '{reason}' => '<br />' . $failures[$email],
                            ]),
                            'counter' => true,
                        ];
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
                            $importLog[] = [
                                'type'    => 'info',
                                'message' => t('list_import', 'The email "{email}" was not found, we will try to create it...', [
                                    '{email}' => html_encode($email),
                                ]),
                                'counter' => false,
                            ];

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
                                    if (($blacklisted instanceof EmailBlacklistCheckInfo) && $blacklisted->getReason()) {
                                        $subscriber->addError('email', $blacklisted->getReason());
                                    } else {
                                        $subscriber->addError('email', t('list_import', 'This email address is blacklisted!'));
                                    }
                                }
                            }

                            if (!$validEmail || $subscriber->hasErrors() || !$subscriber->save()) {
                                $importLog[] = [
                                    'type'    => 'error',
                                    'message' => t('list_import', 'Failed to save the email "{email}", reason: {reason}', [
                                        '{email}'  => html_encode($email),
                                        '{reason}' => '<br />' . $subscriber->shortErrors->getAllAsString(),
                                    ]),
                                    'counter' => true,
                                ];
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

                            $importLog[] = [
                                'type'    => 'success',
                                'message' => t('list_import', 'The email "{email}" has been successfully saved.', [
                                    '{email}' => html_encode($email),
                                ]),
                                'counter' => true,
                            ];
                        } else {
                            $importLog[] = [
                                'type'    => 'info',
                                'message' => t('list_import', 'The email "{email}" has been found, we will update it.', [
                                    '{email}' => html_encode($email),
                                ]),
                                'counter' => true,
                            ];
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

                            $isInsert = false;
                            if (empty($valueModel)) {
                                $isInsert = true;
                                $valueModel = new ListFieldValue();
                                $valueModel->field_id      = (int)$fieldModel->field_id;
                                $valueModel->subscriber_id = (int)$subscriber->subscriber_id;
                            }
                            if ($isInsert || $valueModel->value !== $detail['tagValue']) {
                                $valueModel->value = (string)$detail['tagValue'];
                                $valueModel->save();
                            }
                        }

                        // since 1.9.26
                        if ($canUpdateGeoLocation) {
                            $subscriber->updateGeoLocationFields();
                        }

                        // since 2.1.4
                        $subscriber->handleFieldsDefaultValues();

                        unset($data[$index]);
                        ++$importCount;

                        if ($finished) {
                            break;
                        }
                    } catch (Exception $e) {
                        Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
                    }
                }

                $transaction->commit();
            } catch (Exception $e) {
                $transaction->rollback();
                Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
            }

            if ($finished) {
                $this->renderJson([
                    'result'  => 'error',
                    'message' => $finished,
                ]);
                return;
            }

            $import->is_first_batch = 0;
            $import->current_page++;

            $this->renderJson([
                'result'    => 'success',
                'message'   => t('list_import', 'Imported {count} subscribers starting from row {rowStart} and ending with row {rowEnd}! Going further, please wait...', [
                    '{count}'    => $importCount,
                    '{rowStart}' => $offset,
                    '{rowEnd}'   => $offset + $importAtOnce,
                ]),
                'attributes'   => $import->attributes,
                'import_log'   => $importLog,
                'recordsCount' => $totalFileRecords,
            ]);
        } catch (Exception $e) {
            if (is_file($filePath . $import->file_name)) {
                unlink($filePath . $import->file_name);
            }

            $this->renderJson([
                'result'  => 'error',
                'message' => t('list_import', 'Your file cannot be imported, a general error has been encountered: {message}!', [
                    '{message}' => $e->getMessage(),
                ]),
            ]);
        }
    }

    /**
     * @param string $list_uid
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionDatabase($list_uid)
    {
        /** @var Lists $list */
        $list = $this->loadListModel((string)$list_uid);

        if (!request()->getIsPostRequest()) {
            $this->redirect(['list_import/index', 'list_uid' => $list->list_uid]);
        }

        /** @var OptionImporter $importOptions */
        $importOptions = container()->get(OptionImporter::class);
        $importAtOnce  = $importOptions->getImportAtOnce();
        $pause         = $importOptions->getPause();

        $import = new ListDatabaseImport();
        $import->attributes = (array)request()->getPost($import->getModelName(), []);
        $import->validateAndConnect();

        if ($import->hasErrors()) {
            $message = t('app', 'Your form has a few errors, please fix them and try again!') . '<br />' . $import->shortErrors->getAllAsString();
            if (request()->getIsAjaxRequest()) {
                $this->renderJson([
                    'result'  => 'error',
                    'message' => $message,
                ]);
                return;
            }
            notify()->addError($message);
            $this->redirect(['list_import/index', 'list_uid' => $list->list_uid]);
        }

        if (!request()->getIsAjaxRequest()) {
            $this->setData([
                'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('list_import', 'Import subscribers'),
                'pageHeading'     => t('list_import', 'Import subscribers'),
                'pageBreadcrumbs' => [
                    t('lists', 'Lists') => createUrl('lists/index'),
                    $list->name . ' ' => createUrl('lists/overview', ['list_uid' => $list->list_uid]),
                    t('list_import', 'Database Import'),
                ],
            ]);

            $this->render('database', compact('list', 'import', 'importAtOnce', 'pause'));
            return;
        }

        $importLog = [];

        try {
            $columns = $import->getColumns();
            if (empty($columns)) {
                $this->renderJson([
                    'result'  => 'error',
                    'message' => t('list_import', 'Cannot find your database columns!'),
                ]);
                return;
            }

            if ($import->is_first_batch) {
                $totalRecords = $import->rows_count = $import->countResults();
            } else {
                $totalRecords = $import->rows_count;
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
                        $this->renderJson([
                            'result'  => 'error',
                            'message' => t('lists', 'You have reached the maximum number of allowed subscribers.'),
                        ]);
                        return;
                    }
                }

                if ($maxSubscribersPerList > -1) {
                    $criteria->compare('t.list_id', (int)$list->list_id);
                    $listSubscribersCount = ListSubscriber::model()->count($criteria);
                    if ($listSubscribersCount >= $maxSubscribersPerList) {
                        $this->renderJson([
                            'result'  => 'error',
                            'message' => t('lists', 'You have reached the maximum number of allowed subscribers into this list.'),
                        ]);
                        return;
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
                $this->renderJson([
                    'result'  => 'error',
                    'message' => t('list_import', 'Empty column names are not allowed!'),
                ]);
                return;
            }

            $foundEmailTag = false;
            foreach ($foundTags as $tagName) {
                if ($tagName === 'EMAIL' || $tagName == strtoupper((string)$import->email_column)) {
                    $foundEmailTag = true;
                    break;
                }
            }

            if (!$foundEmailTag) {
                $this->renderJson([
                    'result'  => 'error',
                    'message' => t('list_import', 'Cannot find the "email" column in your database!'),
                ]);
                return;
            }

            $foundReservedColumns = [];
            foreach ($columns as $columnName) {
                $columnName    = StringHelper::getTagFromString($columnName);
                $columnName    = (string)str_replace(array_keys($searchReplaceTags), array_values($searchReplaceTags), $columnName);
                $tagIsReserved = TagRegistry::model()->findByAttributes(['tag' => '[' . $columnName . ']']);
                if (!empty($tagIsReserved)) {
                    $foundReservedColumns[] = $columnName;
                }
            }

            if (!empty($foundReservedColumns)) {
                $this->renderJson([
                    'result'  => 'error',
                    'message' => t('list_import', 'Your database contains the columns: "{columns}" which are system reserved. Please update your database and change the column names or ignore them!', [
                        '{columns}' => implode(', ', $foundReservedColumns),
                    ]),
                ]);
                return;
            }

            if ($import->is_first_batch) {

                /** @var Customer $customer */
                $customer = customer()->getModel();

                /** @var CustomerActionLogBehavior $logAction */
                $logAction = $customer->getLogAction();
                $logAction->listImportStart($list, $import);

                $importLog[] = [
                    'type'    => 'info',
                    'message' => t('list_import', 'Found the following column names: {columns}', [
                        '{columns}' => implode(', ', $columns),
                    ]),
                    'counter' => false,
                ];
            }

            $offset = $importAtOnce * ($import->current_page - 1);
            if ($offset >= $totalRecords) {

                /** @var Customer $customer */
                $customer = customer()->getModel();

                /** @var CustomerActionLogBehavior $logAction */
                $logAction = $customer->getLogAction();
                $logAction->listImportEnd($list, $import);

                $this->renderJson([
                    'result'  => 'success',
                    'message' => t('list_import', 'The import process has finished!'),
                    'finished'=> true,
                ]);
                return;
            }

            $results = $import->getResults($offset, $importAtOnce);
            if (empty($results)) {

                /** @var Customer $customer */
                $customer = customer()->getModel();

                /** @var CustomerActionLogBehavior $logAction */
                $logAction = $customer->getLogAction();
                $logAction->listImportEnd($list, $import);

                $this->renderJson([
                    'result'  => 'success',
                    'message' => t('list_import', 'The import process has finished!'),
                    'finished'=> true,
                ]);
                return;
            }

            $fieldType = ListFieldType::model()->findByAttributes([
                'identifier' => 'text',
            ]);

            $data = [];
            foreach ($results as $result) {
                $rowData = [];
                foreach ($result as $name => $value) {
                    $tagName = StringHelper::getTagFromString($name);
                    $tagName = (string)str_replace(array_keys($searchReplaceTags), array_values($searchReplaceTags), $tagName);

                    $rowData[] = [
                        'name'     => ucwords((string)str_replace('_', ' ', (string)$name)),
                        'tagName'  => trim($tagName),
                        'tagValue' => trim((string)$value),
                    ];
                }
                $data[] = $rowData;
            }

            // @phpstan-ignore-next-line
            if (count($data) === 0) {

                /** @var Customer $customer */
                $customer = customer()->getModel();

                /** @var CustomerActionLogBehavior $logAction */
                $logAction = $customer->getLogAction();
                $logAction->listImportEnd($list, $import);

                if ($import->is_first_batch) {
                    $this->renderJson([
                        'result'  => 'error',
                        'message' => t('list_import', 'Your database does not contain enough data to be imported!'),
                    ]);
                } else {
                    $this->renderJson([
                        'result'  => 'success',
                        'message' => t('list_import', 'The import process has finished!'),
                        'finished'=> true,
                    ]);
                }
                return;
            }

            $tagToModel = [];
            foreach ($data[0] as $sample) {
                if ($import->is_first_batch) {
                    $importLog[] = [
                        'type'     => 'info',
                        'message'  => t('list_import', 'Checking to see if the tag "{tag}" is defined in your list fields...', [
                            '{tag}'=> html_encode($sample['tagName']),
                        ]),
                        'counter'  => false,
                    ];
                }

                $model = ListField::model()->findByAttributes([
                    'list_id' => $list->list_id,
                    'tag'     => $sample['tagName'],
                ]);

                if (!empty($model)) {
                    if ($import->is_first_batch) {
                        $importLog[] = [
                            'type'    => 'info',
                            'message' => t('list_import', 'The tag "{tag}" is already defined in your list fields.', [
                                '{tag}' => html_encode($sample['tagName']),
                            ]),
                            'counter' => false,
                        ];
                    }

                    $tagToModel[$sample['tagName']] = $model;
                    continue;
                }

                if ($import->is_first_batch) {
                    $importLog[] = [
                        'type'    => 'info',
                        'message' => t('list_import', 'The tag "{tag}" is not defined in your list fields, we will try to create it.', [
                            '{tag}' => html_encode($sample['tagName']),
                        ]),
                        'counter' => false,
                    ];
                }

                $model = new ListField();
                $model->type_id     = (int)$fieldType->type_id;
                $model->list_id     = (int)$list->list_id;
                $model->label       = (string)$sample['name'];
                $model->tag         = (string)$sample['tagName'];
                $model->visibility  = (string)$list->customer->getGroupOption('lists.custom_fields_default_visibility', ListField::VISIBILITY_VISIBLE);

                if ($model->save()) {
                    if ($import->is_first_batch) {
                        $importLog[] = [
                            'type'    => 'success',
                            'message' => t('list_import', 'The tag "{tag}" has been successfully created.', [
                                '{tag}' => html_encode($sample['tagName']),
                            ]),
                            'counter' => false,
                        ];
                    }

                    $tagToModel[$sample['tagName']] = $model;
                } else {
                    if ($import->is_first_batch) {
                        $importLog[] = [
                            'type'    => 'error',
                            'message' => t('list_import', 'The tag "{tag}" cannot be saved, reason: {reason}', [
                                '{tag}'    => html_encode($sample['tagName']),
                                '{reason}' => '<br />' . $model->shortErrors->getAllAsString(),
                            ]),
                            'counter' => false,
                        ];
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
            /**
             * @var string $email
             * @var string $message
             */
            foreach ($failures as $email => $message) {
                EmailBlacklist::addToBlacklist($email, $message);
            }
            // end 1.3.5.9

            $finished    = false;
            $importCount = 0;

            // since 1.3.5.9
            hooks()->doAction('list_import_before_processing_data', $collection = new CAttributeCollection([
                'data'        => $data,
                'list'        => $list,
                'importLog'   => $importLog,
                'finished'    => $finished,
                'importCount' => $importCount,
                'failures'    => $failures,
                'importType'  => 'database',
            ]));

            /** @var array $data */
            $data = (array)$collection->itemAt('data');

            /** @var array $importLog */
            $importLog = (array)$collection->itemAt('importLog');

            /** @var int $importCount */
            $importCount = (int)$collection->itemAt('importCount');

            /** @var bool $finished */
            $finished = (bool)$collection->itemAt('finished');

            /** @var array $failures */
            $failures = (array)$collection->itemAt('failures');
            //

            // since 1.9.10
            $list->setSubscribersCountCacheEnabled(false)
                ->flushSubscribersCountCacheOnEndRequest();

            /**
             * @var int $index
             * @var array $fields
             */
            foreach ($data as $index => $fields) {
                $email = '';
                /** @var array $detail */
                foreach ($fields as $detail) {
                    if ($detail['tagName'] == 'EMAIL' && !empty($detail['tagValue'])) {
                        $email = (string)$detail['tagValue'];
                        break;
                    }
                }

                if (empty($email)) {
                    unset($data[$index]);
                    continue;
                }

                $importLog[] = [
                    'type'    => 'info',
                    'message' => t('list_import', 'Checking the list for the email: "{email}"', [
                        '{email}' => html_encode($email),
                    ]),
                    'counter' => false,
                ];

                if (!empty($failures[$email])) {
                    $importLog[] = [
                        'type'    => 'error',
                        'message' => t('list_import', 'Failed to save the email "{email}", reason: {reason}', [
                            '{email}'  => html_encode($email),
                            '{reason}' => '<br />' . $failures[$email],
                        ]),
                        'counter' => true,
                    ];
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
                        $importLog[] = [
                            'type'    => 'info',
                            'message' => t('list_import', 'The email "{email}" was not found, we will try to create it...', [
                                '{email}' => html_encode($email),
                            ]),
                            'counter' => false,
                        ];

                        $subscriber = new ListSubscriber();
                        $subscriber->list_id = (int)$list->list_id;
                        $subscriber->email   = $email;
                        $subscriber->source  = ListSubscriber::SOURCE_IMPORT;
                        $subscriber->status  = ListSubscriber::STATUS_CONFIRMED;

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
                            $importLog[] = [
                                'type'    => 'error',
                                'message' => t('list_import', 'Failed to save the email "{email}", reason: {reason}', [
                                    '{email}'  => html_encode($email),
                                    '{reason}' => '<br />' . $subscriber->shortErrors->getAllAsString(),
                                ]),
                                'counter' => true,
                            ];
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

                        $importLog[] = [
                            'type'    => 'success',
                            'message' => t('list_import', 'The email "{email}" has been successfully saved.', [
                                '{email}' => html_encode($email),
                            ]),
                            'counter' => true,
                        ];
                    } else {
                        $importLog[] = [
                            'type'    => 'info',
                            'message' => t('list_import', 'The email "{email}" has been found, we will update it.', [
                                '{email}' => html_encode($email),
                            ]),
                            'counter' => true,
                        ];
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
                            $valueModel->field_id = (int)$fieldModel->field_id;
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

                    unset($data[$index]);
                    ++$importCount;

                    if ($finished) {
                        break;
                    }
                } catch (Exception $e) {
                    Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
                }
            }

            $import->is_first_batch = 0;
            $import->current_page++;

            $this->renderJson([
                'result'    => 'success',
                'message'   => t('list_import', 'Imported {count} subscribers starting from row {rowStart} and ending with row {rowEnd}! Going further, please wait...', [
                    '{count}'     => $importCount,
                    '{rowStart}'  => $offset,
                    '{rowEnd}'    => $offset + $importAtOnce,
                ]),
                'attributes'    => $import->attributes,
                'import_log'    => $importLog,
                'recordsCount'  => $totalRecords,
            ]);
        } catch (Exception $e) {
            $this->renderJson([
                'result'    => 'error',
                'message'   => t('list_import', 'Your database cannot be imported, a general error has been encountered: {message}!', [
                    '{message}' => $e->getMessage(),
                ]),
            ]);
        }
    }

    /**
     * @param string $list_uid
     *
     * @return void
     * @throws CHttpException
     * @throws CException
     */
    public function actionUrl($list_uid)
    {
        /** @var Lists $list */
        $list = $this->loadListModel((string)$list_uid);

        $importUrl = ListUrlImport::model()->findByAttributes([
            'list_id' => $list->list_id,
        ]);

        if (empty($importUrl)) {
            $importUrl = new ListUrlImport();
        }

        $importUrl->attributes = request()->getPost($importUrl->getModelName(), []);
        $importUrl->list_id    = (int)$list->list_id;
        if (!$importUrl->save()) {
            notify()->addError($importUrl->shortErrors->getAllAsString());
        } else {
            notify()->addSuccess(t('lists', 'The url has been added successfully!'));
        }

        $this->redirect(['list_import/index', 'list_uid' => $list->list_uid]);
    }

    /**
     * Will prevent the CSRF token expiration if the import takes too much time.
     *
     * @return void
     */
    public function actionPing()
    {
        $this->render('ping');
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
            'list_uid'    => $list_uid,
            'customer_id' => (int)customer()->getId(),
        ]);

        if ($model === null) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        return $model;
    }

    /**
     * @param string $list_uid
     * @param string $importClass
     *
     * @return void
     * @throws CHttpException
     * @throws CException
     */
    protected function handleQueuePlacement(string $list_uid, string $importClass = 'ListCsvImport')
    {
        /** @var Lists $list */
        $list = $this->loadListModel((string)$list_uid);

        // helps for when the document has been created on a Macintosh computer
        if (!ini_get('auto_detect_line_endings')) {
            ini_set('auto_detect_line_endings', '1');
        }

        /** @var OptionImporter $optionImporter */
        $optionImporter = container()->get(OptionImporter::class);

        /** @var ListCsvImport $import */
        $import = new $importClass('upload');
        $import->file_size_limit = $optionImporter->getFileSizeLimit();
        $import->attributes      = (array)request()->getPost($import->getModelName(), []);
        $import->file            = CUploadedFile::getInstance($import, 'file');

        // @phpstan-ignore-next-line
        if (!$import->file) {
            notify()->addError(t('list_import', 'Please select a file for import!'));
            $this->redirect(['list_import/index', 'list_uid' => $list->list_uid]);
            return;
        }

        $finalPath = (string)Yii::getPathOfAlias('common.runtime.list-import-queue');
        if (!file_exists($finalPath) && !mkdir($finalPath, 0777, true)) {
            notify()->addError(t('list_import', 'Unable to create target directory!'));
            $this->redirect(['list_import/index', 'list_uid' => $list->list_uid]);
            return;
        }

        $extension  = '.csv';
        $suffix     = '';
        $count      = 0;

        while (is_file($finalPath . '/' . $list->list_uid . $suffix . $extension)) {
            $count++;
            $suffix = '-' . $count;
            clearstatcache();
        }

        $fileName = $list->list_uid . $suffix . $extension;
        $filePath = $finalPath . '/' . $fileName;

        if (!$import->upload()) {
            notify()->addError($import->shortErrors->getAllAsString());
            $this->redirect(['list_import/index', 'list_uid' => $list->list_uid]);
        }

        $tmpFile = rtrim($import->getUploadPath(), '/') . '/' . $import->file_name;
        if (!copy($tmpFile, $filePath)) {
            notify()->addError(t('list_import', 'Unable to queue your import file!'));
            $this->redirect(['list_import/index', 'list_uid' => $list->list_uid]);
        }
        unlink($tmpFile);

        notify()->addSuccess(t('list_import', 'Your file has been queued successfully!'));
        $this->redirect(['list_import/index', 'list_uid' => $list->list_uid]);
    }
}
