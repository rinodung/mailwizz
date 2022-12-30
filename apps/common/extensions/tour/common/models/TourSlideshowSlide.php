<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * TourSlideshowSlides
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 */

/**
 * This is the model class for table "welcome_tour_slideshow_slides".
 *
 * The followings are the available columns in table 'welcome_tour_slideshow_slides':
 * @property integer $slide_id
 * @property integer $slideshow_id
 * @property string $title
 * @property string $content
 * @property string $image
 * @property integer $sort_order
 * @property string $status
 * @property string $date_added
 * @property string $last_updated
 *
 * The followings are the available model relations:
 * @property TourSlideshow $slideshow
 */
class TourSlideshowSlide extends ActiveRecord
{
    /**
     * Use the needed traits
     */
    use AddShortcutMethodsFromCurrentExtensionTrait;

    /**
     * @var CUploadedFile|null
     */
    public $image_up;

    /**
     * @return string
     */
    public function tableName()
    {
        return '{{tour_slideshow_slide}}';
    }

    /**
     * @return array
     * @throws CException
     */
    public function rules()
    {
        $mimes = null;
        if (CommonHelper::functionExists('finfo_open')) {

            /** @var FileExtensionMimes $extensionMimes */
            $extensionMimes = app()->getComponent('extensionMimes');

            /** @var array $mimes */
            $mimes = $extensionMimes->get(['png', 'jpg', 'jpeg', 'gif'])->toArray();
        }

        $rules = [
            ['content, sort_order, status', 'required'],
            ['title', 'length', 'max' => 100],
            ['image', 'length', 'max' => 255],
            ['status', 'length', 'max' => 8],
            ['sort_order', 'numerical', 'integerOnly' => true],
            ['sort_order', 'in', 'range' => array_keys($this->getSortOrderList())],

            ['image_up', 'file', 'types' => ['png', 'jpg', 'jpeg', 'gif'], 'mimeTypes' => $mimes, 'allowEmpty' => true],
            ['image', '_validateImage'],

            // The following rule is used by search().
            ['title, description, status', 'safe', 'on'=>'search'],
        ];
        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array
     */
    public function relations()
    {
        $relations = [
            'slideshow' => [self::BELONGS_TO, 'TourSlideshow', 'slideshow_id'],
        ];

        return CMap::mergeArray($relations, parent::relations());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'slide_id'     => $this->t('Slide'),
            'slideshow_id' => $this->t('Slideshow'),
            'title'        => $this->t('Title'),
            'content'      => $this->t('Content'),
            'image'        => $this->t('Image'),
            'sort_order'   => $this->t('Sort order'),
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
     *
     * @throws CException
     */
    public function search()
    {
        $criteria=new CDbCriteria();

        $criteria->compare('slideshow_id', (int)$this->slideshow_id);
        $criteria->compare('slide_id', $this->slide_id);
        $criteria->compare('title', $this->title, true);
        $criteria->compare('content', $this->content, true);
        $criteria->compare('status', $this->status);

        $criteria->order = 'sort_order ASC, slide_id ASC';

        return new CActiveDataProvider(get_class($this), [
            'criteria'      => $criteria,
            'pagination'    => [
                'pageSize'  => $this->paginationOptions->getPageSize(),
                'pageVar'   => 'page',
            ],
            'sort'  => [
                'defaultOrder'  => [
                    'sort_order' => CSort::SORT_ASC,
                    'slide_id'   => CSort::SORT_DESC,
                ],
            ],
        ]);
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return TourSlideshowSlide the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var TourSlideshowSlide $model */
        $model = parent::model($className);

        return $model;
    }

    /**
     * @param int $width
     * @param int $height
     *
     * @return string
     */
    public function getDefaultImageUrl(int $width, int $height): string
    {
        return sprintf('https://via.placeholder.com/%dx%d?text=...', $width, $height);
    }

    /**
     * @param int $width
     * @param int $height
     * @param bool $forceSize
     *
     * @return string
     */
    public function getImageUrl(int $width = 50, int $height = 50, bool $forceSize = false): string
    {
        if (empty($this->image)) {
            return $this->getDefaultImageUrl($width, $height);
        }
        return ImageHelper::resize((string)$this->image, $width, $height, $forceSize);
    }

    /**
     * @param string $attribute
     * @param string $targetAttribute
     *
     * @return $this
     */
    public function handleUploadedImage(string $attribute, string $targetAttribute): self
    {
        if ($this->hasErrors()) {
            return $this;
        }

        /** @var CUploadedFile|null $image */
        $image = CUploadedFile::getInstance($this, $attribute);

        if (!$image) {
            return $this;
        }

        $storagePath = (string)Yii::getPathOfAlias('root.frontend.assets.files.tour');
        if (!file_exists($storagePath) || !is_dir($storagePath)) {
            if (!mkdir($storagePath, 0777, true)) {
                $this->addError($attribute, $this->t('The logos storage directory({path}) does not exists and cannot be created!', [
                    '{path}' => $storagePath,
                ]));
                return $this;
            }
        }

        $newName = StringHelper::uniqid() . '-' . $image->getName();
        if (!$image->saveAs($storagePath . '/' . $newName)) {
            $this->addError($attribute, $this->t('Cannot move the image into the correct storage folder!'));
            return $this;
        }

        $this->$targetAttribute = '/frontend/assets/files/tour/' . $newName;
        return $this;
    }

    /**
     * @param string $attribute
     * @param array $params
     *
     * @return void
     */
    public function _validateImage(string $attribute, array $params = []): void
    {
        if ($this->hasErrors($attribute) || empty($this->$attribute)) {
            return;
        }

        /** @var string $fullPath */
        $fullPath = (string)Yii::getPathOfAlias('root') . $this->$attribute;

        $extensionName = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
        if (!in_array($extensionName, (array)app_param('files.images.extensions', []))) {
            $this->addError($attribute, $this->t('Seems that "{attr}" is not a valid image!', [
                '{attr}' => $this->getAttributeLabel($attribute),
            ]));
            return;
        }

        if (strpos($this->$attribute, '/frontend/assets/files/tour/') !== 0 || !is_file($fullPath) || !($info = ImageHelper::getImageSize($fullPath))) {
            $this->addError($attribute, $this->t('Seems that "{attr}" is not a valid image!', [
                '{attr}' => $this->getAttributeLabel($attribute),
            ]));
            return;
        }
    }

    /**
     * @return void
     */
    protected function afterValidate()
    {
        $this->handleUploadedImage('image_up', 'image');
        parent::afterValidate();
    }
}
