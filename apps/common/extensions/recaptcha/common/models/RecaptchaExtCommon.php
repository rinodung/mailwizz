<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * RecaptchaExtCommon
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 */

class RecaptchaExtCommon extends ExtensionModel
{
    /**
     * @var string
     */
    public $enabled = self::TEXT_NO;

    /**
     * @var string
     */
    public $enabled_for_list_forms = self::TEXT_NO;

    /**
     * @var string
     */
    public $enabled_for_block_email_form = self::TEXT_NO;

    /**
     * @var string
     */
    public $enabled_for_registration = self::TEXT_NO;

    /**
     * @var string
     */
    public $enabled_for_login = self::TEXT_NO;

    /**
     * @var string
     */
    public $enabled_for_forgot = self::TEXT_NO;

    /**
     * @var string
     */
    public $site_key = '';

    /**
     * @var string
     */
    public $secret_key = '';

    /**
     * @var array
     */
    public $domains_keys_pair = [];

    /**
     * @var null|RecaptchaExtDomainsKeysPair
     */
    private $_currentDomainsKeysPair;

    /**
     * @return array
     * @throws CException
     */
    public function rules()
    {
        $rules = [
            ['site_key, secret_key', 'safe'],
            ['enabled, enabled_for_list_forms, enabled_for_block_email_form, enabled_for_registration, enabled_for_login, enabled_for_forgot', 'in', 'range' => array_keys($this->getYesNoOptions())],
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
            'enabled'                               => t('app', 'Enabled'),
            'enabled_for_list_forms'                => $this->t('Enabled for list forms'),
            'enabled_for_block_email_form'          => $this->t('Enabled for public block email form'),
            'enabled_for_registration'              => $this->t('Enable for registration'),
            'enabled_for_login'                     => $this->t('Enable for login'),
            'enabled_for_forgot'                    => $this->t('Enable for forgot password'),
            'site_key'                              => $this->t('Site key'),
            'secret_key'                            => $this->t('Secret key'),
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
            'enabled'                               => t('app', 'Whether the feature is enabled'),
            'enabled_for_list_forms'                => $this->t('Whether the feature is enabled for list forms'),
            'enabled_for_block_email_form'          => $this->t('Whether the feature is enabled for public block email form'),
            'enabled_for_registration'              => $this->t('Whether the feature is enabled for registration'),
            'enabled_for_login'                     => $this->t('Whether the feature is enabled for login'),
            'enabled_for_forgot'                    => $this->t('Whether the feature is enabled for forgot password'),
            'site_key'                              => $this->t('The site key for recaptcha service'),
            'secret_key'                            => $this->t('The secret key for recaptcha service'),
        ];
        return CMap::mergeArray($texts, parent::attributeHelpTexts());
    }

    /**
     * @return bool
     */
    public function save()
    {
        $save = parent::save();

        $domainsKeysPair       = [];
        $domainsKeysPairModels = [];
        foreach ($this->domains_keys_pair as $index => $pair) {
            $model = new RecaptchaExtDomainsKeysPair();
            $model->attributes = $pair;
            $domainsKeysPairModels[] = $model;
            if ($model->validate()) {
                $domainsKeysPair[] = $model->attributes;
            }
        }
        $this->setOption('domains_keys_pair', $domainsKeysPair);
        $this->domains_keys_pair = $domainsKeysPairModels;

        return $save;
    }

    /**
     * @return string
     */
    public function getCategoryName(): string
    {
        return '';
    }

    /**
     * @return bool
     */
    public function getIsEnabled(): bool
    {
        return $this->enabled === self::TEXT_YES;
    }

    /**
     * @return bool
     */
    public function getIsEnabledForListForms(): bool
    {
        return $this->enabled_for_list_forms === self::TEXT_YES;
    }

    /**
     * @return bool
     */
    public function getIsEnabledForBlockEmailForm(): bool
    {
        return $this->enabled_for_block_email_form === self::TEXT_YES;
    }

    /**
     * @return bool
     */
    public function getIsEnabledForRegistration(): bool
    {
        return $this->enabled_for_registration === self::TEXT_YES;
    }

    /**
     * @return bool
     */
    public function getIsEnabledForLogin(): bool
    {
        return $this->enabled_for_login === self::TEXT_YES;
    }

    /**
     * @return bool
     */
    public function getIsEnabledForForgot(): bool
    {
        return $this->enabled_for_forgot === self::TEXT_YES;
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
     * @return array
     */
    public function getDomainsKeysPairs(): array
    {
        return (array)$this->domains_keys_pair;
    }

    /**
     * @param RecaptchaExtDomainsKeysPair $model
     *
     * @return void
     */
    public function setCurrentDomainsKeysPair(RecaptchaExtDomainsKeysPair $model): void
    {
        $this->_currentDomainsKeysPair = $model;
    }

    /**
     * @return RecaptchaExtDomainsKeysPair|null
     */
    public function getCurrentDomainKeysPair(): ?RecaptchaExtDomainsKeysPair
    {
        return $this->_currentDomainsKeysPair;
    }

    /**
     * @return string
     */
    public function getCurrentDomainSiteKey(): string
    {
        /** @var RecaptchaExtDomainsKeysPair $currentDomainKeysPair */
        $currentDomainKeysPair = $this->getCurrentDomainKeysPair();
        if ($currentDomainKeysPair != null) {
            return $currentDomainKeysPair->getSiteKey();
        }
        return (string)$this->getSiteKey();
    }

    /**
     * @return string
     */
    public function getCurrentDomainSecretKey(): string
    {
        /** @var RecaptchaExtDomainsKeysPair $currentDomainKeysPair */
        $currentDomainKeysPair = $this->getCurrentDomainKeysPair();
        if ($currentDomainKeysPair != null) {
            return $currentDomainKeysPair->getSecretKey();
        }
        return (string)$this->getSecretKey();
    }

    /**
     * @return void
     */
    protected function afterConstruct()
    {
        parent::afterConstruct();

        $domainsKeysPair         = (array)$this->getOption('domains_keys_pair', []);
        $this->domains_keys_pair = [];
        /** @var array $pair */
        foreach ($domainsKeysPair as $pair) {
            $model = new RecaptchaExtDomainsKeysPair();
            $model->attributes = $pair;
            if ($model->validate()) {
                $this->domains_keys_pair[] = $model;
            }
        }
    }
}
