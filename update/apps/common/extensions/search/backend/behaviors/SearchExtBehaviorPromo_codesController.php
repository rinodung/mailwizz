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

class SearchExtBehaviorPromo_codesController extends SearchExtBaseBehavior
{
    /**
     * @return array
     */
    public function searchableActions(): array
    {
        return [
            'index' => [
                'keywords'          => ['monetization', 'promotions', 'promos', 'discount'],
                'childrenGenerator' => [$this, '_indexChildrenGenerator'],
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
        $criteria->addCondition('code LIKE :term OR type LIKE :term');
        $criteria->params[':term'] = '%' . $term . '%';
        $criteria->order = 'promo_code_id DESC';
        $criteria->limit = 5;

        /** @var PricePlanPromoCode[] $models */
        $models = PricePlanPromoCode::model()->findAll($criteria);
        $items  = [];
        foreach ($models as $model) {
            $item        = new SearchExtSearchItem();
            $item->title = $model->code;
            $item->url   = createUrl('promo_codes/update', ['id' => $model->promo_code_id]);
            $item->score++;
            $items[] = $item->getFields();
        }
        return $items;
    }
}
