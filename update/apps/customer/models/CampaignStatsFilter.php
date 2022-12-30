<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * CampaignStatsFilter
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.9.5
 */

class CampaignStatsFilter extends Campaign
{
    /**
     * @const string
     */
    const ACTION_VIEW = 'view';

    /**
     * @const string
     */
    const ACTION_EXPORT = 'export';

    /**
     * @var array
     */
    public $lists = [];

    /**
     * @var array
     */
    public $campaigns = [];

    /**
     * @var string
     */
    public $date_start;

    /**
     * @var string
     */
    public $date_end;

    /**
     * @var string
     */
    public $action;

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return CampaignStatsFilter the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var CampaignStatsFilter $model */
        $model = parent::model($className);

        return $model;
    }

    /**
     * @inheritDoc
     */
    public function rules()
    {
        return [
            ['lists, campaigns, date_start, date_end, action', 'safe'],
        ];
    }

    /**
     * @inheritDoc
     */
    public function attributeLabels()
    {
        return CMap::mergeArray(parent::attributeLabels(), [

            'lists'      => t('campaigns', 'Lists'),
            'campaigns'  => t('campaigns', 'Campaigns'),
            'date_start' => t('campaigns', 'Send at (start)'),
            'date_end'   => t('campaigns', 'Send at (end)'),
            'action'     => t('campaigns', 'Action'),

            'name'              => t('campaigns', 'Campaign'),
            'subject'           => t('campaigns', 'Subject'),
            'subscribersCount'  => t('campaigns', 'Subscribers'),
            'deliverySuccess'   => t('campaigns', 'Delivery'),
            'uniqueOpens'       => t('campaigns', 'Opens'),
            'allOpens'          => t('campaigns', 'All opens'),
            'uniqueClicks'      => t('campaigns', 'Clicks'),
            'allClicks'         => t('campaigns', 'All clicks'),
            'unsubscribes'      => t('campaigns', 'Unsubscribes'),
            'bounces'           => t('campaigns', 'Bounces'),
            'softBounces'       => t('campaigns', 'Bounces (S)'),
            'hardBounces'       => t('campaigns', 'Bounces (H)'),
            'internalBounces'   => t('campaigns', 'Bounces (I)'),
            'complaints'        => t('campaigns', 'Complaints'),
            'listName'          => t('campaigns', 'List'),
            'sendAt'            => t('campaigns', 'Send date'),
        ]);
    }

    /**
     * @return string
     */
    public function getSubscribersCount(): string
    {
        return (string)$this->getStats()->getProcessedCount(true);
    }

    /**
     * @return string
     */
    public function getDeliverySuccess(): string
    {
        return $this->getStats()->getDeliverySuccessCount(true) . ' (' . $this->getStats()->getDeliverySuccessRate(true) . '%)';
    }

    /**
     * @return string
     */
    public function getUniqueOpens(): string
    {
        return $this->getStats()->getUniqueOpensCount(true) . ' (' . $this->getStats()->getUniqueOpensRate(true) . '%)';
    }

    /**
     * @return string
     */
    public function getAllOpens(): string
    {
        return $this->getStats()->getOpensCount(true) . ' (' . $this->getStats()->getOpensRate(true) . '%)';
    }

    /**
     * @return string
     */
    public function getUniqueClicks(): string
    {
        return $this->getStats()->getUniqueClicksCount(true) . ' (' . $this->getStats()->getUniqueClicksRate(true) . '%)';
    }

    /**
     * @return string
     */
    public function getAllClicks(): string
    {
        return $this->getStats()->getClicksCount(true) . ' (' . $this->getStats()->getClicksRate(true) . '%)';
    }

    /**
     * @return string
     */
    public function getUnsubscribes(): string
    {
        return $this->getStats()->getUnsubscribesCount(true) . ' (' . $this->getStats()->getUnsubscribesRate(true) . '%)';
    }

    /**
     * @return string
     */
    public function getBounces(): string
    {
        return $this->getStats()->getBouncesCount(true) . ' (' . $this->getStats()->getBouncesRate(true) . '%)';
    }

    /**
     * @return string
     */
    public function getSoftBounces(): string
    {
        return $this->getStats()->getSoftBouncesCount(true) . ' (' . $this->getStats()->getSoftBouncesRate(true) . '%)';
    }

    /**
     * @return string
     */
    public function getHardBounces(): string
    {
        return $this->getStats()->getHardBouncesCount(true) . ' (' . $this->getStats()->getHardBouncesRate(true) . '%)';
    }

    /**
     * @return string
     */
    public function getInternalBounces(): string
    {
        return $this->getStats()->getInternalBouncesCount(true) . ' (' . $this->getStats()->getInternalBouncesRate(true) . '%)';
    }

    /**
     * @return string
     */
    public function getComplaints(): string
    {
        return $this->getStats()->getComplaintsCount(true) . ' (' . $this->getStats()->getComplaintsRate(true) . '%)';
    }

    /**
     * @return string
     */
    public function getListName(): string
    {
        return !empty($this->list) ? $this->list->name : '';
    }

    /**
     * @return array
     */
    public function getFilterActionsList(): array
    {
        $actions = [
            self::ACTION_VIEW    => t('campaigns', 'View'),
            self::ACTION_EXPORT  => t('campaigns', 'Export'),
        ];

        if (!empty($this->customer_id) && $this->customer->getGroupOption('campaigns.can_export_stats', 'yes') != 'yes') {
            unset($actions[self::ACTION_EXPORT]);
        }

        return $actions;
    }

    /**
     * @return bool
     */
    public function getIsExportAction(): bool
    {
        return $this->action === self::ACTION_EXPORT;
    }

    /**
     * @return bool
     */
    public function getIsViewAction(): bool
    {
        return $this->action === self::ACTION_VIEW;
    }

    /**
     * @return CActiveDataProvider
     */
    public function search()
    {
        $criteria = new CDbCriteria();
        $criteria->with = [];
        $criteria->compare('t.customer_id', (int)$this->customer_id);
        $criteria->compare('t.type', self::TYPE_REGULAR);
        $criteria->compare('t.status', self::STATUS_SENT);

        if (!empty($this->lists) && is_array($this->lists)) {
            $this->lists = array_filter(array_unique(array_map('intval', array_map('trim', $this->lists))));
            if (!empty($this->lists)) {
                $criteria->addInCondition('t.list_id', $this->lists);
            }
        }

        if (!empty($this->campaigns) && is_array($this->campaigns)) {
            $this->campaigns = array_filter(array_unique(array_map('intval', array_map('trim', $this->campaigns))));
            if (!empty($this->campaigns)) {
                $criteria->addInCondition('t.campaign_id', $this->campaigns);
            }
        }

        if (!empty($this->date_start) && !empty($this->date_end)) {
            $criteria->compare('t.send_at', '>=' . date('Y-m-d', (int)strtotime($this->date_start)));
            $criteria->compare('t.send_at', '<=' . date('Y-m-d', (int)strtotime($this->date_end)));
        } elseif (!empty($this->send_at_start)) {
            $criteria->compare('t.send_at', '>=' . date('Y-m-d', (int)strtotime($this->date_start)));
        } elseif (!empty($this->send_at_end)) {
            $criteria->compare('t.send_at', '<=' . date('Y-m-d', (int)strtotime($this->date_end)));
        }

        $criteria->order = 't.campaign_id DESC';

        return new CActiveDataProvider(get_class($this), [
            'criteria'      => $criteria,
            'pagination'    => [
                'pageSize'  => 10,
                'pageVar'   => 'page',
            ],
            'sort'  => [
                'defaultOrder'  => [
                    't.campaign_id'   => CSort::SORT_DESC,
                ],
            ],
        ]);
    }

    /**
     * @return string
     */
    public function getDatePickerFormat(): string
    {
        return 'yy-mm-dd';
    }

    /**
     * @return string
     */
    public function getDatePickerLanguage(): string
    {
        $language = app()->getLanguage();
        if (strpos($language, '_') === false) {
            return $language;
        }
        $language = explode('_', $language);

        return $language[0];
    }

    /**
     * @return bool
     */
    public function getHasFilters(): bool
    {
        $attributes = [
            'action', 'lists', 'campaigns', 'date_start', 'date_end',
        ];

        foreach ($attributes as $attribute) {
            if (!empty($this->$attribute)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param int $customerId
     * @return array
     */
    public static function getCampaignsForCampaignFilterDropdown(int $customerId): array
    {
        $criteria = new CDbCriteria();
        $criteria->select = 'campaign_id, name';
        $criteria->compare('customer_id', (int)$customerId);
        $criteria->compare('type', self::TYPE_REGULAR);
        $criteria->compare('status', self::STATUS_SENT);
        $criteria->order = 'campaign_id DESC';

        return CampaignCollection::findAll($criteria)->mapWithKeys(function (Campaign $campaign) {
            return [$campaign->campaign_id => $campaign->name];
        })->all();
    }
}
