<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * OptionCommon
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

class OptionCommon extends OptionBase
{
    /**
     * Site status
     */
    const STATUS_ONLINE  = 'online';
    const STATUS_OFFLINE = 'offline';

    /**
     * @var string
     */
    public $site_name;

    /**
     * @var string
     */
    public $site_tagline;

    /**
     * @var string
     */
    public $site_description;

    /**
     * @var string
     */
    public $site_keywords;

    /**
     * @var int
     */
    public $clean_urls = 0;

    /**
     * @var string
     */
    public $site_status = 'online';

    /**
     * @var string
     */
    public $site_offline_message = 'Application currently offline. Try again later!';

    /**
     * @var string
     */
    public $api_status = 'online';

    /**
     * @var int
     */
    public $backend_page_size = 10;

    /**
     * @var int
     */
    public $customer_page_size = 10;

    /**
     * @var string
     */
    public $check_version_update = self::TEXT_YES;

    /**
     * @var string
     */
    public $default_mailer;

    /**
     * @var string
     */
    public $company_info;

    /**
     * @var string
     */
    public $show_backend_timeinfo = self::TEXT_NO;

    /**
     * @var string
     */
    public $show_customer_timeinfo = self::TEXT_NO;

    /**
     * @var string
     */
    public $support_url = '';

    /**
     * @var string
     */
    public $ga_tracking_code_id;

    /**
     * @var string
     */
    public $use_tidy = self::TEXT_YES;

    /**
     * @var string
     */
    public $auto_update = self::TEXT_NO;

    /**
     * @var string
     */
    public $frontend_homepage = self::TEXT_YES;

    /**
     * @var string
     */
    public $version = '2.0.0';

    /**
     * @var string
     */
    protected $_categoryName = 'system.common';

    /**
     * @return void
     */
    public function afterConstruct()
    {
        /** @phpstan-ignore-next-line */
        if (defined('MW_SUPPORT_KB_URL') && strlen((string)MW_SUPPORT_KB_URL)) {
            $this->support_url = MW_SUPPORT_KB_URL;
        }

        parent::afterConstruct();
    }

    /**
     * @return array
     * @throws CException
     */
    public function rules()
    {
        $rules = [
            ['site_name, site_tagline, clean_urls, site_status, site_offline_message, api_status, backend_page_size, customer_page_size, default_mailer, show_backend_timeinfo, show_customer_timeinfo, use_tidy, auto_update, frontend_homepage', 'required'],
            ['site_description, site_keywords', 'safe'],
            ['clean_urls', 'in', 'range' => [0, 1]],
            ['site_status, api_status', 'in', 'range' => ['online', 'offline']],
            ['site_offline_message, ga_tracking_code_id', 'length', 'max' => 250],
            ['backend_page_size, customer_page_size', 'in', 'range' => array_keys($this->paginationOptions->getOptionsList())],
            ['check_version_update, show_backend_timeinfo, show_customer_timeinfo, use_tidy, frontend_homepage', 'in', 'range' => array_keys($this->getYesNoOptions())],
            ['default_mailer', 'in', 'range' => array_keys($this->getSystemMailers())],
            ['company_info', 'safe'],
            ['support_url', 'url'],
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
            'site_name'             => $this->t('Site name'),
            'site_tagline'          => $this->t('Site tagline'),
            'site_description'      => $this->t('Site description'),
            'site_keywords'         => $this->t('Site keywords'),
            'clean_urls'            => $this->t('Clean urls'),
            'site_status'           => $this->t('Site status'),
            'site_offline_message'  => $this->t('Site offline message'),
            'api_status'            => $this->t('Api status'),

            'backend_page_size'     => $this->t('Backend page size'),
            'customer_page_size'    => $this->t('Customer page size'),
            'check_version_update'  => $this->t('Check for new version automatically'),
            'default_mailer'        => $this->t('Default system mailer'),
            'company_info'          => $this->t('Company info'),

            'show_backend_timeinfo' => $this->t('Show backend time info'),
            'show_customer_timeinfo'=> $this->t('Show customer time info'),

            'support_url'           => $this->t('Support url'),
            'ga_tracking_code_id'   => $this->t('GA tracking code id'),

            'use_tidy'              => $this->t('Use Tidy'),
            'auto_update'           => $this->t('Application auto update'),
            'frontend_homepage'     => $this->t('Enable frontend homepage'),
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
            'site_name'           => t('app', 'MailWizz'),
            'site_tagline'        => t('app', 'Email marketing application'),
            'site_description'    => '',
            'site_keywords'       => '',
            'company_info'        => '',
            'support_url'         => 'http://',
            'ga_tracking_code_id' => 'UA-0000000-0',
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
            'site_name'             => $this->t('Your site name, will be used in places like logo, emails, etc.'),
            'site_tagline'          => $this->t('A very short description of your website.'),
            'site_description'      => $this->t('Description'),
            'site_keywords'         => $this->t('Keywords'),
            'clean_urls'            => $this->t('Enabling this will remove the index.php part of your urls.'),
            'site_status'           => $this->t('Whether the website is online or offline.'),
            'site_offline_message'  => $this->t('If the website is offline, show this message to users.'),
            'api_status'            => $this->t('Whether the website api is online or offline.'),

            'backend_page_size'     => $this->t('How many items to show per page in backend area'),
            'customer_page_size'    => $this->t('How many items to show per page in customer area'),
            'check_version_update'  => $this->t('Whether to check for new application version automatically'),
            'default_mailer'        => $this->t('Choose the default system mailer, please do your research if needed'),
            'company_info'          => $this->t('Your company info, used in places like payment page'),

            'show_backend_timeinfo' => $this->t('Whether to show the time info in the backend area'),
            'show_customer_timeinfo'=> $this->t('Whether to show the time info in the customer area'),

            'support_url'           => $this->t('Leave empty to disable the left side menu item for Support forum.'),
            'ga_tracking_code_id'   => $this->t('Make sure you only paste the code id, which looks like UA-0000000-0.'),
            'use_tidy'              => $this->t('Whether to use Tidy for email templates cleanup and formatting'),
            'auto_update'           => $this->t('Whether to let the application auto-update itself'),
            'frontend_homepage'     => $this->t('Whether to show the homepage in frontend instead of redirecting to customer area'),
        ];

        return CMap::mergeArray($texts, parent::attributeHelpTexts());
    }

