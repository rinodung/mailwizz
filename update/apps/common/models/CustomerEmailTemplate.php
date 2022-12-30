<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * CustomerEmailTemplate
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

/**
 * This is the model class for table "customer_email_template".
 *
 * The followings are the available columns in table 'customer_email_template':
 * @property integer|null $template_id
 * @property string $template_uid
 * @property integer|null $customer_id
 * @property integer|null $category_id
 * @property string $name
 * @property string $content
 * @property string $content_hash
 * @property string $create_screenshot
 * @property string $screenshot
 * @property string $inline_css
 * @property string $minify
 * @property string $meta_data
 * @property integer $sort_order
 * @property string|CDbExpression $date_added
 * @property string|CDbExpression $last_updated
 *
 * The followings are the available model relations:
 * @property CampaignTemplate[] $campaignTemplates
 * @property CustomerEmailTemplateCategory $category
 * @property Customer $customer
 *
 * The followings are the available model behaviors:
 * @property EmailTemplateUploadBehavior $uploader
 */
class CustomerEmailTemplate extends ActiveRecord
{
    /**
     * @var CUploadedFile
     */
    public $archive;

    /**
     * @return string
     */
    public function tableName()
    {
        return '{{customer_email_template}}';
    }

    /**
     * @return array
     */
    public function rules()
    {
        $mimes = null;
        if (CommonHelper::functionExists('finfo_open')) {

            /** @var FileExtensionMimes $extensionMimes */
            $extensionMimes = app()->getComponent('extensionMimes');

            /** @var array $mimes */
            $mimes = $extensionMimes->get('zip')->toArray();
        }

        $rules =  [
            ['name, content', 'required', 'on' => 'insert, update'],
            ['archive', 'required', 'on' => 'upload'],
            ['name, content', 'unsafe', 'on' => 'upload'],

            ['name', 'length', 'max'=>255],
            ['category_id', 'exist', 'className' => CustomerEmailTemplateCategory::class],
            ['content', 'safe'],
            ['archive', 'file', 'types' => ['zip'], 'mimeTypes' => $mimes, 'allowEmpty' => true],
            ['sort_order', 'numerical', 'integerOnly' => true],

            ['customer_id, category_id, name', 'safe', 'on' => 'search'],
        ];

        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array
     */
    public function behaviors()
    {
        $behaviors = [
            // will handle the upload but also the afterDelete event to delete uploaded files.
            'uploader' => [
                'class' => 'common.components.db.behaviors.EmailTemplateUploadBehavior',
            ],
        ];

        return CMap::mergeArray($behaviors, parent::behaviors());
    }

    /**
     * @return array
     */
    public function relations()
    {
        $relations = [
            'campaignTemplates' => [self::HAS_MANY, CampaignTemplate::class, 'customer_template_id'],
            'category'          => [self::BELONGS_TO, CustomerEmailTemplateCategory::class, 'category_id'],
            'customer'          => [self::BELONGS_TO, Customer::class, 'customer_id'],
        ];

        return CMap::mergeArray($relations, parent::relations());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels =  [
            'template_id'   => t('email_templates', 'Template'),
            'template_uid'  => t('email_templates', 'Template uid'),
            'customer_id'   => t('email_templates', 'Customer'),
            'category_id'   => t('email_templates', 'Category'),
            'name'          => t('email_templates', 'Name'),
            'content'       => t('email_templates', 'Content'),
            'content_hash'  => t('email_templates', 'Content hash'),
            'create_screenshot' => t('email_templates', 'Create screenshot'),
            'screenshot'    => t('email_templates', 'Screenshot'),
            'archive'       => t('email_templates', 'Archive file'),
        ];

        return CMap::mergeArray($labels, parent::attributeLabels());
    }

    /**
     * Retrieves a list of models based on the current search/filter conditions.
     *
     * Typical usecase:
     * - Initialize the model fields with values from filter form.
     * - Execute this method to get CActiveDataProvider instance which will filter
     * models according to data in model fields.
     * - Pass data provider to CGridView, CListView or any similar widget.
     *
     * @return CActiveDataProvider the data provider that can return the models
     * based on the search/filter conditions.
     * @throws CException
     */
    public function search()
    {
        $criteria = new CDbCriteria();
        $criteria->with = [];

        $criteria->compare('t.name', $this->name, true);
        $criteria->compare('t.category_id', $this->category_id);

        if (!empty($this->customer_id)) {
            if (is_numeric($this->customer_id)) {
                $criteria->compare('t.customer_id', $this->customer_id);
            } else {
                $criteria->with['customer'] = [
                    'condition' => 'customer.email LIKE :name OR customer.first_name LIKE :name OR customer.last_name LIKE :name',
                    'params'    => [':name' => '%' . $this->customer_id . '%'],
                ];
            }
        } elseif ($this->customer_id === null) {
            $criteria->addCondition('t.customer_id IS NULL');
        }

        return new CActiveDataProvider(get_class($this), [
            'criteria'      => $criteria,
            'pagination'    => [
                'pageSize'  => $this->paginationOptions->getPageSize(),
                'pageVar'   => 'page',
            ],
            'sort'  => [
                'defaultOrder' => [
                    'last_updated'   => CSort::SORT_DESC,
                ],
            ],
        ]);
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return CustomerEmailTemplate the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var CustomerEmailTemplate $model */
        $model = parent::model($className);

        return $model;
    }

    /**
     * @param string $template_uid
     *
     * @return CustomerEmailTemplate|null
     */
    public function findByUid(string $template_uid): ?self
    {
        return self::model()->findByAttributes([
            'template_uid' => $template_uid,
        ]);
    }

    /**
     * @return string
     */
    public function generateUid(): string
    {
        $unique = StringHelper::uniqid();
        $exists = $this->findByUid($unique);

        if (!empty($exists)) {
            return $this->generateUid();
        }

        return $unique;
    }

    /**
     * @return array
     */
    public function getInlineCssArray(): array
    {
        return $this->getYesNoOptions();
    }

    /**
     * @return array
     */
    public function attributeHelpTexts()
    {
        $texts = [
            'name' => t('email_templates', 'The name of the template, used for you to make the difference if having to many templates.'),
        ];

        return CMap::mergeArray($texts, parent::attributeHelpTexts());
    }

    /**
     * @return CustomerEmailTemplate|null
     * @throws CException
     */
    public function copy(): ?self
    {
        $copied = null;

        if ($this->getIsNewRecord()) {

            // 1.8.0
            hooks()->doAction('copy_customer_email_template', new CAttributeCollection([
                'template' => $this,
                'copied'   => $copied,
            ]));

            return null;
        }

        $storagePath = (string)Yii::getPathOfAlias('root.frontend.assets.gallery');
        $filesPath   = $storagePath . '/' . $this->template_uid;

        $templateUid  = $this->generateUid();
        $newFilesPath = $storagePath . '/' . $templateUid;

        if (file_exists($filesPath) && is_dir($filesPath) && mkdir($newFilesPath, 0777, true)) {
            if (!FileSystemHelper::copyOnlyDirectoryContents($filesPath, $newFilesPath)) {
                return null;
            }
        }

        $template = clone $this;
        $template->setIsNewRecord(true);
        $template->template_id  = null;
        $template->template_uid = $templateUid;
        $template->content      = (string)str_replace($this->template_uid, $templateUid, $this->content);
        $template->content_hash = '';
        $template->screenshot   = (string)preg_replace('#' . $this->template_uid . '#', $templateUid, $this->screenshot, 1);
        $template->date_added   = MW_DATETIME_NOW;
        $template->last_updated = MW_DATETIME_NOW;

        if (!$template->save(false)) {
            if (file_exists($newFilesPath) && is_dir($newFilesPath)) {
                FileSystemHelper::deleteDirectoryContents($newFilesPath, true, 1);
            }
            return null;
        }

        $copied = $template;

        // 1.8.0
        hooks()->doAction('copy_customer_email_template', new CAttributeCollection([
            'template' => $this,
            'copied'   => $copied,
        ]));

        return $copied;
    }

    /**
     * @return string
     */
    public function getScreenshotSrc(): string
    {
        if (!empty($this->screenshot)) {
            if (FilterVarHelper::url($this->screenshot)) {
                return $this->screenshot;
            }
            try {
                if ($image = @ImageHelper::resize((string)$this->screenshot)) {
                    return $image;
                }
            } catch (Exception $e) {
            }
        }
        return ImageHelper::resize('/frontend/assets/files/no-template-image-320x320.jpg');
    }

    /**
     * @param int $length
     *
     * @return string
     */
    public function getShortName(int $length = 17): string
    {
        return StringHelper::truncateLength((string)$this->name, (int)$length);
    }

    /**
     * @return string
     */
    public function getUid(): string
    {
        return (string)$this->template_uid;
    }

    /**
     * @return bool
     */
    protected function beforeSave()
    {
        if (empty($this->template_uid)) {
            $this->template_uid = $this->generateUid();
        }

        if (empty($this->name)) {
            $this->name = 'Untitled';
        }

        if ($this->content_hash != sha1($this->content)) {
            $this->create_screenshot = self::TEXT_YES;
        }

        $this->content_hash = sha1($this->content);

        return parent::beforeSave();
    }

    /**
     * @return void
     */
    protected function afterDelete()
    {
        // clean template files, if any.
        $storagePath = (string)Yii::getPathOfAlias('root.frontend.assets.gallery');
        $templateFiles = $storagePath . '/' . $this->template_uid;
        if (file_exists($templateFiles) && is_dir($templateFiles)) {
            FileSystemHelper::deleteDirectoryContents($templateFiles, true, 1);
        }

        parent::afterDelete();
    }
}
