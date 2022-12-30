<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * OptionCustomerSubaccountPermissionsDomains
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.0.0
 */

class OptionCustomerSubaccountPermissionsDomains extends OptionCustomerSubaccountPermissions
{
    /**
     * @var string
     */
    public $manage = self::TEXT_YES;

    /**
     * @return string
     */
    public function getPermissionsCategoryName(): string
    {
        return 'domains';
    }

    /**
     * @param Customer $parent
     *
     * @return bool
     */
    public function getParentCustomerIsAllowedAccess(Customer $parent): bool
    {
        return $parent->getGroupOption('sending_domains.can_manage_sending_domains', 'no') === 'yes' ||
               $parent->getGroupOption('tracking_domains.can_manage_tracking_domains', 'no') === 'yes';
    }

    /**
     * @return array
     * @throws CException
     */
    public function rules()
    {
        $rules = [
            ['manage', 'required'],
            ['manage', 'in', 'range' => array_keys($this->getYesNoOptions())],
        ];

        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array
     * @throws CException
     */
    public function attributeLabels()
    {
        $labels = [
            'manage' => $this->t('Manage domains'),
        ];

        return CMap::mergeArray($labels, parent::attributeLabels());
    }

    /**
     * @return array
     * @throws CException
     */
    public function attributeHelpTexts()
    {
        $texts = [
            'manage' => $this->t('Whether this subaccount can manage all operations related to sending and tracking domains. Assign with care!'),
        ];

        return CMap::mergeArray($texts, parent::attributeHelpTexts());
    }

    /**
     * @return bool
     */
    public function getCanManage(): bool
    {
        return (string)$this->manage === self::TEXT_YES;
    }
}
