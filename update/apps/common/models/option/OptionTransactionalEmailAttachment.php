<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * OptionTransactionalEmailAttachment
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.0.33
 */

class OptionTransactionalEmailAttachment extends OptionBase
{
    /**
     * @var string
     */
    public $enabled = self::TEXT_NO;

    /**
     * @var int
     */
    public $allowed_file_size = 1048576; // 1 mb by default

    /**
     * @var int
     */
    public $allowed_files_count = 5;

    /**
     * @var array
     */
    public $allowed_extensions = [];

    /**
     * @var array
     */
    public $allowed_mime_types = [];

    /**
     * @var string
     */
    protected $_categoryName = 'system.transactional_email.attachments';

    /**
     * @return array
     * @throws CException
     */
    public function rules()
    {
        $rules = [
            ['enabled, allowed_file_size, allowed_files_count', 'required'],
            ['enabled', 'in', 'range' => array_keys($this->getEnabledOptions())],
            ['allowed_file_size', 'in', 'range' => array_keys($this->getFileSizeOptions())],
            ['allowed_files_count', 'numerical', 'integerOnly' => true, 'min' => 1, 'max' => 50],
            ['allowed_extensions, allowed_mime_types', 'safe'],
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
            'enabled'               => $this->t('Enabled'),
            'allowed_file_size'     => $this->t('Allowed file size'),
            'allowed_files_count'   => $this->t('Allowed files count'),
            'allowed_extensions'    => $this->t('Allowed extensions'),
            'allowed_mime_types'    => $this->t('Allowed mime types'),
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
            'enabled'               => '',
            'allowed_file_size'     => '',
            'allowed_files_count'   => $this->t('i.e: 5'),
            'allowed_extensions'    => $this->t('i.e: png'),
            'allowed_mime_types'    => $this->t('i.e: image/png'),
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
            'enabled'               => $this->t('Wheather this feature is enabled and customers can add attachments'),
            'allowed_file_size'     => $this->t('Maximum size of a file allowed for upload'),
            'allowed_files_count'   => $this->t('Maximum number of files allowed for upload'),
            'allowed_extensions'    => $this->t('Only allow uploading of files having this extension'),
            'allowed_mime_types'    => $this->t('Only allow uploading of files having the above extensions and these mime types'),
        ];

        return CMap::mergeArray($texts, parent::attributeHelpTexts());
    }

    /**
     * @return array
     */
    public function getEnabledOptions(): array
    {
        return $this->getYesNoOptions();
    }

    /**
     * @return array
     */
    public function getAllowedExtensions(): array
    {
        return (array)$this->allowed_extensions;
    }

    /**
     * @return array
     */
    public function getAllowedMimeTypes(): array
    {
        return (array)$this->allowed_mime_types;
    }

    /**
     * @return int
     */
    public function getAllowedFileSize(): int
    {
        return (int)$this->allowed_file_size;
    }

    /**
     * @return int
     */
    public function getAllowedFilesCount(): int
    {
        return (int)$this->allowed_files_count;
    }

    /**
     * @return bool
     */
    public function getIsEnabled(): bool
    {
        return $this->enabled === self::TEXT_YES;
    }

    /**
     * @return bool
     */
    protected function beforeValidate()
    {
        if (!is_array($this->allowed_extensions)) {
            $this->allowed_extensions = [];
        }

        if (!is_array($this->allowed_mime_types)) {
            $this->allowed_mime_types = [];
        }

        $this->allowed_extensions = array_unique($this->allowed_extensions);
        $this->allowed_mime_types = array_unique($this->allowed_mime_types);

        $errors = [];
        foreach ($this->allowed_extensions as $index => $ext) {
            $ext = trim((string)$ext);
            if (empty($ext)) {
                unset($this->allowed_extensions[$index]);
                continue;
            }
            if (!preg_match('/([a-z]){2,5}/', $ext)) {
                $errors[] = $this->t('The extension "{ext}" does not seem to be valid!', [
                    '{ext}' => html_encode($ext),
                ]);
            }
        }
        if (!empty($errors)) {
            $this->addError('allowed_extensions', implode('<br />', $errors));
        }

        $errors = [];
        foreach ($this->allowed_mime_types as $index => $mime) {
            $mime = trim((string)$mime);
            if (empty($mime)) {
                unset($this->allowed_mime_types[$index]);
                continue;
            }
            if (!preg_match('/([a-z\-\.\/\_])/', $mime)) {
                $errors[] = $this->t('The mime type "{mime}" does not seem to be valid!', [
                    '{mime}' => html_encode($mime),
                ]);
            }
        }
        if (!empty($errors)) {
            $this->addError('allowed_mime_types', implode('<br />', $errors));
        }

        return parent::beforeValidate();
    }
}
