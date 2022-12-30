<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 */

class SearchExtBehaviorDelivery_serversController extends SearchExtBaseBehavior
{
    /**
     * @return array
     */
    public function searchableActions(): array
    {
        return [
            'index' => [
                'keywords'          => ['delivery server', 'create delivery server', 'server', 'delivery'],
                'skip'              => [$this, '_indexSkip'],
                'childrenGenerator' => [$this, '_indexChildrenGenerator'],
            ],
            'create' => [
                'keywords'          => ['delivery server create', 'create delivery server', 'server', 'delivery'],
                'skip'              => [$this, '_createSkip'],
            ],
        ];
    }

    /**
     * @param SearchExtSearchItem $item
     *
     * @return bool
     */
    public function _indexSkip(SearchExtSearchItem $item)
    {
        if (apps()->isAppName('customer')) {

            /** @var Customer $customer */
            $customer = customer()->getModel();

            if ((int)$customer->getGroupOption('servers.max_delivery_servers', 0) === 0) {
                return true;
            }

            if (is_subaccount() && !subaccount()->canManageServers()) {
                return true;
            }

            return false;
        }

        /** @var User $user */
        $user = user()->getModel();
        return !$user->hasRouteAccess($item->route);
    }

    /**
     * @param string $term
     * @param SearchExtSearchItem|null $parent
     *
     * @return array
     */
    public function _indexChildrenGenerator(string $term, ?SearchExtSearchItem $parent = null): array
    {
        $criteria = new CDbCriteria();

        if (apps()->isAppName('customer')) {
            $criteria->addCondition('customer_id = :cid');
            $criteria->params[':cid'] = (int)customer()->getId();
        }

        $criteria->addCondition('(name LIKE :term OR hostname LIKE :term OR username LIKE :term OR from_email LIKE :term)');
        $criteria->params[':term'] = '%' . $term . '%';
        $criteria->order = 'server_id DESC';
        $criteria->limit = 5;

        /** @var DeliveryServer[] $models */
        $models = DeliveryServer::model()->findAll($criteria);
        $items  = [];
        foreach ($models as $model) {
            $item        = new SearchExtSearchItem();
            $item->title = !empty($model->name) ? $model->name : $model->hostname;
            $item->url   = createUrl('delivery_servers/update', ['id' => $model->server_id, 'type' => $model->type]);
            $item->score++;
            $items[] = $item->getFields();
        }
        return $items;
    }

    /**
     * @return bool
     */
    public function _createSkip(): bool
    {
        return true;
    }
}
