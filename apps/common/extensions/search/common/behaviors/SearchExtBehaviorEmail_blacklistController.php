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

class SearchExtBehaviorEmail_blacklistController extends SearchExtBaseBehavior
{
    /**
     * @return array
     */
    public function searchableActions(): array
    {
        return [
            'index' => [
                'keywords'  => ['black lists'],
                'skip'      => [$this, '_skip'],
            ],
            'create' => [
                'keywords'  => ['create black lists'],
                'skip'      => [$this, '_skip'],
            ],
        ];
    }

    /**
     * @param SearchExtSearchItem $item
     *
     * @return bool
     */
    public function _skip(SearchExtSearchItem $item): bool
    {
        if (apps()->isAppName('customer')) {

            /** @var Customer $customer */
            $customer = customer()->getModel();

            if ($customer->getGroupOption('lists.can_use_own_blacklist', 'no') !== 'yes') {
                return true;
            }

            if (is_subaccount() && !subaccount()->canManageBlacklists()) {
                return true;
            }

            return false;
        }

        /** @var User $user */
        $user = user()->getModel();
        return !$user->hasRouteAccess($item->route);
    }
}
