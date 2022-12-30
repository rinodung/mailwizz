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

class SearchExtBehaviorTemplates_categoriesController extends SearchExtBaseBehavior
{
    /**
     * @return array
     */
    public function searchableActions(): array
    {
        return [
            'index' => [
                'keywords'          => ['template category', 'email templates category', 'template category'],
                'childrenGenerator' => [$this, '_indexChildrenGenerator'],
            ],
            'create' => [
                'keywords' => ['create email templates category', 'create template category'],
            ],
        ];
    }

    /**
     * @return bool
     */
    public function _skip(): bool
    {
        if (is_subaccount() && !subaccount()->canManageEmailTemplates()) {
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
        $criteria->order = 'category_id DESC';
        $criteria->limit = 5;

        /** @var CustomerEmailTemplateCategory[] $models */
        $models = CustomerEmailTemplateCategory::model()->findAll($criteria);

        $items  = [];
        foreach ($models as $model) {
            $item        = new SearchExtSearchItem();
            $item->title = $model->name;
            $item->url   = createUrl('templates_categories/update', ['id' => $model->category_id]);
            $item->score++;
            $items[] = $item->getFields();
        }
        return $items;
    }
}
