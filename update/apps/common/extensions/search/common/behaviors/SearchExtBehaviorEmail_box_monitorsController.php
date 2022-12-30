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

class SearchExtBehaviorEmail_box_monitorsController extends SearchExtBaseBehavior
{
    /**
     * @return array
     */
    public function searchableActions(): array
    {
        return [
            'index' => [
                'keywords'          => ['monitoring'],
                'skip'              => [$this, '_skip'],
                'childrenGenerator' => [$this, '_indexChildrenGenerator'],
            ],
            'create' => [
                'keywords'          => ['monitoring'],
                'skip'              => [$this, '_skip'],
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

            if ((int)$customer->getGroupOption('servers.max_email_box_monitors', 0) === 0) {
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

        $criteria->addCondition('(email LIKE :term OR hostname LIKE :term OR username LIKE :term)');
        $criteria->params[':term'] = '%' . $term . '%';
        $criteria->order = 'server_id DESC';
        $criteria->limit = 5;

        /** @var EmailBoxMonitor[] $models */
        $models = EmailBoxMonitor::model()->findAll($criteria);
        $items  = [];
        foreach ($models as $model) {
            $item        = new SearchExtSearchItem();
            $item->title = $model->hostname;
            $item->url   = createUrl('email_box_monitors/update', ['id' => $model->server_id]);
            $item->score++;
            $items[] = $item->getFields();
        }
        return $items;
    }
}
