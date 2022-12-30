<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * ListFormCustomWebhook
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.4.3
 */

/**
 * This is the model class for table "{{list_form_custom_webhook}}".
 *
 * The followings are the available columns in table '{{list_form_custom_webhook}}':
 * @property integer|null $webhook_id
 * @property integer $list_id
 * @property integer $type_id
 * @property string $request_url
 * @property string $request_type
 * @property string|CDbExpression $date_added
 * @property string|CDbExpression $last_updated
 *
 * The followings are the available model relations:
 * @property ListPage $list
 * @property ListPage $type
 */
class ListFormCustomWebhook extends ActiveRecord
{
    /**
     * Flags
     */
    const REQUEST_TYPE_POST = 'post';
    const REQUEST_TYPE_GET  = 'get';

    /**
     * @return string
     */
    public function tableName()
    {
        return '{{list_form_custom_webhook}}';
    }

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            ['request_url, request_type', 'required'],
            ['request_url', 'url'],
            ['request_type', 'in', 'range' => array_keys($this->getRequestTypes())],
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
            'webhook_id'   => t('lists', 'Webhook'),
            'list_id'      => t('lists', 'List'),
            'type_id'      => t('lists', 'Page type'),
            'request_url'  => t('lists', 'Request url'),
            'request_type' => t('lists', 'Request type'),
        ];
        return CMap::mergeArray($labels, parent::attributeLabels());
    }

    /**
     * @return array
     */
    public function attributeHelpTexts()
    {
        $texts = [
            'webhook_id'   => '',
            'list_id'      => '',
            'type_id'      => '',
            'request_url'  => t('lists', 'The request url for this hook'),
            'request_type' => t('lists', 'The type of the request, post or get'),
        ];

        return CMap::mergeArray($texts, parent::attributeHelpTexts());
    }

    /**
     * @return array
     */
    public function attributePlaceholders()
    {
        $placeholders = [
            'webhook_id'   => '',
            'list_id'      => '',
            'type_id'      => '',
            'request_url'  => t('lists', 'i.e: http://www.some-other-website.com/process-data-offline.php'),
            'request_type' => '',
        ];

        return CMap::mergeArray($placeholders, parent::attributePlaceholders());
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return ListFormCustomWebhook the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var ListFormCustomWebhook $model */
        $model = parent::model($className);

        return $model;
    }

    /**
     * @return array
     */
    public function getRequestTypes(): array
    {
        return [
            self::REQUEST_TYPE_POST => strtoupper(t('lists', self::REQUEST_TYPE_POST)),
            self::REQUEST_TYPE_GET  => strtoupper(t('lists', self::REQUEST_TYPE_GET)),
        ];
    }
}
