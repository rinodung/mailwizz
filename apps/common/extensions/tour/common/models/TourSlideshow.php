<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * TourSlideshow
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 */

/**
 * This is the model class for table "welcome_tour_slideshow".
 *
 * The followings are the available columns in table 'welcome_tour_slideshow':
 * @property integer $slideshow_id
 * @property string $name
 * @property string $application
 * @property string $status
 * @property string $date_added
 * @property string $last_updated
 *
 * The followings are the available model relations:
 * @property TourSlideshowSlide[] $slides
 * @property TourSlideshowSlide[] $slidesCount
 */
class TourSlideshow extends ActiveRecord
{
    /**
     * Use the needed traits
     */
    use AddShortcutMethodsFromCurrentExtensionTrait;

    /**
     * Flags
     */
    const APPLICATION_BACKEND = 'backend';
    const APPLICATION_CUSTOMER = 'customer';

    /**
     * @return string
     */
    public function tableName()
    {
        return '{{tour_slideshow}}';
    }

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            ['name, application, status', 'required'],
            ['name', 'length', 'max' => 100],
            ['application', 'length', 'max' => 45],
            ['application', 'in', 'range' => array_keys($this->getApplicationsList())],
            ['status', 'length', 'max' => 8],
            ['status', 'in', 'range' => array_keys($this->getStatusesList())],

            // The following rule is used by search().
            ['name, application, status', 'safe', 'on'=>'search'],
        ];

        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array
     */
    public function relations()
    {
        $relations = [
            'slides'      => [self::HAS_MANY, 'TourSlideshowSlide', 'slideshow_id'],
            'slidesCount' => [self::STAT, 'TourSlideshowSlide', 'slideshow_id'],
        ];
        return CMap::mergeArray($relations, parent::relations());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'slideshow_id' => $this->t('Slideshow'),
            'name'         => $this->t('Name'),
            'application'  => $this->t('Application'),
            'slidesCount'  => $this->t('Slides count'),
        ];
        return CMap::mergeArray($labels, parent::attributeLabels());
    }

    /**
     * @return array
     */
    public function attributeHelpTexts()
    {
        $texts = [
            'name'        => $this->t('The name of the slideshow, for internal reference only'),
            'application' => $this->t('The application where this slideshow will be shown'),
        ];

        return CMap::mergeArray($texts, parent::attributeHelpTexts());
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

        $criteria->compare('name', $this->name, true);
        $criteria->compare('application', $this->application, true);
        $criteria->compare('status', $this->status);

        $criteria->order = 'slideshow_id DESC';

        return new CActiveDataProvider(get_class($this), [
            'criteria'     => $criteria,
            'pagination'   => [
                'pageSize' => $this->paginationOptions->getPageSize(),
                'pageVar'  => 'page',
            ],
            'sort'  => [
                'defaultOrder'  => [
                    'slideshow_id' => CSort::SORT_DESC,
                ],
            ],
        ]);
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return TourSlideshow the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var TourSlideshow $model */
        $model = parent::model($className);

        return $model;
    }

    /**
     * @return array
     */
    public function getApplicationsList(): array
    {
        return [
            self::APPLICATION_BACKEND  => $this->t('Backend'),
            self::APPLICATION_CUSTOMER => $this->t('Customer'),
        ];
    }
}
