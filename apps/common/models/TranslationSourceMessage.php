<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * TranslationSourceMessage
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.0
 */

/**
 * This is the model class for table "{{translation_source_message}}".
 *
 * The followings are the available columns in table '{{translation_source_message}}':
 * @property integer $id
 * @property string $category
 * @property string $message
 * @property string $language
 * @property string $translation
 *
 * The followings are the available model relations:
 * @property TranslationMessage[] $messages
 */
class TranslationSourceMessage extends ActiveRecord
{
    /**
     * @var string
     */
    public $language = '';

    /**
     * @var string
     */
    public $translation = '';

    /**
     * @return string
     */
    public function tableName()
    {
        return '{{translation_source_message}}';
    }

    /**
     * @return array|mixed
     * @throws CException
     */
    public function rules()
    {
        $rules = [
            ['category, message', 'required'],
            ['category', 'length', 'max' => 100],
            ['message', 'length', 'max' => 5000],

            // The following rule is used by search().
            ['category, message, translation', 'safe', 'on'=>'search'],
        ];
        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array
     * @throws CException
     */
    public function relations()
    {
        $relations = [
            'messages' => [self::HAS_MANY, TranslationMessage::class, 'id'],
        ];
        return CMap::mergeArray($relations, parent::relations());
    }

    /**
     * @return array
     * @throws CException
     */
    public function attributeLabels()
    {
        $labels = [
            'category'     => $this->t('Category'),
            'message'      => $this->t('Message'),
            'translation'  => $this->t('Translation'),
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

        $criteria->compare('t.category', $this->category, true);
        $criteria->compare('t.message', $this->message, true);

        if (!empty($this->translation)) {
            $criteria->with['messages'] = [
                'together' => true,
            ];
            $criteria->compare('messages.translation', $this->translation, true);
        }

        $criteria->order = 't.category ASC';

        return new CActiveDataProvider(get_class($this), [
            'criteria'   => $criteria,
            'pagination' => [
                'pageSize' => $this->paginationOptions->getPageSize(),
                'pageVar'  => 'page',
            ],
            'sort'=>[
                'defaultOrder' => [
                    't.category'  => CSort::SORT_ASC,
                ],
            ],
        ]);
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return TranslationSourceMessage the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var TranslationSourceMessage $model */
        $model = parent::model($className);

        return $model;
    }

    /**
     * @return string
     */
    public function getTranslationCategory(): string
    {
        return 'translations';
    }

    /**
     * @param int $language_id
     * @return string
     * @throws CHttpException
     */
    public function getTranslationInputField(int $language_id): string
    {
        $model = Language::model()->findByAttributes([
            'language_id' => $language_id,
        ]);

        if ($model === null) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        $language = (string)$model->getLanguageAndLocaleCode();

        /** @var TranslationMessage|null $translation */
        $translation = TranslationMessage::model()->findByAttributes([
            'id'       => (int)$this->id,
            'language' => $language,
        ]);

        if (empty($translation)) {
            $translation = new TranslationMessage();
        }

        $htmlOptions = $translation->fieldDecorator->getHtmlOptions('translation');

        $key = StringHelper::random();
        $html = CHtml::hiddenField($translation->getModelName() . '[' . $key . '][id]', $this->id);
        $html .= CHtml::hiddenField($translation->getModelName() . '[' . $key . '][language]', $language);
        $html .= CHtml::textArea($translation->getModelName() . '[' . $key . '][translation]', $translation->translation, $htmlOptions);

        return $html;
    }
}
