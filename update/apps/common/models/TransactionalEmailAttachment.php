<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * This is the model class for table "transactional_email_attachment".
 *
 * The followings are the available columns in table 'transactional_email_attachment':
 * @property integer|null $attachment_id
 * @property integer|null $email_id
 * @property string|CUploadedFile[] $file
 * @property string $name
 * @property integer $size
 * @property string $extension
 * @property string $type
 * @property string|CDbExpression $date_added
 * @property string|CDbExpression $last_updated
 *
 * The followings are the available model relations:
 * @property TransactionalEmail $email
 */
class TransactionalEmailAttachment extends ActiveRecord
{
    /**
     * @return string
     */
    public function tableName()
    {
        return '{{transactional_email_attachment}}';
    }

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            ['email_id, file', 'required'],
            ['name, size, extension, type', 'required', 'except' => 'multi-upload'],
            ['name, size, extension, type', 'unsafe', 'on' => 'multi-upload'],

            ['file', 'file',
                'types'      => $this->getAllowedExtensions(),
                'mimeTypes'  => ($this->getAllowedMimeTypes() === [] ? null : $this->getAllowedMimeTypes()),
                'maxSize'    => $this->getAllowedFileSize(),
                'maxFiles'   => $this->getAllowedFilesCount(),
                'allowEmpty' => true,
                'on'         => 'multi-upload',
            ],

