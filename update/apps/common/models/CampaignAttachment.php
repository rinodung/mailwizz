<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * This is the model class for table "campaign_attachment".
 *
 * The followings are the available columns in table 'campaign_attachment':
 * @property integer|null $attachment_id
 * @property integer|null $campaign_id
 * @property string|array $file
 * @property string $name
 * @property integer $size
 * @property string $extension
 * @property string $mime_type
 * @property string|CDbExpression $date_added
 * @property string|CDbExpression $last_updated
 *
 * The followings are the available model relations:
 * @property Campaign $campaign
 */
class CampaignAttachment extends ActiveRecord
{
    /**
     * @return string
     */
    public function tableName()
    {
        return '{{campaign_attachment}}';
    }

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            ['campaign_id, file', 'required'],
            ['name, size, extension, mime_type', 'required', 'except' => 'multi-upload'],
            ['name, size, extension, mime_type', 'unsafe', 'on' => 'multi-upload'],

            ['file', 'file',
                'types'      => $this->getAllowedExtensions(),
                'mimeTypes'  => ($this->getAllowedMimeTypes() === [] ? null : $this->getAllowedMimeTypes()),
                'maxSize'    => $this->getAllowedFileSize(),
                'maxFiles'   => $this->getAllowedFilesCount(),
                'allowEmpty' => true,
                'on'         => 'multi-upload',
            ],

            ['campaign_id', 'exist', 'className' => Campaign::class],
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
            'campaign' => [self::BELONGS_TO, Campaign::class, 'campaign_id'],
        ];
        return CMap::mergeArray($relations, parent::relations());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'attachment_id'  => t('campaigns', 'Attachment'),
            'campaign_id'    => t('campaigns', 'Campaign'),
            'file'           => t('campaigns', 'File'),
            'name'           => t('campaigns', 'Name'),
            'size'           => t('campaigns', 'Size'),
            'extension'      => t('campaigns', 'Extension'),
            'mime_type'      => t('campaigns', 'Mime type'),
        ];

        return CMap::mergeArray($labels, parent::attributeLabels());
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return CampaignAttachment the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var CampaignAttachment $model */
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

        /** @var OptionCampaignAttachment $optionCampaignAttachment */
        $optionCampaignAttachment = container()->get(OptionCampaignAttachment::class);

        $allowedExtensions = $optionCampaignAttachment->getAllowedExtensions();

        if (!empty($allowedExtensions)) {
            $extensions = $allowedExtensions;
        }

        return array_filter(array_unique((array)hooks()->applyFilters('campaign_attachments_allowed_extensions', $extensions)));
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

        /** @var OptionCampaignAttachment $optionCampaignAttachment */
        $optionCampaignAttachment = container()->get(OptionCampaignAttachment::class);

        $allowedMimes = $optionCampaignAttachment->getAllowedMimeTypes();

        if (!empty($allowedMimes)) {
            $mimes = $allowedMimes;
        }

        return array_filter(array_unique((array)hooks()->applyFilters('campaign_attachments_allowed_mime_types', $mimes)));
    }

    /**
     * @return int
     */
    public function getAllowedFileSize(): int
    {
        /** @var OptionCampaignAttachment $optionCampaignAttachment */
        $optionCampaignAttachment = container()->get(OptionCampaignAttachment::class);

        $size = $optionCampaignAttachment->getAllowedFileSize();
        return (int)hooks()->applyFilters('campaign_attachments_allowed_file_size', $size);
    }

    /**
     * @return int
     */
    public function getAllowedFilesCount(): int
    {
        /** @var OptionCampaignAttachment $optionCampaignAttachment */
        $optionCampaignAttachment = container()->get(OptionCampaignAttachment::class);

        $count = $optionCampaignAttachment->getAllowedFilesCount();
        return (int)hooks()->applyFilters('campaign_attachments_allowed_files_count', $count);
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
        if (empty($this->campaign)) {
            return '';
        }

        return sprintf('/frontend/assets/files/campaign-attachments/%s/', $this->campaign->campaign_uid);
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

        $files = CUploadedFile::getInstances($this, 'file');
        if (empty($files)) {
            return;
        }

        foreach ($files as $file) {
            $model = new self();
            $model->campaign_id  = (int)$this->campaign_id;
            $model->file         = $this->getRelativePath() . $file->name;
            $model->name         = $file->name;
            $model->size         = $file->size;
            $model->extension    = $file->extensionName;
            $model->mime_type    = $file->type;

            if (!$model->save()) {
                continue;
            }

            if (!$file->saveAs($absolute . $file->name)) {
                $model->delete();
            }
        }
    }
}
