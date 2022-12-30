<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * OptionCustomerCampaigns
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.4.3
 */

class OptionCustomerCampaigns extends OptionBase
{
    /**
     * @var int
     */
    public $max_campaigns = -1;

    /**
     * @var int
     */
    public $max_active_campaigns = -1;

    /**
     * @var string
     */
    public $email_header;

    /**
     * @var string
     */
    public $email_footer;

    /**
     * @var string
     */
    public $must_verify_sending_domain = self::TEXT_NO;

    /**
     * @var string
     */
    public $can_delete_own_campaigns = self::TEXT_YES;

    /**
     * @var int
     */
    public $subscribers_at_once = 300;

    /**
     * @var int
     */
    public $change_server_at = 100;

    /**
     * @var int
     */
    public $max_bounce_rate = -1;

    /**
     * @var int
     */
    public $max_complaint_rate = -1;

    /**
     * @var string
     */
    public $can_export_stats = self::TEXT_YES;

    /**
     * @var string
     */
    public $can_use_autoresponders = self::TEXT_YES;

    /**
     * @var string
     */
    public $can_embed_images = self::TEXT_NO;

    /**
     * @var string
     */
    public $can_use_timewarp = self::TEXT_NO;

    /**
     * @var string
     */
    public $require_approval = self::TEXT_NO;

    /**
     * @var string
     */
    public $show_geo_opens = self::TEXT_NO;

    /**
     * @var string
     */
    public $show_24hours_performance_graph = self::TEXT_YES;

    /**
     * @var string
     */
    public $show_top_domains_opens_clicks_graph = self::TEXT_YES;

    /**
     * @var string
     */
    public $feedback_id_header_format = '[CAMPAIGN_UID]:[CAMPAIGN_TYPE]:[LIST_UID]:[CUSTOMER_UID]';

    /**
     * @var string
     */
    public $list_unsubscribe_header_email = '';

    /**
     * @var string
     */
    public $abuse_reports_email_notification = self::TEXT_YES;

    /**
     * @var string
     */
    public $can_use_recurring_campaigns = self::TEXT_YES;

    /**
     * @var string
     */
    protected $_categoryName = 'system.customer_campaigns';

    /**
     * @return array
     * @throws CException
     */
    public function rules()
    {
        $rules = [
            ['max_campaigns, max_active_campaigns, must_verify_sending_domain, can_delete_own_campaigns, max_bounce_rate, max_complaint_rate, can_export_stats, can_use_autoresponders, can_embed_images, can_use_timewarp, require_approval, abuse_reports_email_notification, can_use_recurring_campaigns', 'required'],
            ['max_campaigns, max_active_campaigns', 'numerical', 'integerOnly' => true, 'min' => -1],
            ['max_bounce_rate, max_complaint_rate', 'numerical', 'min' => -1, 'max' => 100],
            ['must_verify_sending_domain, can_delete_own_campaigns, can_export_stats, can_use_autoresponders, can_embed_images, can_use_timewarp, require_approval, abuse_reports_email_notification, can_use_recurring_campaigns', 'in', 'range' => array_keys($this->getYesNoOptions())],

            ['show_geo_opens, show_24hours_performance_graph, show_top_domains_opens_clicks_graph', 'required'],
            ['show_geo_opens, show_24hours_performance_graph, show_top_domains_opens_clicks_graph', 'in', 'range' => array_keys($this->getYesNoOptions())],

            ['subscribers_at_once, change_server_at', 'required'],
            ['subscribers_at_once', 'numerical', 'min' => 1, 'max' => 10000],
            ['change_server_at', 'numerical', 'min' => 0, 'max' => 10000],
            ['feedback_id_header_format', 'length', 'max' => 500],
            ['feedback_id_header_format', '_validateFeedbackIdHeaderFormat'],
            ['list_unsubscribe_header_email', 'email', 'validateIDN' => true],
            ['list_unsubscribe_header_email', 'length', 'max' => 150],

            ['email_header, email_footer', 'safe'],
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
            'max_campaigns'                 => $this->t('Max. campaigns'),
            'max_active_campaigns'          => $this->t('Max. active campaigns'),
            'email_header'                  => $this->t('Email header'),
            'email_footer'                  => $this->t('Email footer'),
            'must_verify_sending_domain'    => $this->t('Verify sending domain'),
            'can_delete_own_campaigns'      => $this->t('Delete own campaigns'),
            'can_export_stats'              => $this->t('Export stats'),
            'feedback_id_header_format'     => $this->t('Feedback-ID header format'),
            'list_unsubscribe_header_email' => $this->t('List unsubscribe header email'),
            'can_use_autoresponders'        => $this->t('Use autoresponders'),
            'can_embed_images'              => $this->t('Embed images'),
            'can_use_timewarp'              => $this->t('Use timewarp'),
            'require_approval'              => $this->t('Require approval'),
            'can_use_recurring_campaigns'   => $this->t('Recurring campaigns'),

            'show_geo_opens'                      => $this->t('Show geo opens'),
            'show_24hours_performance_graph'      => $this->t('Show 24 hours performance graph'),
            'show_top_domains_opens_clicks_graph' => $this->t('Show top domains graph for all clicks/opens'),

            'subscribers_at_once' => $this->t('Subscribers at once'),
            'change_server_at'    => $this->t('Change server at'),
            'max_bounce_rate'     => $this->t('Max. bounce rate'),
            'max_complaint_rate'  => $this->t('Max. complaint rate'),

            'abuse_reports_email_notification' => $this->t('Abuse reports email notification'),
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
            'max_campaigns'         => '',
            'max_active_campaigns'  => '',
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
            'max_campaigns'                 => $this->t('Maximum number of campaigns a customer can have, set to -1 for unlimited'),
            'max_active_campaigns'          => $this->t('Maximum number of active campaigns a customer can have, set to -1 for unlimited'),
            'email_header'                  => $this->t('The email header that should be appended to each campaign. It will be inserted exactly after the starting body tag and it can also contain template tags, which will pe parsed. Make sure you style it accordingly'),
            'email_footer'                  => $this->t('The email footer that should be appended to each campaign. It will be inserted exactly before the ending body tag and it can also contain template tags, which will pe parsed. Make sure you style it accordingly'),
            'must_verify_sending_domain'    => $this->t('Whether customers must verify the domain name used in the FROM email address of a campaign'),
            'feedback_id_header_format'     => $this->t('The format of the Feedback-ID header.'),
            'list_unsubscribe_header_email' => $this->t('The email address to be used in the list unsubscribe header. This email will receive the unsubscribe requests if added, so you can monitor it using an Email Box Monitor and automate the process.'),

            'can_delete_own_campaigns'      => $this->t('Whether customers are allowed to delete their own campaigns'),
            'can_export_stats'              => $this->t('Whether customer can export campaign stats'),
            'can_use_autoresponders'        => $this->t('Whether customers are allowed to use autoresponders'),
            'can_embed_images'              => $this->t('Whether customers can select if they can embed images in the email content'),
            'can_use_timewarp'              => $this->t('Whether customers can send campaigns directly in their subscribers local timezone'),
            'can_use_recurring_campaigns'   => $this->t('Whether customers can send recurring campaigns'),
            'require_approval'              => $this->t('Whether customers require approval before sending a campaign. The campaign must be reviewed by an admin and approved before sending'),

            'show_geo_opens'                      => $this->t('Whether customers can view geo opens reports'),
            'show_24hours_performance_graph'      => $this->t('Whether to show the 24 hours performance graph in the campaign overview area'),
            'show_top_domains_opens_clicks_graph' => $this->t('Whether to show the top domains graph for all opens/clicks in the campaign overview area'),

            'subscribers_at_once' => $this->t('How many subscribers to process at once for each loaded campaign.'),
            'change_server_at'    => $this->t('After how many sent emails we should change the delivery server. This only applies if there are multiple delivery servers. Set this to 0 to disable it.'),
            'max_bounce_rate'     => $this->t('When a campaign reaches this bounce rate, it will be blocked. Set to -1 to disable this check or between 1 and 100 to set the percent of allowed bounce rate.'),
            'max_complaint_rate'  => $this->t('When a campaign reaches this complaint rate, it will be blocked. Set to -1 to disable this check or between 1 and 100 to set the percent of allowed complaint rate.'),

            'abuse_reports_email_notification' => $this->t('When a subscriber submits an abuse report for a campaign, whether the customer should be notified via email or not.'),
        ];

        return CMap::mergeArray($texts, parent::attributeHelpTexts());
    }