    /**
     * @return array
     */
    public function getSiteStatusOptions(): array
    {
        return [
            self::STATUS_ONLINE  => $this->t('Online'),
            self::STATUS_OFFLINE => $this->t('Offline'),
        ];
    }

    /**
     * @return array
     * @throws CException
     */
    public function getSystemMailers(): array
    {
        static $list;
        if ($list !== null) {
            return $list;
        }
        $list = [];
        $mailers = mailer()->getAllInstances();
        /** @var MailerAbstract $instance */
        foreach ($mailers as $instance) {
            $list[$instance->getName()] = $instance->getName() . ' - ' . $instance->getDescription();
        }
        return $list;
    }

    /**
     * @return string
     */
    public function getSiteName(): string
    {
        return !empty($this->site_name) ? (string)$this->site_name : 'Marketing website';
    }

    /**
     * @return string
     */
    public function getSiteDescription(): string
    {
        return (string)$this->site_description;
    }

    /**
     * @return string
     */
    public function getSiteKeywords(): string
    {
        return (string)$this->site_keywords;
    }

    /**
     * @return string
     */
    public function getSiteTagline(): string
    {
        return (string)$this->site_tagline;
    }

    /**
     * @return bool
     */
    public function getIsSiteOnline(): bool
    {
        return (string)$this->site_status !== 'offline';
    }

    /**
     * @return bool
     */
    public function getIsApiOnline(): bool
    {
        return (string)$this->api_status !== 'offline';
    }

    /**
     * @return bool
     */
    public function getUseCleanUrls(): bool
    {
        return (int)$this->clean_urls > 0;
    }

    /**
     * @return int
     */
    public function getBackendPageSize(): int
    {
        return (int)$this->backend_page_size;
    }

    /**
     * @return int
     */
    public function getCustomerPageSize(): int
    {
        return (int)$this->customer_page_size;
    }

    /**
     * @return bool
     */
    public function getCheckVersionUpdate(): bool
    {
        return (string)$this->check_version_update === self::TEXT_YES;
    }

    /**
     * @return string
     */
    public function getCompanyInfo(): string
    {
        return (string)$this->company_info;
    }

    /**
     * @return bool
     */
    public function getShowBackendTimeInfo(): bool
    {
        return (string)$this->show_backend_timeinfo === self::TEXT_YES;
    }

    /**
     * @return bool
     */
    public function getShowCustomerTimeInfo(): bool
    {
        return (string)$this->show_customer_timeinfo === self::TEXT_YES;
    }

    /**
     * @return string
     */
    public function getSupportUrl(): string
    {
        return (string)$this->support_url;
    }

    /**
     * @return string
     */
    public function getDefaultMailer(): string
    {
        return (string)$this->default_mailer;
    }

    /**
     * @return string
     */
    public function getGaTrackingCodeId(): string
    {
        return (string)$this->ga_tracking_code_id;
    }

    /**
     * @return bool
     */
    public function getUseTidy(): bool
    {
        return (string)$this->use_tidy === self::TEXT_YES;
    }

    /**
     * @return bool
     */
    public function getAutoUpdate(): bool
    {
        return (string)$this->auto_update === self::TEXT_YES;
    }

    /**
     * @return bool
     */
    public function getFrontendHomepage(): bool
    {
        return (string)$this->frontend_homepage === self::TEXT_YES;
    }

    /**
     * @return string
     */
    public function getSiteOfflineMessage(): string
    {
        return (string)$this->site_offline_message;
    }
}
