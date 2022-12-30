<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * ListPageType
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

/**
 * This is the model class for table "list_page_type".
 *
 * The followings are the available columns in table 'list_page_type':
 * @property integer $type_id
 * @property string $name
 * @property string $slug
 * @property string $description
 * @property string $email_subject
 * @property string $content
 * @property string $full_html
 * @property string $meta_data
 * @property string|CDbExpression $date_added
 * @property string|CDbExpression $last_updated
 *
 * The followings are the available model relations:
 * @property ListPage[] $listPages
 *
 *  The followings are the available model behavior:
 * @property PageTypeTagsBehavior $tags
 * @property PageTypeEmailSubjectBehavior $emailSubject
 */
class ListPageType extends ActiveRecord
{
    /**
     * @return string
     */
    public function tableName()
    {
        return '{{list_page_type}}';
    }

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            ['name, content, description', 'required'],

            // meta data
            ['email_subject', 'length', 'max' => 500],
        ];

        // 1.3.8.8
        if ($this->emailSubject->getCanHaveEmailSubject()) {
            $rules[] = ['email_subject', 'required'];
        }

        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array
     */
    public function behaviors()
    {
        $behaviors = [
            'tags' => [
                'class' => 'common.components.db.behaviors.PageTypeTagsBehavior',
            ],
            'emailSubject' => [
                'class' => 'common.components.db.behaviors.PageTypeEmailSubjectBehavior',
            ],
        ];

        return CMap::mergeArray($behaviors, parent::behaviors());
    }

    /**
     * @return array
     */
    public function relations()
    {
        $relations = [
            'listPages' => [self::HAS_MANY, ListPage::class, 'type_id'],
        ];

        return CMap::mergeArray($relations, parent::relations());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'type_id'       => t('list_page_types', 'Type'),
            'name'          => t('list_page_types', 'Name'),
            'description'   => t('list_page_types', 'Description'),
            'content'       => t('list_page_types', 'Default content'),
            'full_html'     => t('list_page_types', 'Full html'),

            // meta data
            'email_subject' => t('list_page_types', 'Email subject'),
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

        return new CActiveDataProvider(get_class($this), [
            'criteria'      => $criteria,
            'pagination'    => [
                'pageSize'  => $this->paginationOptions->getPageSize(),
                'pageVar'   => 'page',
            ],
            'sort'  => [
                'defaultOrder' => [
                    'type_id' => CSort::SORT_DESC,
                ],
            ],
        ]);
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return ListPageType the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var ListPageType $model */
        $model = parent::model($className);

        return $model;
    }

    /**
     * @param string $slug
     *
     * @return ListPageType|null
     */
    public function findBySlug(string $slug): ?self
    {
        return self::model()->findByAttributes([
            'slug' => $slug,
        ]);
    }

    /**
     * @return bool
     */
    protected function beforeDelete()
    {
        return false;
    }

    /**
     * @return bool
     */
    protected function beforeSave()
    {
        $this->content = StringHelper::decodeSurroundingTags($this->content);
        return parent::beforeSave();
    }
}
