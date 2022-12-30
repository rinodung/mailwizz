<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * DateTimeFormatterBehavior
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

/**
 * @property ActiveRecord $owner
 */
class DateTimeFormatterBehavior extends CActiveRecordBehavior
{
    /**
     * @var string
     */
    public $dateAddedAttribute = 'date_added';

    /**
     * @var string
     */
    public $lastUpdatedAttribute = 'last_updated';

    /**
     * @var string
     */
    private $_timeZone = '';

    /**
     * @return string
     * @throws Exception
     */
    public function getDateAdded()
    {
        $dateAdded = $this->owner->hasAttribute($this->dateAddedAttribute) ? $this->owner->getAttribute($this->dateAddedAttribute) : '';
        return $this->formatLocalizedDateTime($dateAdded);
    }

    /**
     * @return string
     * @throws Exception
     */
    public function getLastUpdated()
    {
        $lastUpdated = $this->owner->hasAttribute($this->lastUpdatedAttribute) ? $this->owner->getAttribute($this->lastUpdatedAttribute) : '';
        return $this->formatLocalizedDateTime($lastUpdated);
    }

    /**
     * @param mixed $dateTimeValue
     * @param mixed $inFormat
     * @param mixed $dateWidth
     * @param mixed $timeWidth
     *
     * @return string
     * @throws Exception
     */
    public function formatLocalizedDateTime($dateTimeValue = null, $inFormat = null, $dateWidth = null, $timeWidth = null)
    {
        $dateWidth = is_null($dateWidth) ? 'short' : (string)$dateWidth;
        $timeWidth = is_null($timeWidth) ? 'short' : (string)$timeWidth;
        return dateFormatter()->formatDateTime($this->convertDateTime($dateTimeValue, $inFormat), $dateWidth, $timeWidth);
    }

    /**
     * @param mixed $dateValue
     * @param mixed $inFormat
     * @param mixed $dateWidth
     *
     * @return string
     * @throws Exception
     */
    public function formatLocalizedDate($dateValue = null, $inFormat = null, $dateWidth = null)
    {
        $dateWidth = is_null($dateWidth) ? 'short' : (string)$dateWidth;
        return dateFormatter()->formatDateTime($this->convertDate($dateValue, $inFormat), $dateWidth, '');
    }

    /**
     * @param mixed $dateTimeValue
     * @param mixed $inFormat
     * @param mixed $timeWidth
     *
     * @return string
     * @throws Exception
     */
    public function formatLocalizedTime($dateTimeValue = null, $inFormat = null, $timeWidth = null)
    {
        $timeWidth = is_null($timeWidth) ? 'short' : (string)$timeWidth;
        return dateFormatter()->formatDateTime($this->convertDateTime($dateTimeValue, $inFormat), '', $timeWidth);
    }

    /**
     * @param mixed $dateTimeValue
     * @param mixed $inFormat
     * @param mixed $outFormat
     *
     * @return string
     * @throws Exception
     */
    public function formatDateTime($dateTimeValue = null, $inFormat = null, $outFormat = null)
    {
        $outFormat  = is_null($outFormat) ? 'yyyy-MM-dd HH:mm:ss' : (string)$outFormat;
        return dateFormatter()->format($outFormat, $this->convertDateTime($dateTimeValue, $inFormat));
    }

    /**
     * @param mixed $utcDateTimeValue
     * @param mixed $inFormat
     * @param mixed $outFormat
     *
     * @return string
     * @throws Exception
     */
    public function convertDateTime($utcDateTimeValue = null, $inFormat = null, $outFormat = null)
    {
        $utcDateTimeValue  = is_null($utcDateTimeValue) ? date('Y-m-d H:i:s') : (string)$utcDateTimeValue;
        $utcDateTimeValue  = ($utcDateTimeValue === 'NOW()') ? date('Y-m-d H:i:s') : (string)$utcDateTimeValue;
        $inFormat          = is_null($inFormat) ? 'yyyy-MM-dd HH:mm:ss' : (string)$inFormat;
        $outFormat         = is_null($outFormat) ? 'yyyy-MM-dd HH:mm:ss' : (string)$outFormat;
        $dateFormatter     = dateFormatter();
        $utcDateTimeValue  = $dateFormatter->format('yyyy-MM-dd HH:mm:ss', CDateTimeParser::parse($utcDateTimeValue, $inFormat));

        if (($this->getTimeZone())) {
            $dateTime = new DateTime($utcDateTimeValue);
            $dateTime->setTimezone(new DateTimeZone($this->getTimeZone()));
            $utcDateTimeValue = $dateTime->format('Y-m-d H:i:s');
        }

        return $dateFormatter->format($outFormat, $utcDateTimeValue);
    }