            ['email_id', 'exist', 'className' => TransactionalEmail::class],
            ['name', 'match', 'pattern' => '/\w+/i'],
            ['size', 'numerical', 'integerOnly' => true, 'min' => 0, 'max' => $this->getAllowedFileSize()],
        ];

        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array
     */
    public function relations()
    {
        $relations = [
            'email' => [self::BELONGS_TO, TransactionalEmail::class, 'email_id'],
        ];
        return CMap::mergeArray($relations, parent::relations());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'attachment_id'  => t('transactional_emails', 'Attachment'),
            'email_id'       => t('transactional_emails', 'Transactional email'),
            'file'           => t('transactional_emails', 'File'),
            'name'           => t('transactional_emails', 'Name'),
            'size'           => t('transactional_emails', 'Size'),
            'extension'      => t('transactional_emails', 'Extension'),
            'type'           => t('transactional_emails', 'Mime type'),
        ];

        return CMap::mergeArray($labels, parent::attributeLabels());
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return TransactionalEmailAttachment the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var TransactionalEmailAttachment $model */
        $model = parent::model($className);

        return $model;
    }

    /**
     * @return bool
     */
    public function validateAndSave()
    {
        return $this->validate();
    }

    /**
     * @return array
     */
    public function getAllowedExtensions(): array
    {
        $extensions = [
            'png', 'jpg', 'jpeg', 'gif',
            'pdf', 'doc', 'docx', 'xls', 'xlsx',
            'ppt', 'pptx',
        ];

        /** @var OptionTransactionalEmailAttachment $optionTransactionalEmailAttachment */
        $optionTransactionalEmailAttachment = container()->get(OptionTransactionalEmailAttachment::class);

        $allowedExtensions = $optionTransactionalEmailAttachment->getAllowedExtensions();

        if (!empty($allowedExtensions)) {
            $extensions = $allowedExtensions;
        }

        return array_filter(array_unique((array)hooks()->applyFilters('transactional_email_attachments_allowed_extensions', $extensions)));
    }

    /**
     * @return array
     * @throws CException
     */
    public function getAllowedMimeTypes(): array
    {
        if (!CommonHelper::functionExists('finfo_open')) {
            return [];
        }

        /** @var FileExtensionMimes $extensionMimes */
        $extensionMimes = app()->getComponent('extensionMimes');

        $mimes = [];
        foreach ($this->getAllowedExtensions() as $type) {
            $mimes = CMap::mergeArray($mimes, $extensionMimes->get($type)->toArray());
        }

        /** @var OptionTransactionalEmailAttachment $optionTransactionalEmailAttachment */
        $optionTransactionalEmailAttachment = container()->get(OptionTransactionalEmailAttachment::class);

        $allowedMimes = $optionTransactionalEmailAttachment->getAllowedMimeTypes();

        if (!empty($allowedMimes)) {
            $mimes = $allowedMimes;
        }

        return array_filter(array_unique((array)hooks()->applyFilters('transactional_email_attachments_allowed_types', $mimes)));
    }

    /**
     * @return int
     */
    public function getAllowedFileSize(): int
    {
        /** @var OptionTransactionalEmailAttachment $optionTransactionalEmailAttachment */
        $optionTransactionalEmailAttachment = container()->get(OptionTransactionalEmailAttachment::class);

        $size = $optionTransactionalEmailAttachment->getAllowedFileSize();
        return (int)hooks()->applyFilters('transactional_email_attachments_allowed_file_size', $size);
    }

    /**
     * @return int
     */
    public function getAllowedFilesCount(): int
    {
        /** @var OptionTransactionalEmailAttachment $optionTransactionalEmailAttachment */
        $optionTransactionalEmailAttachment = container()->get(OptionTransactionalEmailAttachment::class);

        $count = $optionTransactionalEmailAttachment->getAllowedFilesCount();
        return (int)hooks()->applyFilters('transactional_email_attachments_allowed_files_count', $count);
    }

    /**
     * @return string
     */
    public function getAbsolutePath(): string
    {
        if (!($relativePath = $this->getRelativePath())) {
            return '';
        }
        return (string)Yii::getPathOfAlias('root') . $relativePath;
    }

    /**
     * @return string
     */
    public function getRelativePath(): string
    {
        if (empty($this->email)) {
            return '';
        }

        return sprintf('/frontend/assets/files/transactional-email-attachments/%s/', $this->email->email_uid);
    }

    /**
     * @return string
     */
    public function getContentAsBase64(): string
    {
        // @phpstan-ignore-next-line
        if (!is_file($file = (string)Yii::getPathOfAlias('root') . (string)$this->file)) {
            return '';
        }
        return (string)base64_encode((string)FileSystemHelper::getFileContents($file));
    }

    /**
     * @return void
     * @throws CDbException
     */
    protected function afterValidate()
    {
        if ($this->hasErrors()) {
            parent::afterValidate();
            return;
        }

        if ($this->getScenario() === 'multi-upload') {
            $this->handleMultiFileUpload();
        }

        parent::afterValidate();
    }

    /**
     * @return void
     */
    protected function afterDelete()
    {
        // @phpstan-ignore-next-line
        if (is_file($file = (string)Yii::getPathOfAlias('root') . (string)$this->file)) {
            unlink($file);
        }
        parent::afterDelete();
    }

    /**
     * @throws CDbException
     */
    protected function handleMultiFileUpload(): void
    {
        $absolute = $this->getAbsolutePath();
        if (empty($absolute) || (!file_exists($absolute) && !is_dir($absolute) && !mkdir($absolute, 0777, true))) {
            return;
        }

        if (empty($this->file) || !is_array($this->file)) {
            return;
        }

        foreach ($this->file as $file) {
            $model = new self();
            $model->email_id     = (int)$this->email_id;
            $model->file         = $this->getRelativePath() . $file->name;
            $model->name         = $file->name;
            $model->size         = $file->size;
            $model->extension    = $file->extensionName;
            $model->type         = $file->type;

            if (!$model->save()) {
                Yii::log($model->shortErrors->getAllAsString(), CLogger::LEVEL_ERROR);
                continue;
            }

            if (is_uploaded_file($file->tempName)) {
                if (!$file->saveAs($absolute . $file->name)) {
                    $model->delete();
                }
            } else {
                if (!copy($file->tempName, $absolute . $file->name)) {
                    $model->delete();
                }
                unlink($file->tempName);
            }
        }
    }
}
