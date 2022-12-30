<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * OptionCustomerRegistration
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.4.3
 */

class OptionCustomerRegistration extends OptionBase
{
    /**
     * Send email methods
     */
    const SEND_EMAIL_TRANSACTIONAL = 'transactional';
    const SEND_EMAIL_DIRECT = 'direct';

    /**
     * @var string
     */
    public $enabled = self::TEXT_NO;

    /**
     * @var string
     */
    public $default_group;

    /**
     * @var int
     */
    public $unconfirm_days_removal = 7;

    /**
     * @var string
     */
    public $require_approval = self::TEXT_NO;

    /**
     * @var string
     */
    public $require_email_confirmation = self::TEXT_YES;

    /**
     * @var string
     */
    public $company_required = self::TEXT_NO;

    /**
     * @var string
     */
    public $tc_url;

    /**
     * @var string
     */
    public $new_customer_registration_notification_to;

    /**
     * @var string
     */
    public $send_email_method = 'transactional';

    /**
     * @var string
     */
    public $forbidden_domains = '';

    /**
     * @var string
     */
    public $facebook_app_id;

    /**
     * @var string
     */
    public $facebook_app_secret;

    /**
     * @var string
     */
    public $facebook_enabled = self::TEXT_NO;

    /**
     * @var string
     */
    public $twitter_app_consumer_key;

    /**
     * @var string
     */
    public $twitter_app_consumer_secret;

    /**
     * @var string
     */
    public $twitter_enabled = self::TEXT_NO;

    /**
     * @var string
     */
    public $welcome_email = self::TEXT_NO;

    /**
     * @var string
     */
    public $welcome_email_subject;

    /**
     * @var string
     */
    public $welcome_email_content;

    /**
     * @var string
     */
    public $default_country;

    /**
     * @var string
     */
    public $default_timezone;

    /**
     * @var string
     */
    public $api_enabled = self::TEXT_NO;

    /**
     * @var string
     */
    public $api_url;

    /**
     * @var string
     */
    public $api_key;

    /**
     * @var string
     */
    public $api_list_uid;

    /**
     * @var string
     */
    public $api_consent_text = '';

    /**
     * @var int
     */
    public $minimum_age = 16;

    /**
     * @var string
     */
    protected $_categoryName = 'system.customer_registration';

    /**
     * @return void
     */
    public function init()
    {
        parent::init();

        /** @var OptionUrl $optionUrl */
        $optionUrl = container()->get(OptionUrl::class);

        $this->api_url = (string)rtrim($optionUrl->getApiUrl(), '/');
    }

