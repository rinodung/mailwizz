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

class SearchExtBehaviorCampaigns_geo_opensController extends SearchExtBaseBehavior
{
    /**
     * @return array
     */
    public function searchableActions(): array
    {
        return [
            'index' => [
                'keywords' => ['campaigns geo localization'],
                'skip'     => [$this, '_skip'],
            ],
        ];
    }

    /**
     * @return bool
     */
    public function _skip(): bool
    {
        /** @var Customer $customer */
        $customer = customer()->getModel();

        if ($customer->getGroupOption('campaigns.show_geo_opens', 'no') != 'yes') {
            return true;
        }

        if (is_subaccount() && !subaccount()->canManageCampaigns()) {
            return true;
        }

        return false;
    }
}
