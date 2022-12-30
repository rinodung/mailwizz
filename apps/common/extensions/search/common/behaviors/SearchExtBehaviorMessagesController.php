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

class SearchExtBehaviorMessagesController extends SearchExtBaseBehavior
{
    /**
     * @return array
     */
    public function searchableActions(): array
    {
        return [
            'index' => [
                'keywords' => ['messages list', 'notifications', 'view messages'],
                'skip'     => [$this, '_indexSkip'],
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
            if (is_subaccount()) {
                return true;
            }
            return false;
        }

        /** @var User $user */
        $user = user()->getModel();
        return !$user->hasRouteAccess($item->route);
    }
}
