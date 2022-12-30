<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * CompanyType
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.4.7
 */

/**
 * This is the model class for table "{{company_type}}".
 *
 * The followings are the available columns in table '{{company_type}}':
 * @property integer $type_id
 * @property string $name
 * @property string|CDbExpression $date_added
 * @property string|CDbExpression $last_updated
 *
 * The followings are the available model relations:
 * @property CustomerCompany[] $customerCompanies
 * @property ListCompany[] $listCompanies
 */
class CompanyType extends ActiveRecord
{
    /**
     * @return string
     */
    public function tableName()
    {
        return '{{company_type}}';
    }

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            ['name', 'required'],
            ['name', 'length', 'max' => 255],
            ['name', 'unique'],
            // The following rule is used by search().
            ['name', 'safe', 'on'=>'search'],
        ];

        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array
     */
    public function relations()
    {
        $relations = [
            'customerCompanies'  => [self::HAS_MANY, CustomerCompany::class, 'type_id'],
            'listCompanies'      => [self::HAS_MANY, ListCompany::class, 'type_id'],
        ];

        return CMap::mergeArray($relations, parent::relations());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'type_id' => t('company_types', 'Type'),
            'name'    => t('company_types', 'Name'),
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
     * @return CompanyType the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var CompanyType $model */
        $model = parent::model($className);

        return $model;
    }

    /**
     * @return array
     */
    public static function getListForDropDown(): array
    {
        $criteria = new CDbCriteria();
        $criteria->select = 'type_id, name';
        $criteria->order  = 'name ASC';

        return CompanyTypeCollection::findAll($criteria)->mapWithKeys(function (CompanyType $type) {
            return [$type->type_id => $type->name];
        })->all();
    }
}
