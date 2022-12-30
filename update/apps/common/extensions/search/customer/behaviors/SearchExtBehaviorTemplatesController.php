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

class SearchExtBehaviorTemplatesController extends SearchExtBaseBehavior
{
    /**
     * @return array
     */
    public function searchableActions(): array
    {
        return [
            'index' => [
                'keywords'          => ['gallery', 'email templates'],
                'childrenGenerator' => [$this, '_indexChildrenGenerator'],
            ],
            'gallery' => [
                'keywords'          => ['gallery', 'gallery email templates gallery', 'import email template', 'import template'],
                'childrenGenerator' => [$this, '_galleryChildrenGenerator'],
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
        $criteria->order = 'template_id DESC';
        $criteria->limit = 5;

        /** @var CustomerEmailTemplate[] $models */
        $models = CustomerEmailTemplate::model()->findAll($criteria);
        $items  = [];
        foreach ($models as $model) {
            $item        = new SearchExtSearchItem();
            $item->title = $model->name;
            $item->url   = createUrl('templates/update', ['template_uid' => $model->template_uid]);
            $item->score++;
            $items[] = $item->getFields();
        }
        return $items;
    }

    /**
     * @param string $term
     * @param SearchExtSearchItem|null $parent
     *
     * @return array
     */
    public function _galleryChildrenGenerator(string $term, ?SearchExtSearchItem $parent = null): array
    {
        $criteria = new CDbCriteria();
        $criteria->addCondition('customer_id IS NULL');
        $criteria->addCondition('name LIKE :term');
        $criteria->params[':term'] = '%' . $term . '%';
        $criteria->limit = 5;

        /** @var CustomerEmailTemplate[] $models */
        $models = CustomerEmailTemplate::model()->findAll($criteria);
        $items  = [];
        foreach ($models as $model) {
            $item        = new SearchExtSearchItem();
            $item->title = $model->name;
            $item->url   = createUrl('templates/gallery_import', ['template_uid' => $model->template_uid]);
            $item->score++;
            $items[] = $item->getFields();
        }
        return $items;
    }
}
