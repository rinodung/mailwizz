<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * CampaignStatsProcessor
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.3
 */

/**
 * @property Campaign $owner
 */
class CampaignStatsProcessorBehavior extends CBehavior
{
    /**
     * Amount in seconds for short caches
     */
    const CACHE_SHORT = 300; // 5 mins

    /**
     * Amount in seconds for medium caches
     */
    const CACHE_MEDIUM = 21600; // 6 hours

    /**
     * Amount in seconds for long caches
     */
    const CACHE_LONG = 604800; // 7 days

    /**
     * Cache forever
     */
    const CACHE_FOREVER = 0;

    /**
     * @var int|null
     */
    protected $_listSubscribers;

    /**
     * @var int|null
     */
    protected $_segmentSubscribers;

    /**
     * @var int|null
     */
    protected $_subscribersCount;

    /**
     * @var int|null
     */
    protected $_processedCount;

    /**
     * @var int|null
     */
    protected $_deliverySuccessCount;

    /**
     * @var int|null
     */
    protected $_deliveryErrorCount;

    /**
     * @var float|null
     */
    protected $_deliverySuccessRate;

    /**
     * @var float|null
     */
    protected $_deliveryErrorRate;

    /**
     * @var int|null
     */
    protected $_opensCount;

    /**
     * @var int|null
     */
    protected $_uniqueOpensCount;

    /**
     * @var float|null
     */
    protected $_opensRate;

    /**
     * @var float|null
     */
    protected $_uniqueOpensRate;

    /**
     * @var int|null
     */
    protected $_bouncesCount;

    /**
     * @var int|null
     */
    protected $_hardBouncesCount;

    /**
     * @var int|null
     */
    protected $_internalBouncesCount;

    /**
     * @var int|null
     */
    protected $_softBouncesCount;

    /**
     * @var float|null
     */
    protected $_bouncesRate;

    /**
     * @var float|null
     */
    protected $_hardBouncesRate;

    /**
     * @var float|null
     */
    protected $_internalBouncesRate;

    /**
     * @var float|null
     */
    protected $_softBouncesRate;

    /**
     * @var int|null
     */
    protected $_unsubscribesCount;

    /**
     * @var float|null
     */
    protected $_unsubscribesRate;

    /**
     * @var int|null
     */
    protected $_complaintsCount;

    /**
     * @var float|null
     */
    protected $_complaintsRate;

    /**
     * @var mixed
     */
    protected $_completitionDuration;

    /**
     * @var float|null
     */
    protected $_completitionRate;

    /**
     * @var int|null
     */
    protected $_trackingUrlsCount;

    /**
     * @var int|null
     */
    protected $_clicksCount;

    /**
     * @var int|null
     */
    protected $_uniqueClicksCount;

    /**
     * @var float|null
     */
    protected $_clicksRate;

    /**
     * @var float|null
     */
    protected $_uniqueClicksRate;

    /**
     * @var CompanyType|null
     */
    protected $_industry;

    /**
     * @var int|null
     */
    protected $_industryProcessedCount;

    /**
     * @var int|null
     */
    protected $_industryOpensCount;

    /**
     * @var float|null
     */
    protected $_industryOpensRate;

    /**
     * @var int|null
     */
    protected $_industryClicksCount;

    /**
     * @var float|null
     */
    protected $_industryClicksRate;

    /**
     * @var bool
     */
    protected $_enableCache = true;

    /**
     * @var bool
     */
    protected $_enableCacheInit = true;

    /**
     * @param CComponent $owner
     *
     * @return void
     * @throws CException
     */
    public function attach($owner)
    {
        // since 1.5.2
        $this->_enableCache     = (bool)app_param('campaign.stats.processor.enable_cache', true);
        $this->_enableCacheInit = $this->_enableCache;

        if (!($owner instanceof Campaign)) {
            throw new CException(t('customers', 'The {className} behavior can only be attach to a Campaign model', [
                '{className}' => get_class($this),
            ]));
        }

        parent::attach($owner);
    }

    /**
     * @return $this
     */
    public function enableCache()
    {
        if (!$this->_enableCacheInit) {
            return $this;
        }
        $this->_enableCache = true;
        return $this;
    }

    /**
     * @return $this
     */
    public function disableCache()
    {
        $this->_enableCache = false;
        return $this;
    }

    /**
     * @param string $key
     * @return bool|mixed
     */
    public function getFromCache($key)
    {
        if (!$this->_enableCache) {
            return false;
        }
        return cache()->get($key);
    }

    /**
     * @param string $id
     * @param mixed $value
     * @param int $expire
     * @return bool
     */
    public function setInCache($id, $value, $expire = 0)
    {
        if (!$this->_enableCache) {
            return false;
        }
        return cache()->set($id, $value, $expire);
    }

    /**
     * @param string $key
     * @return bool|mixed
     */
    public function deleteFromCache($key)
    {
        if (!$this->_enableCache) {
            return false;
        }
        return cache()->delete($key);
    }

    /**
     * @param bool $formatNumber
     * @return int|string
     */
    public function getListSubscribers($formatNumber = false)
    {
        if ($formatNumber) {
            return $this->format($this->_getListSubscribers());
        }
        return $this->_getListSubscribers();
    }

    /**
     * @param bool $formatNumber
     *
     * @return int|string
     * @throws CDbException
     */
    public function getSegmentSubscribers($formatNumber = false)
    {
        if ($formatNumber) {
            return $this->format($this->_getSegmentSubscribers());
        }

        return $this->_getSegmentSubscribers();
    }

    /**
     * @param bool $formatNumber
     *
     * @return int|string
     * @throws CDbException
     */
    public function getSubscribersCount($formatNumber = false)
    {
        if ($formatNumber) {
            return $this->format($this->_getSubscribersCount());
        }

        return $this->_getSubscribersCount();
    }

    /**
     * @param bool $formatNumber
     * @return int|string
     */
    public function getProcessedCount($formatNumber = false)
    {
        if ($formatNumber) {
            return $this->format($this->_getProcessedCount());
        }

        return $this->_getProcessedCount();
    }

    /**
     * @param bool $formatNumber
     * @return int|string
     */
    public function getDeliverySuccessCount($formatNumber = false)
    {
        if ($formatNumber) {
            return $this->format($this->_getDeliverySuccessCount());
        }

        return $this->_getDeliverySuccessCount();
    }

    /**
     * @param bool $formatNumber
     * @return float|string
     */
    public function getDeliverySuccessRate($formatNumber = false)
    {
        if ($formatNumber) {
            return $this->format($this->_getDeliverySuccessRate());
        }

        return $this->_getDeliverySuccessRate();
    }

