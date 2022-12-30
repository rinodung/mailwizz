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

class SearchExtBehaviorListsController extends SearchExtBaseBehavior
{
    /**
     * @return array
     */
    public function searchableActions(): array
    {
        return [
            'index' => [
                'title'     => t('lists', 'Email lists'),
                'keywords'  => [
                    'list', 'email list', 'subscribers', 'segments', 'pages', 'embed', 'subscribe', 'unsubscribe',
                    'import', 'list import', 'export', 'list export', 'custom fields', 'custom field', 'list fields',
                ],
                'skip'              => [$this, '_indexSkip'],
                'childrenGenerator' => [$this, '_indexChildrenGenerator'],
            ],
            'create' => [
                'keywords'  => ['list', 'create list', 'list create', 'subscriber'],
                'skip'      => [$this, '_createSkip'],
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
            if (is_subaccount() && !subaccount()->canManageLists()) {
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

        $criteria->addCondition('(name LIKE :term OR display_name LIKE :term OR description LIKE :term)');
        $criteria->params[':term'] = '%' . $term . '%';
        $criteria->order = 'list_id DESC';
        $criteria->limit = 5;

        /** @var Lists[] $models */
        $models = Lists::model()->findAll($criteria);
        $items  = [];
        foreach ($models as $model) {
            $item        = new SearchExtSearchItem();
            $item->title = $model->name;
            $item->url   = createUrl('lists/overview', ['list_uid' => $model->list_uid]);
            $item->score++;

            if (apps()->isAppName('customer')) {
                $item->buttons = [
                    CHtml::link(IconHelper::make('update'), ['lists/update', 'list_uid' => $model->list_uid], ['title' => t('lists', 'Update'), 'class' => 'btn btn-xs btn-primary btn-flat']),
                    CHtml::link(IconHelper::make('fa-users'), ['list_subscribers/index', 'list_uid' => $model->list_uid], ['title' => t('lists', 'Subscribers'), 'class' => 'btn btn-xs btn-primary btn-flat']),
                    CHtml::link(IconHelper::make('import'), ['list_import/index', 'list_uid' => $model->list_uid], ['title' => t('lists', 'Import'), 'class' => 'btn btn-xs btn-primary btn-flat']),
                    CHtml::link(IconHelper::make('ion-folder'), ['list_page/index', 'list_uid' => $model->list_uid, 'type' => 'subscribe-form'], ['title' => t('lists', 'Pages'), 'class' => 'btn btn-xs btn-primary btn-flat']),
                ];
            }

            $items[] = $item->getFields();
        }
        return $items;
    }

    /**
     * @return bool
     */
    public function _createSkip(): bool
    {
        if (apps()->isAppName('customer')) {

            /** @var Customer $customer */
            $customer = customer()->getModel();
            return (int)$customer->getGroupOption('lists.max_lists', -1) == 0;
        }
        return true;
    }
}
