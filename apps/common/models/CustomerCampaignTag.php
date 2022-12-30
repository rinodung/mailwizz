<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * CustomerCampaignTag
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.5.9
 */

/**
 * This is the model class for table "customer_campaign_tag".
 *
 * The followings are the available columns in table 'customer_campaign_tag':
 * @property integer $tag_id
 * @property string $tag_uid
 * @property integer $customer_id
 * @property string $tag
 * @property string $content
 * @property string $random
 * @property string|CDbExpression $date_added
 * @property string|CDbExpression $last_updated
 *
 * The followings are the available model relations:
 * @property Customer $customer
 */
class CustomerCampaignTag extends ActiveRecord
{
    /**
     * @return string
     */
    public function tableName()
    {
        return '{{customer_campaign_tag}}';
    }

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            ['customer_id, tag, content', 'required'],
            ['customer_id', 'numerical', 'integerOnly' => true],
            ['customer_id', 'exist', 'className' => Customer::class],
            ['tag', 'length', 'min' => 1, 'max' => 50],
            ['tag', 'match', 'pattern' => '#^(([A-Z\p{Cyrillic}\p{Arabic}\p{Greek}]+)([A-Z\p{Cyrillic}\p{Arabic}\p{Greek}0-9\_]+)?([A-Z\p{Cyrillic}\p{Arabic}\p{Greek}0-9]+)?)$#u'],
            ['content', 'length', 'max' => 65535],
            ['random', 'in', 'range' => array_keys($this->getYesNoOptions())],

            ['tag, content, random', 'safe', 'on'=>'search'],
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
            'tag_id'		=> t('campaigns', 'Tag'),
            'customer_id' 	=> t('campaigns', 'Customer'),
            'tag' 			=> t('campaigns', 'Tag'),
            'content' 		=> t('campaigns', 'Content'),
            'random' 		=> t('campaigns', 'Random'),
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

        $criteria->compare('customer_id', $this->customer_id);
        $criteria->compare('tag', $this->tag, true);
        $criteria->compare('content', $this->content, true);
        $criteria->compare('random', $this->random);

        return new CActiveDataProvider(get_class($this), [
            'criteria'      => $criteria,
            'pagination'    => [
                'pageSize'  => $this->paginationOptions->getPageSize(),
                'pageVar'   => 'page',
            ],
            'sort' => [
                'defaultOrder' => [
                    'tag_id' => CSort::SORT_DESC,
                ],
            ],
        ]);
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return CustomerCampaignTag the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var CustomerCampaignTag $model */
        $model = parent::model($className);

        return $model;
    }

    /**
     * @return array
     */
    public function attributeHelpTexts()
    {
        $texts = [
            'tag' => t('campaigns', 'The name of the tag in uppercase letters. Please note that the {prefix} prefix will be added to your tag, therefore you will access your tag like: {like}', [
                '{prefix}' => self::getTagPrefix(),
                '{like}'   => '[' . self::getTagPrefix() . 'YOUR_TAG_NAME]',
            ]),
            'random' => t('campaigns', 'Whether to randomize the lines of text from the content box'),
            'content'=> t('campaigns', 'The tag content'),
        ];

        return CMap::mergeArray($texts, parent::attributeHelpTexts());
    }

    /**
     * @return string
     */
    public function getUid(): string
    {
        return (string)$this->tag_uid;
    }

    /**
     * @param string $tag_uid
     *
     * @return CustomerCampaignTag|null
     */
    public function findByUid(string $tag_uid): ?self
    {
        return self::model()->findByAttributes([
            'tag_uid' => $tag_uid,
        ]);
    }

    /**
     * @return string
     */
    public function generateUid(): string
    {
        $unique = StringHelper::uniqid();
        $exists = $this->findByUid($unique);

        if (!empty($exists)) {
            return $this->generateUid();
        }

        return $unique;
    }

    /**
     * @return string
     */
    public function getFullTagWithPrefix(): string
    {
        return '[' . self::getTagPrefix() . $this->tag . ']';
    }

    /**
     * @return string
     */
    public static function getTagPrefix(): string
    {
        return (string)app_param('customer.campaigns.custom_tags.prefix', 'CCT_');
    }

    /**
     * @return bool
     */
    protected function beforeSave()
    {
        if (!parent::beforeSave()) {
            return false;
        }

        if ($this->getIsNewRecord()) {
            $this->tag_uid = $this->generateUid();
        }

        return true;
    }
}
