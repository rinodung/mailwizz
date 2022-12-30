<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * Delivery_serversController
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.9.17
 */
class Delivery_serversController extends Controller
{
    /**
     * @var array
     */
    public $cacheableActions = ['index', 'view'];

    /**
     * access rules for this controller
     *
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
     * Handles the listing of the delivery servers.
     * The listing is based on page number and number of servers per page.
     * This action will produce a valid ETAG for caching purposes.
     *
     * @return void
     * @throws CException
     */
    public function actionIndex()
    {
        $perPage = (int)request()->getQuery('per_page', 10);
        $page    = (int)request()->getQuery('page', 1);
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
            'count'        => null,
            'total_pages'  => null,
            'current_page' => null,
            'next_page'    => null,
            'prev_page'    => null,
            'records'      => [],
        ];

        $criteria = new CDbCriteria();
        $criteria->select = 't.server_id, t.type, t.name, t.hostname, t.status';
        $criteria->compare('t.customer_id', (int)user()->getId());
        $count = DeliveryServer::model()->count($criteria);

        if ($count == 0) {
            $this->renderJson([
                'status' => 'success',
                'data'   => $data,
            ]);
            return;
        }

        $totalPages = ceil($count / $perPage);

        $data['count']        = $count;
        $data['current_page'] = $page;
        $data['next_page']    = $page < $totalPages ? $page + 1 : null;
        $data['prev_page']    = $page > 1 ? $page - 1 : null;
        $data['total_pages']  = $totalPages;

        $criteria->order  = 't.name ASC';
        $criteria->limit  = $perPage;
        $criteria->offset = ($page - 1) * $perPage;

        $servers = DeliveryServer::model()->findAll($criteria);

        foreach ($servers as $server) {
            $record = $server->getAttributes(['server_id', 'type', 'name', 'hostname', 'status']);
            $data['records'][] = $record;
        }

        $this->renderJson([
            'status' => 'success',
            'data'   => $data,
        ]);
    }

    /**
     * Handles the listing of a single delivery server.
     * This action will produce a valid ETAG for caching purposes.
     *
     * @param int $server_id
     *
     * @return void
     * @throws CException
     */
    public function actionView(int $server_id)
    {
        /** @var DeliveryServer|null $server */
        $server = $this->loadServerById((int)$server_id);
        if (empty($server)) {
            $this->renderJson([
                'status' => 'error',
                'error'  => t('api', 'The server does not exist.'),
            ], 404);
            return;
        }

        $recordData = $server->getAttributes(['server_id', 'type', 'name', 'hostname', 'status']);

        $data = ['record' => $recordData];

        $this->renderJson([
            'status' => 'success',
            'data'   => $data,
        ]);
    }

    /**
     * It will generate the timestamp that will be used to generate the ETAG for GET requests.
     *
     * @return float|int
     * @throws CException
     */
    public function generateLastModified()
    {
        static $lastModified;

        if ($lastModified !== null) {
            return $lastModified;
        }

        $row = [];

        if ($this->getAction()->getId() === 'index') {
            $perPage = (int)request()->getQuery('per_page', 10);
            $page    = (int)request()->getQuery('page', 1);

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
                FROM `{{delivery_server}}` `a` 
                WHERE `a`.`customer_id` = :cid 
                ORDER BY `a`.`name` ASC 
                LIMIT :l OFFSET :o
                ) AS t 
                WHERE `t`.`customer_id` = :cid
            ';

            $command = db()->createCommand($sql);
            $command->bindValue(':cid', (int)user()->getId(), PDO::PARAM_INT);
            $command->bindValue(':l', (int)$limit, PDO::PARAM_INT);
            $command->bindValue(':o', (int)$offset, PDO::PARAM_INT);

            $row = $command->queryRow();
        } elseif ($this->getAction()->getId() === 'view') {
            $sql = 'SELECT UNIX_TIMESTAMP(t.last_updated) as `timestamp` FROM `{{delivery_server}}` t WHERE `t`.`server_id` = :id AND `t`.`customer_id` = :cid LIMIT 1';
            $command = db()->createCommand($sql);
            $command->bindValue(':id', request()->getQuery('server_id'), PDO::PARAM_STR);
            $command->bindValue(':cid', (int)user()->getId(), PDO::PARAM_INT);

            $row = $command->queryRow();
        }

        if (isset($row['timestamp'])) {
            $timestamp = round((float)$row['timestamp']);
            if (preg_match('/\.(\d+)/', (string)$row['timestamp'], $matches)) {
                $timestamp += (int)$matches[1];
            }
            return $lastModified = $timestamp;
        }

        return $lastModified = parent::generateLastModified();
    }

    /**
     * @param int $server_id
     * @return null|DeliveryServer
     */
    public function loadServerById(int $server_id): ?DeliveryServer
    {
        $criteria = new CDbCriteria();
        $criteria->compare('server_id', $server_id);
        $criteria->compare('customer_id', (int)user()->getId());
        return DeliveryServer::model()->find($criteria);
    }
}
