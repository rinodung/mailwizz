<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * CampaignShareCode
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.7.6
 */

/**
 * This is the model class for table "{{campaign_share_code}}".
 *
 * The followings are the available columns in table '{{campaign_share_code}}':
 * @property integer $code_id
 * @property string $code_uid
 * @property string $used
 * @property string|CDbExpression $date_added
 * @property string|CDbExpression $last_updated
 *
 * The followings are the available model relations:
 * @property Campaign[] $campaigns
 */
class CampaignShareCode extends ActiveRecord
{
    /**
     * @return string
     */
    public function tableName()
    {
        return '{{campaign_share_code}}';
    }

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [];

        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array
     */
    public function relations()
    {
        $relations = [
            'campaigns' => [self::MANY_MANY, Campaign::class, '{{campaign_share_code_to_campaign}}(code_id, campaign_id)'],
        ];
        return CMap::mergeArray($relations, parent::relations());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'code_uid' => t('campaigns', 'Code'),
        ];

        return CMap::mergeArray($labels, parent::attributeLabels());
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return CampaignShareCode the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var CampaignShareCode $model */
        $model = parent::model($className);

        return $model;
    }

    /**
     * @param string $code_uid
     *
     * @return CampaignShareCode|null
     */
    public function findByCode(string $code_uid): ?self
    {
        return self::model()->findByAttributes([
            'code_uid' => $code_uid,
        ]);
    }

    /**
     * @return string
     */
    public function generateCode(): string
    {
        $unique = StringHelper::randomSha1();
        $exists = $this->findByCode($unique);

        if (!empty($exists)) {
            return $this->generateCode();
        }

        return $unique;
    }

    /**
     * @return bool
     */
    protected function beforeSave()
    {
        if (empty($this->code_uid)) {
            $this->code_uid = $this->generateCode();
        }

        return parent::beforeSave();
    }
}
