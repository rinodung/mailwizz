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

class SearchExtBehaviorPrice_plansController extends SearchExtBaseBehavior
{
    /**
     * @return array
     */
    public function searchableActions(): array
    {
        return [
            'index' => [
                'keywords'          => ['monetization'],
                'skip'              => [$this, '_indexSkip'],
                'childrenGenerator' => [$this, '_indexChildrenGenerator'],
            ],
            'orders' => [
                'keywords' => ['order', 'orders', 'my order', 'my orders'],
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

            /** @var OptionMonetizationMonetization $optionMonetizationMonetization */
            $optionMonetizationMonetization = container()->get(OptionMonetizationMonetization::class);

            if (!$optionMonetizationMonetization->getIsEnabled()) {
                return true;
            }

            if (is_subaccount()) {
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
        if (apps()->isAppName('customer')) {
            return [];
        }

        $criteria = new CDbCriteria();
        $criteria->addCondition('name LIKE :term OR description LIKE :term');
        $criteria->params[':term'] = '%' . $term . '%';
        $criteria->order = 'plan_id DESC';
        $criteria->limit = 5;

        /** @var PricePlan[] $models */
        $models = PricePlan::model()->findAll($criteria);
        $items  = [];
        foreach ($models as $model) {
            $item        = new SearchExtSearchItem();
            $item->title = $model->name;
            $item->url   = createUrl('price_plans/update', ['id' => $model->plan_id]);
            $item->score++;
            $items[] = $item->getFields();
        }
        return $items;
    }
}
