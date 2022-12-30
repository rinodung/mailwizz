<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * List_subscribersController
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

class List_subscribersController extends Controller
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
     * Handles the listing of the email list subscribers.
     * The listing is based on page number and number of subscribers per page.
     *
     * @param string $list_uid
     *
     * @return void
     * @throws CException
     */
    public function actionIndex($list_uid)
    {
        $criteria = new CDbCriteria();
        $criteria->compare('list_uid', $list_uid);
        $criteria->compare('customer_id', (int)user()->getId());
        $criteria->addNotInCondition('status', [Lists::STATUS_PENDING_DELETE, Lists::STATUS_ARCHIVED]);
        $list = Lists::model()->find($criteria);

        if (empty($list)) {
            $this->renderJson([
                'status'    => 'error',
                'error'     => t('api', 'The subscribers list does not exist.'),
            ], 404);
            return;
        }

        $criteria = new CDbCriteria();
        $criteria->compare('list_id', (int)$list->list_id);
        $criteria->order = 'sort_order ASC';
        $fields = ListField::model()->findAll($criteria);

        if (empty($fields)) {
            $this->renderJson([
                'status'    => 'error',
                'error'     => t('api', 'The subscribers list does not have any custom field defined.'),
            ], 404);
            return;
        }

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
        $criteria->compare('t.list_id', (int)$list->list_id);

        // since 1.9.15
        $status = request()->getQuery('status', '');
        $allowedStatuses = array_keys(ListSubscriber::model()->getFilterStatusesList());
        if (!empty($status) && in_array($status, $allowedStatuses)) {
            $criteria->compare('t.status', $status);
        }

        $count = ListSubscriber::model()->count($criteria);

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

        $criteria->order    = 't.subscriber_id DESC';
        $criteria->limit    = $perPage;
        $criteria->offset   = ($page - 1) * $perPage;

        $subscribers = ListSubscriber::model()->findAll($criteria);

        foreach ($subscribers as $subscriber) {
            $record = ['subscriber_uid' => null]; // keep this first!
            foreach ($fields as $field) {
                if ($field->tag == 'EMAIL') {
                    $record[$field->tag] = $subscriber->getDisplayEmail();
                    continue;
                }

                $value = '';
                $criteria = new CDbCriteria();
                $criteria->select = 'value';
                $criteria->compare('field_id', (int)$field->field_id);
                $criteria->compare('subscriber_id', (int)$subscriber->subscriber_id);
                $valueModels = ListFieldValue::model()->findAll($criteria);
                if (!empty($valueModels)) {
                    $value = [];
                    foreach ($valueModels as $valueModel) {
                        $value[] = $valueModel->value;
                    }
                    $value = implode(', ', $value);
                }
                $record[$field->tag] = $value;
            }

            $record['subscriber_uid']   = (string)$subscriber->subscriber_uid;
            $record['status']           = $subscriber->status;
            $record['source']           = $subscriber->source;
            $record['ip_address']       = $subscriber->ip_address;
            $record['date_added']       = $subscriber->date_added;

            $data['records'][] = $record;
        }

        $this->renderJson([
            'status'    => 'success',
            'data'      => $data,
        ]);
    }

    /**
     * Handles the listing of a single subscriber from a list.
     *
     * @param string $list_uid
     * @param string $subscriber_uid
     *
     * @return void
     * @throws CException
     */
    public function actionView($list_uid, $subscriber_uid)
    {
        /** @var Lists|null $list */
        $list = $this->loadListByUid($list_uid);

        if (empty($list)) {
            $this->renderJson([
                'status'    => 'error',
                'error'     => t('api', 'The subscribers list does not exist.'),
            ], 404);
            return;
        }

        /** @var ListSubscriber|null $subscriber */
        $subscriber = ListSubscriber::model()->findByAttributes([
            'subscriber_uid'    => $subscriber_uid,
            'list_id'           => (int)$list->list_id,
        ]);
        if (empty($subscriber)) {
            $this->renderJson([
                'status'    => 'error',
                'error'     => t('api', 'The subscriber does not exist in this list.'),
            ], 404);
            return;
        }

        /** @var ListField[] $fields */
        $fields = ListField::model()->findAllByAttributes([
            'list_id' => (int)$list->list_id,
        ]);

        if (empty($fields)) {
            $this->renderJson([
                'status'    => 'error',
                'error'     => t('api', 'The subscribers list does not have any custom field defined.'),
            ], 404);
            return;
        }

        $data = [
            'record' => [
                'subscriber_uid' => null,
                'status'         => null,
                'source'         => null,
                'ip_address'     => null,
            ],
        ];

        foreach ($fields as $field) {
            if ($field->tag == 'EMAIL') {
                $data['record'][$field->tag] = $subscriber->getDisplayEmail();
                continue;
            }

            $value = '';
            $criteria = new CDbCriteria();
            $criteria->select = 'value';
            $criteria->compare('field_id', (int)$field->field_id);
            $criteria->compare('subscriber_id', (int)$subscriber->subscriber_id);
            $valueModels = ListFieldValue::model()->findAll($criteria);
            if (!empty($valueModels)) {
                $value = [];
                foreach ($valueModels as $valueModel) {
                    $value[] = $valueModel->value;
                }
                $value = implode(', ', $value);
            }

            $data['record'][$field->tag] = $value;
        }

        $data['record']['subscriber_uid'] = (string)$subscriber->subscriber_uid;
        $data['record']['status']         = $subscriber->status;
        $data['record']['source']         = $subscriber->source;
        $data['record']['ip_address']     = $subscriber->ip_address;

        $this->renderJson([
            'status'    => 'success',
            'data'      => $data,
        ]);
    }

    /**
     * Handles the creation of a new subscriber for a certain email list.
     *
     * @param string $list_uid
     *
     * @return void
     * @throws CException
     * @throws Throwable
     */
    public function actionCreate($list_uid)
    {
        if (!request()->getIsPostRequest()) {
            $this->renderJson([
                'status'    => 'error',
                'error'     => t('api', 'Only POST requests allowed for this endpoint.'),
            ], 400);
            return;
        }

        $email = (string)request()->getPost('EMAIL', '');
        if (empty($email)) {
            $this->renderJson([
                'status'    => 'error',
                'error'     => t('api', 'Please provide the subscriber email address.'),
            ], 422);
            return;
        }

        $validator = new CEmailValidator();
        $validator->allowEmpty  = false;
        $validator->validateIDN = true;

        if (!$validator->validateValue($email)) {
            $this->renderJson([
                'status'    => 'error',
                'error'     => t('api', 'Please provide a valid email address.'),
            ], 422);
            return;
        }

        /** @var Lists|null $list */
        $list = $this->loadListByUid($list_uid);

        if (empty($list)) {
            $this->renderJson([
                'status'    => 'error',
                'error'     => t('api', 'The subscribers list does not exist.'),
            ], 404);
            return;
        }

        $mutexKey = sha1(__METHOD__ . ':' . $list->list_uid . ':' . date('YmdH') . ':' . $email);
        if (!mutex()->acquire($mutexKey)) {
            $this->renderJson([
                'status'    => 'error',
                'error'     => t('api', 'Unable to acquire the mutex lock, please try again.'),
            ], 409);
            return;
        }

        /** @var Customer $customer */
        $customer = $list->customer;

        $maxSubscribersPerList   = (int)$customer->getGroupOption('lists.max_subscribers_per_list', -1);
        $maxSubscribers          = (int)$customer->getGroupOption('lists.max_subscribers', -1);

        if ($maxSubscribers > -1 || $maxSubscribersPerList > -1) {
            $criteria = new CDbCriteria();
            $criteria->select = 'COUNT(DISTINCT(t.email)) as counter';

            if ($maxSubscribers > -1 && ($listsIds = $customer->getAllListsIds())) {
                $criteria->addInCondition('t.list_id', $listsIds);
                $totalSubscribersCount = ListSubscriber::model()->count($criteria);
                if ($totalSubscribersCount >= $maxSubscribers) {
                    mutex()->release($mutexKey);
                    $this->renderJson([
                        'status'    => 'error',
                        'error'     => t('lists', 'The maximum number of allowed subscribers has been reached.'),
                    ], 409);
                    return;
                }
            }

            if ($maxSubscribersPerList > -1) {
                $criteria->compare('t.list_id', (int)$list->list_id);
                $listSubscribersCount = ListSubscriber::model()->count($criteria);
                if ($listSubscribersCount >= $maxSubscribersPerList) {
                    mutex()->release($mutexKey);
                    $this->renderJson([
                        'status'    => 'error',
                        'error'     => t('lists', 'The maximum number of allowed subscribers for this list has been reached.'),
                    ], 409);
                    return;
                }
            }
        }

        /** @var ListSubscriber|null $subscriber */
        $subscriber = ListSubscriber::model()->findByAttributes([
            'list_id'   => (int)$list->list_id,
            'email'     => $email,
        ]);

        // 1.6.6
        $stop = false;
        if (!empty($subscriber)) {
            $stop = true;
            if ($subscriber->getIsUnsubscribed()) {
                $stop = false;
            }
        }

        if ($stop) {
            mutex()->release($mutexKey);
            $this->renderJson([
                'status'    => 'error',
                'error'     => t('api', 'The subscriber already exists in this list.'),
            ], 409);
            return;
        }

        // 1.6.6
        if (empty($subscriber)) {
            $subscriber = new ListSubscriber();
        }

        $subscriber->list_id    = (int)$list->list_id;
        $subscriber->email      = $email;
        $subscriber->source     = ListSubscriber::SOURCE_API;
        // HTTP_X_MW_REMOTE_ADDR kept for BC
        $subscriber->ip_address = (string)request()->getServer('HTTP_X_MW_REMOTE_ADDR', (string)request()->getServer('HTTP_X_REMOTE_ADDR', (string)request()->getServer('REMOTE_ADDR')));

        if ($list->opt_in == Lists::OPT_IN_SINGLE) {
            $subscriber->status = ListSubscriber::STATUS_CONFIRMED;
        } else {
            $subscriber->status = ListSubscriber::STATUS_UNCONFIRMED;
        }

        $blacklisted = $subscriber->getIsBlacklisted(['checkZone' => EmailBlacklist::CHECK_ZONE_LIST_SUBSCRIBE]);
        if (!empty($blacklisted)) {
            mutex()->release($mutexKey);
            $this->renderJson([
                'status'    => 'error',
                'error'     => t('api', 'This email address is blacklisted.'),
            ], 409);
            return;
        }

        $fields = ListField::model()->findAllByAttributes([
            'list_id' => (int)$list->list_id,
        ]);

        if (empty($fields)) {
            mutex()->release($mutexKey);
            $this->renderJson([
                'status'    => 'error',
                'error'     => t('api', 'The subscribers list does not have any custom field defined.'),
            ], 404);
            return;
        }

        $errors = [];
        foreach ($fields as $field) {
            $value = request()->getPost($field->tag);
            if ($field->required == ListField::TEXT_YES && empty($value)) {
                $errors[$field->tag] = t('api', 'The field {field} is required by the list but it has not been provided!', [
                    '{field}' => $field->tag,
                ]);
            }
        }

        if (!empty($errors)) {
            mutex()->release($mutexKey);
            $this->renderJson([
                'status'    => 'error',
                'error'     => $errors,
            ], 422);
            return;
        }

        // since 1.3.5.7
        $details = (array)request()->getPost('details', []);
        if (!empty($details)) {
            $statuses   = array_keys($subscriber->getStatusesList());
            $statuses[] = ListSubscriber::STATUS_UNAPPROVED;
            $statuses[] = ListSubscriber::STATUS_BLACKLISTED; // 1.3.7.1
            $statuses   = array_unique($statuses);
            if (!empty($details['status']) && in_array($details['status'], $statuses)) {
                $subscriber->status = (string)$details['status'];
            }
            if (!empty($details['ip_address']) && FilterVarHelper::ip((string)$details['ip_address'])) {
                $subscriber->ip_address = (string)$details['ip_address'];
            }
            if (!empty($details['source']) && in_array($details['source'], array_keys($subscriber->getSourcesList()))) {
                $subscriber->source = (string)$details['source'];
            }
        }

        if (!$subscriber->save()) {
            mutex()->release($mutexKey);
            $this->renderJson([
                'status'    => 'error',
                'error'     => t('api', 'Unable to save the subscriber!'),
            ], 422);
            return;
        }

        // 1.3.7.1
        if ($subscriber->status == ListSubscriber::STATUS_BLACKLISTED) {
            $subscriber->addToBlacklist('Blacklisted via API');
        }

        $substr = CommonHelper::functionExists('mb_substr') ? 'mb_substr' : 'substr';

        foreach ($fields as $field) {

            // 1.8.1
            ListFieldValue::model()->deleteAllByAttributes([
                'subscriber_id' => (int)$subscriber->subscriber_id,
                'field_id'      => (int)$field->field_id,
            ]);

            $value = request()->getPost($field->tag, ListField::parseDefaultValueTags((string)$field->default_value, $subscriber));
            if (!is_array($value)) {
                $value = [$value];
            }
            $value = array_unique($value);

            foreach ($value as $val) {
                $valueModel                 = new ListFieldValue();
                $valueModel->field_id       = (int)$field->field_id;
                $valueModel->subscriber_id  = (int)$subscriber->subscriber_id;
                $valueModel->value          = $substr((string)$val, 0, 255);
                $valueModel->save();
            }
        }

        // since 1.3.6.2
        $this->handleListSubscriberMustApprove($list, $subscriber, $customer);

        mutex()->release($mutexKey);

        $this->renderJson([
            'status' => 'success',
            'data'   => [
                'record' => $subscriber->getAttributes(['subscriber_uid', 'email', 'ip_address', 'source', 'date_added']),
            ],
        ], 201);
    }

    /**
     * Handles the creation of bulk subscribers for a certain email list.
     *
     * @param string $list_uid
     *
     * @return void
     * @throws CException
     * @throws Throwable
     */
    public function actionCreate_bulk($list_uid)
    {
        if (!request()->getIsPostRequest()) {
            $this->renderJson([
                'status'    => 'error',
                'error'     => t('api', 'Only POST requests allowed for this endpoint.'),
            ], 400);
            return;
        }

        /** @var Lists|null $list */
        $list = $this->loadListByUid($list_uid);

        if (empty($list)) {
            $this->renderJson([
                'status'    => 'error',
                'error'     => t('api', 'The subscribers list does not exist.'),
            ], 404);
            return;
        }

        /** @var Customer $customer */
        $customer = $list->customer;

        $maxSubscribersPerList   = (int)$customer->getGroupOption('lists.max_subscribers_per_list', -1);
        $maxSubscribers          = (int)$customer->getGroupOption('lists.max_subscribers', -1);
        $totalSubscribersCount   = 0;
        $listSubscribersCount    = 0;

        if ($maxSubscribers > -1 || $maxSubscribersPerList > -1) {
            $criteria = new CDbCriteria();
            $criteria->select = 'COUNT(DISTINCT(t.email)) as counter';

            if ($maxSubscribers > -1 && ($listsIds = $customer->getAllListsIds())) {
                $criteria->addInCondition('t.list_id', $listsIds);
                $totalSubscribersCount = ListSubscriber::model()->count($criteria);
                if ($totalSubscribersCount >= $maxSubscribers) {
                    $this->renderJson([
                        'status'    => 'error',
                        'error'     => t('lists', 'The maximum number of allowed subscribers has been reached.'),
                    ], 409);
                    return;
                }
            }

            if ($maxSubscribersPerList > -1) {
                $criteria->compare('t.list_id', (int)$list->list_id);
                $listSubscribersCount = ListSubscriber::model()->count($criteria);
                if ($listSubscribersCount >= $maxSubscribersPerList) {
                    $this->renderJson([
                        'status'    => 'error',
                        'error'     => t('lists', 'The maximum number of allowed subscribers for this list has been reached.'),
                    ], 409);
                    return;
                }
            }
        }

        $subscribers = request()->getPost('subscribers');
        if (empty($subscribers) || !is_array($subscribers)) {
            $this->renderJson([
                'status'    => 'error',
                'error'     => t('api', 'Please provide the subscribers list.'),
            ], 422);
            return;
        }

        // at most 10k
        $subscribers = array_slice($subscribers, 0, 10000);

        $fields = ListField::model()->findAllByAttributes([
            'list_id' => (int)$list->list_id,
        ]);
        if (empty($fields)) {
            $this->renderJson([
                'status'    => 'error',
                'error'     => t('api', 'The subscribers list does not have any custom field defined.'),
            ], 404);
            return;
        }

        $substr = CommonHelper::functionExists('mb_substr') ? 'mb_substr' : 'substr';

        $emailValidator = new CEmailValidator();
        $emailValidator->allowEmpty  = false;
        $emailValidator->validateIDN = true;

        $subscribersList = [];
        foreach ($subscribers as $subscriber) {
            $subscribersListItem = [
                'data'   => [
                    'details' => isset($subscriber['details']) && is_array($subscriber['details']) ? $subscriber['details'] : [],
                ],
                'errors' => [],
            ];

            if (empty($subscriber['EMAIL']) || !$emailValidator->validateValue($subscriber['EMAIL'])) {
                $subscribersListItem['data']   = $subscriber;
                $subscribersListItem['errors'] = [
                    'EMAIL' => t('api', 'Please provide a valid email address.'),
                ];
                $subscribersList[] = $subscribersListItem;
                continue;
            }

            $errors = [];
            foreach ($fields as $field) {
                $value = $subscriber[$field->tag] ?? null;
                $subscribersListItem['data'][$field->tag] = $value;

                if ($field->required == ListField::TEXT_YES && empty($value)) {
                    $errors[$field->tag] = t('api', 'The field {field} is required by the list but it has not been provided!', [
                        '{field}' => $field->tag,
                    ]);
                }
            }

            if (!empty($errors)) {
                $subscribersListItem['errors']  = $errors;
                $subscribersList[]              = $subscribersListItem;
                continue;
            }

            $subscribersList[] = $subscribersListItem;
        }

        foreach ($subscribersList as $index => $subscribersListItem) {
            if (!empty($subscribersListItem['errors'])) {
                continue;
            }

            // handle the limits
            if ($maxSubscribers > -1 && $totalSubscribersCount >= $maxSubscribers) {
                $subscribersList[$index]['errors'] = [
                    '_common' => t('lists', 'The maximum number of allowed subscribers has been reached.'),
                ];
                continue;
            }
            if ($maxSubscribersPerList > -1 && $listSubscribersCount >= $maxSubscribersPerList) {
                $subscribersList[$index]['errors'] = [
                    '_common' => t('lists', 'The maximum number of allowed subscribers for this list has been reached.'),
                ];
                continue;
            }
            //

            $totalSubscribersCount++;
            $listSubscribersCount++;
            // end limits

            /** @var ListSubscriber|null $subscriber */
            $subscriber = ListSubscriber::model()->findByAttributes([
                'list_id'   => (int)$list->list_id,
                'email'     => $subscribersListItem['data']['EMAIL'],
            ]);

            // 1.6.6
            $stop = false;
            if (!empty($subscriber)) {
                $stop = true;
                if ($subscriber->getIsUnsubscribed()) {
                    $stop = false;
                }
            }

            if ($stop) {
                $subscribersList[$index]['errors'] = [
                    'EMAIL' => t('api', 'The subscriber already exists in this list.'),
                ];
                continue;
            }


            // 1.6.6
            if (empty($subscriber)) {
                $subscriber = new ListSubscriber();
            }

            $subscriber->list_id    = (int)$list->list_id;
            $subscriber->email      = $subscribersListItem['data']['EMAIL'];
            $subscriber->source     = ListSubscriber::SOURCE_API;
            // HTTP_X_MW_REMOTE_ADDR kept for BC
            $subscriber->ip_address = (string)request()->getServer('HTTP_X_MW_REMOTE_ADDR', (string)request()->getServer('HTTP_X_REMOTE_ADDR', (string)request()->getServer('REMOTE_ADDR')));

            if ($list->opt_in == Lists::OPT_IN_SINGLE) {
                $subscriber->status = ListSubscriber::STATUS_CONFIRMED;
            } else {
                $subscriber->status = ListSubscriber::STATUS_UNCONFIRMED;
            }

            $blacklisted = $subscriber->getIsBlacklisted(['checkZone' => EmailBlacklist::CHECK_ZONE_LIST_SUBSCRIBE]);
            if (!empty($blacklisted)) {
                $subscribersList[$index]['errors'] = [
                    'EMAIL' => t('api', 'This email address is blacklisted.'),
                ];
                continue;
            }

            // since 1.3.5.7
            $details = $subscribersListItem['data']['details'];
            if (!empty($details)) {
                $statuses   = array_keys($subscriber->getStatusesList());
                $statuses[] = ListSubscriber::STATUS_UNAPPROVED;
                $statuses[] = ListSubscriber::STATUS_BLACKLISTED; // 1.3.7.1
                $statuses   = array_unique($statuses);
                if (!empty($details['status']) && in_array($details['status'], $statuses)) {
                    $subscriber->status = (string)$details['status'];
                }
                if (!empty($details['ip_address']) && FilterVarHelper::ip((string)$details['ip_address'])) {
                    $subscriber->ip_address = (string)$details['ip_address'];
                }
                if (!empty($details['source']) && in_array($details['source'], array_keys($subscriber->getSourcesList()))) {
                    $subscriber->source = (string)$details['source'];
                }
            }

            if (!$subscriber->save()) {
                $subscribersList[$index]['errors'] = [
                    '_common' => t('api', 'Unable to save the subscriber!'),
                ];
                continue;
            }

            // 1.3.7.1
            if ($subscriber->status == ListSubscriber::STATUS_BLACKLISTED) {
                $subscriber->addToBlacklist('Blacklisted via API');
            }

            foreach ($fields as $field) {

                // 1.8.1
                ListFieldValue::model()->deleteAllByAttributes([
                    'subscriber_id' => (int)$subscriber->subscriber_id,
                    'field_id'      => (int)$field->field_id,
                ]);

                $value = !empty($subscribersListItem['data'][$field->tag]) ? $subscribersListItem['data'][$field->tag] : ListField::parseDefaultValueTags((string)$field->default_value, $subscriber);
                if (!is_array($value)) {
                    $value = [$value];
                }
                $value = array_unique($value);

                foreach ($value as $val) {
                    $valueModel                 = new ListFieldValue();
                    $valueModel->field_id       = (int)$field->field_id;
                    $valueModel->subscriber_id  = (int)$subscriber->subscriber_id;
                    $valueModel->value          = $substr((string)$val, 0, 255);
                    $valueModel->save();
                }
            }

            // since 1.3.6.2
            $this->handleListSubscriberMustApprove($list, $subscriber, $customer);

            // 1.9.15
            $subscribersList[$index]['data']['subscriber_uid'] = $subscriber->subscriber_uid;
        }

        foreach ($subscribersList as $index => $item) {
            unset($subscribersList[$index]['data']['details']);
            if (empty($subscribersList[$index]['errors'])) {
                unset($subscribersList[$index]['errors']);
            }
        }

        $this->renderJson([
            'status' => 'success',
            'data'   => [
                'records' => $subscribersList,
            ],
        ], 201);
    }

    /**
     * Handles the updating of an list subscriber.
     *
     * @param string $list_uid
     * @param string $subscriber_uid
     *
     * @return void
     * @throws CException
     */
    public function actionUpdate($list_uid, $subscriber_uid)
    {
        if (!request()->getIsPutRequest()) {
            $this->renderJson([
                'status'    => 'error',
                'error'     => t('api', 'Only PUT requests allowed for this endpoint.'),
            ], 400);
            return;
        }

        /** @var Customer $customer */
        $customer = user()->getModel();

        /** @var Lists|null $list */
        $list = $this->loadListByUid($list_uid);

        if (empty($list)) {
            $this->renderJson([
                'status'    => 'error',
                'error'     => t('api', 'The subscribers list does not exist.'),
            ], 404);
            return;
        }

        /** @var ListSubscriber|null $subscriber */
        $subscriber = ListSubscriber::model()->findByAttributes([
            'subscriber_uid'    => $subscriber_uid,
            'list_id'           => (int)$list->list_id,
        ]);

        if (empty($subscriber)) {
            $this->renderJson([
                'status'    => 'error',
                'error'     => t('api', 'The subscriber does not exist in this list.'),
            ], 409);
            return;
        }

        $email = (string)request()->getPut('EMAIL', '');
        if (empty($email)) {
            $email = $subscriber->email;
        }

        $validator = new CEmailValidator();
        $validator->allowEmpty  = false;
        $validator->validateIDN = true;

        if (!$validator->validateValue($email)) {
            $this->renderJson([
                'status'    => 'error',
                'error'     => t('api', 'Please provide a valid email address.'),
            ], 422);
            return;
        }

        $fields = ListField::model()->findAllByAttributes([
            'list_id'   => (int)$list->list_id,
        ]);

        if (empty($fields)) {
            $this->renderJson([
                'status'    => 'error',
                'error'     => t('api', 'The subscribers list does not have any custom field defined.'),
            ], 404);
            return;
        }

        $errors = [];
        foreach ($fields as $field) {

            // no need for email since we have it anyway.
            if ($field->tag == 'EMAIL') {
                continue;
            }

            $value = request()->getPut($field->tag);
            if ($field->required == ListField::TEXT_YES && empty($value)) {
                $errors[$field->tag] = t('api', 'The field {field} is required by the list but it has not been provided!', [
                    '{field}' => $field->tag,
                ]);
            }
        }

        if (!empty($errors)) {
            $this->renderJson([
                'status'    => 'error',
                'error'     => $errors,
            ], 422);
            return;
        }

        $criteria = new CDbCriteria();
        $criteria->condition = 't.list_id = :lid AND t.email = :email AND t.subscriber_id != :sid';
        $criteria->params = [
            ':lid'      => (int)$list->list_id,
            ':email'    => $email,
            ':sid'      => (int)$subscriber->subscriber_id,
        ];
        $duplicate = ListSubscriber::model()->find($criteria);
        if (!empty($duplicate)) {
            $this->renderJson([
                'status'    => 'error',
                'error'     => t('api', 'Another subscriber with this email address already exists in this list.'),
            ], 409);
            return;
        }

        $subscriber->email = $email;
        $blacklisted = $subscriber->getIsBlacklisted(['checkZone' => EmailBlacklist::CHECK_ZONE_LIST_SUBSCRIBE]);
        if (!empty($blacklisted)) {
            $this->renderJson([
                'status'    => 'error',
                'error'     => t('api', 'This email address is blacklisted.'),
            ], 409);
            return;
        }

        // since 1.3.5.7
        $details = (array)request()->getPut('details', []);
        if (!empty($details)) {
            $statuses   = array_keys($subscriber->getStatusesList());
            $statuses[] = ListSubscriber::STATUS_BLACKLISTED;
            if (!empty($details['status']) && in_array($details['status'], $statuses)) {
                $subscriber->status = (string)$details['status'];
            }
            if (!empty($details['ip_address']) && FilterVarHelper::ip((string)$details['ip_address'])) {
                $subscriber->ip_address = (string)$details['ip_address'];
            }
            if (!empty($details['source']) && in_array($details['source'], array_keys($subscriber->getSourcesList()))) {
                $subscriber->source = (string)$details['source'];
            }
        }

        if (!$subscriber->save()) {
            $this->renderJson([
                'status'    => 'error',
                'error'     => t('api', 'Unable to save the subscriber!'),
            ], 422);
            return;
        }

        // 1.3.7.1
        if ($subscriber->status == ListSubscriber::STATUS_BLACKLISTED) {
            $subscriber->addToBlacklist('Blacklisted via API');
        }

        $substr = CommonHelper::functionExists('mb_substr') ? 'mb_substr' : 'substr';

        foreach ($fields as $field) {
            $fieldValue = request()->getPut($field->tag);

            // if the field has not been sent, skip it.
            if ($fieldValue === null) {
                continue;
            }

            // delete existing values
            ListFieldValue::model()->deleteAllByAttributes([
                'field_id'      => (int)$field->field_id,
                'subscriber_id' => (int)$subscriber->subscriber_id,
            ]);

            if (!is_array($fieldValue)) {
                $fieldValue = [$fieldValue];
            }
            $fieldValue = array_unique($fieldValue);

            // insert new ones
            foreach ($fieldValue as $value) {
                $valueModel                 = new ListFieldValue();
                $valueModel->field_id       = (int)$field->field_id;
                $valueModel->subscriber_id  = (int)$subscriber->subscriber_id;
                $valueModel->value          = $substr((string)$value, 0, 255);
                $valueModel->save();
            }
        }

        /** @var CustomerActionLogBehavior $logAction */
        $logAction = $customer->getLogAction();
        $logAction->subscriberUpdated($subscriber);

        $this->renderJson([
            'status' => 'success',
            'data'   => [
                'record' => $subscriber->getAttributes(['subscriber_uid', 'email', 'ip_address', 'source', 'date_added']),
            ],
        ]);
    }

    /**
     * Handles unsubscription of an existing email list subscriber.
     *
     * @param string $list_uid
     * @param string $subscriber_uid
     *
     * @return void
     * @throws CException
     */
    public function actionUnsubscribe($list_uid, $subscriber_uid)
    {
        if (!request()->getIsPutRequest()) {
            $this->renderJson([
                'status' => 'error',
                'error'  => t('api', 'Only PUT requests allowed for this endpoint.'),
            ], 400);
            return;
        }

        /** @var Customer $customer */
        $customer = user()->getModel();

        /** @var Lists|null $list */
        $list = $this->loadListByUid($list_uid);

        if (empty($list)) {
            $this->renderJson([
                'status' => 'error',
                'error'  => t('api', 'The subscribers list does not exist.'),
            ], 404);
            return;
        }

        /** @var ListSubscriber|null $subscriber */
        $subscriber = ListSubscriber::model()->findByAttributes([
            'subscriber_uid' => $subscriber_uid,
            'list_id'        => (int)$list->list_id,
        ]);

        if (empty($subscriber)) {
            $this->renderJson([
                'status' => 'error',
                'error'  => t('api', 'The subscriber does not exist in this list.'),
            ], 404);
            return;
        }

        if (!$subscriber->getIsConfirmed()) {
            $this->renderJson([
                'status' => 'success',
            ]);
            return;
        }

        $saved = $subscriber->saveStatus(ListSubscriber::STATUS_UNSUBSCRIBED);

        // since 1.3.5 - this should be expanded in future
        if ($saved) {
            $subscriber->takeListSubscriberAction(ListSubscriberAction::ACTION_UNSUBSCRIBE);
        }

        /** @var CustomerActionLogBehavior $logAction */
        $logAction = $customer->getLogAction();
        $logAction->subscriberUnsubscribed($subscriber);

        $this->renderJson([
            'status'    => 'success',
        ]);
    }

    /**
     * Handles deleting of an existing email list subscriber.
     *
     * @param string $list_uid
     * @param string $subscriber_uid
     *
     * @return void
     * @throws CDbException
     * @throws CException
     */
    public function actionDelete($list_uid, $subscriber_uid)
    {
        if (!request()->getIsDeleteRequest()) {
            $this->renderJson([
                'status'    => 'error',
                'error'     => t('api', 'Only DELETE requests allowed for this endpoint.'),
            ], 400);
            return;
        }

        /** @var Customer $customer */
        $customer = user()->getModel();

        /** @var Lists|null $list */
        $list = $this->loadListByUid($list_uid);

        if (empty($list)) {
            $this->renderJson([
                'status'    => 'error',
                'error'     => t('api', 'The subscribers list does not exist.'),
            ], 404);
            return;
        }

        /** @var ListSubscriber|null $subscriber */
        $subscriber = ListSubscriber::model()->findByAttributes([
            'subscriber_uid'    => $subscriber_uid,
            'list_id'           => (int)$list->list_id,
        ]);

        if (empty($subscriber)) {
            $this->renderJson([
                'status'    => 'error',
                'error'     => t('api', 'The subscriber does not exist in this list.'),
            ], 404);
            return;
        }

        $subscriber->delete();

        /** @var CustomerActionLogBehavior $logAction */
        $logAction = $customer->getLogAction();
        $logAction->subscriberDeleted($subscriber);

        // since 1.3.5.9
        hooks()->doAction('controller_action_delete_data', $collection = new CAttributeCollection([
            'controller'  => $this,
            'list'        => $list,
            'subscriber'  => $subscriber,
        ]));

        $this->renderJson([
            'status'    => 'success',
        ]);
    }

    /**
     * Search given list for a subscriber by the given email address
     *
     * @param string $list_uid
     *
     * @return void
     * @throws CException
     */
    public function actionSearch_by_email($list_uid)
    {
        $email = request()->getQuery('EMAIL');
        if (empty($email)) {
            $this->renderJson([
                'status'    => 'error',
                'error'     => t('api', 'Please provide the subscriber email address.'),
            ], 422);
            return;
        }

        $validator = new CEmailValidator();
        $validator->allowEmpty  = false;
        $validator->validateIDN = true;
        if (!$validator->validateValue($email)) {
            $this->renderJson([
                'status'    => 'error',
                'error'     => t('api', 'Please provide a valid email address.'),
            ], 422);
            return;
        }

        /** @var Lists|null $list */
        $list = $this->loadListByUid($list_uid);

        if (empty($list)) {
            $this->renderJson([
                'status'    => 'error',
                'error'     => t('api', 'The subscribers list does not exist.'),
            ], 404);
            return;
        }

        /** @var ListSubscriber|null $subscriber */
        $subscriber = ListSubscriber::model()->findByAttributes([
            'list_id'   => (int)$list->list_id,
            'email'     => $email,
        ]);

        if (empty($subscriber)) {
            $this->renderJson([
                'status'    => 'error',
                'error'     => t('api', 'The subscriber does not exist in this list.'),
            ], 404);
            return;
        }

        $this->renderJson([
            'status'    => 'success',
            'data'      => $subscriber->getAttributes(['subscriber_uid', 'status', 'date_added']),
        ]);
    }

    /**
     * Search by email in all lists
     *
     * @return void
     * @throws CException
     */
    public function actionSearch_by_email_in_all_lists()
    {
        $email = request()->getQuery('EMAIL');
        if (empty($email)) {
            $this->renderJson([
                'status'    => 'error',
                'error'     => t('api', 'Please provide the subscriber email address.'),
            ], 422);
            return;
        }

        $validator = new CEmailValidator();
        $validator->allowEmpty  = false;
        $validator->validateIDN = true;
        if (!$validator->validateValue($email)) {
            $this->renderJson([
                'status'    => 'error',
                'error'     => t('api', 'Please provide a valid email address.'),
            ], 422);
            return;
        }

        /** @var Customer $customer */
        $customer = user()->getModel();

        $criteria = new CDbCriteria();
        $criteria->compare('email', $email);
        $criteria->addInCondition('list_id', $customer->getAllListsIdsNotArchived());
        $criteria->limit = 100;

        /** @var ListSubscriber[] $subscribers */
        $subscribers = ListSubscriber::model()->findAll($criteria);

        $data = ['records' => []];
        $data['count']          = count($subscribers);
        $data['current_page']   = 1;
        $data['next_page']      = null;
        $data['prev_page']      = null;
        $data['total_pages']    = 1;

        foreach ($subscribers as $subscriber) {
            $record = [];
            $record['subscriber_uid']   = (string)$subscriber->subscriber_uid;
            $record['email']            = $subscriber->email;
            $record['status']           = $subscriber->status;
            $record['source']           = $subscriber->source;
            $record['ip_address']       = $subscriber->ip_address;
            $record['list']             = $subscriber->list->getAttributes(['list_uid', 'display_name', 'name']);
            $record['date_added']       = $subscriber->date_added;

            $data['records'][] = $record;
        }

        $this->renderJson([
            'status' => 'success',
            'data'   => $data,
        ]);
    }

    /**
     * Unsubscribe by email from all lists
     *
     * @return void
     * @throws CException
     */
    public function actionUnsubscribe_by_email_from_all_lists()
    {
        if (!request()->getIsPutRequest()) {
            $this->renderJson([
                'status'    => 'error',
                'error'     => t('api', 'Only PUT requests allowed for this endpoint.'),
            ], 400);
            return;
        }

        $email = request()->getPut('EMAIL');
        if (empty($email)) {
            $this->renderJson([
                'status'    => 'error',
                'error'     => t('api', 'Please provide the subscriber email address.'),
            ], 422);
            return;
        }

        $validator = new CEmailValidator();
        $validator->allowEmpty  = false;
        $validator->validateIDN = true;
        if (!$validator->validateValue($email)) {
            $this->renderJson([
                'status'    => 'error',
                'error'     => t('api', 'Please provide a valid email address.'),
            ], 422);
            return;
        }

        /** @var Customer $customer */
        $customer = user()->getModel();

        $criteria = new CDbCriteria();
        $criteria->compare('email', $email);
        $criteria->addInCondition('list_id', $customer->getAllListsIds());
        $criteria->limit = 1000;

        /** @var ListSubscriber[] $subscribers */
        $subscribers = ListSubscriber::model()->findAll($criteria);

        foreach ($subscribers as $subscriber) {
            $saved = $subscriber->saveStatus(ListSubscriber::STATUS_UNSUBSCRIBED);

            if ($saved) {
                $subscriber->takeListSubscriberAction(ListSubscriberAction::ACTION_UNSUBSCRIBE);
            }

            /** @var CustomerActionLogBehavior $logAction */
            $logAction = $customer->getLogAction();
            $logAction->subscriberUnsubscribed($subscriber);
        }

        $this->renderJson([
            'status' => 'success',
        ]);
    }

    /**
     * Handles the listing of the email list subscribers based on search params for custom fields.
     * The listing is based on page number and number of subscribers per page.
     *
     * @param string $list_uid
     *
     * @return void
     * @throws CException
     */
    public function actionSearch_by_custom_fields($list_uid)
    {
        $criteria = new CDbCriteria();
        $criteria->compare('list_uid', $list_uid);
        $criteria->compare('customer_id', (int)user()->getId());
        $criteria->addNotInCondition('status', [Lists::STATUS_PENDING_DELETE, Lists::STATUS_ARCHIVED]);
        $list = Lists::model()->find($criteria);

        if (empty($list)) {
            $this->renderJson([
                'status'    => 'error',
                'error'     => t('api', 'The subscribers list does not exist.'),
            ], 404);
            return;
        }

        $criteria = new CDbCriteria();
        $criteria->compare('list_id', (int)$list->list_id);
        $criteria->order = 'sort_order ASC';
        $fields = ListField::model()->findAll($criteria);

        if (empty($fields)) {
            $this->renderJson([
                'status'    => 'error',
                'error'     => t('api', 'The subscribers list does not have any custom field defined.'),
            ], 404);
            return;
        }

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
        $criteria->with = [];
        $criteria->compare('t.list_id', (int)$list->list_id);

        $listFieldValue = [];
        foreach ($fields as $field) {
            if ($val = request()->getQuery($field->tag)) {
                $listFieldValue[$field->field_id] = $val;
                continue;
            }
        }

        if (empty($listFieldValue)) {
            $this->renderJson([
                'status'    => 'success',
                'data'      => $data,
            ]);
            return;
        }

        $criteria->with['fieldValues'] = [
            'joinType'  => 'INNER JOIN',
            'together'  => true,
        ];

        foreach ($listFieldValue as $fieldId => $value) {
            $criteria->compare('fieldValues.field_id', $fieldId);
            $criteria->compare('fieldValues.value', $value);
        }

        $countCriteria = clone $criteria;
        $findCriteria  = clone $criteria;

        $countCriteria->select = 'COUNT(DISTINCT(t.subscriber_id)) as counter';
        $findCriteria->group   = 't.subscriber_id';

        $count = ListSubscriber::model()->count($countCriteria);

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

        $findCriteria->order    = 't.subscriber_id DESC';
        $findCriteria->limit    = $perPage;
        $findCriteria->offset   = ($page - 1) * $perPage;

        $subscribers = ListSubscriber::model()->findAll($findCriteria);

        foreach ($subscribers as $subscriber) {
            $record = ['subscriber_uid' => null]; // keep this first!
            foreach ($fields as $field) {
                if ($field->tag == 'EMAIL') {
                    $record[$field->tag] = $subscriber->getDisplayEmail();
                    continue;
                }

                $value = '';
                $criteria = new CDbCriteria();
                $criteria->select = 'value';
                $criteria->compare('field_id', (int)$field->field_id);
                $criteria->compare('subscriber_id', (int)$subscriber->subscriber_id);
                $valueModels = ListFieldValue::model()->findAll($criteria);
                if (!empty($valueModels)) {
                    $value = [];
                    foreach ($valueModels as $valueModel) {
                        $value[] = $valueModel->value;
                    }
                    $value = implode(', ', $value);
                }
                $record[$field->tag] = $value;
            }

            $record['subscriber_uid']   = (string)$subscriber->subscriber_uid;
            $record['status']           = $subscriber->status;
            $record['source']           = $subscriber->source;
            $record['ip_address']       = $subscriber->ip_address;
            $record['date_added']       = $subscriber->date_added;

            $data['records'][] = $record;
        }

        $this->renderJson([
            'status'    => 'success',
            'data'      => $data,
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
                     SELECT `a`.`list_id`, `a`.`status`, UNIX_TIMESTAMP(`a`.`last_updated`) as `last_updated`
                     FROM `{{list_subscriber}}` `a`
                     WHERE `a`.`list_id` = :lid
                     ORDER BY a.`subscriber_id` DESC
                     LIMIT :l OFFSET :o
                ) AS t
                WHERE `t`.`list_id` = :lid
            ';

            $command = db()->createCommand($sql);
            $command->bindValue(':lid', (int)$list->list_id, PDO::PARAM_INT);
            $command->bindValue(':l', (int)$limit, PDO::PARAM_INT);
            $command->bindValue(':o', (int)$offset, PDO::PARAM_INT);

            $row = $command->queryRow();
        } elseif ($this->getAction()->getId() == 'view') {
            $listUid        = request()->getQuery('list_uid');
            $subscriberUid  = request()->getQuery('subscriber_uid');

            $list = Lists::model()->findByAttributes([
                'list_uid'    => $listUid,
                'customer_id' => (int)user()->getId(),
            ]);

            if (empty($list)) {
                return $lastModified = parent::generateLastModified();
            }

            $subscriber = ListSubscriber::model()->findByAttributes([
                'subscriber_uid' => $subscriberUid,
                'list_id'        => (int)$list->list_id,
            ]);

            if (!empty($subscriber)) {
                $row['timestamp'] = (int)strtotime($subscriber->last_updated);
            }
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

    /**
     * @param Lists $list
     * @param ListSubscriber $subscriber
     *
     * @return bool
     * @throws CException
     */
    protected function sendSubscribeConfirmationEmail(Lists $list, ListSubscriber $subscriber): bool
    {
        if (!($server = DeliveryServer::pickServer(0, $list))) {
            // since 2.1.4
            $this->handleSendSubscribeConfirmationEmailFailed($list);

            return false;
        }

        $pageType = ListPageType::model()->findBySlug('subscribe-confirm-email');

        if (empty($pageType)) {
            return false;
        }

        $page = ListPage::model()->findByAttributes([
            'list_id'   => (int)$list->list_id,
            'type_id'   => (int)$pageType->type_id,
        ]);

        $content = !empty($page->content) ? (string)$page->content : (string)$pageType->content;
        $subject = !empty($page->email_subject) ? (string)$page->email_subject : (string)$pageType->email_subject;

        /** @var OptionUrl $optionUrl */
        $optionUrl = container()->get(OptionUrl::class);

        $subscribeUrl = $optionUrl->getFrontendUrl('lists/' . $list->list_uid . '/confirm-subscribe/' . $subscriber->subscriber_uid);

        // 1.5.3
        $updateProfileUrl = $optionUrl->getFrontendUrl('lists/' . $list->list_uid . '/update-profile/' . $subscriber->subscriber_uid);
        $unsubscribeUrl   = $optionUrl->getFrontendUrl('lists/' . $list->list_uid . '/unsubscribe/' . $subscriber->subscriber_uid);

        $searchReplace = [
            '[LIST_NAME]'           => $list->display_name,
            '[LIST_DISPLAY_NAME]'   => $list->display_name,
            '[LIST_INTERNAL_NAME]'  => $list->name,
            '[LIST_UID]'            => $list->list_uid,
            '[COMPANY_NAME]'        => !empty($list->company) ? $list->company->name : null,
            '[SUBSCRIBE_URL]'       => $subscribeUrl,
            '[CURRENT_YEAR]'        => date('Y'),

            // 1.5.3
            '[UPDATE_PROFILE_URL]'  => $updateProfileUrl,
            '[UNSUBSCRIBE_URL]'     => $unsubscribeUrl,
            '[COMPANY_FULL_ADDRESS]'=> !empty($list->company) ? nl2br($list->company->getFormattedAddress()) : null,
        ];

        //
        $subscriberCustomFields = $subscriber->getAllCustomFieldsWithValues();
        foreach ($subscriberCustomFields as $field => $value) {
            $searchReplace[$field] = $value;
        }
        //

        $content = str_replace(array_keys($searchReplace), array_values($searchReplace), $content);
        $subject = str_replace(array_keys($searchReplace), array_values($searchReplace), $subject);

        // 1.5.3
        if (CampaignHelper::isTemplateEngineEnabled()) {
            $content = CampaignHelper::parseByTemplateEngine($content, $searchReplace);
            $subject = CampaignHelper::parseByTemplateEngine($subject, $searchReplace);
        }

        $params = [
            'to'        => $subscriber->email,
            'fromName'  => $list->default->from_name,
            'subject'   => $subject,
            'body'      => $content,
        ];

        $sent = false;
        for ($i = 0; $i < 3; ++$i) {
            if ($sent = $server->setDeliveryFor(DeliveryServer::DELIVERY_FOR_LIST)->setDeliveryObject($list)->sendEmail($params)) {
                break;
            }
            if (!($server = DeliveryServer::pickServer((int)$server->server_id, $list))) {
                break;
            }
        }

        // since 2.1.4
        if (!$sent) {
            $this->handleSendSubscribeConfirmationEmailFailed($list);
        }

        return !empty($sent);
    }

    /**
     * @since 2.1.4
     *
     * @param Lists $list
     *
     * @return void
     */
    protected function handleSendSubscribeConfirmationEmailFailed(Lists $list)
    {
        /** @var OptionUrl $optionUrl */
        $optionUrl = container()->get(OptionUrl::class);

        $messageTitle   = 'Unable to send email';
        $messageContent = 'A subscriber has been rejected from joining the {list} list because the system was not able to find a suitable delivery server to send the email';

        try {
            $message = new CustomerMessage();
            $message->customer_id = (int)$list->customer_id;
            $message->title   = $messageTitle;
            $message->message = $messageContent;
            $message->message_translation_params = [
                '{list}' => CHtml::link($list->name, $optionUrl->getCustomerUrl('lists/' . $list->list_uid . '/overview')),
            ];
            $message->save();

            $message = new UserMessage();
            $message->title   = $messageTitle;
            $message->message = $messageContent;
            $message->message_translation_params = [
                '{list}' => CHtml::link($list->name, $optionUrl->getBackendUrl('lists/index?Lists[list_uid]=' . $list->list_uid)),
            ];
            $message->broadcast();
        } catch (Exception $e) {
            Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
        }
    }

    /**
     * @param Lists $list
     * @param ListSubscriber $subscriber
     *
     * @throws CException
     */
    protected function sendSubscribeWelcomeEmail(Lists $list, ListSubscriber $subscriber): void
    {
        if ($list->welcome_email != Lists::TEXT_YES) {
            return;
        }

        /** @var ListPageType|null $pageType */
        $pageType = ListPageType::model()->findBySlug('welcome-email');
        if (empty($pageType)) {
            return;
        }

        /** @var DeliveryServer|null $server */
        $server = DeliveryServer::pickServer(0, $list);
        if (empty($server)) {

            // since 2.1.4
            $this->handleSendSubscribeConfirmationEmailFailed($list);

            return;
        }

        $page = ListPage::model()->findByAttributes([
            'list_id' => (int)$list->list_id,
            'type_id' => (int)$pageType->type_id,
        ]);

        $_content = !empty($page->content) ? (string)$page->content : (string)$pageType->content;
        $_subject = !empty($page->email_subject) ? (string)$page->email_subject : (string)$pageType->email_subject;

        /** @var OptionUrl $optionUrl */
        $optionUrl = container()->get(OptionUrl::class);

        $updateProfileUrl = $optionUrl->getFrontendUrl('lists/' . $list->list_uid . '/update-profile/' . $subscriber->subscriber_uid);
        $unsubscribeUrl   = $optionUrl->getFrontendUrl('lists/' . $list->list_uid . '/unsubscribe/' . $subscriber->subscriber_uid);

        $searchReplace    = [
            '[LIST_NAME]'           => $list->display_name,
            '[LIST_DISPLAY_NAME]'   => $list->display_name,
            '[LIST_INTERNAL_NAME]'  => $list->name,
            '[LIST_UID]'            => $list->list_uid,
            '[COMPANY_NAME]'        => !empty($list->company) ? $list->company->name : null,
            '[UPDATE_PROFILE_URL]'  => $updateProfileUrl,
            '[UNSUBSCRIBE_URL]'     => $unsubscribeUrl,
            '[COMPANY_FULL_ADDRESS]'=> !empty($list->company) ? nl2br($list->company->getFormattedAddress()) : null,
            '[CURRENT_YEAR]'        => date('Y'),
        ];

        //
        $subscriberCustomFields = $subscriber->getAllCustomFieldsWithValues();
        foreach ($subscriberCustomFields as $field => $value) {
            $searchReplace[$field] = $value;
        }
        //

        $_content = str_replace(array_keys($searchReplace), array_values($searchReplace), $_content);
        $_subject = str_replace(array_keys($searchReplace), array_values($searchReplace), $_subject);

        // 1.5.3
        if (CampaignHelper::isTemplateEngineEnabled()) {
            $_content = CampaignHelper::parseByTemplateEngine($_content, $searchReplace);
            $_subject = CampaignHelper::parseByTemplateEngine($_subject, $searchReplace);
        }

        $params = [
            'to'        => $subscriber->email,
            'fromName'  => $list->default->from_name,
            'subject'   => $_subject,
            'body'      => $_content,
        ];

        $sent = false;
        for ($i = 0; $i < 3; ++$i) {
            if ($sent = $server->setDeliveryFor(DeliveryServer::DELIVERY_FOR_LIST)->setDeliveryObject($list)->sendEmail($params)) {
                break;
            }
            if (!($server = DeliveryServer::pickServer((int)$server->server_id, $list))) {
                break;
            }
        }

        // since 2.1.4
        if (!$sent) {
            $this->handleSendSubscribeWelcomeEmailFail($list);
        }
    }

    /**
     * @param Lists $list
     *
     * @return void
     */
    protected function handleSendSubscribeWelcomeEmailFail(Lists $list): void
    {
        /** @var OptionUrl $optionUrl */
        $optionUrl = container()->get(OptionUrl::class);

        $messageTitle   = 'Unable to send email';
        $messageContent = 'Sending the welcome email for one of the {list} list subscriber failed because the system was not able to find a suitable delivery server to send the email';

        try {
            $message = new CustomerMessage();
            $message->customer_id = (int)$list->customer_id;
            $message->title   = $messageTitle;
            $message->message = $messageContent;
            $message->message_translation_params = [
                '{list}' => CHtml::link($list->name, $optionUrl->getCustomerUrl('lists/' . $list->list_uid . '/overview')),
            ];
            $message->save();

            $message = new UserMessage();
            $message->title   = $messageTitle;
            $message->message = $messageContent;
            $message->message_translation_params = [
                '{list}' => CHtml::link($list->name, $optionUrl->getBackendUrl('lists/index?Lists[list_uid]=' . $list->list_uid)),
            ];
            $message->broadcast();
        } catch (Exception $e) {
            Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
        }
    }

    /**
     * @param Lists $list
     * @param ListSubscriber $subscriber
     * @param Customer $customer
     *
     * @throws CException
     * @throws Throwable
     */
    protected function handleListSubscriberMustApprove(Lists $list, ListSubscriber $subscriber, Customer $customer): void
    {
        // since 1.3.6.2
        $mustApprove = $list->subscriber_require_approval == Lists::TEXT_YES && $subscriber->getIsUnapproved();

        /** @var DeliveryServer|null $server */
        $server = null;

        if ($mustApprove) {

            /** @var DeliveryServer|null $server */
            $server = DeliveryServer::pickServer(0, $list);
            if (empty($server)) {
                $subscriber->status = ListSubscriber::STATUS_CONFIRMED;
                $mustApprove        = false;
            }
        }

        if ($mustApprove) {
            $fieldsTags = [];
            $fields     = [];
            $listFields = ListField::model()->findAll([
                'select'    => 'field_id, label, tag',
                'condition' => 'list_id = :lid',
                'order'     => 'sort_order ASC',
                'params'    => [':lid' => (int)$list->list_id],
            ]);

            foreach ($listFields as $field) {
                $fieldValues = ListFieldValue::model()->findAll([
                    'select'    => 'value',
                    'condition' => 'subscriber_id = :sid AND field_id = :fid',
                    'params'    => [':sid' => (int)$subscriber->subscriber_id, ':fid' => (int)$field->field_id],
                ]);
                $values = [];
                foreach ($fieldValues as $value) {
                    $values[] = $value->value;
                }
                $fields[$field->label] = implode(', ', $values);
                $fieldsTags['[' . $field->tag . ']'] = implode(', ', $values);
            }

            $submittedData = [];
            foreach ($fields as $key => $value) {
                $submittedData[] = sprintf('%s: %s', $key, $value);
            }
            $submittedData = implode('<br />', $submittedData);

            /** @var OptionUrl $optionUrl */
            $optionUrl = container()->get(OptionUrl::class);

            $params  = CommonEmailTemplate::getAsParamsArrayBySlug(
                'new-list-subscriber',
                [
                    'fromName'  => $list->default->from_name,
                    'subject'   => t('lists', 'New list subscriber!'),
                ],
                CMap::mergeArray($fieldsTags, [
                    '[LIST_NAME]'      => $list->name,
                    '[DETAILS_URL]'    => $optionUrl->getCustomerUrl(sprintf('lists/%s/subscribers/%s/update', $list->list_uid, $subscriber->subscriber_uid)),
                    '[SUBMITTED_DATA]' => $submittedData,
                ])
            );

            $recipients = explode(',', $list->customerNotification->subscribe_to);
            $recipients = array_map('trim', $recipients);

            foreach ($recipients as $recipient) {
                if (!FilterVarHelper::email($recipient)) {
                    continue;
                }
                $params['to'] = [$recipient => $customer->getFullName()];
                if (!empty($server)) {
                    $server->setDeliveryFor(DeliveryServer::DELIVERY_FOR_LIST)->setDeliveryObject($list)->sendEmail($params);
                }
            }
        } else {
            if ($list->opt_in == Lists::OPT_IN_DOUBLE) {
                if ($subscriber->isUnconfirmed) {
                    $this->sendSubscribeConfirmationEmail($list, $subscriber);
                }
            } else {
                if ($subscriber->isConfirmed) {
                    // since 1.3.5 - this should be expanded in future
                    $subscriber->takeListSubscriberAction(ListSubscriberAction::ACTION_SUBSCRIBE);

                    // since 1.9.5
                    $subscriber->sendCreatedNotifications();

                    // since 1.3.5.4 - send the welcome email
                    $this->sendSubscribeWelcomeEmail($list, $subscriber);
                }
            }
        }
    }
}