    /**
     * @return array
     * @throws CException
     */
    public function rules()
    {
        $rules = [
            ['enabled, unconfirm_days_removal, require_approval, require_email_confirmation, company_required, send_email_method, welcome_email, minimum_age', 'required'],
            ['enabled, require_approval, require_email_confirmation, company_required, facebook_enabled, twitter_enabled, welcome_email', 'in', 'range' => array_keys($this->getYesNoOptions())],
            ['unconfirm_days_removal', 'numerical', 'integerOnly' => true, 'min' => 1, 'max' => 365],
            ['default_group', 'exist', 'className' => CustomerGroup::class, 'attributeName' => 'group_id'],
            ['tc_url', 'url'],
            ['send_email_method', 'in', 'range' => array_keys($this->getSendEmailMethods())],
            ['forbidden_domains', '_validateForbiddenDomains'],
            ['new_customer_registration_notification_to, facebook_app_id, facebook_app_secret, twitter_app_consumer_key, twitter_app_consumer_secret', 'safe'],
            ['welcome_email_subject, welcome_email_content', 'safe'],
            ['default_country', 'in', 'range' => array_keys(Country::getAsDropdownOptions())],
            ['default_timezone', 'in', 'range' => array_keys(DateTimeHelper::getTimeZones())],
            ['api_url', 'url'],
            ['api_key', 'length', 'is' => 40],
            ['api_list_uid', 'safe'],
            ['api_enabled', 'in', 'range' => array_keys($this->getYesNoOptions())],
            ['api_consent_text', 'length', 'max' => 255],
            ['minimum_age', 'numerical', 'min' => 14, 'max' => 100],
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
            'enabled'                   => $this->t('Enabled'),
            'unconfirm_days_removal'    => $this->t('Unconfirmed removal days'),
            'default_group'             => $this->t('Default group'),
            'require_approval'          => $this->t('Require approval'),
            'require_email_confirmation'=> $this->t('Require email confirmation'),
            'company_required'          => $this->t('Require company info'),
            'tc_url'                    => $this->t('Terms and conditions url'),
            'send_email_method'         => $this->t('Send email method'),
            'forbidden_domains'         => $this->t('Forbidden domains'),

            'api_enabled'       => $this->t('Enabled'),
            'api_url'           => $this->t('Api url'),
            'api_key'           => $this->t('Api key'),
            'api_list_uid'      => $this->t('Api list unique id'),
            'api_consent_text'  => $this->t('Consent text'),

            'facebook_app_id'             => $this->t('Facebook application id'),
            'facebook_app_secret'         => $this->t('Facebook application secret'),
            'facebook_enabled'            => $this->t('Enabled'),
            'twitter_app_consumer_key'    => $this->t('Twitter application consumer key'),
            'twitter_app_consumer_secret' => $this->t('Twitter application consumer secret'),
            'twitter_enabled'             => $this->t('Enabled'),

            'new_customer_registration_notification_to' => $this->t('New customer notification'),

            'welcome_email'         => $this->t('Send welcome email'),
            'welcome_email_subject' => $this->t('Subject'),
            'welcome_email_content' => $this->t('Content'),

            'default_country'  => $this->t('Default country'),
            'default_timezone' => $this->t('Default timezone'),

            'minimum_age'      => $this->t('Minimum age'),
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
            'enabled'                => '',
            'unconfirm_days_removal' => '',
            'default_group'          => '',
            'require_approval'       => '',
            'company_required'       => '',
            'tc_url'                 => '',
            'send_email_method'      => '',
            'forbidden_domains'      => 'yahoo.com, hotmail.com, gmail.com',

            'facebook_app_id'             => '365206940300000',
            'facebook_app_secret'         => 'e48f5d4b30fcea90cb47a7b8cb50ft2y',
            'twitter_app_consumer_key'    => 'E1BBQZGOLU6IXAVRVZN371237',
            'twitter_app_consumer_secret' => 'f2SVAvDEwcpqEmoDxoXN42p19Xem6zsXHYF7l0eUaI6Ed9alt2',

            'api_consent_text' => $this->t('I give my consent to [NAME HERE] to send me newsletters using the information i have provided in this form.'),

            'new_customer_registration_notification_to' => '',
            'minimum_age'   => 16,
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
            'enabled'                    => $this->t('Whether the customer registration is enabled'),
            'unconfirm_days_removal'     => $this->t('How many days to keep the unconfirmed customers in the system before permanent removal'),
            'default_group'              => $this->t('Default group for customer after registration'),
            'require_approval'           => $this->t('Whether customers must be approved after they have confirmed the registration'),
            'require_email_confirmation' => $this->t('Whether the customers must confirm their email address before being able to login'),
            'company_required'           => $this->t('Whether the company basic info is required'),
            'tc_url'                     => $this->t('The url for terms and conditions for the customer to read before signup'),
            'send_email_method'          => $this->t('Whether to send the email directly or to queue it to be later sent via transactional emails'),
            'forbidden_domains'          => $this->t('Do not allow registration if an email address belongs to any of these domains. You can type: "yahoo.com" to block only yahoo.com emails, or "yahoo" to block all domain names that start with "yahoo" wording, i.e: yahoo.co.uk'),

            'new_customer_registration_notification_to' => $this->t('One or multiple email addresses separated by a comma to where notifications about new customer registration will be sent'),

            'welcome_email'         => $this->t('Whether this welcome email should be sent to new customers'),
            'welcome_email_subject' => $this->t('The subject for the welcome email, following customer tags are recognized and parsed: {tags}', ['{tags}' => '[FIRST_NAME], [LAST_NAME], [FULL_NAME], [EMAIL]']),
            'welcome_email_content' => $this->t('The content for the welcome email, following customer tags are recognized and parsed: {tags}. Please note that the common template will be used as the layout.', ['{tags}' => '[FIRST_NAME], [LAST_NAME], [FULL_NAME], [EMAIL]']),

            'api_enabled'       => $this->t('Whether the feature is enabled'),
            'api_url'           => $this->t('The url where the api resides and where we will send the customer as a subscriber'),
            'api_key'           => $this->t('The key for api access'),
            'api_list_uid'      => $this->t('The list unique id where the subscriber will go. You can use multiple lists as well, separate them using a comma, i.e: ju12gt28s412m, h12uod3nsyr2b'),
            'api_consent_text'  => $this->t('The consent text the subscriber has to agree to. This is required by regulations such as GDPR. Your email list must have a custom field tagged CONSENT. If you add text here, we will show a checkbox with the consent text in the registration page and we will subscribe the customer only if the checkbox is checked'),

            'minimum_age'       => $this->t('Minimum allowed age for customers to register'),
        ];

        return CMap::mergeArray($texts, parent::attributeHelpTexts());
    }

    /**
     * @return array
     */
    public function getGroupsList()
    {
        static $options;
        if ($options !== null) {
            return $options;
        }

        $options = [];
        $groups  = CustomerGroup::model()->findAll();

        foreach ($groups as $group) {
            $options[$group->group_id] = $group->name;
        }

        return $options;
    }