    /**
     * @param bool $formatNumber
     * @return int|string
     */
    public function getDeliveryErrorCount($formatNumber = false)
    {
        if ($formatNumber) {
            return $this->format($this->_getDeliveryErrorCount());
        }

        return $this->_getDeliveryErrorCount();
    }

    /**
     * @param bool $formatNumber
     * @return float|string
     */
    public function getDeliveryErrorRate($formatNumber = false)
    {
        if ($formatNumber) {
            return $this->format($this->_getDeliveryErrorRate());
        }

        return $this->_getDeliveryErrorRate();
    }

    /**
     * @return bool|mixed
     */
    public function deleteOpensCountCache()
    {
        $cacheKey = sha1(__CLASS__ . '::_getOpensCount' . get_class($this->owner) . $this->owner->campaign_id . 'opens');
        return $this->deleteFromCache($cacheKey);
    }

    /**
     * @param bool $formatNumber
     * @return int|string
     */
    public function getOpensCount($formatNumber = false)
    {
        if ($formatNumber) {
            return $this->format($this->_getOpensCount());
        }

        return $this->_getOpensCount();
    }

    /**
     * @return bool|mixed
     */
    public function deleteUniqueOpensCountCache()
    {
        $cacheKey = sha1(__CLASS__ . '::_getUniqueOpensCount' . get_class($this->owner) . $this->owner->campaign_id . 'opens-unique');
        return $this->deleteFromCache($cacheKey);
    }

    /**
     * @param bool $formatNumber
     * @return int|string
     */
    public function getUniqueOpensCount($formatNumber = false)
    {
        if ($formatNumber) {
            return $this->format($this->_getUniqueOpensCount());
        }

        return $this->_getUniqueOpensCount();
    }

    /**
     * @param bool $formatNumber
     * @return float|string
     */
    public function getOpensRate($formatNumber = false)
    {
        if ($formatNumber) {
            return $this->format($this->_getOpensRate());
        }

        return $this->_getOpensRate();
    }

    /**
     * @param bool $formatNumber
     * @return float|string
     */
    public function getUniqueOpensRate($formatNumber = false)
    {
        if ($formatNumber) {
            return $this->format($this->_getUniqueOpensRate());
        }

        return $this->_getUniqueOpensRate();
    }

    /**
     * @param bool $formatNumber
     * @return float|string
     */
    public function getClicksToOpensRate($formatNumber = false)
    {
        $clicks = (int)$this->getUniqueClicksCount();
        $opens  = (int)$this->getUniqueOpensCount();

        if ($clicks == 0 || $opens == 0) {
            return $rate = 0.0;
        }

        $rate = ($clicks / $opens) * 100;

        if ($formatNumber) {
            return $this->format($rate);
        }

        return (float)$rate;
    }

    /**
     * @param bool $formatNumber
     * @return float|string
     */
    public function getOpensToClicksRate($formatNumber = false)
    {
        $clicks = (int)$this->getUniqueClicksCount();
        $opens  = (int)$this->getUniqueOpensCount();

        if ($clicks == 0 || $opens == 0) {
            return $rate = 0.0;
        }

        $rate = ($opens / $clicks) * 100;

        if ($formatNumber) {
            return $this->format($rate);
        }

        return (float)$rate;
    }

    /**
     * @param bool $formatNumber
     * @return int|string
     */
    public function getBouncesCount($formatNumber = false)
    {
        if ($formatNumber) {
            return $this->format($this->_getBouncesCount());
        }

        return $this->_getBouncesCount();
    }

    /**
     * @param bool $formatNumber
     * @return float|string
     */
    public function getBouncesRate($formatNumber = false)
    {
        if ($formatNumber) {
            return $this->format($this->_getBouncesRate());
        }

        return $this->_getBouncesRate();
    }

    /**
     * @param bool $formatNumber
     * @return int|string
     */
    public function getHardBouncesCount($formatNumber = false)
    {
        if ($formatNumber) {
            return $this->format($this->_getHardBouncesCount());
        }

        return $this->_getHardBouncesCount();
    }

    /**
     * @param bool $formatNumber
     * @return float|string
     */
    public function getHardBouncesRate($formatNumber = false)
    {
        if ($formatNumber) {
            return $this->format($this->_getHardBouncesRate());
        }

        return $this->_getHardBouncesRate();
    }

    /**
     * @param bool $formatNumber
     * @return int|string
     */
    public function getSoftBouncesCount($formatNumber = false)
    {
        if ($formatNumber) {
            return $this->format($this->_getSoftBouncesCount());
        }

        return $this->_getSoftBouncesCount();
    }

    /**
     * @param bool $formatNumber
     * @return float|string
     */
    public function getSoftBouncesRate($formatNumber = false)
    {
        if ($formatNumber) {
            return $this->format($this->_getSoftBouncesRate());
        }

        return $this->_getSoftBouncesRate();
    }

    /**
     * @param bool $formatNumber
     * @return int|string
     */
    public function getInternalBouncesCount($formatNumber = false)
    {
        if ($formatNumber) {
            return $this->format($this->_getInternalBouncesCount());
        }

        return $this->_getInternalBouncesCount();
    }

    /**
     * @param bool $formatNumber
     * @return float|string
     */
    public function getInternalBouncesRate($formatNumber = false)
    {
        if ($formatNumber) {
            return $this->format($this->_getInternalBouncesRate());
        }

        return $this->_getInternalBouncesRate();
    }

    /**
     * @param bool $formatNumber
     * @return int|string
     */
    public function getUnsubscribesCount($formatNumber = false)
    {
        if ($formatNumber) {
            return $this->format($this->_getUnsubscribesCount());
        }

        return $this->_getUnsubscribesCount();
    }

    /**
     * @param bool $formatNumber
     * @return float|string
     */
    public function getUnsubscribesRate($formatNumber = false)
    {
        if ($formatNumber) {
            return $this->format($this->_getUnsubscribesRate());
        }

        return $this->_getUnsubscribesRate();
    }

    /**
     * @param bool $formatNumber
     * @return int|string
     */
    public function getComplaintsCount($formatNumber = false)
    {
        if ($formatNumber) {
            return $this->format($this->_getComplaintsCount());
        }

        return $this->_getComplaintsCount();
    }

    /**
     * @param bool $formatNumber
     * @return float|string
     */
    public function getComplaintsRate($formatNumber = false)
    {
        if ($formatNumber) {
            return $this->format($this->_getComplaintsRate());
        }

        return $this->_getComplaintsRate();
    }

