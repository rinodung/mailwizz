<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * OptionImporter
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

class OptionImporter extends OptionBase
{
    /**
     * @var string
     */
    public $enabled = self::TEXT_YES;

    /**
     * @var int
     */
    public $file_size_limit = 1048576; // 1 mb by default

    /**
     * @var int
     */
    public $import_at_once = 50; // per batch

    /**
     * @var int
     */
    public $pause = 1; // pause between the batches

    /**
     * @var string
     */
    public $check_mime_type = self::TEXT_YES;

    /**
     * @var string
     */
    public $web_enabled = self::TEXT_YES;

    /**
     * @var string
     */
    public $cli_enabled = self::TEXT_NO;

    /**
     * @var string
     */
    public $url_enabled = self::TEXT_NO;

    /**
     * @var string
     */
    public $suppression_list_cli_enabled = self::TEXT_NO;

    /**
     * @var string
     */
    public $email_blacklist_cli_enabled = self::TEXT_NO;

    /**
     * @var string
     */
    protected $_categoryName = 'system.importer';

    /**
     * @return array
     * @throws CException
     */
    public function rules()
    {
        $rules = [
            ['enabled, file_size_limit, import_at_once, pause, check_mime_type, web_enabled, cli_enabled, url_enabled, suppression_list_cli_enabled, email_blacklist_cli_enabled', 'required'],
            ['enabled, web_enabled, cli_enabled, url_enabled, suppression_list_cli_enabled, email_blacklist_cli_enabled', 'in', 'range' => array_keys($this->getYesNoOptions())],
            ['file_size_limit, import_at_once, pause', 'numerical', 'integerOnly' => true],
            ['import_at_once', 'numerical', 'min' => 50, 'max' => 100000],
            ['pause', 'numerical', 'min' => 0, 'max' => 60],
            ['file_size_limit', 'in', 'range' => array_keys($this->getFileSizeOptions())],
            ['check_mime_type', 'in', 'range' => array_keys($this->getYesNoOptions())],
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
            'enabled'                      => $this->t('Enabled'),
            'file_size_limit'              => $this->t('File size limit'),
            'import_at_once'               => $this->t('Import at once'),
            'pause'                        => $this->t('Pause'),
            'check_mime_type'              => $this->t('Check mime type'),
            'cli_enabled'                  => $this->t('CLI import enabled'),
            'web_enabled'                  => $this->t('Web import enabled'),
            'url_enabled'                  => $this->t('Url import enabled'),
            'suppression_list_cli_enabled' => $this->t('Suppression lists CLI import enabled'),
            'email_blacklist_cli_enabled'  => $this->t('Email blacklist CLI import enabled'),
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
            'enabled'                      => null,
            'file_size_limit'              => null,
            'import_at_once'               => null,
            'pause'                        => null,
            'check_mime_type'              => null,
            'web_enabled'                  => null,
            'cli_enabled'                  => null,
            'url_enabled'                  => null,
            'suppression_list_cli_enabled' => null,
            'email_blacklist_cli_enabled'  => null,
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
            'enabled'                      => $this->t('Whether customers are allowed to import subscribers.'),
            'file_size_limit'              => $this->t('The maximum allowed file size for upload.'),
            'import_at_once'               => $this->t('How many subscribers to import per batch.'),
            'pause'                        => $this->t('How many seconds the script should "sleep" after each batch of subscribers.'),
            'check_mime_type'              => $this->t('Whether to check the uploaded file mime type.'),
            'cli_enabled'                  => $this->t('Whether the CLI import is enabled. Please keep in mind that you have to add a cron job in order for this to work.'),
            'web_enabled'                  => $this->t('Whether the import via customer browser is enabled.'),
            'url_enabled'                  => $this->t('Whether the recurring import from remote urls is allowed. CLI import has to be enabled as well in order for this to work. Please note that importing from unknown sources can be dangerous. Use with caution.'),
            'suppression_list_cli_enabled' => $this->t('Whether the CLI import for suppression lists is enabled. Please keep in mind that you have to add a cron job in order for this to work.'),
            'email_blacklist_cli_enabled'  => $this->t('Whether the CLI import for email blacklist is enabled. Please keep in mind that you have to add a cron job in order for this to work.'),
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

    /**
     * @return int
     */
    public function getFileSizeLimit(): int
    {
        return (int)$this->file_size_limit;
    }

    /**
     * @return int
     */
    public function getImportAtOnce(): int
    {
        return (int)$this->import_at_once;
    }

    /**
     * @return int
     */
    public function getPause(): int
    {
        return (int)$this->pause;
    }

    /**
     * @return bool
     */
    public function getCheckMimeType(): bool
    {
        return $this->check_mime_type === self::TEXT_YES;
    }

    /**
     * @return bool
     */
    public function getCanCheckMimeType(): bool
    {
        return $this->getCheckMimeType() && CommonHelper::functionExists('finfo_open');
    }

    /**
     * @return bool
     */
    public function getIsWebEnabled(): bool
    {
        return $this->web_enabled === self::TEXT_YES;
    }

    /**
     * @return bool
     */
    public function getIsCliEnabled(): bool
    {
        return $this->cli_enabled === self::TEXT_YES;
    }

    /**
     * @return bool
     */
    public function getIsUrlEnabled(): bool
    {
        return $this->url_enabled === self::TEXT_YES;
    }

    /**
     * @return bool
     */
    public function getIsSuppressionListCliEnabled(): bool
    {
        return $this->suppression_list_cli_enabled === self::TEXT_YES;
    }

    /**
     * @return bool
     */
    public function getIsEmailBlacklistCliEnabled(): bool
    {
        return $this->email_blacklist_cli_enabled === self::TEXT_YES;
    }
}
