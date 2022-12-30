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

class SearchExtBehaviorList_page_typeController extends SearchExtBaseBehavior
{
    /**
     * @return array
     */
    public function searchableActions(): array
    {
        return [
            'index' => [
                'keywords'          => ['page types', 'list pages types'],
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
        $criteria->addCondition('name LIKE :term OR description LIKE :term');
        $criteria->params[':term'] = '%' . $term . '%';
        $criteria->order = 'type_id DESC';
        $criteria->limit = 5;

        /** @var ListPageType[] $models */
        $models = ListPageType::model()->findAll($criteria);
        $items  = [];
        foreach ($models as $model) {
            $item        = new SearchExtSearchItem();
            $item->title = $model->name;
            $item->url   = createUrl('list_page_type/update', ['id' => $model->type_id]);
            $item->score++;
            $items[] = $item->getFields();
        }
        return $items;
    }
}
