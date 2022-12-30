<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * Zone
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

/**
 * This is the model class for table "zone".
 *
 * The followings are the available columns in table 'zone':
 * @property integer $zone_id
 * @property integer|string $country_id
 * @property string $name
 * @property string $code
 * @property string $status
 * @property string|CDbExpression $date_added
 * @property string|CDbExpression $last_updated
 *
 * The followings are the available model relations:
 * @property ListCompany[] $listCompanies
 * @property Tax[] $taxes
 * @property Country $country
 */
class Zone extends ActiveRecord
{
    /**
     * @return string
     */
    public function tableName()
    {
        return '{{zone}}';
    }

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            ['country_id, name, code, status', 'required'],
            ['country_id', 'exist', 'className' => Country::class],
            ['name', 'length', 'min' => 3, 'max' => 150],
            ['code', 'length', 'min' => 1, 'max' => 50],

            ['status', 'in', 'range' => array_keys($this->getStatusesList())],

            // mark them as safe for search
            ['country_id, name, code, status', 'safe', 'on' => 'search'],
        ];

        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array
     */
    public function relations()
    {
        $relations = [
            'listCompanies'     => [self::HAS_MANY, ListCompany::class, 'zone_id'],
            'taxes'             => [self::HAS_MANY, Tax::class, 'zone_id'],
            'country'           => [self::BELONGS_TO, Country::class, 'country_id'],
        ];

        return CMap::mergeArray($relations, parent::relations());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'zone_id'       => t('zones', 'Zone'),
            'country_id'    => t('zones', 'Country'),
            'name'          => t('zones', 'Name'),
            'code'          => t('zones', 'Code'),
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

        if (!empty($this->country_id)) {
            $countryId = (string)$this->country_id;
            if (is_numeric($countryId)) {
                $criteria->compare('t.country_id', $countryId);
            } else {
                $criteria->with['country'] = [
                    'together' => true,
                    'joinType' => 'INNER JOIN',
                ];
                $criteria->compare('country.name', $countryId, true);
            }
        }

        $criteria->compare('t.name', $this->name, true);
        $criteria->compare('t.code', $this->code, true);
        $criteria->compare('t.status', $this->status);

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
     * @return Zone the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var Zone $model */
        $model = parent::model($className);

        return $model;
    }

    /**
     * @param int $countryId
     *
     * @return array
     */
    public static function getAsDropdownOptionsByCountryId(int $countryId): array
    {
        static $options = [];
        $countryId      = (int)$countryId > 0 ? $countryId : 0;
        if (isset($options[$countryId]) || array_key_exists($countryId, $options)) {
            return $options[$countryId];
        }
        if ($countryId == 0) {
            return $options[0] = [];
        }
        $options[$countryId] = [];
        $zones = self::model()->findAll([
            'select'    => 'zone_id, name',
            'condition' => 'country_id = :cid',
            'params'    => [':cid' => (int)$countryId],
            'order'     => 'name ASC',
        ]);
        foreach ($zones as $zone) {
            $options[$countryId][$zone->zone_id] = $zone->name;
        }
        return $options[$countryId];
    }
}
