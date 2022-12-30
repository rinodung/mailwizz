<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * OptionCustomerLists
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.4.3
 */

class OptionCustomerLists extends OptionBase
{
    /**
     * @var string
     */
    public $can_import_subscribers = self::TEXT_YES;

    /**
     * @var string
     */
    public $can_export_subscribers = self::TEXT_YES;

    /**
     * @var string
     */
    public $can_copy_subscribers = self::TEXT_YES;

    /**
     * @var int
     */
    public $max_lists = -1;

    /**
     * @var int
     */
    public $max_subscribers = -1;

    /**
     * @var int
     */
    public $max_subscribers_per_list = -1;

    /**
     * @var int
     */
    public $copy_subscribers_at_once = 100;

    /**
     * @var string
     */
    public $can_delete_own_lists = self::TEXT_YES;

    /**
     * @var string
     */
    public $can_delete_own_subscribers = self::TEXT_YES;

    /**
     * @var string
     */
    public $can_segment_lists = self::TEXT_YES;

    /**
     * @var int
     */
    public $max_segment_conditions = 3;

    /**
     * @var int
     */
    public $max_segment_wait_timeout = 5;

    /**
     * @var string
     */
    public $can_mark_blacklisted_as_confirmed = self::TEXT_NO;

    /**
     * @var string
     */
    public $can_use_own_blacklist = self::TEXT_NO;

    /**
     * @var string
     */
    public $can_edit_own_subscribers = self::TEXT_YES;

    /**
     * @var string
     */
    public $subscriber_profile_update_optin_history = self::TEXT_YES;

    /**
     * @var string
     */
    public $can_create_list_from_filters = self::TEXT_YES;

    /**
     * @var string
     */
    public $show_7days_subscribers_activity_graph = self::TEXT_YES;

    /**
     * @var string
     */
    public $force_optin_process = '';

    /**
     * @var string
     */
    public $force_optout_process = '';

    /**
     * @var string
     */
    public $custom_fields_default_visibility = ListField::VISIBILITY_VISIBLE;

    /**
     * @var string
     */
    protected $_categoryName = 'system.customer_lists';

