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

class SearchExtBehaviorLists_toolsController extends SearchExtBaseBehavior
{
    /**
     * @return array
     */
    public function searchableActions(): array
    {
        return [
            'index' => [
                'keywords'          => ['tools', 'sync', 'split', 'list sync', 'lists sync', 'list split', 'lists split'],
                'keywordsGenerator' => [$this, '_indexKeywordsGenerator'],
            ],
        ];
    }

    /**
     * @return bool
     */
    public function _skip(): bool
    {
        if (is_subaccount() && !subaccount()->canManageLists()) {
            return true;
        }

        return false;
    }

    /**
     * @return array
     * @throws CException
     */
    public function _indexKeywordsGenerator(): array
    {
        $syncToolModel  = new ListsSyncTool();
        $splitToolModel = new ListSplitTool();

        $keywords = [];

        $keywords = CMap::mergeArray($keywords, array_values($syncToolModel->attributeLabels()));

        return CMap::mergeArray($keywords, array_values($splitToolModel->attributeLabels()));
    }
}
