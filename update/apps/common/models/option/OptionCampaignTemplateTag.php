<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * OptionCampaignTemplateTag
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.3
 */

class OptionCampaignTemplateTag extends OptionBase
{
    /**
     * @var array
     */
    public $template_tags = [];

    /**
     * @var string
     */
    protected $_categoryName = 'system.campaign.template_tags';

    /**
     * @var CampaignTemplate
     */
    protected $_campaignTemplateModel;

    /**
     * @return array
     * @throws CException
     */
    public function rules()
    {
        $rules = [
            ['template_tags', 'safe'],
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
            'template_tags' => $this->t('Template tags'),
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
        $texts = [];

        return CMap::mergeArray($texts, parent::attributeHelpTexts());
    }

    /**
     * @return array
     */
    public function getRequiredOptions(): array
    {
        return [
            0  => t('app', 'Not required'),
            1  => t('app', 'Required'),
        ];
    }

    /**
     * @return CampaignTemplate
     */
    public function getCampaignTemplateModel(): CampaignTemplate
    {
        if ($this->_campaignTemplateModel !== null) {
            return $this->_campaignTemplateModel;
        }
        return $this->_campaignTemplateModel = new CampaignTemplate();
    }

    /**
     * @return array
     */
    public function getTemplateTags(): array
    {
        return (array)$this->template_tags;
    }

    /**
     * Avoids never ending loop for being called from CampaignTemplate::getAvailableTags
     *
     * @return array
     */
    public static function getSavedTemplateTags(): array
    {
        return (array)options()->get('system.campaign.template_tags.template_tags', []);
    }

    /**
     * @return void
     */
    protected function afterConstruct()
    {
        parent::afterConstruct();
        $this->template_tags = $this->getCampaignTemplateModel()->getAvailableTags();
    }

    /**
     * @return bool
     */
    protected function beforeValidate()
    {
        if (!is_array($this->template_tags)) {
            $this->template_tags = [];
        }

        $availableTags = $this->getCampaignTemplateModel()->getAvailableTags();

        if (isset($this->template_tags['tag'], $this->template_tags['required']) && is_array($this->template_tags['tag']) && is_array($this->template_tags['required'])) {
            if (count($this->template_tags['tag']) == count($this->template_tags['required'])) {
                $this->template_tags['tag']      = array_values($this->template_tags['tag']);
                $this->template_tags['required'] = array_values($this->template_tags['required']);
                $this->template_tags             = (array)array_combine($this->template_tags['tag'], $this->template_tags['required']);
            }
        }

        foreach ($availableTags as $index => $tagInfo) {
            if (isset($this->template_tags[$tagInfo['tag']])) {
                $availableTags[$index]['required'] = (bool)$this->template_tags[$tagInfo['tag']];
            }
        }

        $this->template_tags = $availableTags;

        return parent::beforeValidate();
    }
}
