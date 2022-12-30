<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * PaymentGatewayOfflineExtCommon
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 */

class PaymentGatewayOfflineExtCommon extends ExtensionModel
{
    /**
     * Flags
     */
    const STATUS_ENABLED  = 'enabled';
    const STATUS_DISABLED = 'disabled';

    /**
     * @var string
     */
    public $description = '';

    /**
     * @var string
     */
    public $status = self::STATUS_DISABLED;

    /**
     * @var int
     */
    public $sort_order = 2;

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            ['description', 'safe'],
            ['status', 'in', 'range' => array_keys($this->getStatusesDropDown())],
            ['sort_order', 'numerical', 'integerOnly' => true, 'min' => 0, 'max' => 999],
            ['sort_order', 'length', 'min' => 1, 'max' => 3],
        ];

        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'description' => $this->t('Description'),
            'status'      => t('app', 'Status'),
            'sort_order'  => t('app', 'Sort order'),
        ];

        return CMap::mergeArray($labels, parent::attributeLabels());
    }

    /**
     * @return array
     */
    public function attributePlaceholders()
    {
        $placeholders = [
            'description' => '',
        ];

        return CMap::mergeArray($placeholders, parent::attributePlaceholders());
    }

    /**
     * @return array
     */
    public function attributeHelpTexts()
    {
        $texts = [
            'description' => $this->t('The needed details for customers to see and to use in order to make the offline payment'),
            'status'      => $this->t('Whether this gateway is enabled and can be used for payments processing'),
            'sort_order'  => $this->t('The sort order for this gateway'),
        ];

        return CMap::mergeArray($texts, parent::attributeHelpTexts());
    }

    /**
     * @inheritDoc
     */
    public function getCategoryName(): string
    {
        return '';
    }

    /**
     * @return array
     */
    public function getStatusesDropDown(): array
    {
        return [
            self::STATUS_DISABLED => t('app', 'Disabled'),
            self::STATUS_ENABLED  => t('app', 'Enabled'),
        ];
    }

    /**
     * @return array
     */
    public function getSortOrderDropDown(): array
    {
        $options = [];
        for ($i = 0; $i < 100; ++$i) {
            $options[$i] = $i;
        }
        return $options;
    }

    /**
     * @return bool
     */
    public function getIsEnabled(): bool
    {
        return $this->status === self::STATUS_ENABLED;
    }

    /**
     * @return int
     */
    public function getSortOrder(): int
    {
        return (int)$this->sort_order;
    }
}
