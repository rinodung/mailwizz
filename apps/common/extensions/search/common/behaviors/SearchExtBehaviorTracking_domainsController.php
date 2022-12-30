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

class SearchExtBehaviorTracking_domainsController extends SearchExtBaseBehavior
{
    /**
     * @return array
     */
    public function searchableActions(): array
    {
        return [
            'index' => [
                'keywords'          => ['domain tracking'],
                'skip'              => [$this, '_skip'],
                'childrenGenerator' => [$this, '_indexChildrenGenerator'],
            ],
            'create' => [
                'keywords'  => ['domain tracking create'],
                'skip'      => [$this, '_skip'],
            ],
        ];
    }

    /**
     * @param SearchExtSearchItem $item
     *
     * @return bool
     */
    public function _skip(SearchExtSearchItem $item): bool
    {
        if (apps()->isAppName('customer')) {

            /** @var Customer $customer */
            $customer = customer()->getModel();

            if ($customer->getGroupOption('tracking_domains.can_manage_tracking_domains', 'no') !== 'yes') {
                return true;
            }

            if (is_subaccount() && !subaccount()->canManageDomains()) {
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

        $criteria->addCondition('(name LIKE :term)');
        $criteria->params[':term'] = '%' . $term . '%';
        $criteria->order = 'domain_id DESC';
        $criteria->limit = 5;

        /** @var TrackingDomain[] $models */
        $models = TrackingDomain::model()->findAll($criteria);
        $items  = [];
        foreach ($models as $model) {
            $item        = new SearchExtSearchItem();
            $item->title = $model->name;
            $item->url   = createUrl('tracking_domains/update', ['id' => $model->domain_id]);
            $item->score++;

            $items[] = $item->getFields();
        }
        return $items;
    }
}
