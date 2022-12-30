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

class SearchExtBehaviorCampaign_tagsController extends SearchExtBaseBehavior
{
    /**
     * @return array
     */
    public function searchableActions(): array
    {
        return [
            'index' => [
                'keywords'          => ['campaigns tags'],
                'childrenGenerator' => [$this, '_indexChildrenGenerator'],
            ],
            'create' => [
                'keywords' => ['create tags', 'campaigns tags create'],
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
        $criteria->addCondition('tag LIKE :term');
        $criteria->params[':cid']  = (int)customer()->getId();
        $criteria->params[':term'] = '%' . $term . '%';
        $criteria->order = 'tag_id DESC';
        $criteria->limit = 5;

        /** @var CustomerCampaignTag[] $models */
        $models = CustomerCampaignTag::model()->findAll($criteria);
        $items  = [];
        foreach ($models as $model) {
            $item        = new SearchExtSearchItem();
            $item->title = $model->tag;
            $item->url   = createUrl('campaign_tags/update', ['tag_uid' => $model->tag_uid]);
            $item->score++;
            $items[] = $item->getFields();
        }
        return $items;
    }
}