    /**
     * @param mixed $utcDateValue
     * @param mixed $inFormat
     * @param mixed $outFormat
     *
     * @return string
     * @throws Exception
     */
    public function convertDate($utcDateValue = null, $inFormat = null, $outFormat = null)
    {
        $utcDateValue  = is_null($utcDateValue) ? date('Y-m-d') : $utcDateValue;
        $utcDateValue  = ($utcDateValue === 'NOW()') ? date('Y-m-d') : $utcDateValue;
        $inFormat      = is_null($inFormat) ? 'yyyy-MM-dd' : $inFormat;
        $outFormat     = is_null($outFormat) ? 'yyyy-MM-dd' : $outFormat;

        return $this->convertDateTime($utcDateValue, $inFormat, $outFormat);
    }

    /**
     * @return string
     */
    public function getTimeZone()
    {
        if (!empty($this->_timeZone)) {
            return $this->_timeZone;
        }

        /** @var ActiveRecord $owner */
        $owner = $this->owner;

        if ($owner->getIsNewRecord()) {
            return $this->_timeZone = '';
        }

        // 1.8.5
        if ($owner->hasAttribute('timezone') && !empty($owner->timezone)) {
            return $this->_timeZone = (string)$owner->timezone;
        }

        if (apps()->isAppName('backend') && app()->hasComponent('user') && user()->getId() > 0) {

            /** @var User $user */
            $user = user()->getModel();

            return $this->_timeZone = (string)$user->timezone;
        }

        if ($owner->hasAttribute('user_id') && !empty($owner->user_id) && !empty($owner->user)) {

            /** @var User $user */
            $user = $owner->user;

            return $this->_timeZone = (string)$user->timezone;
        }

        if (apps()->isAppName('customer') && app()->hasComponent('customer') && customer()->getId() > 0) {

            /** @var Customer $customer */
            $customer = customer()->getModel();

            return $this->_timeZone = (string)$customer->timezone;
        }

        if ($owner->hasAttribute('customer_id') && !empty($owner->customer_id) && !empty($owner->customer)) {

            /** @var Customer $customer */
            $customer = $owner->customer;

            return $this->_timeZone = (string)$customer->timezone;
        }

        // since 1.9.12
        if (apps()->isAppName('frontend')) {
            if (app()->hasComponent('customer') && customer()->getId() > 0) {

                /** @var Customer $customer */
                $customer = customer()->getModel();

                return $this->_timeZone = (string)$customer->timezone;
            }

            if (app()->hasComponent('user') && user()->getId() > 0) {

                /** @var User $user */
                $user = user()->getModel();

                return $this->_timeZone = (string)$user->timezone;
            }

            if ($owner->hasAttribute('list_id') && !empty($owner->list) && !empty($owner->list->customer_id) && !empty($owner->list->customer)) {

                /** @var Lists $list */
                $list = $owner->list;

                /** @var Customer $customer */
                $customer = $list->customer;

                return $this->_timeZone = (string)$customer->timezone;
            }

            if ($owner->hasAttribute('campaign_id') && !empty($owner->campaign) && !empty($owner->campaign->customer_id) && !empty($owner->campaign->customer)) {

                /** @var Campaign $campaign */
                $campaign = $owner->campaign;

                /** @var Customer $customer */
                $customer = $campaign->customer;

                return $this->_timeZone = (string)$customer->timezone;
            }

            if (
                $owner->hasAttribute('subscriber_id') && !empty($owner->subscriber) &&
                !empty($owner->subscriber->list_id) && !empty($owner->subscriber->list) &&
                !empty($owner->subscriber->list->customer_id) && !empty($owner->subscriber->list->customer)
            ) {

                /** @var ListSubscriber $subscriber */
                $subscriber = $owner->subscriber;

                /** @var Lists $list */
                $list = $subscriber->list;

                /** @var Customer $customer */
                $customer = $list->customer;

                return $this->_timeZone = (string)$customer->timezone;
            }
        }

        return $this->_timeZone = '';
    }

    /**
     * DateTimeFormatterBehavior::setTimeZone()
     *
     * @param string $value
     * @return DateTimeFormatterBehavior
     */
    public function setTimeZone($value)
    {
        $this->_timeZone = $value;
        return $this;
    }
}
