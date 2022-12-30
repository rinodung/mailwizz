<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * CampaignRandomContent
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.9.5
 */

/**
 * This is the model class for table "{{campaign_random_content}}".
 *
 * The followings are the available columns in table '{{campaign_random_content}}':
 * @property integer $id
 * @property integer $campaign_id
 * @property string $name
 * @property string $content
 *
 * The followings are the available model relations:
 * @property Campaign $campaign
 */
class CampaignRandomContent extends ActiveRecord
{
    /**
     * @return string
     */
    public function tableName()
    {
        return '{{campaign_random_content}}';
    }

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            ['name, content', 'required'],
            ['name', 'length', 'max' => 50],
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
        ];

        return CMap::mergeArray($relations, parent::relations());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'id'          => t('campaigns', 'ID'),
            'campaign_id' => t('campaigns', 'Campaign'),
            'name'        => t('campaigns', 'Name'),
            'content'     => t('campaigns', 'Content'),
        ];

        return CMap::mergeArray($labels, parent::attributeLabels());
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return CampaignRandomContent the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var CampaignRandomContent $model */
        $model = parent::model($className);

        return $model;
    }

    /**
     * @return bool
     */
    protected function beforeSave()
    {
        $criteria = new CDbCriteria();
        $criteria->compare('id', '!=' . (int)$this->id);
        $criteria->compare('campaign_id', (int)$this->campaign_id);
        $criteria->compare('name', (string)$this->name);

        $model = self::model()->find($criteria);
        if (!empty($model)) {
            $this->addError('name', t('campaigns', 'Seems that this name is already taken for this campaign!'));
            return false;
        }

        return parent::beforeSave();
    }
}
