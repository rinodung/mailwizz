<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * PaymentGatewayPaypalExtCommon
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 */

class PaymentGatewayPaypalExtCommon extends ExtensionModel
{
    /**
     * Flags
     */
    const STATUS_ENABLED  = 'enabled';
    const STATUS_DISABLED = 'disabled';
    const MODE_SANDBOX    = 'sandbox';
    const MODE_LIVE       = 'live';

    /**
     * @var string
     */
    public $email = '';

    /**
     * @var string
     */
    public $mode = self::MODE_SANDBOX;

    /**
     * @var string
     */
    public $status = self::STATUS_DISABLED;

    /**
     * @var int
     */
    public $sort_order = 1;

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            ['email, mode, status, sort_order', 'required'],
            ['email', 'email', 'validateIDN' => true],
            ['status', 'in', 'range' => array_keys($this->getStatusesDropDown())],
            ['mode', 'in', 'range' => array_keys($this->getModes())],
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
            'email'      => $this->t('Email'),
            'mode'       => $this->t('Mode'),
            'status'     => t('app', 'Status'),
            'sort_order' => t('app', 'Sort order'),
        ];

        return CMap::mergeArray($labels, parent::attributeLabels());
    }

    /**
     * @return array
     */
    public function attributePlaceholders()
    {
        $placeholders = [];
        return CMap::mergeArray($placeholders, parent::attributePlaceholders());
    }

    /**
     * @return array
     */
    public function attributeHelpTexts()
    {
        $texts = [
            'email'      => $this->t('Your paypal email address where the payments should go'),
            'mode'       => $this->t('Whether the payments are live or run in sandbox'),
            'status'     => $this->t('Whether this gateway is enabled and can be used for payments processing'),
            'sort_order' => $this->t('The sort order for this gateway'),
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
     * @return array
     */
    public function getModes(): array
    {
        return [
            self::MODE_SANDBOX => ucfirst($this->t(self::MODE_SANDBOX)),
            self::MODE_LIVE    => ucfirst($this->t(self::MODE_LIVE)),
        ];
    }

    /**
     * @return string
     */
    public function getModeUrl(): string
    {
        if ($this->mode == self::MODE_LIVE) {
            return 'https://www.paypal.com/cgi-bin/webscr';
        }
        return 'https://www.sandbox.paypal.com/cgi-bin/webscr';
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
