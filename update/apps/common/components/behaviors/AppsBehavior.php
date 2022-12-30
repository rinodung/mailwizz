<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * AppsBehavior
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

/**
 * @property CWebApplication $owner
 */
class AppsBehavior extends CBehavior
{
    /**
     * @var array
     */
    private $_availableApps = [];

    /**
     * @var array
     */
    private $_webApps = [];

    /**
     * @var array
     */
    private $_notWebApps = [];

    /**
     * @var string
     */
    private $_currentAppName;

    /**
     * @var bool
     */
    private $_currentAppIsWeb;

    /**
     * @var string
     */
    private $_cdnSubdomain;

    /**
     * AppsBehavior::setAvailableApps()
     *
     * @param array $apps
     * @return AppsBehavior
     */
    public function setAvailableApps(array $apps): self
    {
        if (!empty($this->_availableApps)) {
            return $this;
        }
        $this->_availableApps = $apps;
        return $this;
    }

    /**
     * AppsBehavior::getAvailableApps()
     *
     * @return array
     */
    public function getAvailableApps(): array
    {
        return $this->_availableApps;
    }

    /**
     * AppsBehavior::setWebApps()
     *
     * @param array $apps
     * @return  AppsBehavior
     */
    public function setWebApps(array $apps): self
    {
        if (!empty($this->_webApps)) {
            return $this;
        }
        $this->_webApps = $apps;
        return $this;
    }

    /**
     * AppsBehavior::getWebApps()
     *
     * @return array
     */
    public function getWebApps(): array
    {
        return $this->_webApps;
    }

    /**
     * AppsBehavior::setNotWebApps()
     *
     * @param array $apps
     * @return AppsBehavior
     */
    public function setNotWebApps(array $apps): self
    {
        if (!empty($this->_notWebApps)) {
            return $this;
        }
        $this->_notWebApps = $apps;
        return $this;
    }

    /**
     * AppsBehavior::getNotWebApps()
     *
     * @return array
     */
    public function getNotWebApps(): array
    {
        return $this->_notWebApps;
    }

    /**
     * AppsBehavior::setCurrentAppName()
     *
     * @param string $appName
     * @return AppsBehavior
     */
    public function setCurrentAppName(string $appName): self
    {
        if ($this->_currentAppName !== null) {
            return $this;
        }
        $this->_currentAppName = $appName;
        return $this;
    }

    /**
     * AppsBehavior::getCurrentAppName()
     *
     * @return string
     */
    public function getCurrentAppName(): string
    {
        return (string)$this->_currentAppName;
    }

    /**
     * AppsBehavior::setCurrentAppIsWeb()
     *
     * @param bool $isWeb
     * @return AppsBehavior
     */
    public function setCurrentAppIsWeb(bool $isWeb): self
    {
        if ($this->_currentAppIsWeb !== null) {
            return $this;
        }
        $this->_currentAppIsWeb = (bool)$isWeb;
        return $this;
    }

    /**
     * AppsBehavior::getCurrentAppIsWeb()
     *
     * @return bool
     */
    public function getCurrentAppIsWeb(): bool
    {
        return $this->_currentAppIsWeb;
    }

    /**
     * AppsBehavior::setCdnSubdomain()
     *
     * @param string $cdnSubdomain
     *
     * @return AppsBehavior
     */
    public function setCdnSubdomain(string $cdnSubdomain): self
    {
        if (!empty($cdnSubdomain) && stripos($cdnSubdomain, 'http') !== 0) {
            $cdnSubdomain = 'http://' . $cdnSubdomain;
        }
        $this->_cdnSubdomain = $cdnSubdomain;
        return $this;
    }

    /**
     * AppsBehavior::getCdnSubdomain()
     *
     * @return string
     */
    public function getCdnSubdomain(): string
    {
        return (string)$this->_cdnSubdomain;
    }

    /**
     * AppsBehavior::isAppName()
     *
     * @param string $appName
     * @return bool
     */
    public function isAppName(string $appName): bool
    {
        return strtolower((string)$appName) === strtolower((string)$this->getCurrentAppName());
    }

    /**
     * @param string $appName
     * @param bool $absolute
     * @param bool $hideScriptName
     *
     * @return string
     */
    public function getAppBaseUrl(string $appName = '', bool $absolute = false, bool $hideScriptName = false): string
    {
        if (empty($appName)) {
            $appName = $this->getCurrentAppName();
        }

        if (!in_array($appName, $this->getWebApps())) {
            return '';
        }

        $currentApp = (string)$this->getCurrentAppName();
        $baseUrl    = (string)$this->owner->getBaseUrl($absolute);
        $baseUrl    = (string)preg_replace('/(\/frontend)$/ix', '', $baseUrl);

        if ($appName == 'frontend') {
            $appName = null;
        }

        $url = preg_replace('/\/(' . preg_quote($currentApp, '/') . ')$/ix', '', $baseUrl) .
               (!empty($appName) ? '/' . ltrim((string)$appName, '/') : '') .
               '/';

        $showScriptName = $this->owner->getUrlManager()->showScriptName;

        if (!$hideScriptName && $showScriptName) {
            $url .= 'index.php/';
        }

        return $url;
    }

