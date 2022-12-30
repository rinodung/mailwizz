<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * ListOpenGraph
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

/**
 * This is the model class for table "list_open_graph".
 *
 * The followings are the available columns in table 'list_open_graph':
 * @property integer $list_id
 * @property string $title
 * @property string $description
 * @property string $image
 *
 * The followings are the available model relations:
 * @property Lists $list
 */
class ListOpenGraph extends ActiveRecord
{
    /**
     * @var string
     */
    public $new_image;

    /**
     * @return string
     */
    public function tableName()
    {
        return '{{list_open_graph}}';
    }

    /**
     * @return array
     */
    public function rules()
    {
        $imageMimes = null;
        if (CommonHelper::functionExists('finfo_open')) {

            /** @var FileExtensionMimes $extensionMimes */
            $extensionMimes = app()->getComponent('extensionMimes');

            /** @var array $imageMimes */
            $imageMimes = $extensionMimes->get(['png', 'jpg', 'jpeg', 'gif'])->toArray();
        }

        $rules = [
            // meta data
            ['title, description', 'length', 'max' => 255],

            ['new_image', 'file', 'types' => ['png', 'jpg', 'jpeg', 'gif'], 'mimeTypes' => $imageMimes, 'allowEmpty' => true],
        ];

        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array
     * @throws CException
     */
    public function relations()
    {
        $relations = [
            'list' => [self::BELONGS_TO, Lists::class, 'list_id'],
        ];

        return CMap::mergeArray($relations, parent::relations());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'list_id'     => t('lists', 'List'),
            'title'       => t('lists', 'Title'),
            'description' => t('lists', 'Description'),
            'new_image'   => t('lists', 'Image'),
        ];

        return CMap::mergeArray($labels, parent::attributeLabels());
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return ListOpenGraph the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var ListOpenGraph $model */
        $model = parent::model($className);

        return $model;
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
            return sprintf('https://via.placeholder.com/%dx%d?text=...', $width, $height);
        }
        return (string)ImageHelper::resize($this->image, $width, $height, $forceSize);
    }

    /**
     * @return void
     */
    protected function afterValidate()
    {
        parent::afterValidate();
        $this->handleUploadedImage();
    }

    /**
     * @return void
     */
    protected function handleUploadedImage(): void
    {
        if ($this->hasErrors()) {
            return;
        }

        /** @var CUploadedFile|null $image */
        $image = CUploadedFile::getInstance($this, 'new_image');

        if (!$image) {
            return;
        }

        $storagePath = (string)Yii::getPathOfAlias('root.frontend.assets.files.open-graph-images');
        if (!file_exists($storagePath) || !is_dir($storagePath)) {
            if (!mkdir($storagePath, 0777, true)) {
                $this->addError('new_image', t('lists', 'The open graph images storage directory({path}) does not exists and cannot be created!', [
                    '{path}' => $storagePath,
                ]));
                return;
            }
        }

        $newFileName = StringHelper::random(8, true) . '-' . $image->getName();
        if (!$image->saveAs($storagePath . '/' . $newFileName)) {
            $this->addError('new_image', t('lists', 'Cannot move the image into the correct storage folder!'));
            return;
        }

        $this->image = '/frontend/assets/files/open-graph-images/' . $newFileName;
    }
}
