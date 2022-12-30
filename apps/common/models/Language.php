<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * Language
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.1
 */

/**
 * This is the model class for table "language".
 *
 * The followings are the available columns in table 'language':
 * @property integer $language_id
 * @property string $name
 * @property string $language_code
 * @property string $region_code
 * @property string $is_default
 * @property string|CDbExpression $date_added
 * @property string|CDbExpression $last_updated
 *
 * The followings are the available model relations:
 * @property Customer[] $customers
 * @property User[] $users
 */
class Language extends ActiveRecord
{
    /**
     * @return string
     */
    public function tableName()
    {
        return '{{language}}';
    }

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            ['name, language_code, is_default', 'required'],
            ['name', 'length', 'max' => 255],
            ['language_code, region_code', 'length', 'is' => 2],
            ['language_code, region_code', 'match', 'pattern' => '/^[a-z]+$/'],
            ['language_code', '_validateCodeAndRegion'],
            ['is_default', 'in', 'range' => array_keys($this->getIsDefaultOptionsArray())],
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
            'customers' => [self::HAS_MANY, Customer::class, 'language_id'],
            'users'     => [self::HAS_MANY, User::class, 'language_id'],
        ];

        return CMap::mergeArray($relations, parent::relations());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'language_id'    => t('languages', 'Language'),
            'name'           => t('languages', 'Name'),
            'language_code'  => t('languages', 'Language code'),
            'region_code'    => t('languages', 'Region code'),
            'is_default'     => t('languages', 'Is default language?'),
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
            'sort'  => [
                'defaultOrder'  => [
                    'name'   => CSort::SORT_ASC,
                ],
            ],
        ]);
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return Language the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var Language $model */
        $model = parent::model($className);

        return $model;
    }

    /**
     * @return array
     */
    public function attributeHelpTexts()
    {
        $texts = [
            'name'          => t('languages', 'The visible language name to distinct between same language but distinct regions (i.e: between English US and English GB)'),
            'language_code' => t('languages', '2 letter language code, i.e: en'),
            'region_code'   => t('languages', '2 letter region code, i.e: us. Please do not fill this field unless necessary. For most of the cases, the language code is enough'),
            'is_default'    => t('languages', 'Whether this language is the default language for users/customers that have not set a language'),
        ];
        return CMap::mergeArray($texts, parent::attributeHelpTexts());
    }

    /**
     * @return array
     */
    public function attributePlaceholders()
    {
        $placeholders = [
            'name'          => t('languages', 'i.e: English - United States'),
            'language_code' => t('languages', 'i.e: en'),
            'region_code'   => t('languages', 'i.e: us'),
        ];
        return CMap::mergeArray($placeholders, parent::attributePlaceholders());
    }

    /**
     * @return array
     */
    public function getIsDefaultOptionsArray(): array
    {
        return [
            self::TEXT_NO  => t('app', ucfirst(self::TEXT_NO)),
            self::TEXT_YES => t('app', ucfirst(self::TEXT_YES)),
        ];
    }

    /**
     * @return Language|null
     */
    public static function getDefaultLanguage(): ?self
    {
        return self::model()->findByAttributes(['is_default' => self::TEXT_YES]);
    }

    /**
     * @return string
     */
    public function getLanguageAndLocaleCode(): string
    {
        if (empty($this->region_code)) {
            return (string)$this->language_code;
        }
        return $this->language_code . '_' . $this->region_code;
    }

    /**
     * @return array
     */
    public static function getLanguagesList(): array
    {
        $criteria = new CDbCriteria();
        $criteria->select = 't.language_id, t.name';
        $criteria->order = 't.name ASC';
        return self::model()->findAll($criteria);
    }

    /**
     * @return array
     */
    public static function getLanguagesArray(): array
    {
        static $_options;
        if ($_options !== null) {
            return $_options;
        }

        return $_options = collect(self::getLanguagesList())->mapWithKeys(function (Language $model) {
            return [$model->language_id => $model->name];
        })->all();
    }

    /**
     * @param string $attribute
     * @param array $params
     */
    public function _validateCodeAndRegion(string $attribute, array $params = []): void
    {
        $languageCode = $this->$attribute;

        $criteria = new CDbCriteria();
        $criteria->compare('language_code', (string)$languageCode);
        $criteria->compare('region_code', $this->region_code);

        if (!$this->getIsNewRecord()) {
            $criteria->addNotInCondition('language_id', [(int)$this->language_id]);
        }

        if ((int)Language::model()->count($criteria) > 0) {
            $this->addError($attribute, t('languages', 'Duplicate entry for the language and region code combination!'));
        }
    }

    /**
     * @return bool
     */
    protected function beforeValidate()
    {
        if ($this->is_default != self::TEXT_YES) {
            $defaultLanguage = self::getDefaultLanguage();
            if (empty($defaultLanguage)) {
                $this->is_default = self::TEXT_YES;
            }
        }

        return parent::beforeValidate();
    }

    /**
     * @return bool
     */
    protected function beforeSave()
    {
        if ($this->is_default == self::TEXT_YES) {
            self::model()->updateAll(['is_default' => self::TEXT_NO], 'language_id != :lid AND is_default = :default', [':lid' => (int)$this->language_id, ':default' => self::TEXT_YES]);
        }

        return parent::beforeSave();
    }

    /**
     * @return bool
     */
    protected function beforeDelete()
    {
        if (!parent::beforeDelete()) {
            return false;
        }

        return $this->is_default != Language::TEXT_YES;
    }

    /**
     * @return void
     */
    protected function afterDelete()
    {
        if (app()->hasComponent('messages')) {
            $languageDir = (string)Yii::getPathOfAlias('common.messages') . '/' . $this->getLanguageAndLocaleCode();
            if (file_exists($languageDir) && is_dir($languageDir)) {
                FileSystemHelper::deleteDirectoryContents($languageDir, true, 1);
            }

            if (app()->getComponent('messages') instanceof CDbMessageSource) {
                TranslationMessage::model()->deleteAll('language = :language', [
                    ':language' => $this->getLanguageAndLocaleCode(),
                ]);
            }
        }

        parent::afterDelete();
    }
}
