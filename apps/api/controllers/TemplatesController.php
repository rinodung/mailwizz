<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * TemplatesController
 *
 * Handles the CRUD actions for templates that will be used in campaigns.
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

class TemplatesController extends Controller
{
    /**
     * @return array
     */
    public function accessRules()
    {
        return [
            // allow all authenticated users on all actions
            ['allow', 'users' => ['@']],
            // deny all rule.
            ['deny'],
        ];
    }

    /**
     * Handles the listing of the templates.
     * The listing is based on page number and number of templates per page.
     *
     * @return void
     * @throws CException
     */
    public function actionIndex()
    {
        $perPage    = (int)request()->getQuery('per_page', 10);
        $page       = (int)request()->getQuery('page', 1);
        $filter     = (array)request()->getQuery('filter', []);
        $maxPerPage = 50;
        $minPerPage = 10;

        if ($perPage < $minPerPage) {
            $perPage = $minPerPage;
        }

        if ($perPage > $maxPerPage) {
            $perPage = $maxPerPage;
        }

        if ($page < 1) {
            $page = 1;
        }

        $data = [
            'count'         => null,
            'total_pages'   => null,
            'current_page'  => null,
            'next_page'     => null,
            'prev_page'     => null,
            'records'       => [],
        ];

        $criteria = new CDbCriteria();
        $criteria->compare('t.customer_id', (int)user()->getId());

        // 1.4.4
        if (!empty($filter) && !empty($filter['name'])) {
            $criteria->compare('t.name', $filter['name'], true);
        }

        $count = CustomerEmailTemplate::model()->count($criteria);

        if ($count == 0) {
            $this->renderJson([
                'status'    => 'success',
                'data'      => $data,
            ]);
            return;
        }

        $totalPages = ceil($count / $perPage);

        $data['count']          = $count;
        $data['current_page']   = $page;
        $data['next_page']      = $page < $totalPages ? $page + 1 : null;
        $data['prev_page']      = $page > 1 ? $page - 1 : null;
        $data['total_pages']    = $totalPages;

        $criteria->order    = 't.template_id DESC';
        $criteria->limit    = $perPage;
        $criteria->offset   = ($page - 1) * $perPage;

        $templates = CustomerEmailTemplate::model()->findAll($criteria);

        foreach ($templates as $template) {
            $attributes = $template->getAttributes(['template_uid', 'name']);

            $attributes['screenshot'] = null;
            if (!empty($template->screenshot)) {
                $attributes['screenshot'] = apps()->getAppUrl('frontend', $template->screenshot, true, true);
            }

            $data['records'][] = $attributes;
        }

        $this->renderJson([
            'status'    => 'success',
            'data'      => $data,
        ]);
    }

    /**
     * Handles the listing of a single template.
     *
     * @param string $template_uid
     *
     * @return void
     * @throws CException
     */
    public function actionView($template_uid)
    {
        $template = CustomerEmailTemplate::model()->findByAttributes([
            'template_uid'  => $template_uid,
            'customer_id'   => (int)user()->getId(),
        ]);

        if (empty($template)) {
            $this->renderJson([
                'status'    => 'error',
                'error'     => t('api', 'The template does not exist.'),
            ], 404);
            return;
        }

        $attributes = $template->getAttributes(['name', 'content']);

        $attributes['screenshot'] = null;
        if (!empty($template->screenshot)) {
            $attributes['screenshot'] = apps()->getAppUrl('frontend', $template->screenshot, true, true);
        }

        $data = [
            'record' => $attributes,
        ];

        $this->renderJson([
            'status'    => 'success',
            'data'      => $data,
        ]);
    }

