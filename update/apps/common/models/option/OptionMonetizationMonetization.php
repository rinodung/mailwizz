<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * OptionMonetizationMonetization
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.4.4
 */

class OptionMonetizationMonetization extends OptionBase
{
    /**
     * @var string
     */
    public $enabled = self::TEXT_NO;

    /**
     * @var string
     */
    protected $_categoryName = 'system.monetization.monetization';

    /**
     * @return array
     * @throws CException
     */
    public function rules()
    {
        $rules = [
            ['enabled', 'required'],
            ['enabled', 'in', 'range' => array_keys($this->getYesNoOptions())],
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
            'enabled' => $this->t('Enabled'),
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
            'enabled' => '',
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
            'enabled' => $this->t('Whether the whole monetization module is enabled'),
        ];

        return CMap::mergeArray($texts, parent::attributeHelpTexts());
    }

    /**
     * @return bool
     */
    public function getIsEnabled(): bool
    {
        return $this->enabled === self::TEXT_YES;
    }
}