    /**
     * @param string $attribute
     * @param array $params
     */
    public function _validateFeedbackIdHeaderFormat(string $attribute, array $params = []): void
    {
        if ($this->hasErrors($attribute)) {
            return;
        }

        $value = $this->$attribute;
        if (empty($value)) {
            return;
        }

        $values = explode(':', $value);
        if (count($values) != 4) {
            $this->addError($attribute, $this->t('The feedback-ID header format is invalid! Please refer to the {link}.', [
                '{link}' => CHtml::link($this->t('documentation'), 'https://support.google.com/mail/answer/6254652?hl=en', ['target' => '_blank']),
            ]));
            return;
        }
    }

    /**
     * @return array
     */
    public function getFeedbackIdFormatTagsInfo(): array
    {
        $tags = [
            '[CAMPAIGN_UID]'    => $this->t('The campaign unique 13 characters id.'),
            '[CAMPAIGN_TYPE]'   => $this->t('The campaign type, regular or autoresponder.'),
            '[SUBSCRIBER_UID]'  => $this->t('The subscriber unique 13 characters id.'),
            '[LIST_UID]'        => $this->t('The list unique 13 characters id.'),
            '[CUSTOMER_UID]'    => $this->t('The customer unique 13 characters id.'),
            '[CUSTOMER_NAME]'   => $this->t('The customer name, lowercased and urlified.'),
        ];
        return (array)hooks()->applyFilters('feedback_id_header_format_tags_info', $tags);
    }

    /**
     * @return array
     */
    public function getFeedbackIdFormatTagsInfoHtml(): array
    {
        $out = [];
        foreach ($this->getFeedbackIdFormatTagsInfo() as $tag => $info) {
            $out[] = '&raquo; ' . sprintf('<b>%s</b>', $tag) . ' - ' . $info;
        }
        return $out;
    }

    /**
     * @return void
     */
    protected function afterConstruct()
    {
        parent::afterConstruct();

        $attributes = [
            'subscribers_at_once'       => 300,
            'change_server_at'          => 100,
            'max_bounce_rate'           => -1,
            'max_complaint_rate'        => -1,
        ];

        foreach ($attributes as $key => $value) {

            // the option has already been set, skip it
            if (options()->get($this->_categoryName . '.' . $key, false) !== false) {
                continue;
            }

            if ($this->$key == $value) {
                $this->$key = (int)options()->get('system.cron.send_campaigns.' . $key, $this->$key);
            }
        }
    }
}