    /**
     * Handles the creation of a new template for campaigns.
     *
     * @return void
     * @throws CException
     */
    public function actionCreate()
    {
        if (!request()->getIsPostRequest()) {
            $this->renderJson([
                'status'    => 'error',
                'error'     => t('api', 'Only POST requests allowed for this endpoint.'),
            ], 400);
            return;
        }

        $attributes = (array)request()->getPost('template', []);
        $template = new CustomerEmailTemplate();
        $template->attributes = $attributes;
        $template->customer_id = (int)user()->getId();

        if (!empty($attributes['archive'])) {
            $archivePath = FileSystemHelper::getTmpDirectory() . '/' . StringHelper::random() . '.zip';
            $archiveContent = base64_decode((string)$attributes['archive']);

            unset($attributes['archive']);

            if (empty($archiveContent)) {
                $this->renderJson([
                    'status'    => 'error',
                    'error'     => t('api', 'It does not seem that you have selected an archive.'),
                ], 422);
                return;
            }

            // http://www.garykessler.net/library/file_sigs.html
            $magicNumbers   = ['504B0304'];
            $substr         = CommonHelper::functionExists('mb_substr') ? 'mb_substr' : 'substr';
            $firstBytes     = strtoupper(bin2hex($substr((string)$archiveContent, 0, 4)));

            if (!in_array($firstBytes, $magicNumbers)) {
                $this->renderJson([
                    'status'    => 'error',
                    'error'     => t('api', 'Your archive does not seem to be a valid zip file.'),
                ], 422);
                return;
            }

            if (!file_put_contents($archivePath, $archiveContent)) {
                $this->renderJson([
                    'status'    => 'error',
                    'error'     => t('api', 'Cannot write archive in the temporary location.'),
                ], 422);
                return;
            }

            $_FILES['archive'] = [
                'name'      => basename($archivePath),
                'type'      => 'application/zip',
                'tmp_name'  => $archivePath,
                'error'     => 0,
                'size'      => filesize($archivePath),
            ];

            $archiveTemplate = new CustomerEmailTemplate('upload');
            $archiveTemplate->customer_id = (int)user()->getId();
            $archiveTemplate->archive     = CUploadedFile::getInstanceByName('archive');
            $archiveTemplate->name        = $template->name;

            if (!$archiveTemplate->validate() || !$archiveTemplate->uploader->handleUpload()) {
                $this->renderJson([
                    'status'    => 'error',
                    'error'     => $archiveTemplate->shortErrors->getAll(),
                ], 422);
                return;
            }

            $this->renderJson([
                'status'        => 'success',
                'template_uid'  => $archiveTemplate->template_uid,
            ], 201);
            return;
        }

        if (!empty($attributes['content'])) {
            $template->content = (string)base64_decode((string)$attributes['content']);
        }

        if (!$template->save()) {
            $this->renderJson([
                'status'    => 'error',
                'error'     => $template->shortErrors->getAll(),
            ], 422);
            return;
        }

        $this->renderJson([
            'status'        => 'success',
            'template_uid'  => $template->template_uid,
        ], 201);
    }

    /**
     * Handles the updating of an existing template for campaigns.
     *
     * @param string $template_uid
     *
     * @return void
     * @throws CException
     */
    public function actionUpdate($template_uid)
    {
        if (!request()->getIsPutRequest()) {
            $this->renderJson([
                'status'    => 'error',
                'error'     => t('api', 'Only PUT requests allowed for this endpoint.'),
            ], 400);
            return;
        }

        $template = CustomerEmailTemplate::model()->findByAttributes([
            'template_uid'  => $template_uid,
            'customer_id'   => (int)user()->getId(),
        ]);

        if (empty($template)) {
            $this->renderJson([
                'status'    => 'error',
                'error'     => t('api', 'The template does not exist.'),
            ], 404);
            return;
        }

        $attributes = (array)request()->getPut('template', []);
        $template->attributes = $attributes;
        $template->customer_id = (int)user()->getId();

        if (!empty($attributes['archive'])) {
            $archivePath = FileSystemHelper::getTmpDirectory() . '/' . StringHelper::random() . '.zip';
            $archiveContent = base64_decode((string)$attributes['archive']);

            unset($attributes['archive']);

            if (empty($archiveContent)) {
                $this->renderJson([
                    'status'    => 'error',
                    'error'     => t('api', 'It does not seem that you have selected an archive.'),
                ], 422);
                return;
            }

            // http://www.garykessler.net/library/file_sigs.html
            $magicNumbers   = ['504B0304'];
            $substr         = CommonHelper::functionExists('mb_substr') ? 'mb_substr' : 'substr';
            $firstBytes     = strtoupper(bin2hex($substr((string)$archiveContent, 0, 4)));

            if (!in_array($firstBytes, $magicNumbers)) {
                $this->renderJson([
                    'status'    => 'error',
                    'error'     => t('api', 'Your archive does not seem to be a valid zip file.'),
                ], 422);
                return;
            }

            if (!file_put_contents($archivePath, $archiveContent)) {
                $this->renderJson([
                    'status'    => 'error',
                    'error'     => t('api', 'Cannot write archive in the temporary location.'),
                ], 422);
                return;
            }

            $_FILES['archive'] = [
                'name'      => basename($archivePath),
                'type'      => 'application/zip',
                'tmp_name'  => $archivePath,
                'error'     => 0,
                'size'      => filesize($archivePath),
            ];

            $template->setScenario('upload');
            $template->archive = CUploadedFile::getInstanceByName('archive');

            if (!$template->validate() || !$template->uploader->handleUpload()) {
                $this->renderJson([
                    'status'    => 'error',
                    'error'     => $template->shortErrors->getAll(),
                ], 422);
                return;
            }

            $this->renderJson([
                'status'        => 'success',
                'template_uid'  => $template->template_uid,
            ], 201);
            return;
        }

        if (empty($template->content) || !empty($attributes['content'])) {
            $template->content = (string)base64_decode((string)$attributes['content']);
        }

        if (!$template->save()) {
            $this->renderJson([
                'status'    => 'error',
                'error'     => $template->shortErrors->getAll(),
            ], 422);
            return;
        }

        $this->renderJson([
            'status' => 'success',
        ]);
    }

