<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * EmailVerificationExtBaseCommon
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.0.0
 */
abstract class EmailVerificationExtBaseCommon extends ExtensionModel
{
    /**
     * @var string
     */
    public $enabled = self::TEXT_NO;

    /**
     * @var array
     */
    public $customer_groups = [];

    /**
     * @var array
     */
    public $check_zones = [];


    /**
     * @return array
     * @throws CException
     */
    public function rules()
    {
        $rules = [
            ['customer_groups, check_zones', 'safe'],
            ['enabled', 'in', 'range' => array_keys($this->getYesNoOptions())],
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
            'enabled'         => t('app', 'Enabled'),
            'customer_groups' => $this->t('Customer groups'),
            'check_zones'     => $this->t('Check zones'),
        ];
        return CMap::mergeArray($labels, parent::attributeLabels());
    }

    /**
     * @return array
     * @throws CException
     */
    public function attributePlaceholders()
    {
        $placeholders = [];
        return CMap::mergeArray($placeholders, parent::attributePlaceholders());
    }

    /**
     * @inheritDoc
     */
    public function attributeHelpTexts()
    {
        $texts = [
            'enabled'         => t('app', 'Whether the feature is enabled'),
            'customer_groups' => $this->t('Decide which customer groups can make use of this extension. If no group is selected, then all customers can use it. Please note that customers must use their own service credentials to validate the emails.'),
            'check_zones'     => $this->t('Select the zones where email validation will run. If no zone is selected, then validation will run everywhere.'),

        ];
        return CMap::mergeArray($texts, parent::attributeHelpTexts());
    }

    /**
     * @inheritDoc
     */
    public function getCategoryName(): string
    {
        return $this->getOptionsPrefix();
    }

    /**
     * @return array
     */
    public function getCustomerGroupsList(): array
    {
        $groups = CustomerGroup::model()->findAll();
        $list   = [];

        foreach ($groups as $group) {
            $list[$group->group_id] = $group->name;
        }

        return $list;
    }

    /**
     * @return array
     */
    public function getCheckZonesList(): array
    {
        $zones = [];
        foreach (EmailBlacklist::getCheckZones() as $zone) {
            $zones[$zone] = $this->t(ucwords(str_replace('_', ' ', $zone)));
        }
        return $zones;
    }

    /**
     * @return bool
     */
    abstract public function getIsEnabled(): bool;

    /**
     * @return string
     */
    abstract public function getName(): string;

    /**
     * @return string
     */
    abstract public function getDescription(): string;

    /**
     * @return array
     */
    abstract public function getCustomerGroups(): array;

    /**
     * @return array
     */
    abstract public function getCheckZones(): array;

    /**
     * @return string
     */
    abstract public function getOptionsPrefix(): string;

    /**
     * @return void
     */
    abstract public function addFilter(): void;

    /**
     * @return string
     */
    abstract public function getApiKey(): string;
}
