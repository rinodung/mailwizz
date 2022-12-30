<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * CkeditorExtCommon
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 */

class CkeditorExtCommon extends ExtensionModel
{
    /**
     * @var int
     */
    public $enable_editor = 1;

    /**
     * @var int
     */
    public $enable_filemanager_user = 0;

    /**
     * @var int
     */
    public $enable_filemanager_customer = 0;

    /**
     * @var string
     */
    public $filemanager_theme = '';

    /**
     * @var string
     */
    public $default_toolbar = 'Default';

    /**
     * @return array
     */
    public function rules()
    {
        /** @var CkeditorExt $extension */
        $extension = $this->getExtension();

        $rules = [
            ['enable_editor, enable_filemanager_user, enable_filemanager_customer, default_toolbar', 'required'],
            ['enable_editor, enable_filemanager_user, enable_filemanager_customer', 'in', 'range' => array_keys($this->getOptionsDropDown())],
            ['default_toolbar', 'in', 'range' => $extension->getEditorToolbars()],
            ['filemanager_theme', 'match', 'pattern' => '/^[a-z\-\_]+$/i'],
        ];

        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'enable_editor'               => $this->t('Enable the editor'),
            'enable_filemanager_user'     => $this->t('Enable filemanager for users'),
            'enable_filemanager_customer' => $this->t('Enable filemanager for customers'),
            'default_toolbar'             => $this->t('Default toolbar'),
            'filemanager_theme'           => $this->t('File manager theme'),
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
            'enable_editor'               => $this->t('Whether to enable the editor'),
            'enable_filemanager_user'     => $this->t('Whether to enable the filemanager for users'),
            'enable_filemanager_customer' => $this->t('Whether to enable the filemanager for customers'),
            'default_toolbar'             => $this->t('Default toolbar for all editor instances'),
            'filemanager_theme'           => $this->t('The file manager theme'),
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
    public function getOptionsDropDown(): array
    {
        return [
            0 => t('app', 'No'),
            1 => t('app', 'Yes'),
        ];
    }

    /**
     * @return array
     */
    public function getToolbarsDropDown(): array
    {
        /** @var CkeditorExt $extension */
        $extension = $this->getExtension();
        $toolbars  = $extension->getEditorToolbars();
        return (array)array_combine($toolbars, $toolbars);
    }

    /**
     * @return array
     * @throws CException
     */
    public function getFilemanagerThemesDropDown(): array
    {
        /** @var CkeditorExt $extension */
        $extension = $this->getExtension();
        $themes   = $extension->getFilemanagerThemes();
        $options  = ['' => ''];
        foreach ($themes as $theme) {
            $options[$theme['name']] = ucwords($theme['name']);
        }
        return $options;
    }

    /**
     * @return bool
     */
    public function getIsEditorEnabled(): bool
    {
        return (int)$this->enable_editor === 1;
    }

    /**
     * @return bool
     */
    public function getIsFilemanagerEnabledForUser(): bool
    {
        return (int)$this->enable_filemanager_user === 1;
    }

    /**
     * @return bool
     */
    public function getIsFilemanagerEnabledForCustomer(): bool
    {
        return (int)$this->enable_filemanager_customer === 1;
    }

    /**
     * @return string
     */
    public function getFilemanagerTheme(): string
    {
        return (string)$this->filemanager_theme;
    }

    /**
     * @return string
     */
    public function getDefaultToolbar(): string
    {
        return (string)$this->default_toolbar;
    }
}
