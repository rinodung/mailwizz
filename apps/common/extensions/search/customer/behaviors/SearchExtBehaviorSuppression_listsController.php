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

class SearchExtBehaviorSuppression_listsController extends SearchExtBaseBehavior
{
    /**
     * @return array
     */
    public function searchableActions(): array
    {
        return [
            'index' => [
                'keywords'          => ['suppress list', 'lists suppression'],
                'skip'              => [$this, '_skip'],
                'childrenGenerator' => [$this, '_indexChildrenGenerator'],
            ],
            'create' => [
                'keywords' => ['create suppression list'],
                'skip'     => [$this, '_skip'],
            ],
        ];
    }

    /**
     * @return bool
     */
    public function _skip(): bool
    {
        /** @var Customer $customer */
        $customer = customer()->getModel();

        if ($customer->getGroupOption('lists.can_use_own_blacklist', 'no') != 'yes') {
            return true;
        }

        if (is_subaccount() && !subaccount()->canManageBlacklists()) {
            return true;
        }

        return false;
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
        $criteria->addCondition('customer_id = :cid');
        $criteria->addCondition('name LIKE :term');
        $criteria->params[':cid']  = (int)customer()->getId();
        $criteria->params[':term'] = '%' . $term . '%';
        $criteria->order = 'list_id DESC';
        $criteria->limit = 5;

        /** @var CustomerSuppressionList[] $models */
        $models = CustomerSuppressionList::model()->findAll($criteria);
        $items  = [];
        foreach ($models as $model) {
            $item        = new SearchExtSearchItem();
            $item->title = $model->name;
            $item->url   = createUrl('suppression_lists/update', ['list_uid' => $model->list_uid]);
            $item->score++;
            $items[] = $item->getFields();
        }
        return $items;
    }
}
