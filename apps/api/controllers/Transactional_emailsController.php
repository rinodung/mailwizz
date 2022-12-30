<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * Transactional_emailsController
 *
 * Handles the CRUD actions for transactional emails.
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.4.5
 */

class Transactional_emailsController extends Controller
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
     * Handles the listing of the transactional emails.
     * The listing is based on page number and number of templates per page.
     *
     * @return void
     * @throws CException
     */
    public function actionIndex()
    {
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

        $data = [
            'count'         => null,
            'total_pages'   => null,
            'current_page'  => null,
            'next_page'     => null,
            'prev_page'     => null,
            'records'       => [],
        ];

        $criteria = new CDbCriteria();
        $criteria->compare('customer_id', (int)user()->getId());

        $count = TransactionalEmail::model()->count($criteria);

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

        $criteria->order    = 't.email_id DESC';
        $criteria->limit    = $perPage;
        $criteria->offset   = ($page - 1) * $perPage;

        $emails = TransactionalEmail::model()->findAll($criteria);

        foreach ($emails as $email) {
            $attributes = $email->getAttributes();
            unset($attributes['email_id']);
            $data['records'][] = $attributes;
        }

        $this->renderJson([
            'status'    => 'success',
            'data'      => $data,
        ]);
    }

    /**
     * Handles the listing of a single email.
     *
     * @param string $email_uid
     *
     * @return void
     * @throws CException
     */
    public function actionView($email_uid)
    {
        $email = TransactionalEmail::model()->findByAttributes([
            'email_uid'   => $email_uid,
            'customer_id' => (int)user()->getId(),
        ]);

        if (empty($email)) {
            $this->renderJson([
                'status'    => 'error',
                'error'     => t('api', 'The email does not exist.'),
            ], 404);
            return;
        }

        /** @var TransactionalEmailAttachment[] $emailAttachments */
        $emailAttachments = $email->attachments;
        $attachmentsData = ['attachments' => []];
        if (!empty($emailAttachments)) {
            foreach ($emailAttachments as $attachment) {
                $attachmentData = [
                    'type'    => $attachment->type,
                    'name'    => $attachment->name,
                    'content' => $attachment->getContentAsBase64(),
                ];

                $attachmentsData['attachments'][] = $attachmentData;
            }
        }

        $attributes = CMap::mergeArray($email->getAttributes(), $attachmentsData);
        unset($attributes['email_id']);

        $data = [
            'record' => $attributes,
        ];

        $this->renderJson([
            'status'    => 'success',
            'data'      => $data,
        ]);
    }

    /**
     * Handles the creation of a new transactional email.
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

        $attributes = (array)request()->getPost('email', []);
        $email = new TransactionalEmail();
        $email->attributes  = $attributes;
        $email->body        = !empty($email->body) ? (string)base64_decode($email->body) : '';
        $email->plain_text  = !empty($email->plain_text) ? (string)base64_decode($email->plain_text) : '';
        $email->customer_id = (int)user()->getId();

        $transaction = db()->beginTransaction();
        $success    = false;
        $error      = '';
        $returnCode = 201;
        try {
            if (!$email->save()) {
                throw new Exception($email->shortErrors->getAllAsString(), 422);
            }

            // Handle the attachments
            $attachmentAttributes   = !empty($attributes['attachments']) && is_array($attributes['attachments']) ? $attributes['attachments'] : [];

            /** @var OptionTransactionalEmailAttachment $optionTransactionalEmailAttachment */
            $optionTransactionalEmailAttachment = container()->get(OptionTransactionalEmailAttachment::class);

            $canAddAttachments = $optionTransactionalEmailAttachment->getIsEnabled();
            if ($canAddAttachments && !empty($attachmentAttributes)) {
                foreach ($attachmentAttributes as $attribute) {
                    if (empty($attribute['name']) || empty($attribute['type']) || empty($attribute['data'])) {
                        throw new Exception(t('api', 'Please send the data in the correct format ([name, type, data]).'), 422);
                    }

                    $attachmentExt = pathinfo($attribute['name'], PATHINFO_EXTENSION);
                    if (empty($attachmentExt)) {
                        throw new Exception(t('api', 'Your attachment file is missing the extension.'), 422);
                    }

                    $attachmentPath    = FileSystemHelper::getTmpDirectory() . '/' . StringHelper::random() . '.' . $attachmentExt;
                    $attachmentContent = (string)base64_decode($attribute['data']);

                    if (empty($attachmentContent)) {
                        throw new Exception(t('api', 'Your attachment file is empty.'), 422);
                    }

                    if (!file_put_contents($attachmentPath, $attachmentContent)) {
                        throw new Exception(t('api', 'Cannot write attachment in the temporary location.'), 422);
                    }

                    $_FILES['file']['name'][]     = $attribute['name'];
                    $_FILES['file']['type'][]     = $attribute['type'];
                    $_FILES['file']['tmp_name'][] = $attachmentPath;
                    $_FILES['file']['error'][]    = 0;
                    $_FILES['file']['size'][]     = filesize($attachmentPath);
                }

                $attachment = new TransactionalEmailAttachment('multi-upload');
                $attachment->email_id = (int)$email->email_id;
                if ($attachments = CUploadedFile::getInstancesByName('file')) {
                    $attachment->file = $attachments;
                    $attachment->validateAndSave();

                    if ($attachment->hasErrors()) {
                        throw new Exception(t('api', 'Some files failed to be attached, here is why: {message}', [
                            '{message}' => '<br />' . $attachment->shortErrors->getAllAsString(),
                        ]), 422);
                    }
                }
            }
            $transaction->commit();
            $success = true;
        } catch (Exception $e) {
            $transaction->rollback();
            $error      = $e->getMessage();
            $returnCode = $e->getCode();
        }

        if (!$success) {
            $this->renderJson([
                'status'    => 'error',
                'error'     => $error,
            ], (int)$returnCode);
            return;
        }

        $this->renderJson([
            'status'     => 'success',
            'email_uid'  => $email->email_uid,
        ], (int)$returnCode);
    }

    /**
     * Handles deleting an existing transactional email.
     *
     * @param string $email_uid
     *
     * @return void
     * @throws CDbException
     * @throws CException
     */
    public function actionDelete($email_uid)
    {
        if (!request()->getIsDeleteRequest()) {
            $this->renderJson([
                'status'    => 'error',
                'error'     => t('api', 'Only DELETE requests allowed for this endpoint.'),
            ], 400);
            return;
        }

        $email = TransactionalEmail::model()->findByAttributes([
            'email_uid'   => $email_uid,
            'customer_id' => (int)user()->getId(),
        ]);

        if (empty($email)) {
            $this->renderJson([
                'status'    => 'error',
                'error'     => t('api', 'The email does not exist.'),
            ], 404);
            return;
        }

        $email->delete();

        // since 1.3.5.9
        hooks()->doAction('controller_action_delete_data', $collection = new CAttributeCollection([
            'controller' => $this,
            'model'      => $email,
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
                     FROM `{{transactional_email}}` `a`
                     WHERE `a`.`customer_id` = :cid
                     ORDER BY a.`email_id` DESC
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
            $sql = 'SELECT UNIX_TIMESTAMP(t.last_updated) as `timestamp` FROM `{{transactional_email}}` t WHERE `t`.`email_uid` = :uid AND `t`.`customer_id` = :cid LIMIT 1';
            $command = db()->createCommand($sql);
            $command->bindValue(':uid', request()->getQuery('email_uid'), PDO::PARAM_STR);
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
