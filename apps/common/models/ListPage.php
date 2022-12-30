<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * ListPage
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

/**
 * This is the model class for table "list_page".
 *
 * The followings are the available columns in table 'list_page':
 * @property integer $list_id
 * @property integer $type_id
 * @property string $email_subject
 * @property string $content
 * @property string $meta_data
 * @property string|CDbExpression $date_added
 * @property string|CDbExpression $last_updated
 *
 * The followings are the available model relations:
 * @property ListPageType $type
 * @property Lists $list
 *
 * The followings are the available model behaviors:
 * @property PageTypeTagsBehavior $tags
 * @property PageTypeEmailSubjectBehavior $emailSubject
 */
class ListPage extends ActiveRecord
{
    /**
     * @return array
     */
    public function primaryKey()
    {
        return ['list_id', 'type_id'];
    }

    /**
     * @return string
     */
    public function tableName()
    {
        return '{{list_page}}';
    }

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            ['content', 'safe'],

            // meta data
            ['email_subject', 'length', 'max' => 500],
        ];

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
            'type' => [self::BELONGS_TO, ListPageType::class, 'type_id'],
            'list' => [self::BELONGS_TO, Lists::class, 'list_id'],
        ];

        return CMap::mergeArray($relations, parent::relations());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'list_id'     => t('list_pages', 'List'),
            'type_id'     => t('list_pages', 'Type'),
            'content'     => t('list_pages', 'Content'),

            // meta data
            'email_subject' => t('list_pages', 'Email subject'),
        ];

        return CMap::mergeArray($labels, parent::attributeLabels());
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return ListPage the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var ListPage $model */
        $model = parent::model($className);

        return $model;
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