    /**
     * @param string $appName
     * @param string $uri
     * @param bool $absolute
     * @param bool $hideScriptName
     *
     * @return string
     */
    public function getAppUrl(string $appName = '', string $uri = '', bool $absolute = false, bool $hideScriptName = false): string
    {
        if (!($base = $this->getAppBaseUrl($appName, $absolute, $hideScriptName))) {
            return '';
        }

        if (substr($base, -1, 1) != '/') {
            $base .= '/';
        }

        $fullUrl = $base . ltrim((string)$uri, '/');
        if ($this->getCdnSubdomain() !== false && $this->getCanUseCdnSubdomain($absolute, $uri, $fullUrl)) {
            if ($this->getCdnSubdomain() === '') {
                $this->setCdnSubdomain('');

                /** @var OptionCdn $optionCdn */
                $optionCdn = container()->get(OptionCdn::class);

                if ($optionCdn->getIsEnabled() && ($cdnDomain = $optionCdn->getSubdomain())) {
                    $this->setCdnSubdomain($cdnDomain);
                }
            }
            if ($this->getCdnSubdomain()) {
                return sprintf('%s/%s', $this->getCdnSubdomain(), trim((string)$fullUrl, '/'));
            }
        }

        return $fullUrl;
    }

    /**
     * @param string $appendThis
     * @param bool $absolute
     *
     * @return string
     */
    public function getBaseUrl(string $appendThis = '', bool $absolute = false): string
    {
        $relative = (string)$this->owner->getBaseUrl();
        $baseUrl  = (string)preg_replace('/\/?' . preg_quote((string)$this->getCurrentAppName(), '/') . '\/?$/', '', $relative);
        $baseUrl  = '/' . trim((string)$baseUrl, '/') . '/' . trim((string)$appendThis, '/');
        $baseUrl  = (string)str_replace('//', '/', $baseUrl);

        if ($absolute) {
            $absoluteUrl = (string)$this->owner->getBaseUrl(true);
            $absoluteUrl = (string)str_replace($relative, '', $absoluteUrl);
            $baseUrl     = $absoluteUrl . (string)str_replace('//', '/', $baseUrl);
        }

        if ($this->getCdnSubdomain() !== false && $this->getCanUseCdnSubdomain($absolute, $appendThis, $baseUrl)) {
            if ($this->getCdnSubdomain() === '') {
                $this->setCdnSubdomain('');

                /** @var OptionCdn $optionCdn */
                $optionCdn = container()->get(OptionCdn::class);
                if ($optionCdn->getIsEnabled() && ($cdnDomain = $optionCdn->getSubdomain())) {
                    $this->setCdnSubdomain($cdnDomain);
                }
            }
            if ($this->getCdnSubdomain()) {
                return sprintf('%s/%s', $this->getCdnSubdomain(), ltrim((string)$baseUrl, '/'));
            }
        }

        return $baseUrl;
    }

    /**
     * @param string $appendThis
     *
     * @return string
     */
    public function getCurrentHostUrl(string $appendThis = ''): string
    {
        $info  = $this->getAppUrl('frontend', '/', true, true);
        $host  = parse_url($info, PHP_URL_SCHEME) . '://';
        $host .= parse_url($info, PHP_URL_HOST);

        if (($port = (int)parse_url($info, PHP_URL_PORT)) && $port > 0 && $port != 80 && $port != 443) {
            $host .= ':' . $port;
        }

        if ($appendThis) {
            $host .= '/' . ltrim((string)$appendThis, '/');
        }

        return $host;
    }

    /**
     * @param bool $absolute
     * @param string $uri
     * @param string $fullUrl
     *
     * @return bool
     */
    protected function getCanUseCdnSubdomain(bool $absolute, string $uri, string $fullUrl): bool
    {
        if ($absolute || !$uri || !$fullUrl) {
            return false;
        }

        if (strpos($fullUrl, 'http') === 0 || stripos($fullUrl, '//') === 0 || FilterVarHelper::url($fullUrl)) {
            return false;
        }

        $uriPath = (string)parse_url($uri, PHP_URL_PATH);
        if (!(strlen($extension = strtolower(pathinfo($uriPath, PATHINFO_EXTENSION))))) {
            return false;
        }

        $allowedExtensions = ['css', 'js', 'png', 'jpg', 'jpeg', 'gif'];
        $allowedExtensions = array_map('strtolower', $allowedExtensions);

        return in_array($extension, $allowedExtensions);
    }
}
