<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * OptionCustomerSubaccountPermissionsServers
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.0.0
 */

class OptionCustomerSubaccountPermissionsServers extends OptionCustomerSubaccountPermissions
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
        return 'servers';
    }

    /**
     * @param Customer $parent
     *
     * @return bool
     */
    public function getParentCustomerIsAllowedAccess(Customer $parent): bool
    {
        return ((int)$parent->getGroupOption('servers.max_bounce_servers', 0)) ||
               ((int)$parent->getGroupOption('servers.max_delivery_servers', 0)) ||
               ((int)$parent->getGroupOption('servers.max_fbl_servers', 0)) ||
               ((int)$parent->getGroupOption('servers.max_email_box_monitors', 0));
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
            'manage' => $this->t('Manage servers'),
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
            'manage' => $this->t('Whether this subaccount can manage all operations related to delivery, bounce and feedback loop servers. Assign with care!'),
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
