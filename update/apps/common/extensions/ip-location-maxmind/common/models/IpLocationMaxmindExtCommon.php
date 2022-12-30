<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * IpLocationMaxmindExtCommon
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 */

class IpLocationMaxmindExtCommon extends ExtensionModel
{
    /**
     * Status flags
     */
    const STATUS_ENABLED = 'enabled';
    const STATUS_DISABLED = 'disabled';

    /**
     * @var string
     */
    public $status = self::STATUS_DISABLED;

    /**
     * @var int
     */
    public $sort_order = 0;

    /**
     * @var string
     */
    public $status_on_email_open = self::STATUS_DISABLED;

    /**
     * @var string
     */
    public $status_on_track_url = self::STATUS_DISABLED;

    /**
     * @var string
     */
    public $status_on_unsubscribe = self::STATUS_DISABLED;

    /**
     * @var string
     */
    public $status_on_customer_login = self::STATUS_DISABLED;

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            ['status, status_on_email_open, status_on_track_url, status_on_unsubscribe, status_on_customer_login, sort_order', 'required'],
            ['status, status_on_email_open, status_on_track_url, status_on_unsubscribe, status_on_customer_login', 'in', 'range' => array_keys($this->getStatusesDropDown())],
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
            'status_on_email_open'     => $this->t('Status on email open'),
            'status_on_track_url'      => $this->t('Status on track url'),
            'status_on_unsubscribe'    => $this->t('Status on unsubscribe'),
            'status_on_customer_login' => $this->t('Status on customer login'),
            'sort_order'               => $this->t('Sort order'),
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
            'status'                   => $this->t('Whether this service is enabled and can be used'),
            'status_on_email_open'     => $this->t('Whether to collect ip location information when a campaign email is opened'),
            'status_on_track_url'      => $this->t('Whether to collect ip location information when a campaign link is clicked and tracked'),
            'status_on_unsubscribe'    => $this->t('Whether to collect ip location information when a subscriber unsubscribes via a campaign'),
            'status_on_customer_login' => $this->t('Whether to collect ip location information when a customer logs in'),
            'sort_order'               => $this->t('If multiple location services active, sort order decides which one queries first'),
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
     * @return bool
     */
    public function getIsEnabledOnEmailOpen(): bool
    {
        return $this->status_on_email_open === self::STATUS_ENABLED;
    }

    /**
     * @return bool
     */
    public function getIsEnabledOnTrackUrl(): bool
    {
        return $this->status_on_track_url === self::STATUS_ENABLED;
    }

    /**
     * @return bool
     */
    public function getIsEnabledOnUnsubscribe(): bool
    {
        return $this->status_on_unsubscribe === self::STATUS_ENABLED;
    }

    /**
     * @return bool
     */
    public function getIsEnabledOnCustomerLogin(): bool
    {
        return $this->status_on_customer_login === self::STATUS_ENABLED;
    }
}
