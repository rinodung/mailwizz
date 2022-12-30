<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}
/**
 * CommonEmailTemplateTag
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.6.2
 */

/**
 * This is the model class for table "{{common_email_template_tag}}".
 *
 * The followings are the available columns in table '{{common_email_template_tag}}':
 * @property integer $tag_id
 * @property integer $template_id
 * @property string $tag
 * @property string $description
 * @property string|CDbExpression $date_added
 * @property string|CDbExpression $last_updated
 *
 * The followings are the available model relations:
 * @property CommonEmailTemplate $template
 */
class CommonEmailTemplateTag extends ActiveRecord
{
    /**
     * @var string
     */
    public $value = '';

    /**
     * @return string
     */
    public function tableName()
    {
        return '{{common_email_template_tag}}';
    }

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            ['tag', 'required'],
            ['tag', 'length', 'max' => 100],
            ['description', 'length', 'max' => 255],
        ];
        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array
     */
    public function relations()
    {
        $relations = [
            'template' => [self::BELONGS_TO, CommonEmailTemplate::class, 'template_id'],
        ];
        return CMap::mergeArray($relations, parent::relations());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'tag_id'        => t('common_email_templates', 'Tag'),
            'template_id'   => t('common_email_templates', 'Template'),
            'tag'           => t('common_email_templates', 'Tag'),
            'description'   => t('common_email_templates', 'Description'),
        ];
        return CMap::mergeArray($labels, parent::attributeLabels());
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return CommonEmailTemplateTag the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var CommonEmailTemplateTag $model */
        $model = parent::model($className);

        return $model;
    }
}
