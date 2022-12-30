<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * ListCompany
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

/**
 * This is the model class for table "list_company".
 *
 * The followings are the available columns in table 'list_company':
 * @property integer $list_id
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
 * @property string $address_format
 *
 * The followings are the available model relations:
 * @property CompanyType $type
 * @property Country $country
 * @property Zone $zone
 * @property Lists $list
 */
class ListCompany extends ActiveRecord
{
    /**
     * @var string
     */
    public $defaultAddressFormat = "[COMPANY_NAME]\n[COMPANY_ADDRESS_1] [COMPANY_ADDRESS_2]\n[COMPANY_CITY] [COMPANY_ZONE] [COMPANY_ZIP]\n[COMPANY_COUNTRY]\n[COMPANY_WEBSITE]";

    /**
     * @return string
     */
    public function tableName()
    {
        return '{{list_company}}';
    }

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            ['name, country_id, address_1, city, zip_code, address_format', 'required'],

            ['name', 'length', 'max' => 100],
            ['website', 'length', 'max' => 255],
            ['website', 'url'],
            ['country_id, zone_id', 'numerical', 'integerOnly' => true, 'min' => 1],
            ['address_1, address_2, city, address_format', 'length', 'max' => 255],
            ['zone_name', 'length', 'max' => 150],
            ['zip_code', 'length', 'max' => 10],
            ['phone', 'length', 'max' => 32],
            ['type_id', 'exist', 'attributeName' => null, 'className' => CompanyType::class],
            ['country_id', 'exist', 'attributeName' => null, 'className' => Country::class],
            ['zone_id', 'exist', 'attributeName' => null, 'className' => Zone::class],
            ['zone_name', 'match', 'pattern' => '/[a-zA-Z\s\-\.]+/'],
            ['phone', 'match', 'pattern' => '/[0-9\s\-]+/'],
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
            'list'      => [self::BELONGS_TO, Lists::class, 'list_id'],
        ];

        return CMap::mergeArray($relations, parent::relations());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'list_id'           => t('lists', 'List'),
            'type_id'           => t('lists', 'Type/Industry'),
            'country_id'        => t('lists', 'Country'),
            'zone_id'           => t('lists', 'Zone'),
            'name'              => t('lists', 'Name'),
            'website'           => t('lists', 'Website'),
            'address_1'         => t('lists', 'Address 1'),
            'address_2'         => t('lists', 'Address 2'),
            'zone_name'         => t('lists', 'Zone name'),
            'city'              => t('lists', 'City'),
            'zip_code'          => t('lists', 'Zip code'),
            'phone'             => t('lists', 'Phone'),
            'address_format'    => t('lists', 'Address format'),
        ];

        return CMap::mergeArray($labels, parent::attributeLabels());
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return ListCompany the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var ListCompany $model */
        $model = parent::model($className);

        return $model;
    }

    /**
     * @param CustomerCompany $company
     *
     * @return ListCompany
     */
    public function mergeWithCustomerCompany(CustomerCompany $company): self
    {
        $attributes = [
            'name', 'website', 'type_id', 'country_id', 'zone_id', 'address_1', 'address_2',
            'zone_name', 'city', 'zip_code', 'phone',
        ];

        foreach ($attributes as $attribute) {
            $this->$attribute = $company->$attribute;
        }

        return $this;
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
        $htmlOptions  = CMap::mergeArray($_htmlOptions, $htmlOptions);

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

    /**
     * @return array
     */
    public function getAvailableTags(): array
    {
        return [
            ['tag' => '[COMPANY_NAME]', 'required' => true],
            ['tag' => '[COMPANY_WEBSITE]', 'required' => false],
            ['tag' => '[COMPANY_ADDRESS_1]', 'required' => true],
            ['tag' => '[COMPANY_ADDRESS_2]', 'required' => false],
            ['tag' => '[COMPANY_CITY]', 'required' => true],
            ['tag' => '[COMPANY_ZONE]', 'required' => false],
            ['tag' => '[COMPANY_ZONE_CODE]', 'required' => false],
            ['tag' => '[COMPANY_ZIP]', 'required' => false],
            ['tag' => '[COMPANY_COUNTRY]', 'required' => false],
            ['tag' => '[COMPANY_COUNTRY_CODE]', 'required' => false],
        ];
    }

    /**
     * @return string
     */
    public function getFormattedAddress(): string
    {
        $searchReplace = [
            '[COMPANY_NAME]'            => $this->name,
            '[COMPANY_WEBSITE]'         => $this->website,
            '[COMPANY_ADDRESS_1]'       => $this->address_1,
            '[COMPANY_ADDRESS_2]'       => $this->address_2,
            '[COMPANY_CITY]'            => $this->city,
            '[COMPANY_ZONE]'            => !empty($this->zone) ? $this->zone->name : $this->zone_name,
            '[COMPANY_ZONE_CODE]'       => !empty($this->zone) ? $this->zone->code : $this->zone_name,
            '[COMPANY_ZIP]'             => $this->zip_code,
            '[COMPANY_COUNTRY]'         => !empty($this->country) ? $this->country->name : null,
            '[COMPANY_COUNTRY_CODE]'    => !empty($this->country) ? $this->country->code : null,
        ];

        return (string)str_replace(array_keys($searchReplace), array_values($searchReplace), $this->address_format);
    }

    /**
     * @return void
     */
    protected function afterConstruct()
    {
        if (!$this->address_format) {
            $this->address_format = $this->defaultAddressFormat;
        }
        parent::afterConstruct();
    }

    /**
     * @return void
     */
    protected function afterFind()
    {
        if (!$this->address_format) {
            $this->address_format = $this->defaultAddressFormat;
        }
        parent::afterFind();
    }

    /**
     * @return bool
     */
    protected function beforeValidate()
    {
        $tags = $this->getAvailableTags();
        $content = html_decode($this->address_format);
        $hasErrors = false;
        foreach ($tags as $tag) {
            if (!isset($tag['tag']) || !isset($tag['required']) || !$tag['required']) {
                continue;
            }

            if (!isset($tag['pattern']) && strpos($content, $tag['tag']) === false) {
                $this->addError('address_format', t('lists', 'The following tag is required but was not found in your content: {tag}', [
                    '{tag}' => $tag['tag'],
                ]));
                $hasErrors = true;
            } elseif (isset($tag['pattern']) && !preg_match($tag['pattern'], $content)) {
                $this->addError('address_format', t('lists', 'The following tag is required but was not found in your content: {tag}', [
                    '{tag}' => $tag['tag'],
                ]));
                $hasErrors = true;
            }
        }

        if ($hasErrors) {
            return false;
        }

        return parent::beforeValidate();
    }
}
