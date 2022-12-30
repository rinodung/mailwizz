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

class SearchExtBehaviorLanguagesController extends SearchExtBaseBehavior
{
    /**
     * @return array
     */
    public function searchableActions(): array
    {
        return [
            'index' => [
                'keywords'          => ['translate', 'translation', 'i18n', 'internationalisation'],
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
        $criteria->addCondition('name LIKE :term OR language_code LIKE :term OR region_code LIKE :term');
        $criteria->params[':term'] = '%' . $term . '%';
        $criteria->order = 'language_id DESC';
        $criteria->limit = 5;

        /** @var Language[] $models */
        $models = Language::model()->findAll($criteria);
        $items  = [];
        foreach ($models as $model) {
            $item        = new SearchExtSearchItem();
            $item->title = $model->name;
            $item->url   = createUrl('languages/update', ['id' => $model->language_id]);
            $item->score++;
            $items[] = $item->getFields();
        }
        return $items;
    }
}
