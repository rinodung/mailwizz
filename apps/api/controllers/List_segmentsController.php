<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * List_segmentsController
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

class List_segmentsController extends Controller
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
     * Handles the listing of the email list segments.
     * The listing is based on page number and number of list segments per page.
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

        $perPage = (int)request()->getQuery('per_page', 10);
        $page    = (int)request()->getQuery('page', 1);

        $maxPerPage    = 50;
        $minPerPage    = 10;

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
        $criteria->compare('t.list_id', (int)$list->list_id);
        $criteria->addNotInCondition('status', [ListSegment::STATUS_PENDING_DELETE]);

        $count = ListSegment::model()->count($criteria);

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

        $criteria->order    = 't.segment_id DESC';
        $criteria->limit    = $perPage;
        $criteria->offset   = ($page - 1) * $perPage;

        $segments = ListSegment::model()->findAll($criteria);

        foreach ($segments as $segment) {
            $record = $segment->getAttributes(['segment_uid', 'name']);
            $record['subscribers_count'] = $segment->countSubscribers();
            $data['records'][] = $record;
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
            $listUid    = request()->getQuery('list_uid');
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

            $list = Lists::model()->findByAttributes([
                'list_uid'      => $listUid,
                'customer_id'   => (int)user()->getId(),
            ]);

            if (empty($list)) {
                return $lastModified = parent::generateLastModified();
            }

            $limit  = $perPage;
            $offset = ($page - 1) * $perPage;

            $sql = '
                SELECT AVG(t.last_updated) as `timestamp`
                FROM (
                     SELECT `a`.`list_id`, UNIX_TIMESTAMP(`a`.`last_updated`) as `last_updated`
                     FROM `{{list_segment}}` `a` 
                     WHERE `a`.`list_id` = :lid 
                     ORDER BY a.`segment_id` DESC 
                     LIMIT :l OFFSET :o
                ) AS t 
                WHERE `t`.`list_id` = :lid
            ';

            $command = db()->createCommand($sql);
            $command->bindValue(':lid', (int)$list->list_id, PDO::PARAM_INT);
            $command->bindValue(':l', (int)$limit, PDO::PARAM_INT);
            $command->bindValue(':o', (int)$offset, PDO::PARAM_INT);

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