    /**
     * @return array
     */
    public function getSendEmailMethods()
    {
        return [
            self::SEND_EMAIL_TRANSACTIONAL => $this->t(ucfirst(self::SEND_EMAIL_TRANSACTIONAL)),
            self::SEND_EMAIL_DIRECT        => $this->t(ucfirst(self::SEND_EMAIL_DIRECT)),
        ];
    }

    /**
     * @return bool
     */
    public function getSendEmailDirect(): bool
    {
        return (string)$this->send_email_method === self::SEND_EMAIL_DIRECT;
    }

    /**
     * @return bool
     */
    public function getSendEmailTransactional(): bool
    {
        return !$this->getSendEmailDirect();
    }

    /**
     * @return bool
     */
    public function getIsEnabled(): bool
    {
        return (string)$this->enabled === self::TEXT_YES;
    }

    /**
     * @return bool
     */
    public function getIsFacebookEnabled(): bool
    {
        return (string)$this->facebook_enabled === self::TEXT_YES;
    }

    /**
     * @return bool
     */
    public function getIsTwitterEnabled(): bool
    {
        return (string)$this->twitter_enabled === self::TEXT_YES;
    }

    /**
     * @return bool
     */
    public function getIsCompanyRequired(): bool
    {
        return (string)$this->company_required === self::TEXT_YES;
    }

    /**
     * @return bool
     */
    public function getRequireEmailConfirmation(): bool
    {
        return (string)$this->require_email_confirmation === self::TEXT_YES;
    }

    /**
     * @return bool
     */
    public function getRequireApproval(): bool
    {
        return (string)$this->require_approval === self::TEXT_YES;
    }

    /**
     * @return string
     */
    public function getDefaultCountry(): string
    {
        return (string)$this->default_country;
    }

    /**
     * @return string
     */
    public function getDefaultTimezone(): string
    {
        return (string)$this->default_timezone;
    }

    /**
     * @return bool
     */
    public function getApiEnabled(): bool
    {
        return (string)$this->api_enabled === self::TEXT_YES;
    }

    /**
     * @return string
     */
    public function getApiConsentText(): string
    {
        return (string)$this->api_consent_text;
    }

    /**
     * @return CustomerGroup|null
     */
    public function getDefaultGroup(): ?CustomerGroup
    {
        if (empty($this->default_group)) {
            return null;
        }
        return CustomerGroup::model()->findByPk((int)$this->default_group);
    }

    /**
     * @return bool
     */
    public function getSendWelcomeEmail(): bool
    {
        return (string)$this->welcome_email === self::TEXT_YES;
    }

    /**
     * @return string
     */
    public function getWelcomeEmailSubject(): string
    {
        return (string)$this->welcome_email_subject;
    }

    /**
     * @return string
     */
    public function getWelcomeEmailContent(): string
    {
        return (string)$this->welcome_email_content;
    }

    /**
     * @return array
     */
    public function getForbiddenDomainsList(): array
    {
        if (!($domains = (string)$this->forbidden_domains)) {
            return [];
        }

        $domains = explode(',', $domains);
        $domains = array_map('strtolower', array_map('trim', $domains));
        return array_filter(array_unique($domains));
    }

    /**
     * @return array
     */
    public function getNewCustomersRegistrationNotificationTo(): array
    {
        $list = (string)$this->new_customer_registration_notification_to;
        $list = explode(',', $list);
        $list = array_map('trim', $list);
        return array_unique($list);
    }

    /**
     * @return int
     */
    public function getMinimumAge(): int
    {
        return (int)$this->minimum_age;
    }

    /**
     * @return int
     */
    public function getUnconfirmDaysRemoval(): int
    {
        return (int)$this->unconfirm_days_removal;
    }

    /**
     * @return string
     */
    public function getTermsAndConditionsUrl(): string
    {
        return !empty($this->tc_url) && FilterVarHelper::url($this->tc_url) ? $this->tc_url : '';
    }

    /**
     * @param string $attribute
     * @param array $params
     */
    public function _validateForbiddenDomains(string $attribute, array $params = []): void
    {
        if ($this->hasErrors($attribute) || empty($this->$attribute)) {
            return;
        }
        $pieces = explode(',', $this->$attribute);
        $pieces = array_map('strtolower', array_map('trim', $pieces));
        $pieces = array_unique($pieces);

        $valid = [];
        foreach ($pieces as $piece) {
            $valid[] = $piece;
        }
        $this->$attribute = implode(', ', $valid);
    }

    /**
     * @return bool
     */
    protected function beforeValidate()
    {
        if ($this->enabled == self::TEXT_NO) {
            $this->default_group = '';
        }

        return parent::beforeValidate();
    }
}
