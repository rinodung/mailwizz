<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * CustomerIpBlacklist
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.1.6
 */

/**
 * This is the model class for table "customer_ip_blacklist".
 *
 * The followings are the available columns in table 'customer_ip_blacklist':
 * @property integer $id
 * @property integer $customer_id
 * @property string $ip_address
 * @property string|CDbExpression $date_added
 * @property string|CDbExpression $last_updated
 *
 * The followings are the available model relations:
 * @property Customer $customer
 */
class CustomerIpBlacklist extends ActiveRecord
{
    /**
     * @var CUploadedFile $file - uploaded file containing the suppressed ips
     */
    public $file;

    /**
     * @return string
     */
    public function tableName()
    {
        return '{{customer_ip_blacklist}}';
    }

    /**
     * @return array
     */
    public function rules()
    {
        /** @var OptionImporter $optionImporter */
        $optionImporter = container()->get(OptionImporter::class);

        $mimes = null;
        if ($optionImporter->getCanCheckMimeType()) {

            /** @var FileExtensionMimes $extensionMimes */
            $extensionMimes = app()->getComponent('extensionMimes');

            /** @var array $mimes */
            $mimes = $extensionMimes->get('csv')->toArray();
        }

        $rules = [
            ['ip_address', 'required', 'on' => 'insert, update'],
            ['ip_address', 'length', 'max' => 45],
            ['ip_address', '_validateIp'],
            ['ip_address', '_validateIpUnique'],

            ['ip_address', 'safe', 'on' => 'search'],

            ['ip_address', 'unsafe', 'on' => 'import'],
            ['file', 'required', 'on' => 'import'],
            ['file', 'file', 'types' => ['csv'], 'mimeTypes' => $mimes, 'maxSize' => 512000000, 'allowEmpty' => true],
        ];

        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array
     */
    public function relations()
    {
        $relations = [
            'customer' => [self::BELONGS_TO, Customer::class, 'customer_id'],
        ];
        return CMap::mergeArray($relations, parent::relations());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'id'          => t('ip_blacklist', 'IP address'),
            'customer_id' => t('ip_blacklist', 'Customer'),
            'ip_address'  => t('ip_blacklist', 'IP address'),
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
        $criteria->compare('customer_id', (int)$this->customer_id);
        $criteria->compare('ip_address', $this->ip_address, true);

        return new CActiveDataProvider(get_class($this), [
            'criteria'      => $criteria,
            'pagination'    => [
                'pageSize'  => $this->paginationOptions->getPageSize(),
                'pageVar'   => 'page',
            ],
            'sort'=>[
                'defaultOrder'  => [
                    'id'  => CSort::SORT_DESC,
                ],
            ],
        ]);
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return CustomerIpBlacklist the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var CustomerIpBlacklist $model */
        $model = parent::model($className);

        return $model;
    }

    /**
     * @param string $ipAddress
     * @param int $customerId
     * @return CustomerIpBlacklist|null
     */
    public static function findByIpWithCustomerId(string $ipAddress, int $customerId): ?self
    {
        $criteria = new CDbCriteria();
        $criteria->compare('customer_id', $customerId);

        $criteria->addCondition('ip_address = :ip');
        $criteria->params[':ip'] = $ipAddress;

        return self::model()->find($criteria);
    }


    /**
     * @param string $attribute
     * @param array $params
     *
     * @return void
     */
    public function _validateIpUnique(string $attribute, array $params = []): void
    {
        if ($this->hasErrors()) {
            return;
        }

        if (empty($this->$attribute)) {
            return;
        }

        $criteria = new CDbCriteria();
        $criteria->compare('customer_id', (int)$this->customer_id);
        $criteria->addCondition('id != :id');
        $criteria->params[':id'] = (int)$this->id;

        $criteria->addCondition('ip_address = :ip');
        $criteria->params[':ip'] = (string)$this->$attribute;

        $duplicate = self::model()->find($criteria);

        if (!empty($duplicate)) {
            $this->addError('ip_address', t('ip_blacklist', 'The IP address({ip_address}) is already in your blacklist!', [
                '{ip_address}' => (string)$this->$attribute,
            ]));
            return;
        }
    }

    /**
     * @param string $attribute
     * @param array $params
     */
    public function _validateIp(string $attribute, array $params = []): void
    {
        if ($this->hasErrors()) {
            return;
        }

        if (empty($this->$attribute)) {
            return;
        }

        if (FilterVarHelper::ip($this->$attribute)) {
            return;
        }

        $this->addError($attribute, t('ip_blacklist', 'Please enter a valid IP address!'));
    }
}
