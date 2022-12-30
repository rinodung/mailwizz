<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * OptionApiIpAccess
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.5.9
 */

class OptionApiIpAccess extends OptionBase
{
    /**
     * @var string
     */
    public $allowed_ips = '';

    /**
     * @var string
     */
    public $denied_ips  = '';

    /**
     * @var string
     */
    protected $_categoryName = 'system.api.ip_access';

    /**
     * @return array
     * @throws CException
     */
    public function rules()
    {
        $rules = [
            ['allowed_ips, denied_ips', 'length', 'max' => 10000],
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
            'allowed_ips' => $this->t('Allowed IPs'),
            'denied_ips'  => $this->t('Denied IPs'),
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
            'allowed_ips' => '123.123.123.123, 12.12.12.12',
            'denied_ips'  => '11.11.11.11, 22.22.22.22',
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
            'allowed_ips' => $this->t('List of IPs allowed to access the api. Separate multiple IPs by a comma'),
            'denied_ips'  => $this->t('List of IPs denied to access the api. Separate multiple IPs by a comma'),
        ];

        return CMap::mergeArray($texts, parent::attributeHelpTexts());
    }

    /**
     * @return array
     */
    public function getAllowedIps(): array
    {
        return CommonHelper::getArrayFromString((string)$this->allowed_ips);
    }

    /**
     * @return array
     */
    public function getDeniedIps(): array
    {
        return CommonHelper::getArrayFromString((string)$this->denied_ips);
    }

    /**
     * @return bool
     */
    protected function beforeValidate()
    {
        $keys = ['allowed_ips', 'denied_ips'];
        foreach ($keys as $key) {
            $ipList = CommonHelper::getArrayFromString((string)$this->$key);
            foreach ($ipList as $index => $ip) {
                if (!FilterVarHelper::ip($ip)) {
                    unset($ipList[$index]);
                }
            }
            $this->$key = CommonHelper::getStringFromArray($ipList);
        }

        return parent::beforeValidate();
    }
}
