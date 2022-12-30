<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * TranslateExtModel
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 */

class TranslateExtModel extends ExtensionModel
{
    /**
     * @var int
     */
    public $enabled = 0;

    /**
     * @var int
     */
    public $translate_extensions = 0;

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            ['enabled, translate_extensions', 'required'],
            ['enabled, translate_extensions', 'in', 'range' => array_keys($this->getOptionsDropDown())],
        ];

        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'enabled'              => $this->t('Enable automatic translation'),
            'translate_extensions' => $this->t('Enable extensions translation'),
        ];

        return CMap::mergeArray($labels, parent::attributeLabels());
    }

    /**
     * @return array
     */
    public function attributePlaceholders()
    {
        $placeholders = [];

        return CMap::mergeArray($placeholders, parent::attributePlaceholders());
    }

    /**
     * @return array
     */
    public function attributeHelpTexts()
    {
        $texts = [
            'enabled'              => $this->t('Enable writing the missing translations in file.'),
            'translate_extensions' => $this->t('Whether to translate extensions too.'),
        ];

        return CMap::mergeArray($texts, parent::attributeHelpTexts());
    }

    /**
     * @inheritDoc
     */
    public function getCategoryName(): string
    {
        return '';
    }

    /**
     * @return array
     */
    public function getOptionsDropDown()
    {
        return [
            0 => t('app', 'No'),
            1 => t('app', 'Yes'),
        ];
    }

    /**
     * @return bool
     */
    public function getIsEnabled(): bool
    {
        return (int)$this->enabled === 1;
    }

    /**
     * @return bool
     */
    public function getTranslateExtensions(): bool
    {
        return (int)$this->translate_extensions === 1;
    }
}