    /**
     * @return null
     */
    public function getCompletitionDuration()
    {
        return null;
        /*
        $cmp = $this->owner;

        if (!$cmp->isRegular || $this->_completitionDuration !== null || !$this->canBeProcessed || $cmp->status == Campaign::STATUS_SENT) {
            return $this->_completitionDuration;
        }

        // based on last hour
        $criteria = new CDbCriteria();
        $criteria->compare('campaign_id', $cmp->campaign_id);
        $criteria->addCondition('date_added >= DATE_SUB(NOW(), INTERVAL 1 HOUR)');

        $cdlModel = $cmp->getDeliveryLogsArchived() ? CampaignDeliveryLogArchive::model() : CampaignDeliveryLog::model();
        $count    = $cdlModel->count($criteria);

        if ($count > 0) {
            $count              = $count / 3600;
            $estimateSeconds    = floor(($this->_subscribersCount - $this->_processedCount) / $count);
            $now                = time();
            $this->_completitionDuration = DateTimeHelper::timespan($now - $estimateSeconds, $now);
        }

        return $this->_completitionDuration;
        */
    }

    /**
     * @param bool $formatNumber
     * @return float|string
     */
    public function getCompletitionRate($formatNumber = false)
    {
        if ($formatNumber) {
            return $this->format($this->_getCompletitionRate());
        }

        return $this->_getCompletitionRate();
    }

    /**
     * @param bool $formatNumber
     * @return int|string
     */
    public function getTrackingUrlsCount($formatNumber = false)
    {
        if ($formatNumber) {
            return $this->format($this->_getTrackingUrlsCount());
        }

        return $this->_getTrackingUrlsCount();
    }

    /**
     * @return bool|mixed
     */
    public function deleteClicksCountCache()
    {
        $cacheKey = sha1(__CLASS__ . '::_getClicksCount' . get_class($this->owner) . $this->owner->campaign_id);
        return $this->deleteFromCache($cacheKey);
    }

    /**
     * @param bool $formatNumber
     * @return int|string
     */
    public function getClicksCount($formatNumber = false)
    {
        if ($formatNumber) {
            return $this->format($this->_getClicksCount());
        }

        return $this->_getClicksCount();
    }

    /**
     * @param bool $formatNumber
     * @return float|string
     */
    public function getClicksRate($formatNumber = false)
    {
        if ($formatNumber) {
            return $this->format($this->_getClicksRate());
        }

        return $this->_getClicksRate();
    }

    /**
     * @return bool|mixed
     */
    public function deleteUniqueClicksCountCache()
    {
        $cacheKey = sha1(__CLASS__ . '::_getUniqueClicksCount' . get_class($this->owner) . $this->owner->campaign_id);
        return $this->deleteFromCache($cacheKey);
    }

    /**
     * @param bool $formatNumber
     * @return int|string
     */
    public function getUniqueClicksCount($formatNumber = false)
    {
        if ($formatNumber) {
            return $this->format($this->_getUniqueClicksCount());
        }

        return $this->_getUniqueClicksCount();
    }

    /**
     * @param bool $formatNumber
     * @return float|string
     */
    public function getUniqueClicksRate($formatNumber = false)
    {
        if ($formatNumber) {
            return $this->format($this->_getUniqueClicksRate());
        }

        return $this->_getUniqueClicksRate();
    }

    /**
     * @param bool $formatNumber
     * @return float|string
     */
    public function getClicksThroughRate($formatNumber = false)
    {
        $ctr = 0.0;

        if ((int)$this->getUniqueClicksCount() > 0 && (int)$this->getDeliverySuccessCount() > 0) {
            $ctr = ((int)$this->getUniqueClicksCount() / (int)$this->getDeliverySuccessCount()) * 100;
        }

        if ($formatNumber) {
            return $this->format($ctr);
        }

        return (float)$ctr;
    }

    /**
     * @return CompanyType|null
     */
    public function getIndustry(): ?CompanyType
    {
        if ($this->_industry !== null) {
            return $this->_industry;
        }

        $cmp = $this->owner;

        if (!empty($cmp->customer) && !empty($cmp->customer->company) && !empty($cmp->customer->company->type_id)) {
            $this->_industry = $cmp->customer->company->type;
        }

        return $this->_industry;
    }

    /**
     * @param bool $formatNumber
     *
     * @return float|null|string
     * @throws CException
     */
    public function getIndustryOpensRate($formatNumber = false)
    {
        if ($formatNumber) {
            return $this->format($this->_getIndustryOpensRate());
        }

        return (float)$this->_getIndustryOpensRate();
    }

    /**
     * @param bool $formatNumber
     *
     * @return int|string
     * @throws CException
     */
    public function getIndustryProcessedCount(bool $formatNumber = false)
    {
        if ($formatNumber) {
            return $this->format($this->_getIndustryProcessedCount());
        }

        return $this->_getIndustryProcessedCount();
    }

