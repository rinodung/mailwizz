<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * ListImportAbstract
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.4.5
 */

abstract class ListImportAbstract extends FormModel
{
    /**
     * @var int
     */
    public $rows_count = 0;

    /**
     * @var int
     */
    public $current_page = 1;

    /**
     * @var int
     */
    public $is_first_batch = 1;

    /**
     * @var CUploadedFile|null
     */
    public $file;

    /**
     * @var string
     */
    public $file_name;

    /**
     * @var int
     */
    public $file_size_limit = 5242880; // 5 mb by default

    /**
     * @var string
     */
    private $_uploadPath;

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            ['rows_count, current_page, is_first_batch', 'numerical', 'integerOnly' => true],
        ];

        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'file'      => t('list_import', 'File'),
            'file_name' => t('list_import', 'File'),
        ];

        return CMap::mergeArray($labels, parent::attributeLabels());
    }

    /**
     * @return bool
     */
    public function upload(): bool
    {
        // no reason to go further if there are errors.
        if (!$this->validate() || empty($this->file)) {
            return false;
        }

        $uploadPath = (string)$this->getUploadPath();
        if (!file_exists($uploadPath) && !mkdir($uploadPath, 0777, true)) {
            $this->addError('file', t('list_import', 'Unable to create target directory!'));
            return false;
        }

        $this->file_name = StringHelper::randomSha1() . '.csv';

        if (!$this->file->saveAs($uploadPath . $this->file_name)) {
            $this->file_name = '';
            $this->addError('file', t('list_import', 'Unable to move the uploaded file!'));
            return false;
        }

        if (!StringHelper::fixFileEncoding($uploadPath . $this->file_name)) {
            unlink($uploadPath . $this->file_name);
            $this->addError('file', t('list_import', 'Your uploaded file is not using the UTF-8 charset. Please save it in UTF-8 then upload it again.'));
            $this->file_name = '';
            return false;
        }

        return true;
    }

    /**
     * @param string $uploadPath
     */
    public function setUploadPath(string $uploadPath): void
    {
        $this->_uploadPath = $uploadPath;
    }

    /**
     * @return string
     */
    public function getUploadPath(): string
    {
        if (empty($this->_uploadPath)) {
            $this->_uploadPath = (string)Yii::getPathOfAlias('common.runtime.list-import') . '/';
        }
        return rtrim((string)$this->_uploadPath, '/') . '/';
    }
}
