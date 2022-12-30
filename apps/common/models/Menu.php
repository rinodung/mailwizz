<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * Menu
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.0.30
 */

/**
 * This is the model class for table "menu".
 *
 * The followings are the available columns in table 'menu':
 * @property integer $menu_id
 * @property integer $zone_id
 * @property string $name
 * @property string $status
 * @property string|CDbExpression $date_added
 * @property string|CDbExpression $last_updated
 *
 * The followings are the available model relations:
 * @property MenuZone $zone
 * @property MenuItem[] $items
 */
class Menu extends ActiveRecord
{
    /**
     * @return string
     */
    public function tableName()
    {
        return '{{menu}}';
    }

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            ['name, status, zone_id', 'required'],
            ['name', 'length', 'max' => 100],
            ['zone_id', 'numerical', 'integerOnly' => true],
            ['zone_id', 'exist', 'className' => MenuZone::class],
            ['status', 'in', 'range' => array_keys($this->getStatusesList())],

            // The following rule is used by search().
            ['name, status, zone_id', 'safe', 'on' => 'search'],
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
            'zone'  => [self::BELONGS_TO, 'MenuZone', 'zone_id'],
            'items' => [self::HAS_MANY, 'MenuItem', 'menu_id', 'order' => 'sort_order ASC'],
        ];

        return CMap::mergeArray($relations, parent::relations());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'menu_id' => $this->t('Menu'),
            'zone_id' => $this->t('Zone'),
            'name'    => $this->t('Name'),
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

        $criteria->compare('name', $this->name, true);
        $criteria->compare('status', $this->status, true);
        $criteria->compare('zone_id', $this->zone_id, true);

        return new CActiveDataProvider(get_class($this), [
            'criteria'      => $criteria,
            'pagination'    => [
                'pageSize'  => $this->paginationOptions->getPageSize(),
                'pageVar'   => 'page',
            ],
            'sort'  => [
                'defaultOrder' => [
                    'menu_id' => CSort::SORT_DESC,
                ],
            ],
        ]);
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return Menu the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var Menu $model */
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
            'name' => $this->t('My menu name'),
        ];

        return CMap::mergeArray($placeholders, parent::attributePlaceholders());
    }

    /**
     * @return array
     */
    public function getItemsForMenu(): array
    {
        $items = $this->items;

        $menuItems = [];
        foreach ($items as $item) {
            $menuItems[] = [
                'url'         => $item->url,
                'label'       => $item->label,
                'linkOptions' => ['title' => $item->title],
            ];
        }

        return $menuItems;
    }

    /**
     * @param string $slug
     * @return Menu[]
     */
    public static function findByZoneSlug(string $slug): array
    {
        $zone = MenuZone::model()->findByAttributes(['slug' => $slug]);
        if (!$zone) {
            return [];
        }

        return Menu::model()->findAllByAttributes([
            'zone_id' => $zone->zone_id,
            'status'  => self::STATUS_ACTIVE,
        ]);
    }
}
