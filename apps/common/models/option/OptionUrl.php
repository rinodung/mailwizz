<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * OptionUrl
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.0.0
 */

class OptionUrl extends OptionBase
{
    /**
     * Schema flag for HTTP
     */
    const SCHEME_HTTP = 'http';

    /**
     * Schema flag for HTTPS
     */
    const SCHEME_HTTPS = 'https';

    /**
     * @var string
     */
    public $api_absolute_url = '';

    /**
     * @var string
     */
    public $backend_absolute_url = '';

    /**
     * @var string
     */
    public $customer_absolute_url = '';

    /**
     * @var string
     */
    public $frontend_absolute_url = '';

    /**
     * @var string
     */
    public $scheme = 'http';

    /**
     * @var string
     */
    public $hash = '';

    /**
     * @var string
     */
    protected $_categoryName = 'system.urls';

    /**
     * @return array
     * @throws CException
     */
    public function rules()
    {
        $rules = [];
        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array
     * @throws CException
     */
    public function attributeLabels()
    {
        $labels = [];
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
        $texts = [];
        return CMap::mergeArray($texts, parent::attributeHelpTexts());
    }

    /**
     * @inheritDoc
     */
    public function save()
    {
        return false;
    }

    /**
     * @param string $append
     *
     * @return string
     */
    public function getApiUrl(string $append = ''): string
    {
        return $this->api_absolute_url . (!empty($append) ? $append : '');
    }

    /**
     * @param string $append
     *
     * @return string
     */
    public function getBackendUrl(string $append = ''): string
    {
        return $this->backend_absolute_url . (!empty($append) ? $append : '');
    }

    /**
     * @param string $append
     *
     * @return string
     */
    public function getCustomerUrl(string $append = ''): string
    {
        return $this->customer_absolute_url . (!empty($append) ? $append : '');
    }

    /**
     * @param string $append
     *
     * @return string
     */
    public function getFrontendUrl(string $append = ''): string
    {
        return $this->frontend_absolute_url . (!empty($append) ? $append : '');
    }

    /**
     * @param string $append
     * @return string
     */
    public function getCurrentAppUrl(string $append = ''): string
    {
        return $this->getAppUrlByName(apps()->getCurrentAppName(), $append);
    }

    /**
     * @param string $name
     * @param string $append
     * @return string
     */
    public function getAppUrlByName(string $name = 'frontend', string $append = ''): string
    {
        if ($name === 'backend') {
            return $this->getBackendUrl($append);
        }

        if ($name === 'customer') {
            return $this->getCustomerUrl($append);
        }

        if ($name === 'api') {
            return $this->getApiUrl($append);
        }

        return $this->getFrontendUrl($append);
    }

    /**
     * @return void
     */
    public function regenerateSystemUrls(): void
    {
        if (is_cli()) {
            return;
        }

        foreach (apps()->getWebApps() as $appName) {
            $baseUrl = apps()->getAppUrl($appName, '', true);
            if ($this->scheme == self::SCHEME_HTTPS) {
                $baseUrl = preg_replace('#^http://#', 'https://', $baseUrl);
            } else {
                $baseUrl = preg_replace('#^https://#', 'http://', $baseUrl);
            }
            $this->saveAttributes([$appName . '_absolute_url' => $baseUrl]);
        }
    }
}
