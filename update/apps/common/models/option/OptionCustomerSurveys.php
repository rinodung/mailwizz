<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * OptionCustomerSurveys
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.7.8
 */

class OptionCustomerSurveys extends OptionBase
{
    /**
     * @var int
     */
    public $max_surveys = -1;

    /**
     * @var int
     */
    public $max_responders = -1;

    /**
     * @var int
     */
    public $max_responders_per_survey = -1;

    /**
     * @var string
     */
    public $can_delete_own_surveys = self::TEXT_YES;

    /**
     * @var string
     */
    public $can_export_responders = self::TEXT_YES;

    /**
     * @var string
     */
    public $can_segment_surveys = self::TEXT_YES;

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
    public $can_delete_own_responders = self::TEXT_YES;

    /**
     * @var string
     */
    public $can_edit_own_responders = self::TEXT_YES;

    /**
     * @var string
     */
    public $show_7days_responders_activity_graph = self::TEXT_YES;

    /**
     * @var string
     */
    protected $_categoryName = 'system.customer_surveys';

    /**
     * @return array
     * @throws CException
     */
    public function rules()
    {
        $rules = [
            ['max_surveys, max_responders, max_responders_per_survey, can_delete_own_surveys, can_delete_own_responders, can_edit_own_responders, can_export_responders, show_7days_responders_activity_graph, can_segment_surveys, max_segment_conditions, max_segment_wait_timeout,', 'required'],
            ['can_delete_own_surveys, can_delete_own_responders, can_edit_own_responders, can_export_responders, show_7days_responders_activity_graph, can_segment_surveys', 'in', 'range' => array_keys($this->getYesNoOptions())],
            ['max_surveys, max_responders, max_responders_per_survey', 'numerical', 'integerOnly' => true, 'min' => -1],
            ['max_segment_conditions, max_segment_wait_timeout', 'numerical', 'integerOnly' => true, 'min' => 0, 'max' => 60],
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
            'max_surveys'                          => $this->t('Max. surveys'),
            'max_responders'                       => $this->t('Max. responders'),
            'max_responders_per_survey'            => $this->t('Max. responders per survey'),
            'can_delete_own_surveys'               => $this->t('Can delete own surveys'),
            'can_delete_own_responders'            => $this->t('Can delete own responders'),
            'can_edit_own_responders'              => $this->t('Can edit own responders'),
            'can_export_responders'                => $this->t('Can export responders'),
            'can_segment_surveys'                  => $this->t('Can segment surveys'),
            'max_segment_conditions'               => $this->t('Max. segment conditions'),
            'max_segment_wait_timeout'             => $this->t('Max. segment wait timeout'),
            'show_7days_responders_activity_graph' => $this->t('Show 7 days responders activity'),
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
            'max_surveys'               => '',
            'max_responders'            => '',
            'max_responders_per_survey' => '',
            'can_edit_own_responders'   => '',
            'can_export_responders'     => '',
            'max_segment_conditions'    => '',
            'max_segment_wait_timeout'  => '',

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
            'max_surveys'                          => $this->t('Maximum number of surveys a customer can have, set to -1 for unlimited'),
            'max_responders'                       => $this->t('Maximum number of responders a customer can have, set to -1 for unlimited'),
            'max_responders_per_survey'            => $this->t('Maximum number of responders per survey a customer can have, set to -1 for unlimited'),
            'can_delete_own_surveys'               => $this->t('Whether customers are allowed to delete their own surveys'),
            'can_delete_own_responders'            => $this->t('Whether customers are allowed to delete their own responders'),
            'can_edit_own_responders'              => $this->t('Whether customers are allowed to edit their own responders'),
            'can_export_responders'                => $this->t('Whether customers are allowed to export responders'),
            'can_segment_surveys'                  => $this->t('Whether customers are allowed to segment their surveys'),
            'max_segment_conditions'               => $this->t('Maximum number of conditions a survey segment can have. This affects performance drastically, keep the number as low as possible'),
            'max_segment_wait_timeout'             => $this->t('Maximum number of seconds a segment is allowed to take in order to load responders.'),
            'show_7days_responders_activity_graph' => $this->t('Whether to show, in survey overview, the survey responders activity for the last 7 days'),
        ];

        return CMap::mergeArray($texts, parent::attributeHelpTexts());
    }
}
