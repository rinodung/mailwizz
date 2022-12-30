<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * OptionCampaignBlacklistWords
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.5.9
 */

class OptionCampaignBlacklistWords extends OptionBase
{
    /**
     * @var string
     */
    public $enabled = self::TEXT_NO;

    /**
     * @var string
     */
    public $subject = '';

    /**
     * @var string
     */
    public $content = '';

    /**
     * @var string
     */
    public $notifications_to = '';

    /**
     * @var string
     */
    protected $_categoryName = 'system.campaign.blacklist_words';

    /**
     * @return array
     * @throws CException
     */
    public function rules()
    {
        $rules = [
            ['enabled', 'required'],
            ['enabled', 'in', 'range' => array_keys($this->getYesNoOptions())],
            ['subject, content, notifications_to', 'length', 'max' => 10000],
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
            'enabled'          => t('app', 'Enabled'),
            'subject'          => $this->t('Subject'),
            'content'          => $this->t('Content'),
            'notifications_to' => $this->t('Notifications'),
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
     * @return array
     * @throws CException
     */
    public function attributeHelpTexts()
    {
        $texts = [
            'enabled'          => t('app', 'Whether the feature is enabled'),
            'subject'          => $this->t('Words for campaign subject, separated by a comma'),
            'content'          => $this->t('Words for campaign content, separated by a comma'),
            'notifications_to' => $this->t('What email addresses to notify when a campaign is blocked. Separate multiple email addresses by a comma'),
        ];

        return CMap::mergeArray($texts, parent::attributeHelpTexts());
    }

    /**
     * @return bool
     */
    public function getIsEnabled(): bool
    {
        return $this->enabled === self::TEXT_YES;
    }

    /**
     * @return array
     */
    public function getSubjectWords(): array
    {
        if (empty($this->subject)) {
            return [];
        }

        return CommonHelper::getArrayFromString((string)$this->subject);
    }

    /**
     * @return array
     */
    public function getContentWords(): array
    {
        if (empty($this->content)) {
            return [];
        }
        return CommonHelper::getArrayFromString((string)$this->content);
    }

    /**
     * @return array
     */
    public function getNotificationsTo(): array
    {
        if (empty($this->notifications_to)) {
            return [];
        }

        return CommonHelper::getArrayFromString((string)$this->notifications_to);
    }

    /**
     * @return bool
     */
    protected function beforeValidate()
    {
        $keys = ['subject', 'content', 'notifications_to'];
        foreach ($keys as $key) {
            $data = CommonHelper::getArrayFromString((string)$this->$key);
            if ($key == 'notifications_to') {
                foreach ($data as $index => $email) {
                    if (!FilterVarHelper::email($email)) {
                        unset($data[$index]);
                    }
                }
            }
            $this->$key = CommonHelper::getStringFromArray($data);
        }

        return parent::beforeValidate();
    }
}
