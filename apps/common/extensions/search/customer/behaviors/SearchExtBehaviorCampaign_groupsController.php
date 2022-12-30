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

class SearchExtBehaviorCampaign_groupsController extends SearchExtBaseBehavior
{
    /**
     * @return array
     */
    public function searchableActions(): array
    {
        return [
            'index' => [
                'keywords'          => ['campaigns groups'],
                'childrenGenerator' => [$this, '_indexChildrenGenerator'],
            ],
            'create' => [
                'keywords' => ['create groups', 'campaigns groups create'],
            ],
        ];
    }

    /**
     * @return bool
     */
    public function _skip(): bool
    {
        if (is_subaccount() && !subaccount()->canManageCampaigns()) {
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
        $criteria->params[':term'] = '%' . $term . '%';
        $criteria->params[':cid']  = (int)customer()->getId();
        $criteria->order = 'group_id DESC';
        $criteria->limit = 5;

        /** @var CampaignGroup[] $models */
        $models = CampaignGroup::model()->findAll($criteria);
        $items  = [];
        foreach ($models as $model) {
            $item        = new SearchExtSearchItem();
            $item->title = $model->name;
            $item->url   = createUrl('campaign_groups/update', ['group_uid' => $model->group_uid]);
            $item->score++;
            $items[] = $item->getFields();
        }
        return $items;
    }
}
