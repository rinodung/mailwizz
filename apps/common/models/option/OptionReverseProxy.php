<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * OptionReverseProxy
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.1.10
 */

class OptionReverseProxy extends OptionBase
{
    /**
     * @var string
     */
    public $site_behind_cloudflare = self::TEXT_NO;

    /**
     * @var string
     */
    public $site_behind_regular_reverse_proxy = self::TEXT_NO;

    /**
     * @var string
     */
    protected $_categoryName = 'system.reverse_proxy';

    /**
     * @return array
     * @throws CException
     */
    public function rules()
    {
        $rules = [
            ['site_behind_cloudflare, site_behind_regular_reverse_proxy', 'required'],
            ['site_behind_cloudflare, site_behind_regular_reverse_proxy', 'in', 'range' => array_keys($this->getYesNoOptions())],
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
            'site_behind_cloudflare'            => $this->t('Site behind Cloudflare'),
            'site_behind_regular_reverse_proxy' => $this->t('Site behind regular reverse proxy'),
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
            'site_behind_cloudflare'            => $this->t('Whether the website runs behind the reverse proxy service offered by Cloudflare. This is used to better detect various runtime options, such as the IP address of people accessing the site.'),
            'site_behind_regular_reverse_proxy' => $this->t('Whether the website runs behind a regular reverse proxy or load balancer, such as nginx reverse proxy, haproxy, etc. This is used to better detect various runtime options, such as the IP address of people accessing the site.'),
        ];

        return CMap::mergeArray($texts, parent::attributeHelpTexts());
    }

    /**
     * @param array $headerKeys
     *
     * @return string
     * @throws CException
     */
    public function getUserHostAddressFromServerHeaderKeys(array $headerKeys): string
    {
        foreach ($headerKeys as $headerKey) {
            /** @var string[] $ips */
            $ips = array_map('trim', explode(',', (string)request()->getServer($headerKey, '')));
            if ((int)count($ips) === 0) {
                continue;
            }

            // First IP Address in the list is the client IP Address
            // https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/X-Forwarded-For
            $ip = (string)array_shift($ips);

            if (FilterVarHelper::ip($ip)) {
                return $ip;
            }
        }

        return '';
    }

    /**
     * @return bool
     */
    public function getIsSiteBehindCloudflare(): bool
    {
        return (string)$this->site_behind_cloudflare === self::TEXT_YES;
    }

    /**
     * @return string[]
     */
    public function getCloudflareHeaderKeys(): array
    {
        return ['HTTP_CF_CONNECTING_IP'];
    }

    /**
     * @return string
     * @throws CException
     */
    public function getUserHostAddressFromCloudflare(): string
    {
        return $this->getUserHostAddressFromServerHeaderKeys($this->getCloudflareHeaderKeys());
    }

    /**
     * @return bool
     */
    public function getIsSiteBehindRegularReverseProxy(): bool
    {
        return (string)$this->site_behind_regular_reverse_proxy === self::TEXT_YES;
    }

    /**
     * @return string[]
     */
    public function getRegularReverseProxyHeaderKeys(): array
    {
        return ['HTTP_X_FORWARDED_FOR'];
    }

    /**
     * @return string
     * @throws CException
     */
    public function getUserHostAddressFromRegularReverseProxy(): string
    {
        return $this->getUserHostAddressFromServerHeaderKeys($this->getRegularReverseProxyHeaderKeys());
    }

    /**
     * @return bool
     */
    public function getIsSiteBehindReverseProxy(): bool
    {
        return $this->getIsSiteBehindCloudflare() || $this->getIsSiteBehindRegularReverseProxy();
    }

    /**
     * @return bool
     * @throws CException
     */
    public function getSiteLooksLikeBehindReverseProxy(): bool
    {
        return $this->getUserHostAddressFromCloudflare() || $this->getUserHostAddressFromRegularReverseProxy();
    }

    /**
     * @return string
     * @throws CException
     */
    public function getUserHostAddress(): string
    {
        $ipAddress = '';
        if ($this->getIsSiteBehindCloudflare()) {
            $ipAddress = $this->getUserHostAddressFromCloudflare();
        } elseif ($this->getIsSiteBehindRegularReverseProxy()) {
            $ipAddress = $this->getUserHostAddressFromRegularReverseProxy();
        }

        if (!$ipAddress) {
            $ipAddress = !empty($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1';
        }

        return $ipAddress;
    }

    /**
     * @return void
     * @throws CException
     */
    public function rewriteServerUserHostAddress(): void
    {
        $_SERVER['REMOTE_ADDR'] = $this->getUserHostAddress();
    }
}
