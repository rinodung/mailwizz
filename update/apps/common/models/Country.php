<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * Country
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

/**
 * This is the model class for table "country".
 *
 * The followings are the available columns in table 'country':
 * @property integer $country_id
 * @property string $name
 * @property string $code
 * @property string $status
 * @property string|CDbExpression $date_added
 * @property string|CDbExpression $last_updated
 *
 * The followings are the available model relations:
 * @property ListCompany $listCompany
 * @property CustomerCompany $customerCompany
 * @property Tax[] $taxes
 * @property Zone[] $zones
 */
class Country extends ActiveRecord
{
    /**
     * @return string
     */
    public function tableName()
    {
        return '{{country}}';
    }

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            ['name, code, status', 'required'],
            ['name', 'length', 'min' => 3, 'max' => 150],
            ['code', 'length', 'min' => 2, 'max' => 3],
            ['name, code', 'unique'],
            ['status', 'in', 'range' => array_keys($this->getStatusesList())],

            // mark them as safe for search
            ['name, code, status', 'safe', 'on' => 'search'],
        ];

        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array
     */
    public function relations()
    {
        $relations = [
            'listCompany'       => [self::HAS_ONE, ListCompany::class, 'country_id'],
            'customerCompany'   => [self::HAS_ONE, CustomerCompany::class, 'country_id'],
            'taxes'             => [self::HAS_MANY, Tax::class, 'country_id'],
            'zones'             => [self::HAS_MANY, Zone::class, 'country_id'],
        ];

        return CMap::mergeArray($relations, parent::relations());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'country_id'    => t('countries', 'Country'),
            'name'          => t('countries', 'Name'),
            'code'          => t('countries', 'Code'),
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

        $criteria->compare('name', $this->name, true);
        $criteria->compare('code', $this->code, true);
        $criteria->compare('status', $this->status);

        return new CActiveDataProvider(get_class($this), [
            'criteria'      => $criteria,
            'pagination'    => [
                'pageSize'  => $this->paginationOptions->getPageSize(),
                'pageVar'   => 'page',
            ],
            'sort'=>[
                'defaultOrder' => [
                    'name'     => CSort::SORT_ASC,
                ],
            ],
        ]);
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return Country the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var Country $model */
        $model = parent::model($className);

        return $model;
    }

    /**
     * @return array
     */
    public static function getAsDropdownOptions(): array
    {
        static $options;
        if ($options !== null) {
            return $options;
        }

        $criteria = ['select' => 'country_id, name', 'order' => 'name ASC'];
        return $options = CountryCollection::findAll($criteria)->mapWithKeys(function (Country $country) {
            return [$country->country_id => $country->name];
        })->all();
    }
}
