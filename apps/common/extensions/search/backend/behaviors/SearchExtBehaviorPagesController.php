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

class SearchExtBehaviorPagesController extends SearchExtBaseBehavior
{
    /**
     * @return array
     */
    public function searchableActions(): array
    {
        return [
            'index' => [
                'keywords'          => ['custom pages', 'custom content'],
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
        $criteria->addCondition('title LIKE :term');
        $criteria->params[':term'] = '%' . $term . '%';
        $criteria->order = 'page_id DESC';
        $criteria->limit = 5;

        /** @var Page[] $models */
        $models = Page::model()->findAll($criteria);
        $items  = [];
        foreach ($models as $model) {
            $item        = new SearchExtSearchItem();
            $item->title = $model->title;
            $item->url   = createUrl('pages/update', ['id' => $model->page_id]);
            $item->score++;
            $items[] = $item->getFields();
        }
        return $items;
    }
}
