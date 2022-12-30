<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * OptionCampaignExcludeIpsFromTracking
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.5.8
 */

class OptionCampaignExcludeIpsFromTracking extends OptionBase
{
    /**
     * Action flags
     */
    const ACTION_OPEN  = 'open';
    const ACTION_CLICK = 'click';

    /**
     * @var string
     */
    public $open = '';

    /**
     * @var string
     */
    public $url = '';

    /**
     * @var string
     */
    protected $_categoryName = 'system.campaign.exclude_ips_from_tracking';

    /**
     * @return array
     * @throws CException
     */
    public function rules()
    {
        $rules = [
            ['open, url', 'length', 'max' => 60000],
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
            'open' => $this->t('Exclude from open tracking'),
            'url'  => $this->t('Exclude from url tracking'),
        ];

        return CMap::mergeArray($labels, parent::attributeLabels());
    }

    /**
     * @return array
     * @throws CException
     */
    public function attributePlaceholders()
    {
        $placeholders = [
            'open' => '11.11.11.11, 22.22.22.22, 33.33.33.33',
            'url'  => '11.11.11.11, 22.22.22.22, 33.33.33.33',
        ];

        return CMap::mergeArray($placeholders, parent::attributePlaceholders());
    }

    /**
     * @return array
     * @throws CException
     */
    public function attributeHelpTexts()
    {
        $texts = [
            'open' => $this->t('IPs list, separated by a comma, to exclude from open tracking'),
            'url'  => $this->t('IPs list, separated by a comma, to exclude from url tracking'),
        ];

        return CMap::mergeArray($texts, parent::attributeHelpTexts());
    }

    /**
     * @return array
     */
    public function getOpenList(): array
    {
        $ipsList = explode(',', $this->open);
        return array_unique(array_map('trim', $ipsList));
    }

    /**
     * @return array
     */
    public function getUrlList(): array
    {
        $ipsList = explode(',', $this->url);
        return array_unique(array_map('trim', $ipsList));
    }

    /**
     * @param string $ipAddress
     * @param string $action
     *
     * @return bool
     */
    public function canTrackIpAction(string $ipAddress = '', string $action = self::ACTION_OPEN): bool
    {
        $ipAddress = !$ipAddress && !is_cli() ? (string)request()->getUserHostAddress() : $ipAddress;
        if (empty($ipAddress)) {
            return false;
        }

        $ipsList = $action === self::ACTION_OPEN ? $this->getOpenList() : $this->getUrlList();
        if (empty($ipsList)) {
            return false;
        }

        return !IpHelper::isIpInRange($ipAddress, $ipsList);
    }

    /**
     * @return bool
     */
    protected function beforeValidate()
    {
        $keys = ['open', 'url'];
        foreach ($keys as $key) {
            if (!empty($this->$key)) {
                $ips = explode(',', $this->$key);
                $_key = [];
                foreach ($ips as $ip) {
                    $ip = trim((string)$ip);
                    if (empty($ip)) {
                        continue;
                    }
                    if (FilterVarHelper::ip($ip)) {
                        $_key[] = $ip;
                    }
                }
                $_key = array_unique($_key);
                $this->$key = implode(', ', $_key);
            }
        }

        return parent::beforeValidate();
    }
}
