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

class SearchExtBehaviorApi_keysController extends SearchExtBaseBehavior
{
    /**
     * @return array
     */
    public function searchableActions(): array
    {
        return [
            'index' => [
                'keywords'          => ['api access', 'key', 'keys', 'api key', 'api keys'],
                'skip'              => [$this, '_skip'],
                'childrenGenerator' => [$this, '_indexChildrenGenerator'],
            ],
            'generate' => [
                'keywords'          => ['create api key', 'api access'],
                'skip'              => [$this, '_skip'],
            ],
        ];
    }

    /**
     * @return bool
     */
    public function _skip(): bool
    {
        /** @var OptionCommon $common */
        $common = container()->get(OptionCommon::class);

        /** @var Customer $customer */
        $customer = customer()->getModel();

        if (!$common->getIsApiOnline()) {
            return true;
        }

        if ($customer->getGroupOption('api.enabled', 'yes') != 'yes') {
            return true;
        }

        if (is_subaccount() && !subaccount()->canManageApiKeys()) {
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
        $criteria->addCondition('(name LIKE :term OR description LIKE :term OR `key` LIKE :term)');
        $criteria->params[':term'] = '%' . $term . '%';
        $criteria->params[':cid']  = (int)customer()->getId();
        $criteria->order = 'key_id DESC';
        $criteria->limit = 5;

        /** @var CustomerApiKey[] $models */
        $models = CustomerApiKey::model()->findAll($criteria);

        $items = [];
        foreach ($models as $model) {
            $item        = new SearchExtSearchItem();
            $item->title = !empty($model->name) ? $model->name : t('api_keys', 'Api key: {key}', ['{key}' => $model->key]);
            $item->url   = createUrl('api_keys/update', ['id' => $model->key_id]);
            $item->score++;
            $items[] = $item->getFields();
        }
        return $items;
    }
}
