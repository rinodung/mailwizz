<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * CampaignOpenActionSubscriber
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.4.3
 */

/**
 * This is the model class for table "{{campaign_open_action_subscriber}}".
 *
 * The followings are the available columns in table '{{campaign_open_action_subscriber}}':
 * @property string $action_id
 * @property integer $campaign_id
 * @property integer $list_id
 * @property string $action
 * @property string|CDbExpression $date_added
 * @property string|CDbExpression $last_updated
 *
 * The followings are the available model relations:
 * @property Campaign $campaign
 * @property Lists $list
 */
class CampaignOpenActionSubscriber extends ActiveRecord
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
        return '{{campaign_open_action_subscriber}}';
    }

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            ['action, list_id', 'required'],
            ['action', 'length', 'max' => 5],
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
            'campaign' => [self::BELONGS_TO, Campaign::class, 'campaign_id'],
            'list'     => [self::BELONGS_TO, Lists::class, 'list_id'],
        ];

        return CMap::mergeArray($relations, parent::relations());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'action_id'      => t('campaigns', 'Action'),
            'campaign_id'    => t('campaigns', 'Campaign'),
            'list_id'        => t('campaigns', 'To list'),
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
            'list_id'   => t('campaigns', 'The target list for the selected action'),
            'action'    => t('campaigns', 'What action to take against the subscriber when he opens the campaign'),
        ];

        return CMap::mergeArray($texts, parent::attributeHelpTexts());
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return CampaignOpenActionSubscriber the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var CampaignOpenActionSubscriber $model */
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
}
