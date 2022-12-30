<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * RecaptchaExtDomainsKeysPair
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 */

class RecaptchaExtDomainsKeysPair extends ExtensionModel
{
    /**
     * @var string
     */
    public $domain = '';

    /**
     * @var string
     */
    public $site_key = '';

    /**
     * @var string
     */
    public $secret_key = '';

    /**
     * @return array
     * @throws CException
     */
    public function rules()
    {
        $rules = [
            ['domain, site_key, secret_key', 'required'],
            ['domain', '_validateDomain'],
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
            'domain'     => $this->t('Domain'),
            'site_key'   => $this->t('Site key'),
            'secret_key' => $this->t('Secret key'),
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
            'domain'     => 'domain-1.com, domain-2.com, domain-3.com',
            'site_key'   => '6LegYwsTBBBCCPdpjWct69ScnOMG9ZRv2vy8Xbbj',
            'secret_key' => '6LegYwsTBBBCCxQmCT54Q_0bIwZH94ogQwNQCpE',
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
            'domain'     => $this->t('The domain(s) where this key pair will be applied'),
            'site_key'   => $this->t('The site key for recaptcha service'),
            'secret_key' => $this->t('The secret key for recaptcha service'),
        ];
        return CMap::mergeArray($texts, parent::attributeHelpTexts());
    }

    /**
     * @return bool
     */
    public function save()
    {
        return false;
    }

    /**
     * @return void
     */
    public function refresh(): void
    {
    }

    /**
     * @inheritDoc
     */
    public function getCategoryName(): string
    {
        return '';
    }

    /**
     * @return string
     */
    public function getSiteKey(): string
    {
        return (string)$this->site_key;
    }

    /**
     * @return string
     */
    public function getSecretKey(): string
    {
        return (string)$this->secret_key;
    }

    /**
     * @param string $attribute
     * @param array $params
     */
    public function _validateDomain(string $attribute, array $params): void
    {
        $domains      = CommonHelper::getArrayFromString($this->$attribute);
        $errorDomains = [];

        foreach ($domains as $index => $domain) {
            if (strpos($domain, 'http') === 0 || !FilterVarHelper::url('https://' . $domain)) {
                $errorDomains[] = $domain;
            }
        }

        if (!empty($errorDomains)) {
            $this->addError($attribute, $this->t('Invalid domains: {domains}', [
                '{domains}' => implode(', ', $errorDomains),
            ]));
        }
    }

    /**
     * @return array
     */
    public function getDomainsList(): array
    {
        return CommonHelper::getArrayFromString($this->domain);
    }

    /**
     * @return bool
     */
    public function getContainsCurrentDomain(): bool
    {
        if (is_cli()) {
            return false;
        }

        $currentDomain = parse_url(request()->getHostInfo(), PHP_URL_HOST);
        return in_array($currentDomain, $this->getDomainsList());
    }
}