    /**
     * @return array
     * @throws CException
     */
    public function rules()
    {
        $rules = [
            ['can_import_subscribers, can_export_subscribers, can_copy_subscribers, max_lists, max_subscribers, max_subscribers_per_list, copy_subscribers_at_once, can_delete_own_lists, can_delete_own_subscribers, can_segment_lists, max_segment_conditions, max_segment_wait_timeout, can_mark_blacklisted_as_confirmed, can_use_own_blacklist, can_edit_own_subscribers, subscriber_profile_update_optin_history, can_create_list_from_filters, show_7days_subscribers_activity_graph', 'required'],
            ['can_import_subscribers, can_export_subscribers, can_copy_subscribers, can_delete_own_lists, can_delete_own_subscribers, can_segment_lists, can_use_own_blacklist, can_edit_own_subscribers, subscriber_profile_update_optin_history, can_create_list_from_filters, show_7days_subscribers_activity_graph', 'in', 'range' => array_keys($this->getYesNoOptions())],
            ['max_lists, max_subscribers, max_subscribers_per_list', 'numerical', 'integerOnly' => true, 'min' => -1],
            ['copy_subscribers_at_once', 'numerical', 'integerOnly' => true, 'min' => 50, 'max' => 10000],
            ['max_segment_conditions, max_segment_wait_timeout', 'numerical', 'integerOnly' => true, 'min' => 0, 'max' => 60],
            ['can_mark_blacklisted_as_confirmed', 'in', 'range' => array_keys($this->getYesNoOptions())],
            ['force_optin_process', 'in', 'range' => array_keys($this->getOptInOutOptions())],
            ['force_optout_process', 'in', 'range' => array_keys($this->getOptInOutOptions())],
            ['custom_fields_default_visibility', 'in', 'range' => array_keys($this->getCustomFieldsVisibilityOptions())],
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
            'can_import_subscribers'                  => $this->t('Can import subscribers'),
            'can_export_subscribers'                  => $this->t('Can export subscribers'),
            'can_copy_subscribers'                    => $this->t('Can copy subscribers'),
            'max_lists'                               => $this->t('Max. lists'),
            'max_subscribers'                         => $this->t('Max. subscribers'),
            'max_subscribers_per_list'                => $this->t('Max. subscribers per list'),
            'copy_subscribers_at_once'                => $this->t('Copy subscribers at once'),
            'can_delete_own_lists'                    => $this->t('Can delete own lists'),
            'can_delete_own_subscribers'              => $this->t('Can delete own subscribers'),
            'can_segment_lists'                       => $this->t('Can segment lists'),
            'max_segment_conditions'                  => $this->t('Max. segment conditions'),
            'max_segment_wait_timeout'                => $this->t('Max. segment wait timeout'),
            'can_mark_blacklisted_as_confirmed'       => $this->t('Mark blacklisted as confirmed'),
            'can_use_own_blacklist'                   => $this->t('Use own blacklist'),
            'can_edit_own_subscribers'                => $this->t('Can edit own subscribers'),
            'subscriber_profile_update_optin_history' => $this->t('Subscriber profile update optin history'),
            'can_create_list_from_filters'            => $this->t('Can create list from filtered search results'),
            'show_7days_subscribers_activity_graph'   => $this->t('Show 7 days subscribers activity'),
            'force_optin_process'                     => $this->t('Force the OPT-IN process'),
            'force_optout_process'                    => $this->t('Force the OPT-OUT process'),
            'custom_fields_default_visibility'        => $this->t('Custom fields default visibility'),
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
            'can_import_subscribers'                  => '',
            'can_export_subscribers'                  => '',
            'can_copy_subscribers'                    => '',
            'max_lists'                               => '',
            'max_subscribers'                         => '',
            'max_subscribers_per_list'                => '',
            'copy_subscribers_at_once'                => '',
            'max_segment_conditions'                  => '',
            'max_segment_wait_timeout'                => '',
            'can_edit_own_subscribers'                => '',
            'subscriber_profile_update_optin_history' => '',
            'force_optin_process'                     => '',
            'force_optout_process'                    => '',
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
            'can_import_subscribers'                  => $this->t('Whether customers are allowed to import subscribers'),
            'can_export_subscribers'                  => $this->t('Whether customers are allowed to export subscribers'),
            'can_copy_subscribers'                    => $this->t('Whether customers are allowed to copy subscribers from a list into another'),
            'max_lists'                               => $this->t('Maximum number of lists a customer can have, set to -1 for unlimited'),
            'max_subscribers'                         => $this->t('Maximum number of subscribers a customer can have, set to -1 for unlimited'),
            'max_subscribers_per_list'                => $this->t('Maximum number of subscribers per list a customer can have, set to -1 for unlimited'),
            'copy_subscribers_at_once'                => $this->t('How many subscribers to copy at once'),
            'can_delete_own_lists'                    => $this->t('Whether customers are allowed to delete their own lists'),
            'can_delete_own_subscribers'              => $this->t('Whether customers are allowed to delete their own subscribers'),
            'can_segment_lists'                       => $this->t('Whether customers are allowed to segment their lists'),
            'max_segment_conditions'                  => $this->t('Maximum number of conditions a list segment can have. This affects performance drastically, keep the number as low as possible'),
            'max_segment_wait_timeout'                => $this->t('Maximum number of seconds a segment is allowed to take in order to load subscribers.'),
            'can_mark_blacklisted_as_confirmed'       => $this->t('Whether customers can mark blacklisted subscribers as confirmed. Please note that this will remove blacklisted emails from the main blacklist'),
            'can_use_own_blacklist'                   => $this->t('Whether customers can use their own blacklist. Please note that the global blacklist has priority over the customer one.'),
            'can_edit_own_subscribers'                => $this->t('Whether customers are allowed to edit their own subscribers'),
            'subscriber_profile_update_optin_history' => $this->t('Whether missing subscriber optin history can be updated when the subscriber will update his profile'),
            'can_create_list_from_filters'            => $this->t('Whether customers can create new lists based on the search results for the filters from All Subscribers area'),
            'show_7days_subscribers_activity_graph'   => $this->t('Whether to show, in list overview, the list subscribers activity for the last 7 days'),
            'force_optin_process'                     => $this->t('Whether to force the customer to certain OPT-IN process. Leave empty to let the customer select the process'),
            'force_optout_process'                    => $this->t('Whether to force the customer to certain OPT-OUT process. Leave empty to let the customer select the process'),
            'custom_fields_default_visibility'        => $this->t('Decide the default visibility for the list custom fields. This affects how the fields are presented to subscribers'),
        ];

        return CMap::mergeArray($texts, parent::attributeHelpTexts());
    }

    /**
     * @return array
     */
    public function getOptInOutOptions(): array
    {
        return [
            ''  => '',
            'single' => $this->t('Single'),
            'double' => $this->t('Double'),
        ];
    }

    /**
     * @return array
     */
    public function getCustomFieldsVisibilityOptions(): array
    {
        return [
            ListField::VISIBILITY_VISIBLE  => t('app', 'Visible'),
            ListField::VISIBILITY_HIDDEN   => t('app', 'Hidden'),
            ListField::VISIBILITY_NONE     => t('app', 'None'),
        ];
    }
}
