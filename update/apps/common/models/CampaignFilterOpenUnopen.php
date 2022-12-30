<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * CampaignFilterOpenUnopen
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.8.8
 */

/**
 * This is the model class for table "{{campaign_filter_open_unopen}}".
 *
 * The followings are the available columns in table '{{campaign_filter_open_unopen}}':
 * @property integer $campaign_id
 * @property string $action
 * @property integer|array $previous_campaign_id
 *
 * The followings are the available model relations:
 * @property Campaign $campaign
 * @property Campaign $previousCampaign
 */
class CampaignFilterOpenUnopen extends ActiveRecord
{
    /**
     * Action flags
     */
    const ACTION_OPEN   = 'open';
    const ACTION_UNOPEN = 'unopen';

    /**
     * @return string
     */
    public function tableName()
    {
        return '{{campaign_filter_open_unopen}}';
    }

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            ['campaign_id, action, previous_campaign_id', 'required'],
            ['campaign_id, previous_campaign_id', 'numerical', 'integerOnly' => true],
            ['campaign_id, previous_campaign_id', 'exist', 'className' => Campaign::class, 'attributeName' => 'campaign_id'],
            ['action', 'in', 'range' => array_keys($this->getActionsList())],
        ];

        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array
     */
    public function relations()
    {
        $relations = [
            'campaign'         => [self::BELONGS_TO, Campaign::class, 'campaign_id'],
            'previousCampaign' => [self::BELONGS_TO, Campaign::class, 'previous_campaign_id'],
        ];

        return CMap::mergeArray($relations, parent::relations());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'campaign_id'           => t('campaigns', 'Campaign'),
            'action'                => t('campaigns', 'Action'),
            'previous_campaign_id'  => t('campaigns', 'Previous campaign'),
        ];

        return CMap::mergeArray($labels, parent::attributeLabels());
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return CampaignFilterOpenUnopen the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var CampaignFilterOpenUnopen $model */
        $model = parent::model($className);

        return $model;
    }

    /**
     * @return array
     */
    public function getActionsList(): array
    {
        return [
            self::ACTION_OPEN   => ucfirst(t('campaigns', self::ACTION_OPEN)),
            self::ACTION_UNOPEN => ucfirst(t('campaigns', self::ACTION_UNOPEN)),
        ];
    }
}
