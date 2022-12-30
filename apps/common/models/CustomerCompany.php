<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * CustomerCompany
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

/**
 * This is the model class for table "customer_company".
 *
 * The followings are the available columns in table 'customer_company':
 * @property integer $company_id
 * @property integer $customer_id
 * @property integer $type_id
 * @property integer $country_id
 * @property integer $zone_id
 * @property string $name
 * @property string $website
 * @property string $address_1
 * @property string $address_2
 * @property string $zone_name
 * @property string $city
 * @property string $zip_code
 * @property string $phone
 * @property string $fax
 * @property string $vat_number
 * @property string|CDbExpression $date_added
 * @property string|CDbExpression $last_updated
 *
 * The followings are the available model relations:
 * @property CompanyType $type
 * @property Country $country
 * @property Zone $zone
 * @property Customer $customer
 */
class CustomerCompany extends ActiveRecord
{

    /**
     * @return string
     */
    public function tableName()
    {
        return '{{customer_company}}';
    }

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            ['name, country_id, address_1, city, zip_code', 'required', 'on' => 'insert, update, register'],

            ['name, vat_number', 'length', 'max' => 100],
            ['website', 'length', 'max' => 255],
            ['website', 'url'],
            ['country_id, zone_id', 'numerical', 'integerOnly' => true, 'min' => 1],
            ['address_1, address_2, city', 'length', 'max' => 255],
            ['zone_name', 'length', 'max' => 150],
            ['zip_code', 'length', 'max' => 10],
            ['phone, fax', 'length', 'max' => 32],
            ['type_id', 'exist', 'attributeName' => null, 'className' => CompanyType::class],
            ['country_id', 'exist', 'attributeName' => null, 'className' => Country::class],
            ['zone_id', 'exist', 'attributeName' => null, 'className' => Zone::class],
            ['phone, fax', 'match', 'pattern' => '/[0-9\s\-]+/'],
        ];

        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array
     */
    public function relations()
    {
        $relations = [
            'type'      => [self::BELONGS_TO, CompanyType::class, 'type_id'],
            'country'   => [self::BELONGS_TO, Country::class, 'country_id'],
            'zone'      => [self::BELONGS_TO, Zone::class, 'zone_id'],
            'customer'  => [self::BELONGS_TO, Customer::class, 'customer_id'],
        ];

        return CMap::mergeArray($relations, parent::relations());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'company_id'    => t('customers', 'Company'),
            'customer_id'   => t('customers', 'Customer'),
            'type_id'       => t('customers', 'Type/Industry'),
            'country_id'    => t('customers', 'Country'),
            'zone_id'       => t('customers', 'Zone'),
            'name'          => t('customers', 'Name'),
            'website'       => t('customers', 'Website'),
            'address_1'     => t('customers', 'Address'),
            'address_2'     => t('customers', 'Address 2'),
            'zone_name'     => t('customers', 'Zone name'),
            'city'          => t('customers', 'City'),
            'zip_code'      => t('customers', 'Zip code'),
            'phone'         => t('customers', 'Phone'),
            'fax'           => t('customers', 'Fax'),
            'vat_number'    => t('customers', 'VAT Number'),
        ];

        return CMap::mergeArray($labels, parent::attributeLabels());
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return CustomerCompany the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var CustomerCompany $model */
        $model = parent::model($className);

        return $model;
    }

    /**
     * @return array
     */
    public function attributeHelpTexts()
    {
        if ($this->getScenario() === 'register') {
            return [];
        }

        $texts = [
            'name'      => t('customers', 'Your company public display name'),
            'website'   => t('customers', 'Please enter your website address url, starting with http:// or https://'),
            'zone_id'   => t('customers', 'Please select your company country zone. If none applicable, then please fill the zone name field instead.'),
            'zone_name' => t('customers', 'Please fill this field unless you have no option to select from the zone drop down.'),
            'city'      => t('customers', 'No check will be done against your city name, please be accurate!'),
            'zip_code'  => t('customers', 'No check will be done against your zip code, please be accurate!'),

        ];

        return CMap::mergeArray($texts, parent::attributeHelpTexts());
    }

    /**
     * @param array $htmlOptions
     *
     * @return string
     * @throws CException
     */
    public function getCountriesDropDown(array $htmlOptions = []): string
    {
        static $_countries = [];

        if (empty($_countries)) {
            $_countries[''] = t('app', 'Please select');

            $criteria = new CDbCriteria();
            $criteria->select = 'country_id, name';
            $criteria->order  = 'name ASC';
            $models = Country::model()->findAll($criteria);

            foreach ($models as $model) {
                $_countries[$model->country_id] = $model->name;
            }
        }

        $_htmlOptions = $this->fieldDecorator->getHtmlOptions('country_id', ['data-placement' => 'right']);
        $_htmlOptions['data-zones-by-country-url'] = createUrl('account/zones_by_country');
        $htmlOptions = CMap::mergeArray($_htmlOptions, $htmlOptions);

        return CHtml::activeDropDownList($this, 'country_id', $_countries, $htmlOptions);
    }

    /**
     * @param array $htmlOptions
     *
     * @return string
     * @throws CException
     */
    public function getZonesDropDown(array $htmlOptions = []): string
    {
        $zones = ['' => t('app', 'Please select')];

        $criteria = new CDbCriteria();
        $criteria->select = 'zone_id, name';
        $criteria->compare('country_id', (int)$this->country_id);
        $_zones = Zone::model()->findAll($criteria);

        foreach ($_zones as $zone) {
            $zones[$zone->zone_id] = $zone->name;
        }

        $_htmlOptions = $this->fieldDecorator->getHtmlOptions('zone_id', ['data-placement' => 'left']);
        $htmlOptions  = CMap::mergeArray($_htmlOptions, $htmlOptions);

        return CHtml::activeDropDownList($this, 'zone_id', $zones, $htmlOptions);
    }
}
