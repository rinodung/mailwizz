<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * CampaignTemplateUrlActionSubscriber
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.4.3
 */

/**
 * This is the model class for table "{{campaign_template_url_action_subscriber}}".
 *
 * The followings are the available columns in table '{{campaign_template_url_action_subscriber}}':
 * @property string $url_id
 * @property integer $campaign_id
 * @property integer $list_id
 * @property integer $template_id
 * @property string $url
 * @property string $action
 * @property string|CDbExpression $date_added
 * @property string|CDbExpression $last_updated
 *
 * The followings are the available model relations:
 * @property CampaignTemplate $template
 * @property Lists $list
 * @property Campaign $campaign
 */
class CampaignTemplateUrlActionSubscriber extends ActiveRecord
{
    /**
     * Action flags
     */
    const ACTION_COPY = 'copy';
    const ACTION_MOVE = 'move';

    /**
     * @return string
     */
    public function tableName()
    {
        return '{{campaign_template_url_action_subscriber}}';
    }

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            ['url, action, list_id', 'required'],
            ['url', '_validateUrl'],
            ['action', 'length', 'max'=>5],
            ['action', 'in', 'range' => array_keys($this->getActions())],
            ['list_id', 'numerical', 'integerOnly' => true],
            ['list_id', 'exist', 'className' => Lists::class],
        ];

        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array
     */
    public function relations()
    {
        $relations = [
            'template'   => [self::BELONGS_TO, CampaignTemplate::class, 'template_id'],
            'list'       => [self::BELONGS_TO, Lists::class, 'list_id'],
            'campaign'   => [self::BELONGS_TO, Campaign::class, 'campaign_id'],
        ];

        return CMap::mergeArray($relations, parent::relations());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'url_id'         => t('campaigns', 'Url'),
            'campaign_id'    => t('campaigns', 'Campaign'),
            'list_id'        => t('campaigns', 'To list'),
            'template_id'    => t('campaigns', 'Template'),
            'url'            => t('campaigns', 'Url'),
            'action'         => t('campaigns', 'Action'),
        ];

        return CMap::mergeArray($labels, parent::attributeLabels());
    }

    /**
     * @return array
     */
    public function attributeHelpTexts()
    {
        $texts = [
            'list_id'        => t('campaigns', 'The target list for the selected action'),
            'url'            => t('campaigns', 'Trigger the selected action when the subscriber will access this url'),
            'action'         => t('campaigns', 'What action to take against the subscriber when the url is accessed'),
        ];

        return CMap::mergeArray($texts, parent::attributeHelpTexts());
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return CampaignTemplateUrlActionSubscriber the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var CampaignTemplateUrlActionSubscriber $model */
        $model = parent::model($className);

        return $model;
    }

    /**
     * @return array
     */
    public function getActions(): array
    {
        return [
            self::ACTION_COPY => ucfirst(t('app', self::ACTION_COPY)),
            self::ACTION_MOVE => ucfirst(t('app', self::ACTION_MOVE)),
        ];
    }

    /**
     * @return void
     *
     * @param string $attribute
     * @param array $params
     */
    public function _validateUrl(string $attribute, array $params = []): void
    {
        if ($this->hasErrors($attribute)) {
            return;
        }

        // if this is a URL tag
        if (preg_match('/^\[([A-Z_]+)_URL\]$/', $this->$attribute, $matches)) {
            return;
        }

        // if this is a regular url
        $validator = new CUrlValidator();
        if ($validator->validateValue($this->$attribute)) {
            return;
        }

        $this->addError($attribute, t('campaigns', 'Please provide a valid url!'));
    }

    /**
     * @return bool
     */
    protected function beforeSave()
    {
        $this->url = StringHelper::normalizeUrl($this->url);
        return parent::beforeSave();
    }
}