    /**
     * Handles deleting an existing template for campaigns.
     *
     * @param string $template_uid
     *
     * @return void
     * @throws CDbException
     * @throws CException
     */
    public function actionDelete($template_uid)
    {
        if (!request()->getIsDeleteRequest()) {
            $this->renderJson([
                'status'    => 'error',
                'error'     => t('api', 'Only DELETE requests allowed for this endpoint.'),
            ], 400);
            return;
        }

        $template = CustomerEmailTemplate::model()->findByAttributes([
            'template_uid'  => $template_uid,
            'customer_id'   => (int)user()->getId(),
        ]);

        if (empty($template)) {
            $this->renderJson([
                'status'    => 'error',
                'error'     => t('api', 'The template does not exist.'),
            ], 404);
            return;
        }

        $template->delete();

        // since 1.3.5.9
        hooks()->doAction('controller_action_delete_data', $collection = new CAttributeCollection([
            'controller' => $this,
            'model'      => $template,
        ]));

        $this->renderJson([
            'status' => 'success',
        ]);
    }

    /**
     * It will generate the timestamp that will be used to generate the ETAG for GET requests.
     *
     * @return int
     * @throws CException
     */
    public function generateLastModified()
    {
        static $lastModified;

        if ($lastModified !== null) {
            return $lastModified;
        }

        $row = [];

        if ($this->getAction()->getId() == 'index') {
            $perPage    = (int)request()->getQuery('per_page', 10);
            $page       = (int)request()->getQuery('page', 1);
            $maxPerPage = 50;
            $minPerPage = 10;

            if ($perPage < $minPerPage) {
                $perPage = $minPerPage;
            }

            if ($perPage > $maxPerPage) {
                $perPage = $maxPerPage;
            }

            if ($page < 1) {
                $page = 1;
            }

            $limit  = $perPage;
            $offset = ($page - 1) * $perPage;

            $sql = '
                SELECT AVG(t.last_updated) as `timestamp`
                FROM (
                     SELECT `a`.`customer_id`, UNIX_TIMESTAMP(`a`.`last_updated`) as `last_updated`
                     FROM `{{customer_email_template}}` `a`
                     WHERE `a`.`customer_id` = :cid
                     ORDER BY a.`template_id` DESC
                     LIMIT :l OFFSET :o
                ) AS t
                WHERE `t`.`customer_id` = :cid
            ';

            $command = db()->createCommand($sql);
            $command->bindValue(':cid', (int)user()->getId(), PDO::PARAM_INT);
            $command->bindValue(':l', (int)$limit, PDO::PARAM_INT);
            $command->bindValue(':o', (int)$offset, PDO::PARAM_INT);

            $row = $command->queryRow();
        } elseif ($this->getAction()->getId() == 'view') {
            $sql = 'SELECT UNIX_TIMESTAMP(t.last_updated) as `timestamp` FROM `{{customer_email_template}}` t WHERE `t`.`template_uid` = :uid AND `t`.`customer_id` = :cid LIMIT 1';
            $command = db()->createCommand($sql);
            $command->bindValue(':uid', request()->getQuery('template_uid'), PDO::PARAM_STR);
            $command->bindValue(':cid', (int)user()->getId(), PDO::PARAM_INT);

            $row = $command->queryRow();
        }

        if (isset($row['timestamp'])) {
            $timestamp = round((float)$row['timestamp']);
            if (preg_match('/\.(\d+)/', (string)$row['timestamp'], $matches)) {
                $timestamp += (int)$matches[1];
            }
            return $lastModified = (int)$timestamp;
        }

        return $lastModified = parent::generateLastModified();
    }
}
