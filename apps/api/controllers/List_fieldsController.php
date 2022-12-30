<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}
/**
 * List_fieldsController
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */
class List_fieldsController extends Controller
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
     * Handles the listing of the email list custom fields.
     *
     * @param string $list_uid
     *
     * @return void
     * @throws CException
     */
    public function actionIndex($list_uid)
    {
        $list = Lists::model()->findByAttributes([
            'list_uid'      => $list_uid,
            'customer_id'   => (int)user()->getId(),
        ]);

        if (empty($list)) {
            $this->renderJson([
                'status'    => 'error',
                'error'     => t('api', 'The subscribers list does not exist.'),
            ], 404);
            return;
        }

        $fields = ListField::model()->findAllByAttributes([
            'list_id'    => (int)$list->list_id,
        ]);

        if (empty($fields)) {
            $this->renderJson([
                'status'    => 'error',
                'error'     => t('api', 'The subscribers list does not have any custom field defined.'),
            ], 404);
            return;
        }

        $data = [
            'records' => [],
        ];

        foreach ($fields as $field) {
            $attributes         = $field->getAttributes(['tag', 'label', 'required', 'help_text']);
            $attributes['type'] = $field->type->getAttributes(['name', 'identifier', 'description']);

            // since 1.3.6.2
            if (!empty($field->options)) {
                $attributes['options'] = [];
                foreach ($field->options as $option) {
                    $attributes['options'][$option->value] = $option->name;
                }
            }

            $data['records'][]  = $attributes;
        }

        $this->renderJson([
            'status'    => 'success',
            'data'      => $data,
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
            $listUid = request()->getQuery('list_uid');

            $sql = '
                SELECT l.list_id, AVG(UNIX_TIMESTAMP(f.last_updated)) as `timestamp` 
                    FROM {{list}} l
                INNER JOIN {{list_field}} f ON f.list_id = l.list_id 
                WHERE l.list_uid = :uid AND l.customer_id = :cid
                GROUP BY l.list_id 
            ';
            $command = db()->createCommand($sql);
            $command->bindValue(':uid', $listUid, PDO::PARAM_STR);
            $command->bindValue(':cid', (int)user()->getId(), PDO::PARAM_INT);

            $row = $command->queryRow();
        }

        if (isset($row['timestamp'])) {
            $timestamp = round((float)$row['timestamp']);
            // avoid for when subscribers imported having same timestamp
            if (preg_match('/\.(\d+)/', (string)$row['timestamp'], $matches)) {
                $timestamp += (int)$matches[1];
            }
            return $lastModified = (int)$timestamp;
        }

        return $lastModified = parent::generateLastModified();
    }
}
