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

class SearchExtBehaviorCampaignsController extends SearchExtBaseBehavior
{
    /**
     * @return array
     */
    public function searchableActions(): array
    {
        return [
            'index' => [
                'keywords'          => ['campaign', 'campaigns list', 'autoresponders'],
                'skip'              => [$this, '_indexSkip'],
                'childrenGenerator' => [$this, '_indexChildrenGenerator'],
            ],
            'create' => [
                'keywords'  => ['campaign', 'regular campaign', 'autoresponders'],
                'skip'      => [$this, '_createSkip'],
            ],
            'regular' => [
                'keywords'  => ['regular campaign', 'regular campaigns'],
                'skip'      => [$this, '_indexSkip'],
            ],
            'autoresponder' => [
                'keywords'  => ['autoresponder campaign', 'autoresponder campaigns', 'autoresponders'],
                'skip'      => [$this, '_indexSkip'],
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
            if (is_subaccount() && !subaccount()->canManageCampaigns()) {
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

        $criteria->addCondition('(name LIKE :term OR subject LIKE :term)');
        $criteria->params[':term'] = '%' . $term . '%';
        $criteria->order = 'campaign_id DESC';
        $criteria->limit = 5;

        return CampaignCollection::findAll($criteria)->map(function (Campaign $model) {
            $item        = new SearchExtSearchItem();
            $item->title = $model->name;
            $item->url   = createUrl('campaigns/overview', ['campaign_uid' => $model->campaign_uid]);
            $item->score++;
            return $item->getFields();
        })->all();
    }

    /**
     * @return bool
     */
    public function _createSkip(): bool
    {
        return !apps()->isAppName('customer');
    }
}
