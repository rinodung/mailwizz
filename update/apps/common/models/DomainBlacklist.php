<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * DomainBlacklist
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.0.29
 */

/**
 * This is the model class for table "domain_blacklist".
 *
 * The followings are the available columns in table 'domain_blacklist':
 * @property integer $domain_id
 * @property string $domain
 * @property string|CDbExpression $date_added
 * @property string|CDbExpression $last_updated
 */
class DomainBlacklist extends ActiveRecord
{
    /**
     * @var CUploadedFile $file - the uploaded file for import
     */
    public $file;

    /**
     * store domain => bool (whether is blacklisted or not)
     *
     * @var array
     */
    protected static $domainsStore = [];

    /**
     * @return string
     */
    public function tableName()
    {
        return '{{domain_blacklist}}';
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
            ['domain', 'required', 'on' => 'insert, update'],
            ['domain', 'length', 'max' => 100],
            ['domain', '_validateDomain'],
            ['domain', 'unique'],

            ['domain', 'safe', 'on' => 'search'],

            ['domain', 'unsafe', 'on' => 'import'],
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
        $relations = [];
        return CMap::mergeArray($relations, parent::relations());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'domain_id' => t('domain_blacklist', 'Domain'),
            'domain'    => t('domain_blacklist', 'Domain'),
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
        $criteria->compare('domain', $this->domain, true);

        return new CActiveDataProvider(get_class($this), [
            'criteria'      => $criteria,
            'pagination'    => [
                'pageSize'  => $this->paginationOptions->getPageSize(),
                'pageVar'   => 'page',
            ],
            'sort'=>[
                'defaultOrder'  => [
                    'domain_id'  => CSort::SORT_DESC,
                ],
            ],
        ]);
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return DomainBlacklist the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var DomainBlacklist $model */
        $model = parent::model($className);

        return $model;
    }

    /**
     * @param array $insert
     *
     * @return int
     * @throws CException
     */
    public static function insertMultipleUnique(array $insert): int
    {
        $inserted = 0;

        if (empty($insert)) {
            return $inserted;
        }

        $mutexKey = __FILE__ . ':' . __METHOD__;
        if (!mutex()->acquire($mutexKey, 60)) {
            throw new Exception('Unable to acquire the mutex to process the file!');
        }

        // make sure we have no duplicates inside the array itself
        $insert = collect($insert)->map(function ($item) {
            $item['domain'] = strtolower((string)$item['domain']);
            return $item;
        })->unique('domain', true)
            ->reject(function ($item) {
                return !FilterVarHelper::domain($item['domain']);
            })->all();

        // query the database to get all existing data
        $rows = db()->createCommand()
            ->select('LOWER(domain) as domain')
            ->from('{{domain_blacklist}}')
            ->andWhere(['in', 'domain', array_column($insert, 'domain')])
            ->limit(count($insert))
            ->queryColumn();

        // remove the saved hashes from the insert array
        $insert = collect($insert)->reject(function ($item) use (&$rows) {
            return in_array($item['domain'], $rows, true);
        })->all();

        // what we have left is just records not in database.
        if (empty($insert)) {
            mutex()->release($mutexKey);
            return $inserted;
        }

        try {
            $builder = db()->getSchema()->getCommandBuilder();
            $inserted += (int)$builder->createMultipleInsertCommand('{{domain_blacklist}}', $insert)->execute();
        } catch (Exception $e) {
            mutex()->release($mutexKey);
            throw $e;
        }

        mutex()->release($mutexKey);
        return $inserted;
    }

    /**
     * @param string $domain
     * @return DomainBlacklist|null
     */
    public static function findByDomain(string $domain): ?self
    {
        $criteria = new CDbCriteria();
        $criteria->compare('domain', $domain);

        return self::model()->find($criteria);
    }

    /**
     * @param string $email
     * @return bool
     */
    public static function isEmailBlacklisted(string $email): bool
    {
        $domain = EmailHelper::getDomainFromEmail($email);

        if (self::getFromStore($domain) !== null) {
            return self::getFromStore($domain);
        }

        $model = self::findByDomain($domain);
        $isBlacklisted = !empty($model);

        self::addToStore($domain, $isBlacklisted);

        return $isBlacklisted;
    }

    /**
     * @param string $domain
     * @param bool $blacklisted
     */
    public static function addToStore(string $domain, bool $blacklisted = true): void
    {
        if (isset(self::$domainsStore[$domain]) && self::$domainsStore[$domain] === $blacklisted) {
            return;
        }
        self::$domainsStore[$domain] = $blacklisted;
    }

    /**
     * @param string $domain
     * @return bool|null
     */
    public static function getFromStore(string $domain): ?bool
    {
        return self::$domainsStore[$domain] ?? null;
    }

    /**
     * @param string $domain
     * @return bool
     */
    public static function deleteFromStore(string $domain): bool
    {
        if (isset(self::$domainsStore[$domain])) {
            unset(self::$domainsStore[$domain]);
            return true;
        }
        return false;
    }

    /**
     * @param string $attribute
     * @param array $params
     */
    public function _validateDomain(string $attribute, array $params = []): void
    {
        if ($this->hasErrors()) {
            return;
        }

        if (empty($this->$attribute)) {
            return;
        }

        if (!FilterVarHelper::domain($this->$attribute)) {
            $this->addError($attribute, t('domain_blacklist', 'Your specified domain name does not seem to be valid!'));
        }
    }
}
