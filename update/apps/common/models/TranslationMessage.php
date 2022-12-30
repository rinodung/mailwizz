<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * TranslationMessage
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.0
 */

/**
 * This is the model class for table "{{translation_message}}".
 *
 * The followings are the available columns in table '{{translation_message}}':
 * @property integer $id
 * @property string $language
 * @property string $translation
 *
 * The followings are the available model relations:
 * @property TranslationSourceMessage $sourceMessage
 */
class TranslationMessage extends ActiveRecord
{
    /**
     * @return string
     */
    public function tableName()
    {
        return '{{translation_message}}';
    }

    /**
     * @return array|mixed
     * @throws CException
     */
    public function rules()
    {
        $rules = [
            ['id, language, translation', 'required'],
            ['translation', 'length', 'max' => 5000],
            ['language', 'length', 'max' => 16],
            ['id', 'exist', 'className' => TranslationSourceMessage::class],
            ['language', '_validateLanguage'],
            ['id', '_validateId'],

            // The following rule is used by search().
            ['id, language, translation', 'safe', 'on'=>'search'],
        ];
        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array|mixed
     * @throws CException
     */
    public function relations()
    {
        $relations = [
            'sourceMessage' => [self::BELONGS_TO, TranslationSourceMessage::class, 'id'],
        ];
        return CMap::mergeArray($relations, parent::relations());
    }

    /**
     * @return array|mixed
     * @throws CException
     */
    public function attributeLabels()
    {
        $labels = [
            'language'    => $this->t('Language'),
            'translation' => $this->t('Translation'),
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

        $criteria->compare('t.id', $this->id);
        $criteria->compare('t.translation', $this->translation, true);
        $criteria->compare('t.language', $this->language, true);

        return new CActiveDataProvider(get_class($this), [
            'criteria'   => $criteria,
            'pagination' => [
                'pageSize' => $this->paginationOptions->getPageSize(),
                'pageVar'  => 'page',
            ],
            'sort'=>[
                'defaultOrder' => [
                    't.id'  => CSort::SORT_DESC,
                ],
            ],
        ]);
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return TranslationMessage the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var TranslationMessage $model */
        $model = parent::model($className);

        return $model;
    }

    /**
     * @return void
     */
    public function afterSave()
    {
        parent::afterSave();

        $key = CDbMessageSource::CACHE_KEY_PREFIX . '.messages.' . $this->sourceMessage->category . '.' . $this->language;
        cache()->delete($key);
    }

    /**
     * @return string
     */
    public function getTranslationCategory(): string
    {
        return 'translations';
    }

    /**
     * @param string $attribute
     * @param array $params
     */
    public function _validateLanguage(string $attribute, array $params = []): void
    {
        $languageRegion = explode('_', $this->$attribute);

        $attributes = [
            'language_code' => $languageRegion[0],
        ];

        if (isset($languageRegion[1])) {
            $attributes['region_code'] = $languageRegion[1];
        }

        $language = Language::model()->findByAttributes($attributes);

        if (empty($language)) {
            $this->addError($attribute, $this->t('Invalid language'));
        }
    }

    /**
     * @param string $attribute
     * @param array $params
     */
    public function _validateId(string $attribute, array $params = []): void
    {
        if ($this->hasErrors('id') || $this->hasErrors('language')) {
            return;
        }

        if (!$this->getIsNewRecord()) {
            return;
        }

        $count = self::model()->countByAttributes([
            'id'       => $this->id,
            'language' => $this->language,
        ]);

        if ($count > 0) {
            $this->addError($attribute, $this->t('Duplicate language message'));
        }
    }
}
