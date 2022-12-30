<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * MenuItem
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.0.30
 */

/**
 * This is the model class for table "menu_item".
 *
 * The followings are the available columns in table 'menu_item':
 * @property integer $item_id
 * @property integer $menu_id
 * @property string $label
 * @property string $title
 * @property string $url
 * @property int $sort_order
 * @property string|CDbExpression $date_added
 * @property string|CDbExpression $last_updated
 *
 * The followings are the available model relations:
 * @property Menu $menu
 */
class MenuItem extends ActiveRecord
{
    /**
     * @return string
     */
    public function tableName()
    {
        return '{{menu_item}}';
    }

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            ['label, url', 'required'],
            ['title, label', 'length', 'max' => 100],
            ['url', 'length', 'max' => 255],
            ['sort_order', 'in', 'range' => array_keys(ArrayHelper::getAssociativeRange(-100, 100))],
            ['menu_id', 'numerical', 'integerOnly' => true],
            ['menu_id', 'exist', 'className' => Menu::class],

            // The following rule is used by search().
            ['title, menu_id', 'safe', 'on' => 'search'],
        ];

        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array relational rules.
     * @throws CException
     */
    public function relations()
    {
        $relations = [
            'menu'  => [self::BELONGS_TO, 'Menu', 'menu_id'],
        ];

        return CMap::mergeArray($relations, parent::relations());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'item_id'    => $this->t('Item'),
            'menu_id'    => $this->t('Menu'),
            'label'      => $this->t('Label'),
            'title'      => $this->t('Title'),
            'url'        => $this->t('Url'),
            'sort_order' => $this->t('Sort order'),
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
        $criteria = new CDbCriteria();

        $criteria->compare('title', $this->title, true);
        $criteria->compare('label', $this->label, true);

        return new CActiveDataProvider(get_class($this), [
            'criteria'      => $criteria,
            'pagination'    => [
                'pageSize'  => $this->paginationOptions->getPageSize(),
                'pageVar'   => 'page',
            ],
            'sort'  => [
                'defaultOrder' => [
                    'item_id' => CSort::SORT_DESC,
                ],
            ],
        ]);
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return MenuItem the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var MenuItem $model */
        $model = parent::model($className);

        return $model;
    }

    /**
     * @return string
     */
    public function getTranslationCategory(): string
    {
        return 'menus';
    }

    /**
     * @return array
     */
    public function attributeHelpTexts()
    {
        $texts = [];
        return CMap::mergeArray($texts, parent::attributeHelpTexts());
    }

    /**
     * @return array
     */
    public function attributePlaceholders()
    {
        $placeholders = [
            'label' => $this->t('My menu item label'),
            'title' => $this->t('My menu item title'),
            'url'   => $this->t('My menu item url'),
        ];

        return CMap::mergeArray($placeholders, parent::attributePlaceholders());
    }
}
