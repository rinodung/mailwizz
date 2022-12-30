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

class SearchExtBehaviorCustomersController extends SearchExtBaseBehavior
{
    /**
     * @return array
     */
    public function searchableActions(): array
    {
        return [
            'index' => [
                'keywords'          => ['customer', 'customer list', 'customers list'],
                'childrenGenerator' => [$this, '_indexChildrenGenerator'],
            ],
            'create' => [
                'keywords'  => ['create customer', 'customer create'],
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
        $criteria->addCondition('first_name LIKE :term OR last_name LIKE :term OR email LIKE :term');
        $criteria->params[':term'] = '%' . $term . '%';
        $criteria->order = 'customer_id DESC';
        $criteria->limit = 5;

        /** @var Customer[] $models */
        $models = Customer::model()->findAll($criteria);
        $items  = [];
        foreach ($models as $model) {
            $item        = new SearchExtSearchItem();
            $item->title = $model->getFullName();
            $item->url   = createUrl('customers/update', ['id' => $model->customer_id]);
            $item->score++;
            $items[] = $item->getFields();
        }
        return $items;
    }
}
