<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * ListFormCustomRedirect
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.3.1
 */

/**
 * This is the model class for table "{{list_form_custom_redirect}}".
 *
 * The followings are the available columns in table '{{list_form_custom_redirect}}':
 * @property integer $redirect_id
 * @property integer $list_id
 * @property integer $type_id
 * @property string $url
 * @property integer $timeout
 * @property string $date_added
 * @property string $last_updated
 *
 * The followings are the available model relations:
 * @property ListPage $list
 * @property ListPage $type
 */
class ListFormCustomRedirect extends ActiveRecord
{
    /**
     * @return string
     */
    public function tableName()
    {
        return '{{list_form_custom_redirect}}';
    }

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            ['url', 'length', 'min' => 3, 'max' => 255],
            ['timeout', 'numerical', 'integerOnly' => true, 'min' => 0, 'max' => 60],
        ];
        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array
     */
    public function relations()
    {
        $relations = [
            'list' => [self::BELONGS_TO, 'ListPage', 'list_id'],
            'type' => [self::BELONGS_TO, 'ListPage', 'type_id'],
        ];
        return CMap::mergeArray($relations, parent::relations());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'redirect_id' => t('lists', 'Redirect'),
            'list_id'     => t('lists', 'List'),
            'type_id'     => t('lists', 'Type'),
            'url'         => t('lists', 'Url'),
            'timeout'     => t('lists', 'Timeout'),
        ];
        return CMap::mergeArray($labels, parent::attributeLabels());
    }

    /**
     * @return array
     */
    public function attributeHelpTexts()
    {
        $texts = [
            'redirect_id' => '',
            'list_id'     => '',
            'type_id'     => '',
            'url'         => t('lists', 'The url where to redirect the subscriber'),
            'timeout'     => t('lists', 'The number of seconds to wait until redirect the subscriber'),
        ];

        return CMap::mergeArray($texts, parent::attributeHelpTexts());
    }

    /**
     * @return array
     */
    public function attributePlaceholders()
    {
        $placeholders = [
            'redirect_id' => '',
            'list_id'     => '',
            'type_id'     => '',
            'url'         => t('lists', 'i.e: http://www.some-other-website.com/my-redirect-page.php'),
            'timeout'     => t('lists', 'i.e: 10'),
        ];

        return CMap::mergeArray($placeholders, parent::attributePlaceholders());
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return ListFormCustomRedirect the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var ListFormCustomRedirect $model */
        $model = parent::model($className);

        return $model;
    }
}