    /**
     * @return int
     * @throws CException
     */
    public function _getIndustryProcessedCount(): int
    {
        if ($this->_industryProcessedCount !== null) {
            return (int)$this->_industryProcessedCount;
        }
        $this->_industryProcessedCount = 0;

        $industry = $this->getIndustry();
        if (!$industry) {
            return (int)$this->_industryProcessedCount;
        }

        $owner = $this->owner;

        // 1.4.8
        if ($owner->getIsSent() && !empty($owner->option) && $owner->option->processed_count >= 0) {
            return $this->_industryProcessedCount = $owner->option->industry_processed_count >= 0 ? (int)$owner->option->industry_processed_count : 0;
        }

        $cacheKey = sha1(__METHOD__ . $industry->type_id);
        // @phpstan-ignore-next-line
        if (($this->_industryProcessedCount = $this->getFromCache($cacheKey)) !== false) {
            return (int)$this->_industryProcessedCount;
        }

        $cdlModel = $this->owner->getDeliveryLogsArchived() ? CampaignDeliveryLogArchive::model() : CampaignDeliveryLog::model();
        $command = db()->createCommand('
                SELECT COUNT(*) AS counter FROM `{{customer_company}}` cc 
                INNER JOIN `{{campaign}}` c ON cc.customer_id = c.customer_id
                INNER JOIN `' . $cdlModel->tableName() . '` cdl ON cdl.campaign_id = c.campaign_id
                WHERE cc.type_id = :type_id AND c.status = :status AND cdl.status = :cdl_status
            ');

        $row = $command->queryRow(true, [
            ':type_id'    => $industry->type_id,
            ':status'     => Campaign::STATUS_SENT,
            ':cdl_status' => CampaignDeliveryLog::STATUS_SUCCESS,
        ]);

        $this->_industryProcessedCount = (int)$row['counter'];

        $this->setInCache($cacheKey, $this->_industryProcessedCount, self::CACHE_MEDIUM);

        return $this->_industryProcessedCount;
    }

    /**
     * @param bool $formatNumber
     *
     * @return int|string
     * @throws CException
     */
    public function getIndustryOpensCount(bool $formatNumber = false)
    {
        if ($formatNumber) {
            return $this->format($this->_getIndustryOpensCount());
        }

        return $this->_getIndustryOpensCount();
    }

    /**
     * @param bool $formatNumber
     *
     * @return float|string
     * @throws CException
     */
    public function getIndustryClicksRate($formatNumber = false)
    {
        if ($formatNumber) {
            return $this->format($this->_getIndustryClicksRate());
        }

        return $this->_getIndustryClicksRate();
    }

    /**
     * @param bool $formatNumber
     *
     * @return int|string
     * @throws CException
     */
    public function getIndustryClicksCount(bool $formatNumber = false)
    {
        if ($formatNumber) {
            return $this->format($this->_getIndustryClicksCount());
        }

        return $this->_getIndustryClicksCount();
    }

    /**
     * @return int
     */
    protected function _getListSubscribers()
    {
        if ($this->_listSubscribers !== null) {
            return (int)$this->_listSubscribers;
        }
        $this->_listSubscribers = 0;

        if (!empty($this->owner->list_id)) {
            $cacheKey = sha1(__METHOD__ . get_class($this->owner->list) . $this->owner->list_id);
            // @phpstan-ignore-next-line
            if (($this->_listSubscribers = $this->getFromCache($cacheKey)) === false) {
                $this->_listSubscribers = (int)$this->owner->list->confirmedSubscribersCount;
                $this->setInCache($cacheKey, $this->_listSubscribers, $this->getCacheDuration());
            }
        }

        return (int)$this->_listSubscribers;
    }

    /**
     * @return int
     * @throws CDbException
     */
    protected function _getSegmentSubscribers()
    {
        if ($this->_segmentSubscribers !== null) {
            return (int)$this->_segmentSubscribers;
        }
        $this->_segmentSubscribers = 0;

        if (!empty($this->owner->segment_id)) {
            $cacheKey = sha1(__METHOD__ . get_class($this->owner->segment) . $this->owner->segment_id);
            // @phpstan-ignore-next-line
            if (($this->_segmentSubscribers = $this->getFromCache($cacheKey)) === false) {
                $this->_segmentSubscribers = (int)$this->owner->segment->countSubscribers();
                $this->setInCache($cacheKey, $this->_segmentSubscribers, $this->getCacheDuration());
            }
        }

        return (int)$this->_segmentSubscribers;
    }

    /**
     * @return int
     * @throws CDbException
     */
    protected function _getSubscribersCount()
    {
        if ($this->_subscribersCount !== null) {
            return (int)$this->_subscribersCount;
        }
        $this->_subscribersCount = 0;

        $cacheKey = sha1(__METHOD__ . get_class($this->owner) . $this->owner->campaign_id);
        // @phpstan-ignore-next-line
        if (($this->_subscribersCount = $this->getFromCache($cacheKey)) === false) {
            $this->_subscribersCount = (int)$this->owner->countSubscribers();
            $this->setInCache($cacheKey, $this->_subscribersCount, $this->getCacheDuration());
        }

        return (int)$this->_subscribersCount;
    }

    /**
     * @return int
     */
    protected function _getProcessedCount()
    {
        if ($this->_processedCount !== null) {
            return (int)$this->_processedCount;
        }
        $this->_processedCount = 0;

        $owner = $this->owner;

        // 1.4.4
        if ($owner->getIsSent() && !empty($owner->option) && $owner->option->processed_count >= 0) {
            return $this->_processedCount = $owner->option->processed_count;
        }

        $criteria = new CDbCriteria();
        $criteria->compare('campaign_id', $owner->campaign_id);
        $cdlModel = $this->owner->getDeliveryLogsArchived() ? CampaignDeliveryLogArchive::model() : CampaignDeliveryLog::model();

        $cacheKey = sha1(__METHOD__ . get_class($cdlModel) . $this->owner->campaign_id);
        // @phpstan-ignore-next-line
        if (($this->_processedCount = $this->getFromCache($cacheKey)) === false) {
            $this->_processedCount = (int)$cdlModel->count($criteria);
            $this->setInCache($cacheKey, $this->_processedCount, $this->getCacheDuration());
        }

        return (int)$this->_processedCount;
    }

    /**
     * @return int
     */
    protected function _getDeliverySuccessCount()
    {
        if ($this->_deliverySuccessCount !== null) {
            return (int)$this->_deliverySuccessCount;
        }
        $this->_deliverySuccessCount = 0;

        $owner = $this->owner;

        // 1.4.8
        if ($owner->getIsSent() && !empty($owner->option) && $owner->option->processed_count >= 0) {
            return $this->_deliverySuccessCount = $owner->option->delivery_success_count >= 0 ? $owner->option->delivery_success_count : 0;
        }

        $criteria = new CDbCriteria();
        $criteria->compare('campaign_id', $this->owner->campaign_id);
        $criteria->compare('status', CampaignDeliveryLog::STATUS_SUCCESS);
        $criteria->compare('delivery_confirmed', CampaignDeliveryLog::TEXT_YES);
        $cdlModel = $this->owner->getDeliveryLogsArchived() ? CampaignDeliveryLogArchive::model() : CampaignDeliveryLog::model();

        $cacheKey = sha1(__METHOD__ . get_class($cdlModel) . $this->owner->campaign_id . CampaignDeliveryLog::STATUS_SUCCESS);
        // @phpstan-ignore-next-line
        if (($this->_deliverySuccessCount = $this->getFromCache($cacheKey)) === false) {
            $this->_deliverySuccessCount = (int)$cdlModel->count($criteria);
            $this->setInCache($cacheKey, $this->_deliverySuccessCount, $this->getCacheDuration());
        }

        if ((int)$this->_deliverySuccessCount > (int)$this->getProcessedCount()) {
            $this->_deliverySuccessCount = (int)$this->getProcessedCount();
        }

        return (int)$this->_deliverySuccessCount;
    }

    /**
     * @return float
     */
    protected function _getDeliverySuccessRate()
    {
        if ($this->_deliverySuccessRate !== null) {
            return (float)$this->_deliverySuccessRate;
        }
        $this->_deliverySuccessRate = 0.0;

        if ($this->getDeliverySuccessCount() > 0 && $this->getProcessedCount() > 0) {
            $this->_deliverySuccessRate = ((int)$this->getDeliverySuccessCount() * 100) / (int)$this->getProcessedCount();
        }

        return (float)$this->_deliverySuccessRate;
    }

    /**
     * @return int
     */
    protected function _getDeliveryErrorCount()
    {
        if ($this->_deliveryErrorCount !== null) {
            return (int)$this->_deliveryErrorCount;
        }
        $this->_deliveryErrorCount = 0;

        $owner = $this->owner;

        // 1.4.8
        if ($owner->getIsSent() && !empty($owner->option) && $owner->option->processed_count >= 0) {
            return $this->_deliveryErrorCount = $owner->option->delivery_error_count >= 0 ? $owner->option->delivery_error_count : 0;
        }

        // since 1.3.6.1
        $criteria = new CDbCriteria();
        $criteria->compare('campaign_id', $this->owner->campaign_id);
        $criteria->compare('status', CampaignDeliveryLog::STATUS_ERROR);
        $criteria->compare('delivery_confirmed', CampaignDeliveryLog::TEXT_YES);
        $cdlModel = $this->owner->getDeliveryLogsArchived() ? CampaignDeliveryLogArchive::model() : CampaignDeliveryLog::model();

        $cacheKey = sha1(__METHOD__ . get_class($cdlModel) . $this->owner->campaign_id . CampaignDeliveryLog::STATUS_ERROR);
        // @phpstan-ignore-next-line
        if (($this->_deliveryErrorCount = $this->getFromCache($cacheKey)) === false) {
            $this->_deliveryErrorCount = (int)$cdlModel->count($criteria);
            $this->setInCache($cacheKey, $this->_deliveryErrorCount, $this->getCacheDuration());
        }

        if ($this->_deliveryErrorCount < 0) {
            $this->_deliveryErrorCount = 0;
        }

        return (int)$this->_deliveryErrorCount;
    }

    /**
     * @return float
     */
    protected function _getDeliveryErrorRate()
    {
        if ($this->_deliveryErrorRate !== null) {
            return (float)$this->_deliveryErrorRate;
        }
        $this->_deliveryErrorRate = 0.0;

        if ($this->getDeliveryErrorCount() > 0 && $this->getProcessedCount() > 0) {
            $this->_deliveryErrorRate = ((int)$this->getDeliveryErrorCount() * 100) / (int)$this->getProcessedCount();
        }

        return (float)$this->_deliveryErrorRate;
    }

    /**
     * @return int
     */
    protected function _getOpensCount()
    {
        if ($this->_opensCount !== null) {
            return (int)$this->_opensCount;
        }
        $this->_opensCount = 0;

        $owner = $this->owner;

        // 1.7.9
        if ($owner->getIsSent() && !empty($owner->option) && $owner->option->opens_count >= 0) {
            return $this->_opensCount = $owner->option->opens_count;
        }

        $cacheKey = sha1(__METHOD__ . get_class($this->owner) . $this->owner->campaign_id . 'opens');
        // @phpstan-ignore-next-line
        if (($this->_opensCount = $this->getFromCache($cacheKey)) === false) {
            $criteria = new CDbCriteria();
            $criteria->compare('campaign_id', (int)$this->owner->campaign_id);
            $this->_opensCount = (int)CampaignTrackOpen::model()->count($criteria);
            $this->setInCache($cacheKey, $this->_opensCount, $this->getCacheDuration());
        }

        return (int)$this->_opensCount;
    }

    /**
     * @return int
     */
    protected function _getUniqueOpensCount()
    {
        if ($this->_uniqueOpensCount !== null) {
            return (int)$this->_uniqueOpensCount;
        }
        $this->_uniqueOpensCount = 0;

        $owner = $this->owner;

        // 1.7.9
        if ($owner->getIsSent() && !empty($owner->option) && $owner->option->opens_count >= 0) {
            return $this->_uniqueOpensCount = $owner->option->unique_opens_count >= 0 ? $owner->option->unique_opens_count : 0;
        }

        $criteria = new CDbCriteria();
        $criteria->select = 'COUNT(DISTINCT(subscriber_id))';
        $criteria->compare('campaign_id', (int)$this->owner->campaign_id);

        $cacheKey = sha1(__METHOD__ . get_class($this->owner) . $this->owner->campaign_id . 'opens-unique');
        // @phpstan-ignore-next-line
        if (($this->_uniqueOpensCount = $this->getFromCache($cacheKey)) === false) {
            $this->_uniqueOpensCount = (int)CampaignTrackOpen::model()->count($criteria);
            $this->setInCache($cacheKey, $this->_uniqueOpensCount, $this->getCacheDuration());
        }

        return (int)$this->_uniqueOpensCount;
    }

    /**
     * @return float
     */
    protected function _getOpensRate()
    {
        if ($this->_opensRate !== null) {
            return (float)$this->_opensRate;
        }
        $this->_opensRate = 0.0;

        $pcnt = (int)$this->getDeliverySuccessCount() - (int)$this->getBouncesCount();
        if ($pcnt <= 0) {
            return $this->_opensRate = 0.0;
        }

        if ((int)$this->getOpensCount() > 0) {
            $this->_opensRate = floatval(((int)$this->getOpensCount() * 100) / $pcnt);
        }

        return (float)$this->_opensRate;
    }

    /**
     * @return float
     */
    protected function _getUniqueOpensRate()
    {
        if ($this->_uniqueOpensRate !== null) {
            return (float)$this->_uniqueOpensRate;
        }
        $this->_uniqueOpensRate = 0.0;

        $pcnt = (int)$this->getDeliverySuccessCount() - (int)$this->getBouncesCount();
        if ($pcnt <= 0) {
            return $this->_uniqueOpensRate = 0.0;
        }

        if ((int)$this->getUniqueOpensCount() > 0) {
            $this->_uniqueOpensRate = floatval(((int)$this->getUniqueOpensCount() * 100) / $pcnt);
        }

        return (float)$this->_uniqueOpensRate;
    }

    /**
     * @return int
     */
    protected function _getBouncesCount()
    {
        if ($this->_bouncesCount !== null) {
            return (int)$this->_bouncesCount;
        }
        $this->_bouncesCount = 0;

        $owner = $this->owner;

        // 1.7.9
        if ($owner->getIsSent() && !empty($owner->option) && $owner->option->bounces_count >= 0) {
            return $this->_bouncesCount = $owner->option->bounces_count;
        }

        $criteria = new CDbCriteria();
        $criteria->compare('campaign_id', (int)$this->owner->campaign_id);

        $cacheKey = sha1(__METHOD__ . get_class($this->owner) . $this->owner->campaign_id . 'bounces');
        // @phpstan-ignore-next-line
        if (($this->_bouncesCount = $this->getFromCache($cacheKey)) === false) {
            $this->_bouncesCount = (int)CampaignBounceLog::model()->count($criteria);
            $this->setInCache($cacheKey, $this->_bouncesCount, $this->getCacheDuration());
        }

        return (int)$this->_bouncesCount;
    }

    /**
     * @return float
     */
    protected function _getBouncesRate()
    {
        if ($this->_bouncesRate !== null) {
            return (float)$this->_bouncesRate;
        }

        if ((int)$this->getBouncesCount() > 0 && (int)$this->getProcessedCount() > 0) {
            $this->_bouncesRate = floatval(((int)$this->getBouncesCount() * 100) / (int)$this->getProcessedCount());
        }

        return (float)$this->_bouncesRate;
    }

    /**
     * @return int
     */
    protected function _getHardBouncesCount()
    {
        if ($this->_hardBouncesCount !== null) {
            return (int)$this->_hardBouncesCount;
        }
        $this->_hardBouncesCount = 0;

        $owner = $this->owner;

        // 1.7.9
        if ($owner->getIsSent() && !empty($owner->option) && $owner->option->bounces_count >= 0) {
            return $this->_hardBouncesCount = $owner->option->hard_bounces_count >= 0 ? $owner->option->hard_bounces_count : 0;
        }

        $criteria = new CDbCriteria();
        $criteria->compare('campaign_id', $this->owner->campaign_id);
        $criteria->compare('bounce_type', CampaignBounceLog::BOUNCE_HARD);

        $cacheKey = sha1(__METHOD__ . get_class($this->owner) . $this->owner->campaign_id . 'bounces-hard');
        // @phpstan-ignore-next-line
        if (($this->_hardBouncesCount = $this->getFromCache($cacheKey)) === false) {
            $this->_hardBouncesCount = (int)CampaignBounceLog::model()->count($criteria);
            $this->setInCache($cacheKey, $this->_hardBouncesCount, $this->getCacheDuration());
        }

        return (int)$this->_hardBouncesCount;
    }

    /**
     * @return float
     */
    protected function _getHardBouncesRate()
    {
        if ($this->_hardBouncesRate !== null) {
            return (float)$this->_hardBouncesRate;
        }
        $this->_hardBouncesRate = 0.0;

        if ((int)$this->getHardBouncesCount() > 0 && (int)$this->getBouncesCount() > 0) {
            $this->_hardBouncesRate = floatval(((int)$this->getHardBouncesCount() * 100) / (int)$this->getBouncesCount());
        }

        return (float)$this->_hardBouncesRate;
    }

    /**
     * @return int
     */
    protected function _getSoftBouncesCount()
    {
        if ($this->_softBouncesCount !== null) {
            return (int)$this->_softBouncesCount;
        }
        $this->_softBouncesCount = 0;

        $owner = $this->owner;

        // 1.7.9
        if ($owner->getIsSent() && !empty($owner->option) && $owner->option->bounces_count >= 0) {
            return $this->_softBouncesCount = $owner->option->soft_bounces_count >= 0 ? $owner->option->soft_bounces_count : 0;
        }

        $criteria = new CDbCriteria();
        $criteria->compare('campaign_id', $this->owner->campaign_id);
        $criteria->compare('bounce_type', CampaignBounceLog::BOUNCE_SOFT);

        $cacheKey = sha1(__METHOD__ . get_class($this->owner) . $this->owner->campaign_id . 'bounces-soft');
        // @phpstan-ignore-next-line
        if (($this->_softBouncesCount = $this->getFromCache($cacheKey)) === false) {
            $this->_softBouncesCount = (int)CampaignBounceLog::model()->count($criteria);
            $this->setInCache($cacheKey, $this->_softBouncesCount, $this->getCacheDuration());
        }

        return (int)$this->_softBouncesCount;
    }

    /**
     * @return float
     */
    protected function _getSoftBouncesRate()
    {
        if ($this->_softBouncesRate !== null) {
            return (float)$this->_softBouncesRate;
        }
        $this->_softBouncesRate = 0.0;

        if ((int)$this->getSoftBouncesCount() > 0 && (int)$this->getBouncesCount() > 0) {
            $this->_softBouncesRate = floatval(((int)$this->getSoftBouncesCount() * 100) / (int)$this->getBouncesCount());
        }

        return (float)$this->_softBouncesRate;
    }

    /**
     * @return int
     */
    protected function _getInternalBouncesCount()
    {
        if ($this->_internalBouncesCount !== null) {
            return (int)$this->_internalBouncesCount;
        }
        $this->_internalBouncesCount = 0;

        $owner = $this->owner;

        // 1.7.9
        if ($owner->getIsSent() && !empty($owner->option) && $owner->option->bounces_count >= 0) {
            return $this->_internalBouncesCount = $owner->option->internal_bounces_count >= 0 ? $owner->option->internal_bounces_count : 0;
        }

        $criteria = new CDbCriteria();
        $criteria->compare('campaign_id', $this->owner->campaign_id);
        $criteria->compare('bounce_type', CampaignBounceLog::BOUNCE_INTERNAL);

        $cacheKey = sha1(__METHOD__ . get_class($this->owner) . $this->owner->campaign_id . 'bounces-internal');
        // @phpstan-ignore-next-line
        if (($this->_internalBouncesCount = $this->getFromCache($cacheKey)) === false) {
            $this->_internalBouncesCount = (int)CampaignBounceLog::model()->count($criteria);
            $this->setInCache($cacheKey, $this->_internalBouncesCount, $this->getCacheDuration());
        }

        return (int)$this->_internalBouncesCount;
    }

    /**
     * @return float
     */
    protected function _getInternalBouncesRate()
    {
        if ($this->_internalBouncesRate !== null) {
            return (float)$this->_internalBouncesRate;
        }
        $this->_internalBouncesRate = 0.0;

        if ((int)$this->getInternalBouncesCount() > 0 && (int)$this->getBouncesCount() > 0) {
            $this->_internalBouncesRate = floatval(((int)$this->getInternalBouncesCount() * 100) / (int)$this->getBouncesCount());
        }

        return (float)$this->_internalBouncesRate;
    }

    /**
     * @return int
     */
    protected function _getUnsubscribesCount()
    {
        if ($this->_unsubscribesCount !== null) {
            return (int)$this->_unsubscribesCount;
        }
        $this->_unsubscribesCount = 0;

        $criteria = new CDbCriteria();
        $criteria->compare('campaign_id', $this->owner->campaign_id);

        $cacheKey = sha1(__METHOD__ . get_class($this->owner) . $this->owner->campaign_id);
        // @phpstan-ignore-next-line
        if (($this->_unsubscribesCount = $this->getFromCache($cacheKey)) === false) {
            $this->_unsubscribesCount = (int)CampaignTrackUnsubscribe::model()->count($criteria);
            $this->setInCache($cacheKey, $this->_unsubscribesCount, $this->getCacheDuration());
        }

        return (int)$this->_unsubscribesCount;
    }

    /**
     * @return float
     */
    protected function _getUnsubscribesRate()
    {
        if ($this->_unsubscribesRate !== null) {
            return (float)$this->_unsubscribesRate;
        }
        $this->_unsubscribesRate = 0.0;

        if ((int)$this->getUnsubscribesCount() > 0 && (int)$this->getProcessedCount() > 0) {
            $this->_unsubscribesRate = floatval(((int)$this->getUnsubscribesCount() * 100) / (int)$this->getProcessedCount());
        }

        return (float)$this->_unsubscribesRate;
    }

    /**
     * @return int
     */
    protected function _getComplaintsCount()
    {
        if ($this->_complaintsCount !== null) {
            return (int)$this->_complaintsCount;
        }
        $this->_complaintsCount = 0;

        $criteria = new CDbCriteria();
        $criteria->compare('campaign_id', $this->owner->campaign_id);

        $cacheKey = sha1(__METHOD__ . get_class($this->owner) . $this->owner->campaign_id);
        // @phpstan-ignore-next-line
        if (($this->_complaintsCount = $this->getFromCache($cacheKey)) === false) {
            $this->_complaintsCount = (int)CampaignComplainLog::model()->count($criteria);
            $this->setInCache($cacheKey, $this->_complaintsCount, $this->getCacheDuration());
        }

        return (int)$this->_complaintsCount;
    }

    /**
     * @return float
     */
    protected function _getComplaintsRate()
    {
        if ($this->_complaintsRate !== null) {
            return (float)$this->_complaintsRate;
        }
        $this->_complaintsRate = 0.0;

        if ((int)$this->getComplaintsCount() > 0 && (int)$this->getProcessedCount() > 0) {
            $this->_complaintsRate = floatval(((int)$this->getComplaintsCount() * 100) / (int)$this->getProcessedCount());
        }

        return (float)$this->_complaintsRate;
    }

    /**
     * @return float
     */
    protected function _getCompletitionRate()
    {
        if ($this->_completitionRate !== null) {
            return (float)$this->_completitionRate;
        }
        $this->_completitionRate = 0.0;

        if ($this->owner->status == Campaign::STATUS_SENT) {
            return $this->_completitionRate = 100.0;
        }

        if ($this->getProcessedCount() > 0 && $this->getSubscribersCount() > 0) {
            $this->_completitionRate = ((int)$this->getProcessedCount() / (int)$this->getSubscribersCount()) * 100;
        }

        // how can this happen?
        if ($this->_completitionRate > 100) {
            $this->_completitionRate = 100.0;
        }

        return (float)$this->_completitionRate;
    }

    /**
     * @return int
     */
    protected function _getTrackingUrlsCount()
    {
        if ($this->_trackingUrlsCount !== null) {
            return (int)$this->_trackingUrlsCount;
        }
        $this->_trackingUrlsCount = 0;

        $criteria = new CDbCriteria();
        $criteria->compare('campaign_id', $this->owner->campaign_id);

        $cacheKey = sha1(__METHOD__ . get_class($this->owner) . $this->owner->campaign_id);
        // @phpstan-ignore-next-line
        if (($this->_trackingUrlsCount = $this->getFromCache($cacheKey)) === false) {
            $this->_trackingUrlsCount = (int)CampaignUrl::model()->count($criteria);
            $this->setInCache($cacheKey, $this->_trackingUrlsCount, $this->getCacheDuration());
        }

        return (int)$this->_trackingUrlsCount;
    }

    /**
     * @return int
     */
    protected function _getClicksCount()
    {
        if ($this->_clicksCount !== null) {
            return (int)$this->_clicksCount;
        }
        $this->_clicksCount = 0;

        $owner = $this->owner;

        // 1.7.9
        if ($owner->getIsSent() && !empty($owner->option) && $owner->option->clicks_count >= 0) {
            return $this->_clicksCount = $owner->option->clicks_count;
        }

        $cacheKey = sha1(__METHOD__ . get_class($this->owner) . $this->owner->campaign_id);
        // @phpstan-ignore-next-line
        if (($this->_clicksCount = $this->getFromCache($cacheKey)) === false) {

            // since 2.1.4, changed from huge IN condition
            $this->_clicksCount = (int)db()->createCommand()
                ->select(['COUNT(*)'])
                ->from(CampaignTrackUrl::model()->tableName() . ' t')
                ->join(CampaignUrl::model()->tableName() . ' u', 'u.url_id = t.url_id')
                ->where('u.campaign_id = :cid', [':cid' => (int)$this->owner->campaign_id])
                ->queryScalar();

            $this->setInCache($cacheKey, $this->_clicksCount, $this->getCacheDuration());
        }

        return (int)$this->_clicksCount;
    }

    /**
     * @return float
     */
    protected function _getClicksRate()
    {
        if ($this->_clicksRate !== null) {
            return (float)$this->_clicksRate;
        }
        $this->_clicksRate = 0.0;

        if ((int)$this->getClicksCount() > 0) {
            $pcnt = (int)$this->getDeliverySuccessCount() - (int)$this->getBouncesCount();
            if ($pcnt <= 0) {
                return $this->_clicksRate = 0.0;
            }
            $this->_clicksRate = floatval(((int)$this->getClicksCount() / $pcnt) * 100);
        }

        if ($this->_clicksRate > 100) {
            $this->_clicksRate = 100.0;
        }

        return (float)$this->_clicksRate;
    }

    /**
     * @return int
     */
    protected function _getUniqueClicksCount()
    {
        if ($this->_uniqueClicksCount !== null) {
            return (int)$this->_uniqueClicksCount;
        }
        $this->_uniqueClicksCount = 0;

        $owner = $this->owner;

        // 1.7.9
        if ($owner->getIsSent() && !empty($owner->option) && $owner->option->clicks_count >= 0) {
            return $this->_uniqueClicksCount = $owner->option->unique_clicks_count >= 0 ? $owner->option->unique_clicks_count : 0;
        }

        $cacheKey = sha1(__METHOD__ . get_class($this->owner) . $this->owner->campaign_id);
        // @phpstan-ignore-next-line
        if (($this->_uniqueClicksCount = $this->getFromCache($cacheKey)) === false) {

            // since 2.1.4, changed from huge IN condition
            $this->_uniqueClicksCount = (int)db()->createCommand()
                ->select(['COUNT(DISTINCT(t.subscriber_id))'])
                ->from(CampaignTrackUrl::model()->tableName() . ' t')
                ->join(CampaignUrl::model()->tableName() . ' u', 'u.url_id = t.url_id')
                ->where('u.campaign_id = :cid', [':cid' => (int)$this->owner->campaign_id])
                ->queryScalar();

            $this->setInCache($cacheKey, $this->_uniqueClicksCount, $this->getCacheDuration());
        }

        return (int)$this->_uniqueClicksCount;
    }

    /**
     * @return float
     */
    protected function _getUniqueClicksRate()
    {
        if ($this->_uniqueClicksRate !== null) {
            return (float)$this->_uniqueClicksRate;
        }
        $this->_uniqueClicksRate = 0.0;

        if ((int)$this->getUniqueClicksCount() > 0) {
            $pcnt = (int)$this->getDeliverySuccessCount() - (int)$this->getBouncesCount();
            if ($pcnt <= 0) {
                return $this->_uniqueClicksRate = 0.0;
            }
            $this->_uniqueClicksRate = floatval(((int)$this->getUniqueClicksCount() / $pcnt) * 100);
        }

        if ($this->_uniqueClicksRate > 100) {
            $this->_uniqueClicksRate = 100.0;
        }

        return (float)$this->_uniqueClicksRate;
    }

    /**
     * @return float
     * @throws CException
     */
    protected function _getIndustryOpensRate()
    {
        if ($this->_industryOpensRate !== null) {
            return (float)$this->_industryOpensRate;
        }
        $this->_industryOpensRate = 0.0;

        $industry = $this->getIndustry();
        if (!$industry) {
            return $this->_industryOpensRate = 0.0;
        }

        $cacheKey = sha1(__METHOD__ . $industry->type_id);
        if (($rate = $this->getFromCache($cacheKey)) !== false) {
            return $this->_industryOpensRate = (float)$rate;
        }

        if ((int)$this->getIndustryOpensCount() > 0 && (int)$this->getIndustryProcessedCount() > 0) {
            $this->_industryOpensRate = floatval(((int)$this->getIndustryOpensCount() * 100) / (int)$this->getIndustryProcessedCount());
        }

        $this->setInCache($cacheKey, $this->_industryOpensRate, self::CACHE_MEDIUM);

        return (float)$this->_industryOpensRate;
    }

    /**
     * @return int
     * @throws CException
     */
    protected function _getIndustryOpensCount(): int
    {
        if ($this->_industryOpensCount !== null) {
            return (int)$this->_industryOpensCount;
        }
        $this->_industryOpensCount = 0;

        $industry = $this->getIndustry();
        if (!$industry) {
            return (int)$this->_industryOpensCount;
        }

        $cacheKey = sha1(__METHOD__ . $industry->type_id);
        // @phpstan-ignore-next-line
        if (($this->_industryOpensCount = $this->getFromCache($cacheKey)) !== false) {
            return (int)$this->_industryOpensCount;
        }

        $command = db()->createCommand('
            SELECT COUNT(DISTINCT cto.campaign_id, cto.subscriber_id) AS counter FROM `{{customer_company}}` cc 
            INNER JOIN `{{campaign}}` c ON cc.customer_id = c.customer_id
            INNER JOIN `{{campaign_track_open}}` cto ON cto.campaign_id = c.campaign_id
            WHERE cc.type_id = :type_id AND c.status = :status
        ');

        $row = $command->queryRow(true, [
            ':type_id' => $industry->type_id,
            ':status'  => Campaign::STATUS_SENT,
        ]);

        $this->_industryOpensCount = (int)$row['counter'];

        $this->setInCache($cacheKey, $this->_industryOpensCount, self::CACHE_MEDIUM);

        return $this->_industryOpensCount;
    }

    /**
     * @return float
     * @throws CException
     */
    protected function _getIndustryClicksRate()
    {
        if ($this->_industryClicksRate !== null) {
            return (float)$this->_industryClicksRate;
        }
        $this->_industryClicksRate = 0.0;

        if (!$this->getIndustry()) {
            return (float)$this->_industryClicksRate;
        }

        $cacheKey = sha1(__METHOD__ . $this->getIndustry()->type_id);
        if (($rate = $this->getFromCache($cacheKey)) !== false) {
            return $this->_industryClicksRate = (float)$rate;
        }

        if ($this->getIndustryClicksCount() > 0 && $this->getIndustryProcessedCount() > 0) {
            $this->_industryClicksRate = ((int)$this->getIndustryClicksCount() / (int)$this->getIndustryProcessedCount()) * 100;
        }

        if ($this->_industryClicksRate > 100) {
            $this->_industryClicksRate = 100.0;
        }

        $this->setInCache($cacheKey, $this->_industryClicksRate, self::CACHE_MEDIUM);

        return (float)$this->_industryClicksRate;
    }

    /**
     * @return int
     * @throws CException
     */
    protected function _getIndustryClicksCount(): int
    {
        if ($this->_industryClicksCount !== null) {
            return (int)$this->_industryClicksCount;
        }
        $this->_industryClicksCount = 0;

        $industry = $this->getIndustry();
        if (!$industry) {
            return (int)$this->_industryClicksCount;
        }

        $cacheKey = sha1(__METHOD__ . $industry->type_id);
        // @phpstan-ignore-next-line
        if (($this->_industryClicksCount = $this->getFromCache($cacheKey)) !== false) {
            return (int)$this->_industryClicksCount;
        }

        $command = db()->createCommand('
            SELECT COUNT(DISTINCT cu.campaign_id, ctu.subscriber_id) AS counter FROM `{{customer_company}}` cc 
            INNER JOIN `{{campaign}}` c ON cc.customer_id = c.customer_id
            INNER JOIN `{{campaign_url}}` cu ON cu.campaign_id = c.campaign_id
            INNER JOIN `{{campaign_track_url}}` ctu ON ctu.url_id = cu.url_id
            WHERE cc.type_id = :type_id AND c.status = :status
        ');

        $row = $command->queryRow(true, [
            ':type_id' => $industry->type_id,
            ':status'  => Campaign::STATUS_SENT,
        ]);

        $this->_industryClicksCount = (int)$row['counter'];

        $this->setInCache($cacheKey, $this->_industryClicksCount, self::CACHE_MEDIUM);

        return $this->_industryClicksCount;
    }

    /**
     * @param mixed $number
     * @return string
     */
    protected function format($number)
    {
        if (!is_numeric($number)) {
            $number = (int)$number;
        }
        return formatter()->formatNumber($number);
    }

    /**
     * @return int
     */
    protected function getCacheDuration()
    {
        if ($this->owner->status != Campaign::STATUS_SENT) {
            return 60;
        }

        $finishedAt = $this->owner->finished_at;
        if (empty($finishedAt)) {
            return 60;
        }

        $diff = time() - (int)strtotime((string)$finishedAt);

        if ($diff <= (3600 * 24)) {
            return self::CACHE_SHORT;
        }

        return 3600;
    }
}
