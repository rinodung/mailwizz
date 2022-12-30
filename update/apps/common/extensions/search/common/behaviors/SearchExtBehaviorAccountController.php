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

class SearchExtBehaviorAccountController extends SearchExtBaseBehavior
{
    /**
     * @return array
     */
    public function searchableActions(): array
    {
        return [
            'index' => [
                'keywords'  => ['account', 'my account', 'update account'],
            ],
            '2fa' => [
                'keywords'  => ['2fa', 'two factor auth', '2 factor auth'],
                'skip'      => [$this, '_2faSkip'],
            ],
        ];
    }

    /**
     * @return bool
     */
    public function _2faSkip(): bool
    {
        /** @var OptionTwoFactorAuth $twoFaSettings */
        $twoFaSettings = container()->get(OptionTwoFactorAuth::class);
        return !$twoFaSettings->getIsEnabled();
    }
}
