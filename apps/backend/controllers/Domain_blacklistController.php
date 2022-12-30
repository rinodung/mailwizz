<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * Domain_blacklistController
 *
 * Handles the actions for domain blacklist related tasks
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.0.29
 */

class Domain_blacklistController extends Controller
{
    /**
     * @return array
     */
    public function filters()
    {
        $filters = [
            'postOnly + delete, bulk_action, import',
        ];

        return CMap::mergeArray($filters, parent::filters());
    }

    /**
     * List all blacklisted domains
     *
     * @return void
     * @throws CException
     */
    public function actionIndex()
    {
        $domainBlacklist = new DomainBlacklist('search');
        $domainBlacklist->unsetAttributes();
        $domainBlacklist->attributes = (array)request()->getQuery($domainBlacklist->getModelName(), []);

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('domain_blacklist', 'View domains blacklist'),
            'pageHeading'     => t('domain_blacklist', 'View domains blacklist'),
            'pageBreadcrumbs' => [
                t('domain_blacklist', 'Domains blacklist') => createUrl('domain_blacklist/index'),
                t('app', 'View all'),
            ],
        ]);

        $this->render('list', compact('domainBlacklist'));
    }

    /**
     * Add a new domain to blacklist
     *
     * @return void
     * @throws CException
     */
    public function actionCreate()
    {
        $domainBlacklist = new DomainBlacklist();

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($domainBlacklist->getModelName(), []))) {
            $domainBlacklist->attributes = $attributes;
            if (!$domainBlacklist->save()) {
                notify()->addError(t('app', 'Your form has a few errors, please fix them and try again!'));
            } else {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller'      => $this,
                'success'         => notify()->getHasSuccess(),
                'domainBlacklist' => $domainBlacklist,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['domain_blacklist/index']);
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('domain_blacklist', 'Add a new domain to blacklist'),
            'pageHeading'     => t('domain_blacklist', 'Add a new domain to blacklist'),
            'pageBreadcrumbs' => [
                t('domain_blacklist', 'Domains blacklist') => createUrl('domain_blacklist/index'),
                t('app', 'Create new'),
            ],
        ]);

        $this->render('form', compact('domainBlacklist'));
    }

    /**
     * Update existing domain
     *
     * @param int $id
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionUpdate($id)
    {
        /** @var DomainBlacklist|null $domainBlacklist */
        $domainBlacklist = DomainBlacklist::model()->findByPk((int)$id);

        if (empty($domainBlacklist)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($domainBlacklist->getModelName(), []))) {
            $domainBlacklist->attributes = $attributes;
            if (!$domainBlacklist->save()) {
                notify()->addError(t('app', 'Your form has a few errors, please fix them and try again!'));
            } else {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller'      => $this,
                'success'         => notify()->getHasSuccess(),
                'domainBlacklist' => $domainBlacklist,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['domain_blacklist/index']);
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('domain_blacklist', 'Update blacklisted domain'),
            'pageHeading'     => t('domain_blacklist', 'Update blacklisted domain'),
            'pageBreadcrumbs' => [
                t('domain_blacklist', 'Domains blacklist') => createUrl('domain_blacklist/index'),
                t('app', 'Update'),
            ],
        ]);

        $this->render('form', compact('domainBlacklist'));
    }

    /**
     * Delete existing domain
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
        $domainBlacklist = DomainBlacklist::model()->findByPk((int)$id);

        if (empty($domainBlacklist)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }
        $domainBlacklist->delete();

        notify()->addSuccess(t('app', 'Your item has been successfully deleted!'));
        $redirect = request()->getPost('returnUrl', ['domain_blacklist/index']);

        // since 1.3.5.9
        hooks()->doAction('controller_action_delete_data', $collection = new CAttributeCollection([
            'controller' => $this,
            'model'      => $domainBlacklist,
            'redirect'   => $redirect,
        ]));

        if ($collection->itemAt('redirect')) {
            $this->redirect($collection->itemAt('redirect'));
        }
    }

    /**
     * Run a bulk action against the domains blacklist
     *
     * @return void
     * @throws CDbException
     * @throws CException
     */
    public function actionBulk_action()
    {
        $action = request()->getPost('bulk_action');
        $items  = array_unique(array_map('intval', (array)request()->getPost('bulk_item', [])));

        if ($action == DomainBlacklist::BULK_ACTION_DELETE && count($items)) {
            $affected = 0;
            foreach ($items as $item) {
                $domain = DomainBlacklist::model()->findByPk((int)$item);
                if (empty($domain)) {
                    continue;
                }

                $domain->delete();
                $affected++;
            }
            if ($affected) {
                notify()->addSuccess(t('app', 'The action has been successfully completed!'));
            }
        }

        $defaultReturn = request()->getServer('HTTP_REFERER', ['domain_blacklist/index']);
        $this->redirect(request()->getPost('returnUrl', $defaultReturn));
    }

    /**
     * Import blacklisted domains
     *
     * @return void
     * @throws \League\Csv\Exception
     */
    public function actionImport()
    {
        $redirect = ['domain_blacklist/index'];

        if (!request()->getIsPostRequest()) {
            $this->redirect($redirect);
        }

        // helps for when the document has been created on a Macintosh computer
        if (!ini_get('auto_detect_line_endings')) {
            ini_set('auto_detect_line_endings', '1');
        }

        $import = new DomainBlacklist('import');
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
        if (array_search('domain', $csvHeader) === false) {
            notify()->addError(t('app', 'Your form has a few errors, please fix them and try again!'));
            notify()->addError(t('domain_blacklist', 'Your file does not contain the header with the fields title!'));
            $this->redirect($redirect);
        }

        /** @var OptionImporter $optionImporter */
        $optionImporter = container()->get(OptionImporter::class);
        $importAtOnce   = $optionImporter->getImportAtOnce();
        $totalRecords   = $csvReader->count();
        $insert         = [];
        $totalImport    = 0;
        $count          = 0;

        // reset
        $csvReader->setHeaderOffset(0);

        /** @var array $row */
        foreach ($csvReader->getRecords($csvHeader) as $row) {

            // clean the data
            $row = (array)ioFilter()->stripPurify($row);

            $insert[] = [
                'domain'        => $row['domain'],
                'date_added'    => MW_DATETIME_NOW,
                'last_updated'  => MW_DATETIME_NOW,
            ];

            $count++;
            if ($count < $importAtOnce) {
                continue;
            }

            try {
                $importCount = DomainBlacklist::insertMultipleUnique($insert);
                $totalImport += $importCount;
            } catch (Exception $e) {
            }

            $count  = 0;
            $insert = [];
        }

        try {
            $importCount = DomainBlacklist::insertMultipleUnique($insert);
            $totalImport += $importCount;
        } catch (Exception $e) {
        }

        notify()->addSuccess(t('domain_blacklist', 'Your file has been successfully imported, from {count} records, {total} were imported!', [
            '{count}'   => $totalRecords,
            '{total}'   => $totalImport,
        ]));

        $this->redirect($redirect);
    }

    /**
     * Export blacklisted domains
     *
     * @return void
     */
    public function actionExport()
    {
        // Set the download headers
        HeaderHelper::setDownloadHeaders('domain-blacklist-' . date('Y-m-d-h-i-s') . '.csv');

        try {
            $csvWriter = League\Csv\Writer::createFromPath('php://output', 'w');
            $csvWriter->insertAll($this->getDomainBlacklistDataForExport());
        } catch (Exception $e) {
        }

        app()->end();
    }

    /**
     * @return Generator
     */
    protected function getDomainBlacklistDataForExport(): Generator
    {
        $criteria = new CDbCriteria();
        $criteria->select = 't.domain';
        $criteria->limit  = 500;
        $criteria->offset = 0;

        yield ['domain'];

        while (true) {

            /** @var DomainBlacklist[] $models */
            $models = DomainBlacklist::model()->findAll($criteria);
            if (empty($models)) {
                break;
            }

            foreach ($models as $model) {
                yield [$model->domain];
            }

            $criteria->offset = $criteria->offset + $criteria->limit;
        }
    }
}
