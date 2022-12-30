<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * CampaignTemplate
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

/**
 * This is the model class for table "campaign_template".
 *
 * The followings are the available columns in table 'campaign_template':
 * @property integer|null $template_id
 * @property integer|null $campaign_id
 * @property integer $customer_template_id
 * @property string $name
 * @property string $content
 * @property string $inline_css
 * @property string $minify
 * @property string $meta_data
 * @property string $plain_text
 * @property string $only_plain_text
 * @property string $auto_plain_text
 *
 * The followings are the available model relations:
 * @property Campaign $campaign
 * @property CustomerEmailTemplate $customerTemplate
 * @property CampaignTemplateUrlActionListField[] $urlActionListFields
 * @property CampaignTemplateUrlActionSubscriber[] $urlActionSubscribers
 */
class CampaignTemplate extends ActiveRecord
{
    /**
     * @var string
     */
    public $from_url;

    /**
     * @return string
     */
    public function tableName()
    {
        return '{{campaign_template}}';
    }

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            ['content, auto_plain_text, only_plain_text', 'required'],
            ['content', 'customer.components.validators.CampaignTemplateValidator'],
            ['name', 'length', 'max' => 255],
            ['only_plain_text, auto_plain_text', 'in', 'range' => array_keys($this->getYesNoOptions())],
            ['plain_text, from_url', 'safe'],
        ];

        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array
     */
    public function relations()
    {
        $relations = [
            'campaign'              => [self::BELONGS_TO, Campaign::class, 'campaign_id'],
            'customerTemplate'      => [self::BELONGS_TO, CustomerEmailTemplate::class, 'customer_template_id'],
            'urlActionListFields'   => [self::HAS_MANY, CampaignTemplateUrlActionListField::class, 'template_id'],
            'urlActionSubscribers'  => [self::HAS_MANY, CampaignTemplateUrlActionSubscriber::class, 'template_id'],
        ];

        return CMap::mergeArray($relations, parent::relations());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'campaign_id'       => t('campaigns', 'Campaign'),
            'name'              => t('campaigns', 'Template name'),
            'content'           => t('campaigns', 'Content'),
            'plain_text'        => t('campaigns', 'Plain text'),
            'only_plain_text'   => t('campaigns', 'Only plain text'),
            'auto_plain_text'   => t('campaigns', 'Auto plain text'),
            'from_url'          => t('campaigns', 'From url'),
        ];

        return CMap::mergeArray($labels, parent::attributeLabels());
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return CampaignTemplate the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var CampaignTemplate $model */
        $model = parent::model($className);

        return $model;
    }

    /**
     * @return array
     */
    public function getInlineCssArray(): array
    {
        return $this->getYesNoOptions();
    }

    /**
     * @return array
     */
    public function getAutoPlainTextArray(): array
    {
        return $this->getYesNoOptions();
    }

    /**
     * @return array
     */
    public function attributePlaceholders()
    {
        $placeholders = [
            'content'           => '',
            'plain_text'        => '',
            'only_plain_text'   => '',
            'auto_plain_text'   => '',
            'from_url'          => '',
        ];

        return CMap::mergeArray($placeholders, parent::attributePlaceholders());
    }

    /**
     * @return array
     */
    public function attributeHelpTexts()
    {
        $texts = [
            'name'              => t('campaigns', 'The template name, for your reference only.'),
            'content'           => '',
            'plain_text'        => t('campaigns', 'This is the plain text version of the html template. If left empty and autogenerate option is set to "yes" then this will be created based on your html template.'),
            'only_plain_text'   => t('campaigns', 'Whether the template contains only plain text and should be treated like so by all parsers.'),
            'auto_plain_text'   => t('campaigns', 'Whether the plain text version of the html template should be auto generated.'),
            'from_url'          => t('campaigns', 'Enter url to fetch as a template'),
        ];

        return CMap::mergeArray($texts, parent::attributeHelpTexts());
    }

    /**
     * @return array
     */
    public function getAvailableTags(): array
    {
        $tags = [
            ['tag' => '[COMPANY_FULL_ADDRESS]', 'required' => true],
            ['tag' => '[UPDATE_PROFILE_URL]', 'required' => false],
            ['tag' => '[WEB_VERSION_URL]', 'required' => false],
            ['tag' => '[CAMPAIGN_URL]', 'required' => false],
            ['tag' => '[FORWARD_FRIEND_URL]', 'required' => false],

            ['tag' => '[LIST_UID]', 'required' => false],
            ['tag' => '[LIST_NAME]', 'required' => false],
            ['tag' => '[LIST_SUBJECT]', 'required' => false],
            ['tag' => '[LIST_DESCRIPTION]', 'required' => false],
            ['tag' => '[LIST_FROM_NAME]', 'required' => false],
            ['tag' => '[LIST_FROM_EMAIL]', 'required' => false],
            ['tag' => '[LIST_VCARD_URL]', 'required' => false],

            ['tag' => '[CURRENT_YEAR]', 'required' => false],
            ['tag' => '[CURRENT_MONTH]', 'required' => false],
            ['tag' => '[CURRENT_DAY]', 'required' => false],
            ['tag' => '[CURRENT_DATE]', 'required' => false],
            ['tag' => '[CURRENT_MONTH_FULL_NAME]', 'required' => false],

            ['tag' => '[COMPANY_NAME]', 'required' => false],
            ['tag' => '[COMPANY_WEBSITE]', 'required' => false],
            ['tag' => '[COMPANY_ADDRESS_1]', 'required' => false],
            ['tag' => '[COMPANY_ADDRESS_2]', 'required' => false],
            ['tag' => '[COMPANY_CITY]', 'required' => false],
            ['tag' => '[COMPANY_ZONE]', 'required' => false],
            ['tag' => '[COMPANY_ZONE_CODE]', 'required' => false],
            ['tag' => '[COMPANY_ZIP]', 'required' => false],
            ['tag' => '[COMPANY_COUNTRY]', 'required' => false],
            ['tag' => '[COMPANY_COUNTRY_CODE]', 'required' => false],
            ['tag' => '[COMPANY_PHONE]', 'required' => false],

            ['tag' => '[CAMPAIGN_NAME]', 'required' => false],
            ['tag' => '[CAMPAIGN_TYPE]', 'required' => false],
            ['tag' => '[CAMPAIGN_SUBJECT]', 'required' => false],
            ['tag' => '[CAMPAIGN_TO_NAME]', 'required' => false],
            ['tag' => '[CAMPAIGN_FROM_NAME]', 'required' => false],
            ['tag' => '[CAMPAIGN_FROM_EMAIL]', 'required' => false],
            ['tag' => '[CAMPAIGN_REPLY_TO]', 'required' => false],
            ['tag' => '[CAMPAIGN_UID]', 'required' => false],
            ['tag' => '[CAMPAIGN_SEND_AT]', 'required' => false],
            ['tag' => '[CAMPAIGN_STARTED_AT]', 'required' => false],
            ['tag' => '[CAMPAIGN_DATE_ADDED]', 'required' => false],
            ['tag' => '[CAMPAIGN_SEGMENT_NAME]', 'required' => false],
            ['tag' => '[CAMPAIGN_VCARD_URL]', 'required' => false],

            ['tag' => '[SUBSCRIBER_UID]', 'required' => false],
            ['tag' => '[SUBSCRIBER_IP]', 'required' => false],
            ['tag' => '[SUBSCRIBER_DATE_ADDED]', 'required' => false],
            ['tag' => '[SUBSCRIBER_DATE_ADDED_LOCALIZED]', 'required' => false],
            ['tag' => '[SUBSCRIBER_OPTIN_IP]', 'required' => false],
            ['tag' => '[SUBSCRIBER_OPTIN_DATE]', 'required' => false],
            ['tag' => '[SUBSCRIBER_CONFIRM_IP]', 'required' => false],
            ['tag' => '[SUBSCRIBER_CONFIRM_DATE]', 'required' => false],
            ['tag' => '[SUBSCRIBER_LAST_SENT_DATE]', 'required' => false],
            ['tag' => '[SUBSCRIBER_LAST_SENT_DATE_LOCALIZED]', 'required' => false],
            ['tag' => '[SUBSCRIBER_EMAIL_NAME]', 'required' => false],
            ['tag' => '[SUBSCRIBER_EMAIL_DOMAIN]', 'required' => false],
            ['tag' => '[EMAIL_NAME]', 'required' => false],
            ['tag' => '[EMAIL_DOMAIN]', 'required' => false],

            ['tag' => '[DATE]', 'required' => false],
            ['tag' => '[DATETIME]', 'required' => false],
            ['tag' => '[RANDOM_CONTENT:a|b|c]', 'required' => false],
            ['tag' => '[REMOTE_CONTENT url=\'https://www.google.com/\']', 'required' => false],
            ['tag' => '[CAMPAIGN_REPORT_ABUSE_URL]', 'required' => false],
            ['tag' => '[CURRENT_DOMAIN_URL]', 'required' => false],
            ['tag' => '[CURRENT_DOMAIN]', 'required' => false],
            ['tag' => '[SIGN_LT]', 'required' => false],
            ['tag' => '[SIGN_LTE]', 'required' => false],
            ['tag' => '[SIGN_GT]', 'required' => false],
            ['tag' => '[SIGN_GTE]', 'required' => false],

            ['tag' => '[DS_NAME]', 'required' => false],
            ['tag' => '[DS_HOST]', 'required' => false],
            ['tag' => '[DS_TYPE]', 'required' => false],
            ['tag' => '[DS_ID]', 'required' => false],
            ['tag' => '[DS_FROM_NAME]', 'required' => false],
            ['tag' => '[DS_FROM_EMAIL]', 'required' => false],
            ['tag' => '[DS_REPLYTO_EMAIL]', 'required' => false],

            ['tag' => '[SUBSCRIBE_URL]', 'required' => false],
            ['tag' => '[SUBSCRIBE_LINK]', 'required' => false],

            ['tag' => '[UNSUBSCRIBE_URL]',                 'required' => true,  'alt_tags_if_tag_required_and_missing' => ['[UNSUBSCRIBE_LINK]', '[DIRECT_UNSUBSCRIBE_URL]', '[DIRECT_UNSUBSCRIBE_LINK]']],
            ['tag' => '[UNSUBSCRIBE_LINK]',                'required' => false, 'alt_tags_if_tag_required_and_missing' => ['[UNSUBSCRIBE_URL]', '[UNSUBSCRIBE_LINK]', '[DIRECT_UNSUBSCRIBE_LINK]']],
            ['tag' => '[DIRECT_UNSUBSCRIBE_URL]',          'required' => false, 'alt_tags_if_tag_required_and_missing' => ['[UNSUBSCRIBE_URL]', '[UNSUBSCRIBE_LINK]', '[DIRECT_UNSUBSCRIBE_LINK]']],
            ['tag' => '[DIRECT_UNSUBSCRIBE_LINK]',         'required' => false, 'alt_tags_if_tag_required_and_missing' => ['[UNSUBSCRIBE_URL]', '[UNSUBSCRIBE_LINK]', '[DIRECT_UNSUBSCRIBE_URL]']],
            ['tag' => '[UNSUBSCRIBE_FROM_CUSTOMER_URL]',   'required' => false],
            ['tag' => '[UNSUBSCRIBE_FROM_CUSTOMER_LINK]',  'required' => false],

            // 1.8.1
            ['tag' => '[SURVEY:SURVEY_UNIQUE_ID_HERE:VIEW_URL]', 'required' => false],
        ];

        if (!empty($this->campaign) && !empty($this->campaign->list)) {
            $fields = $this->campaign->list->fields;
            foreach ($fields as $field) {
                $tags[] = ['tag' => '[' . $field->tag . ']', 'required' => false];
            }
        } else {
            $tags[] = ['tag' => '[EMAIL]', 'required' => false];
            $tags[] = ['tag' => '[FNAME]', 'required' => false];
            $tags[] = ['tag' => '[LNAME]', 'required' => false];
        }

        // since 1.3.5.9
        if (!empty($this->campaign)) {
            $customerCampaignTags = CustomerCampaignTag::model()->findAll([
                'select'    => 'tag',
                'condition' => 'customer_id = :cid',
                'params'    => [':cid' => $this->campaign->customer_id],
                'limit'     => 100,
            ]);
            foreach ($customerCampaignTags as $cct) {
                $tags[] = ['tag' => '[' . CustomerCampaignTag::getTagPrefix() . $cct->tag . ']', 'required' => false];
            }
        }

        /** @var array $tags */
        $tags = (array)hooks()->applyFilters('campaign_template_available_tags_list', $tags, $this);

        $optionTags = OptionCampaignTemplateTag::getSavedTemplateTags();

        // since 1.3.6.3
        $optionTags = (array)hooks()->applyFilters('campaign_template_available_option_tags_list', $optionTags, $this);

        /** @var array $optionTagInfo */
        foreach ($optionTags as $optionTagInfo) {
            if (!isset($optionTagInfo['tag'], $optionTagInfo['required'])) {
                continue;
            }
            /**
             * @var int $index
             * @var array $tag
             */
            foreach ($tags as $index => $tag) {
                if ($tag['tag'] == $optionTagInfo['tag']) {
                    $tags[$index]['required'] = (bool)$optionTagInfo['required'];
                    break;
                }
            }
        }

        return $tags;
    }

    /**
     * @return array
     */
    public function getContentUrls(): array
    {
        return CampaignHelper::extractTemplateUrls($this->content);
    }

    /**
     * @return bool
     */
    public function getIsOnlyPlainText(): bool
    {
        return (string)$this->only_plain_text === self::TEXT_YES;
    }

    /**
     * @return array
     */
    public function getExtraUtmTags(): array
    {
        return [
            '[TITLE_ATTR]' => t('campaigns', 'Will use the title attribute of the element'),
        ];
    }

    /**
     * @return bool
     */
    protected function beforeSave()
    {
        $this->content = EmojiHelper::encodeEmoji($this->content);
        return parent::beforeSave();
    }
}
