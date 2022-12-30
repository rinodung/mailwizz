<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * OptionTwoFactorAuth
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.6.6
 */

class OptionTwoFactorAuth extends OptionBase
{
    /**
     * @var string
     */
    public $enabled = self::TEXT_NO;

    /**
     * @var string
     */
    public $companyName = '';

    /**
     * @var string the settings category
     */
    protected $_categoryName = 'system.2fa';

    /**
     * @return array
     * @throws CException
     */
    public function rules()
    {
        $rules = [
            ['enabled, companyName', 'required'],
            ['enabled', 'in', 'range' => array_keys($this->getYesNoOptions())],
            ['companyName', 'length', 'min' => 3, 'max' => 255],
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
            'enabled'       => t('app', 'Enabled'),
            'companyName'   => $this->t('Company name'),
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
        $texts = [
            'enabled'     => $this->t('Whether 2FA is enabled system wide'),
            'companyName' => $this->t('It is shown in the authenticator app for easier identification'),
        ];

        return CMap::mergeArray($texts, parent::attributeHelpTexts());
    }

    /**
     * @return bool
     */
    public function getIsEnabled()
    {
        return (string)$this->enabled === self::TEXT_YES;
    }
}
