<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * OptionCampaignMisc
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.5.9
 */

class OptionCampaignMisc extends OptionBase
{
    /**
     * @var string
     */
    public $not_allowed_from_domains = '';

    /**
     * @var string
     */
    public $not_allowed_from_patterns = '';

    /**
     * @var string
     */
    protected $_categoryName = 'system.campaign.misc';

    /**
     * @return array
     * @throws CException
     */
    public function rules()
    {
        $rules = [
            ['not_allowed_from_domains, not_allowed_from_patterns', 'length', 'max' => 60000],
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
            'not_allowed_from_domains'   => $this->t('Not allowed FROM domains'),
            'not_allowed_from_patterns'  => $this->t('Not allowed FROM regex patterns'),
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
            'not_allowed_from_domains'  => 'yahoo.com, gmail.com, aol.com',
            'not_allowed_from_patterns' => "/^(.*)@yahoo\.com$/i\n/^name@(.*)\.com$/i\n/^name@goo(.*)\.(com|net|org)$/i",
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
            'not_allowed_from_domains'  => $this->t('List of domain names that are not allowed to be used in the campaign FROM email address. Separate multiple domains by a comma'),
            'not_allowed_from_patterns' => $this->t('List of regex patterns that are not allowed to be used in the campaign FROM email address. Add each pattern on it\'s own line. Please make sure your patterns are valid!'),
        ];

        return CMap::mergeArray($texts, parent::attributeHelpTexts());
    }

    /**
     * @return array
     */
    public function getNotAllowedFromDomains(): array
    {
        return CommonHelper::getArrayFromString(strtolower($this->not_allowed_from_domains));
    }

    /**
     * @return array
     */
    public function getNotAllowedFromPatterns(): array
    {
        return CommonHelper::getArrayFromString(strtolower($this->not_allowed_from_patterns));
    }

    /**
     * @return bool
     */
    protected function beforeValidate()
    {
        $domains = CommonHelper::getArrayFromString((string)$this->not_allowed_from_domains);
        foreach ($domains as $index => $domain) {
            if (!FilterVarHelper::url('http://' . $domain)) {
                unset($domains[$index]);
            }
        }
        $this->not_allowed_from_domains = CommonHelper::getStringFromArray($domains);

        $patterns = CommonHelper::getArrayFromString((string)$this->not_allowed_from_patterns, "\n");
        $patterns = array_filter(array_unique(array_map('trim', $patterns)));
        $this->not_allowed_from_patterns = CommonHelper::getStringFromArray($patterns, "\n");

        return parent::beforeValidate();
    }
}
