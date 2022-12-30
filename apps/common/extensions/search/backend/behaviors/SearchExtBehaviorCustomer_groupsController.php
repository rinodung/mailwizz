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

class SearchExtBehaviorCustomer_groupsController extends SearchExtBaseBehavior
{
    /**
     * @return array
     */
    public function searchableActions(): array
    {
        return [
            'index' => [
                'keywords'          => ['customer group'],
                'childrenGenerator' => [$this, '_indexChildrenGenerator'],
            ],
            'create' => [
                'keywords'  => ['create customer group', 'customer group create', 'create group'],
            ],
        ];
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
        $criteria->addCondition('name LIKE :term');
        $criteria->params[':term'] = '%' . $term . '%';
        $criteria->order = 'group_id DESC';
        $criteria->limit = 5;

        /** @var CustomerGroup[] $models */
        $models = CustomerGroup::model()->findAll($criteria);
        $items  = [];
        foreach ($models as $model) {
            $item        = new SearchExtSearchItem();
            $item->title = $model->name;
            $item->url   = createUrl('customer_groups/update', ['id' => $model->group_id]);
            $item->score++;
            $items[] = $item->getFields();
        }
        return $items;
    }
}
