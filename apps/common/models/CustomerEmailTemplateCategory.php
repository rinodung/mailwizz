<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * CustomerEmailTemplateCategory
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.9.5
 */

/**
 * This is the model class for table "{{customer_email_template_category}}".
 *
 * The followings are the available columns in table '{{customer_email_template_category}}':
 * @property integer|string $category_id
 * @property integer|string $customer_id
 * @property string $name
 * @property string|CDbExpression $date_added
 * @property string|CDbExpression $last_updated
 *
 * The followings are the available model relations:
 * @property Customer $customer
 * @property CustomerEmailTemplate[] $templates
 * @property CustomerEmailTemplate $templatesCount
 */
class CustomerEmailTemplateCategory extends ActiveRecord
{
    /**
     * @return string
     */
    public function tableName()
    {
        return '{{customer_email_template_category}}';
    }

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            ['name', 'required'],
            ['name', 'length', 'max' => 255],

            ['category_id, customer_id, name', 'safe', 'on'=>'search'],
        ];

        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array
     */
    public function relations()
    {
        $relations = [
            'customer'          => [self::BELONGS_TO, Customer::class, 'customer_id'],
            'templates'         => [self::HAS_MANY, CustomerEmailTemplate::class, 'category_id'],
            'templatesCount'    => [self::STAT, CustomerEmailTemplate::class, 'category_id'],
        ];

        return CMap::mergeArray($relations, parent::relations());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'category_id' => t('email_templates', 'Category'),
            'customer_id' => t('email_templates', 'Customer'),
            'name'        => t('email_templates', 'Name'),

            'templatesCount' => t('email_templates', 'Templates'),
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

        if (!empty($this->customer_id)) {
            $customerId = (string)$this->customer_id;
            if (is_numeric($customerId)) {
                $criteria->compare('t.customer_id', $customerId);
            } else {
                $criteria->with['customer'] = [
                    'condition' => 'customer.email LIKE :name OR customer.first_name LIKE :name OR customer.last_name LIKE :name',
                    'params'    => [':name' => '%' . $customerId . '%'],
                ];
            }
        } elseif ($this->customer_id === null) {
            $criteria->addCondition('t.customer_id IS NULL');
        }

        if (!empty($this->category_id)) {
            $categoryId = (string)$this->category_id;
            if (is_numeric($categoryId)) {
                $criteria->compare('t.category_id', $categoryId);
            } else {
                $criteria->with['category'] = [
                    'condition' => 'category.name LIKE :name',
                    'params'    => [':name' => '%' . $categoryId . '%'],
                ];
            }
        }

        $criteria->compare('t.name', $this->name, true);

        // force order by name
        $criteria->order = 't.name ASC';

        return new CActiveDataProvider(get_class($this), [
            'criteria'      => $criteria,
            'pagination'    => [
                'pageSize'  => $this->paginationOptions->getPageSize(),
                'pageVar'   => 'page',
            ],
            'sort'  => [
                'defaultOrder' => [
                    't.category_id'   => CSort::SORT_DESC,
                ],
            ],
        ]);
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return CustomerEmailTemplateCategory the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var CustomerEmailTemplateCategory $model */
        $model = parent::model($className);

        return $model;
    }

    /**
     * @param int|null $customerId
     *
     * @return array
     */
    public static function getAllAsOptions(?int $customerId = null): array
    {
        $criteria = new CDbCriteria();
        $criteria->select = 'category_id, name';
        if ($customerId) {
            $criteria->compare('customer_id', (int)$customerId);
        } else {
            $criteria->addCondition('customer_id IS NULL');
        }
        $criteria->order = 'name ASC';

        return CustomerEmailTemplateCategoryCollection::findAll($criteria)
            ->mapWithKeys(function (CustomerEmailTemplateCategory $model) {
                return [$model->category_id => $model->name];
            })->all();
    }
}
