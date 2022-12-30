<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * CustomerSuppressionListToCampaign
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.4.4
 */

/**
 * This is the model class for table "{{customer_suppression_list_to_campaign}}".
 *
 * The followings are the available columns in table '{{customer_suppression_list_to_campaign}}':
 * @property integer|null $list_id
 * @property integer|null $campaign_id
 *
 * The followings are the available model relations:
 * @property Campaign $campaign
 * @property CustomerSuppressionList $suppressionList
 */
class CustomerSuppressionListToCampaign extends ActiveRecord
{
    /**
     * @return string
     */
    public function tableName()
    {
        return '{{customer_suppression_list_to_campaign}}';
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [];
    }

    /**
     * @return array
     */
    public function relations()
    {
        $relations = [
            'campaign'        => [self::BELONGS_TO, Campaign::class, 'campaign_id'],
            'suppressionList' => [self::BELONGS_TO, CustomerSuppressionList::class, 'list_id'],
        ];

        return CMap::mergeArray($relations, parent::relations());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        return [];
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return CustomerSuppressionListToCampaign the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var CustomerSuppressionListToCampaign $model */
        $model = parent::model($className);

        return $model;
    }
}
