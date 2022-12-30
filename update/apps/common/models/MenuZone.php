<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * MenuZone
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.0.30
 */

/**
 * This is the model class for table "menu_zone".
 *
 * The followings are the available columns in table 'menu_zone':
 * @property integer $zone_id
 * @property string $slug
 * @property string $name
 * @property string|CDbExpression $date_added
 * @property string|CDbExpression $last_updated
 *
 * The followings are the available model relations:
 * @property Menu[] $menus
 */
class MenuZone extends ActiveRecord
{
    /**
     * Flags for various zones
     */
    const ZONE_FRONTEND_HEADER = 'frontend-header';
    const ZONE_FRONTEND_FOOTER = 'frontend-footer';

    /**
     * @return string
     */
    public function tableName()
    {
        return '{{menu_zone}}';
    }

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            ['name', 'required'],
            ['name', 'length', 'max' => 100],

            // The following rule is used by search().
            ['name', 'safe', 'on' => 'search'],
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
            'menus' => [self::HAS_MANY, 'Menu', 'zone_id'],
        ];

        return CMap::mergeArray($relations, parent::relations());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'zone_id' => $this->t('Zone'),
            'slug'    => $this->t('Slug'),
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
        $criteria->compare('slug', $this->slug, true);

        return new CActiveDataProvider(get_class($this), [
            'criteria'      => $criteria,
            'pagination'    => [
                'pageSize'  => $this->paginationOptions->getPageSize(),
                'pageVar'   => 'page',
            ],
            'sort'  => [
                'defaultOrder' => [
                    'zone_id' => CSort::SORT_DESC,
                ],
            ],
        ]);
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return MenuZone the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var MenuZone $model */
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
            'name' => $this->t('My zone name'),
        ];

        return CMap::mergeArray($placeholders, parent::attributePlaceholders());
    }

    /**
     * @return array
     */
    public static function getAllAsOptions(): array
    {
        static $options;
        if ($options !== null) {
            return $options;
        }
        $options = [];
        $models  = self::model()->findAll();
        foreach ($models as $model) {
            $options[$model->zone_id] = $model->name;
        }
        return $options;
    }

    /**
     * @return string
     */
    public function generateSlug(): string
    {
        $string = !empty($this->slug) ? $this->slug : $this->name;
        $slug = URLify::filter((string)$string);
        $zone_id = (int)$this->zone_id;

        $criteria = new CDbCriteria();
        $criteria->addCondition('zone_id != :id AND slug = :slug');
        $criteria->params = [':id' => $zone_id, ':slug' => $slug];
        $exists = self::model()->find($criteria);

        $i = 0;
        while (!empty($exists)) {
            ++$i;
            $slug = preg_replace('/^(.*)(\d+)$/six', '$1', $slug);
            $slug = URLify::filter($slug . ' ' . $i);
            $criteria = new CDbCriteria();
            $criteria->addCondition('zone_id != :id AND slug = :slug');
            $criteria->params = [':id' => $zone_id, ':slug' => $slug];
            $exists = self::model()->find($criteria);
        }

        return $slug;
    }

    /**
     * @return bool
     */
    protected function beforeValidate()
    {
        $this->slug = $this->generateSlug();
        return parent::beforeValidate();
    }
}
