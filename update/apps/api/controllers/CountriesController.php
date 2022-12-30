<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * CountriesController
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

class CountriesController extends Controller
{
    /**
     * @var array
     */
    public $cacheableActions = ['index', 'zones'];

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
     * Handles the listing of the countries.
     * The listing is based on page number and number of countries per page.
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
        $criteria->select = 't.country_id, t.name, t.code';
        $count = Country::model()->count($criteria);

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

        $criteria->order = 't.name ASC';
        $criteria->limit = $perPage;
        $criteria->offset= ($page - 1) * $perPage;

        $countries = Country::model()->findAll($criteria);

        foreach ($countries as $country) {
            $record = $country->getAttributes(['country_id', 'name', 'code']);
            $data['records'][] = $record;
        }

        $this->renderJson([
            'status'    => 'success',
            'data'      => $data,
        ]);
    }

    /**
     * Handles the listing of the country zones.
     * The listing is based on page number and number of zones per page.
     *
     * @param int $country_id
     *
     * @return void
     * @throws CException
     */
    public function actionZones($country_id)
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
        $criteria->select = 't.zone_id, t.name, t.code';
        $criteria->compare('country_id', (int)$country_id);
        $count = Zone::model()->count($criteria);

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

        $criteria->order    = 't.name ASC';
        $criteria->limit    = $perPage;
        $criteria->offset   = ($page - 1) * $perPage;

        $zones = Zone::model()->findAll($criteria);

        foreach ($zones as $zone) {
            $record = $zone->getAttributes(['zone_id', 'name', 'code']);
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

        $country_id = 0;
        $limit      = 0;
        $offset     = 0;
        $row        = [];

        if ($this->getAction()->getId() == 'index' || $this->getAction()->getId() == 'zones') {
            $country_id = (int)request()->getQuery('country_id');
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
        }

        if ($this->getAction()->getId() == 'index') {
            $sql = '
                SELECT AVG(t.last_updated) as `timestamp`
                FROM (
                SELECT UNIX_TIMESTAMP(`a`.`last_updated`) as `last_updated`
                FROM `{{country}}` `a` 
                WHERE 1 
                ORDER BY a.`name` ASC 
                LIMIT :l OFFSET :o
                ) AS t 
                WHERE 1
            ';

            $command = db()->createCommand($sql);
            $command->bindValue(':l', (int)$limit, PDO::PARAM_INT);
            $command->bindValue(':o', (int)$offset, PDO::PARAM_INT);

            $row = $command->queryRow();
        } elseif ($this->getAction()->getId() == 'zones') {
            $sql = '
                SELECT AVG(t.last_updated) as `timestamp`
                FROM (
                SELECT `a`.`country_id`, UNIX_TIMESTAMP(`a`.`last_updated`) as `last_updated`
                FROM `{{zone}}` `a` 
                WHERE `a`.`country_id` = :cid
                ORDER BY a.`name` ASC 
                LIMIT :l OFFSET :o
                ) AS t 
                WHERE `t`.`country_id` = :cid
            ';

            $command = db()->createCommand($sql);
            $command->bindValue(':cid', (int)$country_id, PDO::PARAM_INT);
            $command->bindValue(':l', (int)$limit, PDO::PARAM_INT);
            $command->bindValue(':o', (int)$offset, PDO::PARAM_INT);

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
