<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * ListsController
 *
 * Handles the CRUD actions for lists.
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

class ListsController extends Controller
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
     * Handles the listing of the email lists.
     * The listing is based on page number and number of lists per page.
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
        $criteria->addNotInCondition('status', [Lists::STATUS_PENDING_DELETE, Lists::STATUS_ARCHIVED]);

        $count = Lists::model()->count($criteria);

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

        $criteria->order    = 't.list_id DESC';
        $criteria->limit    = $perPage;
        $criteria->offset   = ($page - 1) * $perPage;

        $lists = Lists::model()->findAll($criteria);

        foreach ($lists as $list) {
            $defaults       = [];
            $notifications  = [];
            $company        = [];
            $general        = $list->getAttributes(['list_uid', 'name', 'display_name', 'description']);
            if (!empty($list->default)) {
                $defaults = $list->default->getAttributes(['from_email', 'from_name', 'reply_to', 'subject']);
            }
            if (!empty($list->customerNotification)) {
                $notifications = $list->customerNotification->getAttributes(['subscribe', 'unsubscribe', 'subscribe_to', 'unsubscribe_to']);
            }
            if (!empty($list->company)) {
                $company = $list->company->getAttributes(['name', 'address_1', 'address_2', 'zone_name', 'city', 'zip_code', 'phone', 'address_format']);
                if (!empty($list->company->country)) {
                    $company['country'] = $list->company->country->getAttributes(['country_id', 'name', 'code']);
                }
                if (!empty($list->company->zone)) {
                    $company['zone'] = $list->company->zone->getAttributes(['zone_id', 'name', 'code']);
                }
            }
            $record = [
                'general'       => $general,
                'defaults'      => $defaults,
                'notifications' => $notifications,
                'company'       => $company,
            ];
            $data['records'][] = $record;
        }

        $this->renderJson([
            'status'    => 'success',
            'data'      => $data,
        ]);
    }

    /**
     * Handles the listing of a single email list.
     *
     * @param string $list_uid
     *
     * @return void
     * @throws CException
     */
    public function actionView($list_uid)
    {
        /** @var Lists|null $list */
        $list = $this->loadListByUid($list_uid);

        if (empty($list)) {
            $this->renderJson([
                'status'    => 'error',
                'error'     => t('api', 'The list does not exist.'),
            ], 404);
            return;
        }

        $defaults       = [];
        $notifications  = [];
        $company        = [];
        $general        = $list->getAttributes(['list_uid', 'name', 'display_name', 'description']);
        if (!empty($list->default)) {
            $defaults = $list->default->getAttributes(['from_email', 'from_name', 'reply_to', 'subject']);
        }
        if (!empty($list->customerNotification)) {
            $notifications = $list->customerNotification->getAttributes(['subscribe', 'unsubscribe', 'subscribe_to', 'unsubscribe_to']);
        }
        if (!empty($list->company)) {
            $company = $list->company->getAttributes(['name', 'address_1', 'address_2', 'zone_name', 'city', 'zip_code', 'phone', 'address_format']);
            if (!empty($list->company->country)) {
                $company['country'] = $list->company->country->getAttributes(['country_id', 'name', 'code']);
            }
            if (!empty($list->company->zone)) {
                $company['zone'] = $list->company->zone->getAttributes(['zone_id', 'name', 'code']);
            }
        }

        $record = [
            'general'       => $general,
            'defaults'      => $defaults,
            'notifications' => $notifications,
            'company'       => $company,
        ];

        $data = ['record' => $record];

        $this->renderJson([
            'status'    => 'success',
            'data'      => $data,
        ]);
    }

    /**
     * Handles the creation of a new email list.
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

        $general        = (array)request()->getPost('general', []);
        $defaults       = (array)request()->getPost('defaults', []);
        $notifications  = (array)request()->getPost('notifications', []);
        $company        = (array)request()->getPost('company', []);

        /** @var Customer $customer */
        $customer = user()->getModel();

        if (($maxLists = (int)$customer->getGroupOption('lists.max_lists', -1)) > -1) {
            $criteria = new CDbCriteria();
            $criteria->compare('customer_id', (int)$customer->customer_id);
            $criteria->addNotInCondition('status', [Lists::STATUS_PENDING_DELETE]);
            $listsCount = Lists::model()->count($criteria);
            if ($listsCount >= $maxLists) {
                $this->renderJson([
                    'status'    => 'error',
                    'error'     => t('api', 'You have reached the maximum number of allowed lists.'),
                ], 422);
                return;
            }
        }

        $listModel = new Lists();
        $listModel->attributes = $general;

        // since 1.9.11
        if (($forceOptIn = (string)$customer->getGroupOption('lists.force_optin_process', '')) && in_array($forceOptIn, array_keys($listModel->getOptInArray()))) {
            $listModel->opt_in = $forceOptIn;
        }
        if (($forceOptOut = (string)$customer->getGroupOption('lists.force_optout_process', '')) && in_array($forceOptOut, array_keys($listModel->getOptOutArray()))) {
            $listModel->opt_out = $forceOptOut;
        }
        //

        if (!$listModel->validate()) {
            $this->renderJson([
                'status'    => 'error',
                'error'     => [
                    'general' => $listModel->shortErrors->getAll(),
                ],
            ], 422);
            return;
        }

        $defaultsModel = new ListDefault();
        $defaultsModel->attributes = $defaults;
        if (!$defaultsModel->validate()) {
            $this->renderJson([
                'status'    => 'error',
                'error'     => [
                    'defaults' => $defaultsModel->shortErrors->getAll(),
                ],
            ], 422);
            return;
        }

        $notificationsModel = new ListCustomerNotification();
        $notificationsModel->attributes = $notifications;
        if (!$notificationsModel->validate()) {
            $this->renderJson([
                'status'    => 'error',
                'error'     => [
                    'notifications' => $notificationsModel->shortErrors->getAll(),
                ],
            ], 422);
            return;
        }

        $companyModel = new ListCompany();
        if (!empty($customer->company)) {
            $companyModel->mergeWithCustomerCompany($customer->company);
        }

        if (isset($company['country'])) {
            if (empty($company['country_id'])) {
                $country = Country::model()->findByAttributes(['name' => $company['country']]);
                if (!empty($country)) {
                    $company['country_id'] = (int)$country->country_id;
                }
            }
            unset($company['country']);
        }

        if (isset($company['zone'])) {
            if (isset($company['country_id'])) {
                $zone = Zone::model()->findByAttributes([
                    'country_id'    => $company['country_id'],
                    'name'          => $company['zone'],
                ]);
                if (!empty($zone)) {
                    $company['zone_id'] = (int)$zone->zone_id;
                }
            }
            unset($company['zone']);
        }

        $companyModel->attributes = $company;
        if (!$companyModel->validate()) {
            $this->renderJson([
                'status' => 'error',
                'error'  => [
                    'company' => $companyModel->shortErrors->getAll(),
                ],
            ], 422);
            return;
        }

        // at this point there should be no more errors.
        $listModel->customer_id = (int)$customer->customer_id;
        $listModel->attachBehavior('listDefaultFields', [
            'class' => 'customer.components.db.behaviors.ListDefaultFieldsBehavior',
        ]);

        $models = [$listModel, $defaultsModel, $notificationsModel, $companyModel];

        foreach ($models as $model) {
            if (!($model instanceof Lists)) {
                $model->list_id = (int)$listModel->list_id;
            }
            $model->save(false);
        }

        /** @var CustomerActionLogBehavior $logAction */
        $logAction = $customer->getLogAction();
        $logAction->listCreated($listModel);

        $this->renderJson([
            'status'    => 'success',
            'list_uid'  => $listModel->list_uid,
        ], 201);
    }

    /**
     * Handles the updating of an existing email list.
     *
     * @param string $list_uid
     *
     * @return void
     * @throws CException
     */
    public function actionUpdate($list_uid)
    {
        if (!request()->getIsPutRequest()) {
            $this->renderJson([
                'status'    => 'error',
                'error'     => t('api', 'Only PUT requests allowed for this endpoint.'),
            ], 400);
            return;
        }

        /** @var Lists|null $listModel */
        $listModel = $this->loadListByUid($list_uid);

        if (empty($listModel)) {
            $this->renderJson([
                'status'    => 'error',
                'error'     => t('api', 'The list does not exist.'),
            ], 404);
            return;
        }

        $general        = (array)request()->getPut('general', []);
        $defaults       = (array)request()->getPut('defaults', []);
        $notifications  = (array)request()->getPut('notifications', []);
        $company        = (array)request()->getPut('company', []);

        /** @var Customer $customer */
        $customer = user()->getModel();

        $listModel->attributes = $general;

        // since 1.9.11
        if (($forceOptIn = (string)$customer->getGroupOption('lists.force_optin_process', '')) && in_array($forceOptIn, array_keys($listModel->getOptInArray()))) {
            $listModel->opt_in = $forceOptIn;
        }
        if (($forceOptOut = (string)$customer->getGroupOption('lists.force_optout_process', '')) && in_array($forceOptOut, array_keys($listModel->getOptOutArray()))) {
            $listModel->opt_out = $forceOptOut;
        }
        //

        if (!$listModel->validate()) {
            $this->renderJson([
                'status'    => 'error',
                'error'     => [
                    'general' => $listModel->shortErrors->getAll(),
                ],
            ], 422);
            return;
        }

        $defaultsModel = !empty($listModel->default) ? $listModel->default : new ListDefault();
        $defaultsModel->attributes = $defaults;
        if (!$defaultsModel->validate()) {
            $this->renderJson([
                'status'    => 'error',
                'error'     => [
                    'defaults' => $defaultsModel->shortErrors->getAll(),
                ],
            ], 422);
            return;
        }

        $notificationsModel = !empty($listModel->customerNotification) ? $listModel->customerNotification : new ListCustomerNotification();
        $notificationsModel->attributes = $notifications;
        if (!$notificationsModel->validate()) {
            $this->renderJson([
                'status'    => 'error',
                'error'     => [
                    'notifications' => $notificationsModel->shortErrors->getAll(),
                ],
            ], 422);
            return;
        }

        $companyModel = !empty($listModel->company) ? $listModel->company : new ListCompany();
        if (!empty($customer->company)) {
            $companyModel->mergeWithCustomerCompany($customer->company);
        }

        if (isset($company['country'])) {
            if (empty($company['country_id'])) {
                $country = Country::model()->findByAttributes(['name' => $company['country']]);
                if (!empty($country)) {
                    $company['country_id'] = (int)$country->country_id;
                }
            }
            unset($company['country']);
        }

        if (isset($company['zone'])) {
            if (isset($company['country_id'])) {
                $zone = Zone::model()->findByAttributes([
                    'country_id'    => $company['country_id'],
                    'name'          => $company['zone'],
                ]);
                if (!empty($zone)) {
                    $company['zone_id'] = (int)$zone->zone_id;
                }
            }
            unset($company['zone']);
        }

        $companyModel->attributes = $company;
        if (!$companyModel->validate()) {
            $this->renderJson([
                'status'    => 'error',
                'error'     => [
                    'company' => $companyModel->shortErrors->getAll(),
                ],
            ], 422);
        }

        // at this point there should be no more errors.
        $models = [$listModel, $defaultsModel, $notificationsModel, $companyModel];

        foreach ($models as $model) {
            if (!($model instanceof Lists)) {
                $model->list_id = (int)$listModel->list_id;
            }
            $model->save(false);
        }

        /** @var CustomerActionLogBehavior $logAction */
        $logAction = $customer->getLogAction();
        $logAction->listUpdated($listModel);

        $this->renderJson([
            'status' => 'success',
        ]);
    }

    /**
     * Handles copying of an existing email list.
     *
     * @param string $list_uid
     *
     * @return void
     * @throws CException
     */
    public function actionCopy($list_uid)
    {
        if (!request()->getIsPostRequest()) {
            $this->renderJson([
                'status'    => 'error',
                'error'     => t('api', 'Only POST requests allowed for this endpoint.'),
            ], 400);
        }

        /** @var Lists|null $list */
        $list = $this->loadListByUid($list_uid);

        if (empty($list)) {
            $this->renderJson([
                'status' => 'error',
                'error'  => t('api', 'The list does not exist.'),
            ], 404);
            return;
        }

        /** @var Lists|null $newList */
        $newList = $list->copy();

        if (empty($newList)) {
            $this->renderJson([
                'status' => 'error',
                'error'  => t('api', 'Unable to copy the list.'),
            ], 422);
            return;
        }

        $this->renderJson([
            'status'   => 'success',
            'list_uid' => $newList->list_uid,
        ], 201);
    }

    /**
     * Handles deleting of an existing email list.
     *
     * @param string $list_uid
     *
     * @return void
     * @throws CDbException
     * @throws CException
     */
    public function actionDelete($list_uid)
    {
        if (!request()->getIsDeleteRequest()) {
            $this->renderJson([
                'status'    => 'error',
                'error'     => t('api', 'Only DELETE requests allowed for this endpoint.'),
            ], 400);
        }

        /** @var Lists|null $list */
        $list = $this->loadListByUid($list_uid);

        if (empty($list)) {
            $this->renderJson([
                'status'    => 'error',
                'error'     => t('api', 'The list does not exist.'),
            ], 404);
            return;
        }

        $list->delete();

        /** @var Customer $customer */
        $customer = user()->getModel();

        /** @var CustomerActionLogBehavior $logAction */
        $logAction = $customer->getLogAction();
        $logAction->listDeleted($list);

        // since 1.3.5.9
        hooks()->doAction('controller_action_delete_data', $collection = new CAttributeCollection([
            'controller' => $this,
            'model'      => $list,
        ]));

        $this->renderJson([
            'status' => 'success',
        ]);
    }

    /**
     * @param string $list_uid
     *
     * @return Lists|null
     */
    public function loadListByUid(string $list_uid): ?Lists
    {
        $criteria = new CDbCriteria();
        $criteria->compare('list_uid', $list_uid);
        $criteria->compare('customer_id', (int)user()->getId());
        $criteria->addNotInCondition('status', [Lists::STATUS_PENDING_DELETE, Lists::STATUS_ARCHIVED]);
        return Lists::model()->find($criteria);
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
                     SELECT `a`.`customer_id`, `a`.`status`, UNIX_TIMESTAMP(`a`.`last_updated`) as `last_updated`
                     FROM `{{list}}` `a`
                     WHERE `a`.`customer_id` = :cid AND `a`.`status` = :st
                     ORDER BY a.`list_id` DESC
                     LIMIT :l OFFSET :o
                ) AS t
                WHERE `t`.`customer_id` = :cid AND `t`.`status` = :st
            ';

            $command = db()->createCommand($sql);
            $command->bindValue(':cid', (int)user()->getId(), PDO::PARAM_INT);
            $command->bindValue(':l', (int)$limit, PDO::PARAM_INT);
            $command->bindValue(':o', (int)$offset, PDO::PARAM_INT);
            $command->bindValue(':st', Lists::STATUS_ACTIVE, PDO::PARAM_STR);

            $row = $command->queryRow();
        } elseif ($this->getAction()->getId() == 'view') {
            $sql = 'SELECT UNIX_TIMESTAMP(t.last_updated) as `timestamp` FROM `{{list}}` t WHERE `t`.`list_uid` = :uid AND `t`.`customer_id` = :cid AND `t`.`status` = :st LIMIT 1';
            $command = db()->createCommand($sql);
            $command->bindValue(':uid', request()->getQuery('list_uid'), PDO::PARAM_STR);
            $command->bindValue(':cid', (int)user()->getId(), PDO::PARAM_INT);
            $command->bindValue(':st', Lists::STATUS_ACTIVE, PDO::PARAM_STR);

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
