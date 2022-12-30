<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * CustomerActionLogBehavior
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

/**
 * @property Customer $owner
 */
class CustomerActionLogBehavior extends CBehavior
{
    /**
     * @param CComponent $owner
     *
     * @return void
     * @throws CException
     */
    public function attach($owner)
    {
        if (!($owner instanceof Customer)) {
            throw new CException(t('customers', 'The {className} behavior can only be attach to a Customer model', [
                '{className}' => get_class($this),
            ]));
        }
        parent::attach($owner);
    }

    /**
     * @param Lists $list
     *
     * @return bool
     */
    public function listCreated(Lists $list): bool
    {
        if (empty($this->owner->customer_id)) {
            return false;
        }

        /** @var OptionUrl $optionUrl */
        $optionUrl = container()->get(OptionUrl::class);

        $url      = $optionUrl->getCustomerUrl(sprintf('lists/%s/overview', $list->list_uid));
        $message  = 'The list "{listName}" has been successfully created!';
        $listLink = CHtml::link($list->name, $url);
        $message  = t('lists', $message, ['{listName}' => $listLink]);

        $model = new CustomerActionLog();
        $model->customer_id = $this->owner->customer_id;
        $model->category = CustomerActionLog::CATEGORY_LISTS_CREATED;
        $model->reference_id = (int)$list->list_id;
        $model->message = $message;
        return $model->save();
    }

    /**
     * @param Lists $list
     *
     * @return bool
     */
    public function listUpdated(Lists $list): bool
    {
        if (empty($this->owner->customer_id)) {
            return false;
        }

        /** @var OptionUrl $optionUrl */
        $optionUrl = container()->get(OptionUrl::class);

        $url      = $optionUrl->getCustomerUrl(sprintf('lists/%s/overview', $list->list_uid));
        $message  = 'The list "{listName}" has been successfully updated!';
        $listLink = CHtml::link($list->name, $url);
        $message  = t('lists', $message, ['{listName}' => $listLink]);

        $model = new CustomerActionLog();
        $model->customer_id = $this->owner->customer_id;
        $model->category = CustomerActionLog::CATEGORY_LISTS_UPDATED;
        $model->reference_id = (int)$list->list_id;
        $model->message = $message;
        return $model->save();
    }

    /**
     * @param Lists $list
     * @param ListImportAbstract $import
     *
     * @return bool
     */
    public function listImportStart(Lists $list, ListImportAbstract $import): bool
    {
        if (empty($this->owner->customer_id)) {
            return false;
        }

        /** @var OptionUrl $optionUrl */
        $optionUrl = container()->get(OptionUrl::class);

        $url      = $optionUrl->getCustomerUrl(sprintf('lists/%s/overview', $list->list_uid));
        $message  = 'The import process for list "{listName}" has successfully started, counting {rowsCount} records!';
        $listLink = CHtml::link($list->name, $url);
        $message  = t('list_import', $message, ['{listName}' => $listLink, '{rowsCount}' => $import->rows_count]);

        $model = new CustomerActionLog();
        $model->customer_id = $this->owner->customer_id;
        $model->category = CustomerActionLog::CATEGORY_LISTS_IMPORT_START;
        $model->reference_id = (int)$list->list_id;
        $model->message = $message;
        return $model->save();
    }

    /**
     * @param Lists $list
     * @param ListImportAbstract $import
     *
     * @return bool
     */
    public function listImportEnd(Lists $list, ListImportAbstract $import): bool
    {
        if (empty($this->owner->customer_id)) {
            return false;
        }

        /** @var OptionUrl $optionUrl */
        $optionUrl = container()->get(OptionUrl::class);

        $url        = $optionUrl->getCustomerUrl(sprintf('lists/%s/overview', $list->list_uid));
        $message    = 'The import process for list "{listName}" has successfully ended, processing {rowsCount} records!';
        $listLink   = CHtml::link($list->name, $url);
        $message    = t('list_import', $message, ['{listName}' => $listLink, '{rowsCount}' => $import->rows_count]);

        $model = new CustomerActionLog();
        $model->customer_id = $this->owner->customer_id;
        $model->category = CustomerActionLog::CATEGORY_LISTS_IMPORT_END;
        $model->reference_id = (int)$list->list_id;
        $model->message = $message;
        return $model->save();
    }

    /**
     * @param Lists $list
     * @param ListCsvExport $export
     *
     * @return bool
     */
    public function listExportStart(Lists $list, ListCsvExport $export): bool
    {
        if (empty($this->owner->customer_id)) {
            return false;
        }

        /** @var OptionUrl $optionUrl */
        $optionUrl = container()->get(OptionUrl::class);

        $url      = $optionUrl->getCustomerUrl(sprintf('lists/%s/overview', $list->list_uid));
        $message  = 'The export process for list "{listName}" has successfully started, counting {rowsCount} records!';
        $listLink = CHtml::link($list->name, $url);
        $message  = t('list_export', $message, ['{listName}' => $listLink, '{rowsCount}' => $export->count]);

        $model = new CustomerActionLog();
        $model->customer_id = $this->owner->customer_id;
        $model->category = CustomerActionLog::CATEGORY_LISTS_EXPORT_START;
        $model->reference_id = (int)$list->list_id;
        $model->message = $message;
        return $model->save();
    }

    /**
     * @param Lists $list
     * @param ListCsvExport $export
     *
     * @return bool
     */
    public function listExportEnd(Lists $list, ListCsvExport $export): bool
    {
        if (empty($this->owner->customer_id)) {
            return false;
        }

        /** @var OptionUrl $optionUrl */
        $optionUrl = container()->get(OptionUrl::class);

        $url      = $optionUrl->getCustomerUrl(sprintf('lists/%s/overview', $list->list_uid));
        $message  = 'The export process for list "{listName}" has successfully ended, processing {rowsCount} records!';
        $listLink = CHtml::link($list->name, $url);
        $message  = t('list_export', $message, ['{listName}' => $listLink, '{rowsCount}' => $export->count]);

        $model = new CustomerActionLog();
        $model->customer_id = $this->owner->customer_id;
        $model->category = CustomerActionLog::CATEGORY_LISTS_EXPORT_END;
        $model->reference_id = (int)$list->list_id;
        $model->message = $message;
        return $model->save();
    }

    /**
     * @param Lists $list
     *
     * @return bool
     */
    public function listDeleted(Lists $list): bool
    {
        if (empty($this->owner->customer_id)) {
            return false;
        }

        // remove list logs
        $criteria = new CDbCriteria();
        $criteria->compare('customer_id', (int)$this->owner->customer_id);
        $criteria->addInCondition('category', [
            CustomerActionLog::CATEGORY_LISTS_CREATED,
            CustomerActionLog::CATEGORY_LISTS_UPDATED,
            CustomerActionLog::CATEGORY_LISTS_IMPORT_START,
            CustomerActionLog::CATEGORY_LISTS_IMPORT_END,
            CustomerActionLog::CATEGORY_LISTS_EXPORT_START,
            CustomerActionLog::CATEGORY_LISTS_EXPORT_END,
        ]);
        $criteria->compare('reference_id', (int)$list->list_id);
        CustomerActionLog::model()->deleteAll($criteria);

        // remove references
        $criteria = new CDbCriteria();
        $criteria->compare('customer_id', (int)$this->owner->customer_id);
        $criteria->addInCondition('category', [
            // segments
            CustomerActionLog::CATEGORY_LISTS_SEGMENT_CREATED,
            CustomerActionLog::CATEGORY_LISTS_SEGMENT_UPDATED,
            CustomerActionLog::CATEGORY_LISTS_SEGMENT_DELETED,
            // campaigns
            CustomerActionLog::CATEGORY_LISTS_CAMPAIGNS_CREATED,
            CustomerActionLog::CATEGORY_LISTS_CAMPAIGNS_UPDATED,
            CustomerActionLog::CATEGORY_LISTS_CAMPAIGNS_SCHEDULED,
            CustomerActionLog::CATEGORY_LISTS_CAMPAIGNS_SENT,
            CustomerActionLog::CATEGORY_LISTS_CAMPAIGNS_DELETED,
            CustomerActionLog::CATEGORY_LISTS_SEGMENT_CAMPAIGNS_CREATED,
            CustomerActionLog::CATEGORY_LISTS_SEGMENT_CAMPAIGNS_UPDATED,
            CustomerActionLog::CATEGORY_LISTS_SEGMENT_CAMPAIGNS_SCHEDULED,
            CustomerActionLog::CATEGORY_LISTS_SEGMENT_CAMPAIGNS_SENT,
            CustomerActionLog::CATEGORY_LISTS_SEGMENT_CAMPAIGNS_DELETED,
            // subscribers
            CustomerActionLog::CATEGORY_LISTS_SUBSCRIBERS_CREATED,
            CustomerActionLog::CATEGORY_LISTS_SUBSCRIBERS_UPDATED,
            CustomerActionLog::CATEGORY_LISTS_SUBSCRIBERS_DELETED,
            CustomerActionLog::CATEGORY_LISTS_SUBSCRIBERS_UNSUBSCRIBED,
        ]);
        $criteria->compare('reference_relation_id', (int)$list->list_id);
        CustomerActionLog::model()->deleteAll($criteria);

        // add logs
        $message = 'The list "{listName}" has been successfully deleted!';
        $message = t('lists', $message, ['{listName}' => $list->name]);

        $model = new CustomerActionLog();
        $model->customer_id = $this->owner->customer_id;
        $model->category = CustomerActionLog::CATEGORY_LISTS_DELETED;
        $model->message = $message;
        return $model->save();
    }

    /**
     * @param ListSegment $segment
     *
     * @return bool
     */
    public function segmentCreated(ListSegment $segment): bool
    {
        if (empty($this->owner->customer_id)) {
            return false;
        }

        /** @var OptionUrl $optionUrl */
        $optionUrl = container()->get(OptionUrl::class);

        $segUrl = $optionUrl->getCustomerUrl(sprintf('lists/%s/segments/%s/update', $segment->list->list_uid, $segment->segment_uid));
        $url    = $optionUrl->getCustomerUrl(sprintf('lists/%s/overview', $segment->list->list_uid));

        $message  = 'A new segment called "{segmentName}" has been added to the list "{listName}" successfully!';
        $segmLink = CHtml::link($segment->name, $segUrl);
        $listLink = CHtml::link($segment->list->name, $url);
        $message  = t('list_segments', $message, ['{segmentName}' => $segmLink, '{listName}' => $listLink]);

        $model = new CustomerActionLog();
        $model->customer_id = $this->owner->customer_id;
        $model->category = CustomerActionLog::CATEGORY_LISTS_SEGMENT_CREATED;
        $model->reference_id = (int)$segment->segment_id;
        $model->reference_relation_id = (int)$segment->list_id;
        $model->message = $message;
        return $model->save();
    }

    /**
     * @param ListSegment $segment
     *
     * @return bool
     */
    public function segmentUpdated(ListSegment $segment): bool
    {
        /** @var OptionUrl $optionUrl */
        $optionUrl = container()->get(OptionUrl::class);

        $segUrl = $optionUrl->getCustomerUrl(sprintf('lists/%s/segments/%s/update', $segment->list->list_uid, $segment->segment_uid));
        $url    = $optionUrl->getCustomerUrl(sprintf('lists/%s/overview', $segment->list->list_uid));

        $message  = 'The segment called "{segmentName}" belonging to the list "{listName}" has been successfully updated!';
        $segmLink = CHtml::link($segment->name, $segUrl);
        $listLink = CHtml::link($segment->list->name, $url);
        $message  = t('list_segments', $message, ['{segmentName}' => $segmLink, '{listName}' => $listLink]);

        $model = new CustomerActionLog();
        $model->customer_id = $this->owner->customer_id;
        $model->category = CustomerActionLog::CATEGORY_LISTS_SEGMENT_UPDATED;
        $model->reference_id = (int)$segment->segment_id;
        $model->reference_relation_id = (int)$segment->list_id;
        $model->message = $message;
        return $model->save();
    }

    /**
     * @param ListSegment $segment
     *
     * @return bool
     */
    public function segmentDeleted(ListSegment $segment): bool
    {
        if (empty($this->owner->customer_id)) {
            return false;
        }

        // remove segment logs
        $criteria = new CDbCriteria();
        $criteria->compare('customer_id', (int)$this->owner->customer_id);
        $criteria->addInCondition('category', [
            CustomerActionLog::CATEGORY_LISTS_SEGMENT_CREATED,
            CustomerActionLog::CATEGORY_LISTS_SEGMENT_UPDATED,
        ]);
        $criteria->compare('reference_id', (int)$segment->segment_id);
        $criteria->compare('reference_relation_id', (int)$segment->list_id);
        CustomerActionLog::model()->deleteAll($criteria);

        // remove segment campaigns logs
        $criteria = new CDbCriteria();
        $criteria->compare('customer_id', (int)$this->owner->customer_id);
        $criteria->addInCondition('category', [
            CustomerActionLog::CATEGORY_LISTS_SEGMENT_CAMPAIGNS_CREATED,
            CustomerActionLog::CATEGORY_LISTS_SEGMENT_CAMPAIGNS_UPDATED,
            CustomerActionLog::CATEGORY_LISTS_SEGMENT_CAMPAIGNS_SCHEDULED,
            CustomerActionLog::CATEGORY_LISTS_SEGMENT_CAMPAIGNS_SENT,
            CustomerActionLog::CATEGORY_LISTS_SEGMENT_CAMPAIGNS_DELETED,
        ]);
        $criteria->compare('reference_relation_id', (int)$segment->segment_id);
        CustomerActionLog::model()->deleteAll($criteria);

        $message = 'The segment {segmentName} belonging to the list "{listName}" has been successfully deleted!';
        $message = t('list_segments', $message, ['{segmentName}' => $segment->name, '{listName}' => $segment->list->name]);

        $model = new CustomerActionLog();
        $model->customer_id = $this->owner->customer_id;
        $model->category = CustomerActionLog::CATEGORY_LISTS_SEGMENT_DELETED;
        $model->message = $message;
        return $model->save();
    }

    /**
     * @param Campaign $campaign
     *
     * @return bool
     */
    public function campaignCreated(Campaign $campaign): bool
    {
        if (empty($this->owner->customer_id)) {
            return false;
        }

        /** @var OptionUrl $optionUrl */
        $optionUrl = container()->get(OptionUrl::class);

        $url          = $optionUrl->getCustomerUrl(sprintf('campaigns/%s/overview', $campaign->campaign_uid));
        $message      = 'A new campaign({type}) called "{campaignName}" has been created successfully!';
        $campaignLink = CHtml::link($campaign->name, $url);
        $message      = t('campaigns', $message, ['{type}' => $campaign->type, '{campaignName}' => $campaignLink]);

        $model = new CustomerActionLog();
        $model->customer_id = $this->owner->customer_id;
        $model->category = empty($campaign->segment_id) ? CustomerActionLog::CATEGORY_LISTS_CAMPAIGNS_CREATED : CustomerActionLog::CATEGORY_LISTS_SEGMENT_CAMPAIGNS_CREATED;
        $model->reference_id = (int)$campaign->campaign_id;
        $model->reference_relation_id = (int)$campaign->list_id;
        $model->message = $message;
        return $model->save();
    }

    /**
     * @param Campaign $campaign
     *
     * @return bool
     */
    public function campaignUpdated(Campaign $campaign): bool
    {
        if (empty($this->owner->customer_id)) {
            return false;
        }

        /** @var OptionUrl $optionUrl */
        $optionUrl = container()->get(OptionUrl::class);

        $url          = $optionUrl->getCustomerUrl(sprintf('campaigns/%s/overview', $campaign->campaign_uid));
        $message      = 'The campaign({type}) called "{campaignName}" has been updated!';
        $campaignLink = CHtml::link($campaign->name, $url);
        $message      = t('campaigns', $message, ['{type}' => $campaign->type, '{campaignName}' => $campaignLink]);

        $model = new CustomerActionLog();
        $model->customer_id = $this->owner->customer_id;
        $model->category = empty($campaign->segment_id) ? CustomerActionLog::CATEGORY_LISTS_CAMPAIGNS_UPDATED : CustomerActionLog::CATEGORY_LISTS_SEGMENT_CAMPAIGNS_UPDATED;
        $model->reference_id = (int)$campaign->campaign_id;
        $model->reference_relation_id = (int)$campaign->list_id;
        if (!empty($campaign->segment_id)) {
            $model->reference_relation_id = (int)$campaign->segment_id;
        }
        $model->message = $message;
        return $model->save();
    }

    /**
     * @param Campaign $campaign
     *
     * @return bool
     */
    public function campaignScheduled(Campaign $campaign): bool
    {
        if (empty($this->owner->customer_id)) {
            return false;
        }

        /** @var OptionUrl $optionUrl */
        $optionUrl = container()->get(OptionUrl::class);

        $url          = $optionUrl->getCustomerUrl(sprintf('campaigns/%s/overview', $campaign->campaign_uid));
        $message      = 'The campaign({type}) called "{campaignName}" has been scheduled for sending!';
        $campaignLink = CHtml::link($campaign->name, $url);
        $message      = t('campaigns', $message, ['{type}' => $campaign->type, '{campaignName}' => $campaignLink]);

        $model = new CustomerActionLog();
        $model->customer_id = $this->owner->customer_id;
        $model->category = empty($campaign->segment_id) ? CustomerActionLog::CATEGORY_LISTS_CAMPAIGNS_SCHEDULED : CustomerActionLog::CATEGORY_LISTS_SEGMENT_CAMPAIGNS_SCHEDULED;
        $model->reference_id = (int)$campaign->campaign_id;
        $model->reference_relation_id = (int)$campaign->list_id;
        $model->message = $message;
        return $model->save();
    }

    /**
     * @param Campaign $campaign
     *
     * @return bool
     */
    public function campaignSent(Campaign $campaign): bool
    {
        if (empty($this->owner->customer_id)) {
            return false;
        }

        /** @var OptionUrl $optionUrl */
        $optionUrl = container()->get(OptionUrl::class);

        $url          = $optionUrl->getCustomerUrl(sprintf('campaigns/%s/overview', $campaign->campaign_uid));
        $message      = 'The campaign({type}) called "{campaignName}" has been sent!';
        $campaignLink = CHtml::link($campaign->name, $url);
        $message      = t('campaigns', $message, ['{type}' => $campaign->type, '{campaignName}' => $campaignLink]);

        $model = new CustomerActionLog();
        $model->customer_id = $this->owner->customer_id;
        $model->category = empty($campaign->segment_id) ? CustomerActionLog::CATEGORY_LISTS_CAMPAIGNS_SENT : CustomerActionLog::CATEGORY_LISTS_SEGMENT_CAMPAIGNS_SENT;
        $model->reference_id = (int)$campaign->campaign_id;
        $model->reference_relation_id = (int)$campaign->list_id;
        $model->message = $message;
        return $model->save();
    }

    /**
     * @param Campaign $campaign
     *
     * @return bool
     */
    public function campaignDeleted(Campaign $campaign): bool
    {
        if (empty($this->owner->customer_id)) {
            return false;
        }

        // remove campaign logs
        $criteria = new CDbCriteria();
        $criteria->compare('customer_id', (int)$this->owner->customer_id);
        $criteria->addInCondition('category', [
            CustomerActionLog::CATEGORY_LISTS_CAMPAIGNS_CREATED,
            CustomerActionLog::CATEGORY_LISTS_CAMPAIGNS_UPDATED,
            CustomerActionLog::CATEGORY_LISTS_CAMPAIGNS_SCHEDULED,
            CustomerActionLog::CATEGORY_LISTS_CAMPAIGNS_SENT,
            CustomerActionLog::CATEGORY_LISTS_SEGMENT_CAMPAIGNS_CREATED,
            CustomerActionLog::CATEGORY_LISTS_SEGMENT_CAMPAIGNS_UPDATED,
            CustomerActionLog::CATEGORY_LISTS_SEGMENT_CAMPAIGNS_SCHEDULED,
            CustomerActionLog::CATEGORY_LISTS_SEGMENT_CAMPAIGNS_SENT,
        ]);
        $criteria->compare('reference_id', (int)$campaign->campaign_id);

        CustomerActionLog::model()->deleteAll($criteria);

        // add new logs
        $message = 'The campaign({type}) called "{campaignName}" has been successfully deleted!';
        $message = t('campaigns', $message, ['{type}' => $campaign->type, '{campaignName}' => $campaign->name]);

        $model = new CustomerActionLog();
        $model->customer_id = $this->owner->customer_id;
        $model->category = CustomerActionLog::CATEGORY_LISTS_CAMPAIGNS_DELETED;
        $model->reference_id = (int)$campaign->campaign_id;
        $model->reference_relation_id = (int)$campaign->list_id;
        if (!empty($campaign->segment_id)) {
            $model->reference_relation_id = (int)$campaign->segment_id;
        }
        $model->message = $message;
        return $model->save();
    }

    /**
     * @param ListSubscriber $subscriber
     *
     * @return bool
     */
    public function subscriberCreated(ListSubscriber $subscriber): bool
    {
        if (empty($this->owner->customer_id)) {
            return false;
        }

        /** @var OptionUrl $optionUrl */
        $optionUrl = container()->get(OptionUrl::class);

        $listUrl = $optionUrl->getCustomerUrl(sprintf('lists/%s/overview', $subscriber->list->list_uid));
        $subUrl  = $optionUrl->getCustomerUrl(sprintf('lists/%s/subscribers/%s/update', $subscriber->list->list_uid, $subscriber->subscriber_uid));

        $message  = 'A new subscriber having the email address "{email}" has been successfully added to the list "{listName}"!';
        $listLink = CHtml::link($subscriber->list->name, $listUrl);
        $subLink  = CHtml::link($subscriber->getDisplayEmail(), $subUrl);
        $message  = t('list_subscribers', $message, ['{listName}' => $listLink, '{email}' => $subLink]);

        $model = new CustomerActionLog();
        $model->customer_id = $this->owner->customer_id;
        $model->category = CustomerActionLog::CATEGORY_LISTS_SUBSCRIBERS_CREATED;
        $model->reference_id = (int)$subscriber->subscriber_id;
        $model->reference_relation_id = (int)$subscriber->list_id;
        $model->message = $message;
        return $model->save();
    }

    /**
     * @param ListSubscriber $subscriber
     *
     * @return bool
     */
    public function subscriberUpdated(ListSubscriber $subscriber): bool
    {
        if (empty($this->owner->customer_id)) {
            return false;
        }

        /** @var OptionUrl $optionUrl */
        $optionUrl = container()->get(OptionUrl::class);

        $listUrl = $optionUrl->getCustomerUrl(sprintf('lists/%s/overview', $subscriber->list->list_uid));
        $subUrl  = $optionUrl->getCustomerUrl(sprintf('lists/%s/subscribers/%s/update', $subscriber->list->list_uid, $subscriber->subscriber_uid));

        $message  = 'The subscriber having the email address "{email}" has been successfully updated in the "{listName}" list!';
        $listLink = CHtml::link($subscriber->list->name, $listUrl);
        $subLink  = CHtml::link($subscriber->getDisplayEmail(), $subUrl);
        $message  = t('list_subscribers', $message, ['{listName}' => $listLink, '{email}' => $subLink]);

        $model = new CustomerActionLog();
        $model->customer_id = $this->owner->customer_id;
        $model->category = CustomerActionLog::CATEGORY_LISTS_SUBSCRIBERS_UPDATED;
        $model->reference_id = (int)$subscriber->subscriber_id;
        $model->reference_relation_id = (int)$subscriber->list_id;
        $model->message = $message;
        return $model->save();
    }

    /**
     * @param ListSubscriber $subscriber
     *
     * @return bool
     */
    public function subscriberDeleted(ListSubscriber $subscriber): bool
    {
        if (empty($this->owner->customer_id)) {
            return false;
        }

        // remove subscriber logs
        $criteria = new CDbCriteria();
        $criteria->compare('customer_id', (int)$this->owner->customer_id);
        $criteria->addInCondition('category', [
            CustomerActionLog::CATEGORY_LISTS_SUBSCRIBERS_CREATED,
            CustomerActionLog::CATEGORY_LISTS_SUBSCRIBERS_UPDATED,
            CustomerActionLog::CATEGORY_LISTS_SUBSCRIBERS_UNSUBSCRIBED,
        ]);
        $criteria->compare('reference_id', (int)$subscriber->subscriber_id);
        $criteria->compare('reference_relation_id', (int)$subscriber->list_id);
        CustomerActionLog::model()->deleteAll($criteria);

        /** @var OptionUrl $optionUrl */
        $optionUrl = container()->get(OptionUrl::class);

        // add new logs
        $listUrl = $optionUrl->getCustomerUrl(sprintf('lists/%s/overview', $subscriber->list->list_uid));

        $message  = 'The subscriber having the email address "{email}" has been successfully removed from the "{listName}" list!';
        $listLink = CHtml::link($subscriber->list->name, $listUrl);
        $message  = t('list_subscribers', $message, ['{email}' => $subscriber->getDisplayEmail(), '{listName}' => $listLink]);

        $model = new CustomerActionLog();
        $model->customer_id = $this->owner->customer_id;
        $model->category = CustomerActionLog::CATEGORY_LISTS_SUBSCRIBERS_DELETED;
        $model->reference_relation_id = (int)$subscriber->list_id;
        $model->message = $message;
        return $model->save();
    }

    /**
     * @param ListSubscriber $subscriber
     *
     * @return bool
     */
    public function subscriberUnsubscribed(ListSubscriber $subscriber): bool
    {
        if (empty($this->owner->customer_id)) {
            return false;
        }

        /** @var OptionUrl $optionUrl */
        $optionUrl = container()->get(OptionUrl::class);

        $listUrl = $optionUrl->getCustomerUrl(sprintf('lists/%s/overview', $subscriber->list->list_uid));
        $subUrl  = $optionUrl->getCustomerUrl(sprintf('lists/%s/subscribers/%s/update', $subscriber->list->list_uid, $subscriber->subscriber_uid));

        $message  = 'The subscriber having the email address "{email}" has been successfully unsubscribed from the "{listName}" list!';
        $listLink = CHtml::link($subscriber->list->name, $listUrl);
        $subLink  = CHtml::link($subscriber->getDisplayEmail(), $subUrl);
        $message  = t('list_subscribers', $message, ['{listName}' => $listLink, '{email}' => $subLink]);

        $model = new CustomerActionLog();
        $model->customer_id = $this->owner->customer_id;
        $model->category = CustomerActionLog::CATEGORY_LISTS_SUBSCRIBERS_UNSUBSCRIBED;
        $model->reference_id = (int)$subscriber->subscriber_id;
        $model->reference_relation_id = (int)$subscriber->list_id;
        $model->message = $message;
        return $model->save();
    }

    /**
     * @param Survey $survey
     *
     * @return bool
     */
    public function surveyCreated(Survey $survey): bool
    {
        if (empty($this->owner->customer_id)) {
            return false;
        }

        /** @var OptionUrl $optionUrl */
        $optionUrl = container()->get(OptionUrl::class);

        $url        = $optionUrl->getCustomerUrl(sprintf('surveys/%s/overview', $survey->survey_uid));
        $message    = 'The survey "{surveyName}" has been successfully created!';
        $surveyLink = CHtml::link($survey->name, $url);
        $message    = t('surveys', $message, ['{surveyName}' => $surveyLink]);

        $model = new CustomerActionLog();
        $model->customer_id  = $this->owner->customer_id;
        $model->category     = CustomerActionLog::CATEGORY_SURVEYS_CREATED;
        $model->reference_id = (int)$survey->survey_id;
        $model->message      = $message;
        return $model->save();
    }

    /**
     * @param Survey $survey
     *
     * @return bool
     */
    public function surveyUpdated(Survey $survey): bool
    {
        if (empty($this->owner->customer_id)) {
            return false;
        }

        /** @var OptionUrl $optionUrl */
        $optionUrl = container()->get(OptionUrl::class);

        $url        = $optionUrl->getCustomerUrl(sprintf('surveys/%s/overview', $survey->survey_uid));
        $message    = 'The survey "{surveyName}" has been successfully updated!';
        $surveyLink = CHtml::link($survey->name, $url);
        $message    = t('surveys', $message, ['{surveyName}' => $surveyLink]);

        $model = new CustomerActionLog();
        $model->customer_id  = $this->owner->customer_id;
        $model->category     = CustomerActionLog::CATEGORY_SURVEYS_UPDATED;
        $model->reference_id = (int)$survey->survey_id;
        $model->message      = $message;
        return $model->save();
    }

    /**
     * @param Survey $survey
     *
     * @return bool
     */
    public function surveyDeleted(Survey $survey): bool
    {
        if (empty($this->owner->customer_id)) {
            return false;
        }

        // remove logs
        $criteria = new CDbCriteria();
        $criteria->compare('customer_id', (int)$this->owner->customer_id);
        $criteria->addInCondition('category', [
            CustomerActionLog::CATEGORY_SURVEYS_CREATED,
            CustomerActionLog::CATEGORY_SURVEYS_UPDATED,
        ]);
        $criteria->compare('reference_id', (int)$survey->survey_id);
        CustomerActionLog::model()->deleteAll($criteria);

        // remove references
        $criteria = new CDbCriteria();
        $criteria->compare('customer_id', (int)$this->owner->customer_id);
        $criteria->addInCondition('category', [
            // responders
            CustomerActionLog::CATEGORY_SURVEYS_RESPONDERS_CREATED,
            CustomerActionLog::CATEGORY_SURVEYS_RESPONDERS_UPDATED,
            CustomerActionLog::CATEGORY_SURVEYS_RESPONDERS_DELETED,
        ]);
        $criteria->compare('reference_relation_id', (int)$survey->survey_id);
        CustomerActionLog::model()->deleteAll($criteria);

        // add logs
        $message = 'The survey "{surveyName}" has been successfully deleted!';
        $message = t('surveys', $message, ['{surveyName}' => $survey->name]);

        $model = new CustomerActionLog();
        $model->customer_id = $this->owner->customer_id;
        $model->category    = CustomerActionLog::CATEGORY_SURVEYS_DELETED;
        $model->message     = $message;
        return $model->save();
    }

    /**
     * @param SurveyResponder $responder
     *
     * @return bool
     */
    public function responderCreated(SurveyResponder $responder): bool
    {
        if (empty($this->owner->customer_id)) {
            return false;
        }

        /** @var OptionUrl $optionUrl */
        $optionUrl = container()->get(OptionUrl::class);

        $surveyUrl = $optionUrl->getCustomerUrl(sprintf('surveys/%s/overview', $responder->survey->survey_uid));
        $resUrl    = $optionUrl->getCustomerUrl(sprintf('surveys/%s/responders/%s/update', $responder->survey->survey_uid, $responder->responder_uid));

        $message    = 'A new responder having the IP address "{ip}" has been successfully added to the survey "{surveyName}"!';
        $surveyLink = CHtml::link($responder->survey->name, $surveyUrl);
        $resLink    = CHtml::link($responder->ip_address, $resUrl);
        $message    = t('survey_responders', $message, ['{surveyName}' => $surveyLink, '{ip}' => $resLink]);

        $model = new CustomerActionLog();
        $model->customer_id           = $this->owner->customer_id;
        $model->category              = CustomerActionLog::CATEGORY_SURVEYS_RESPONDERS_CREATED;
        $model->reference_id          = (int)$responder->responder_id;
        $model->reference_relation_id = (int)$responder->survey_id;
        $model->message               = $message;
        return $model->save();
    }

    /**
     * @param SurveyResponder $responder
     *
     * @return bool
     */
    public function responderUpdated(SurveyResponder $responder): bool
    {
        if (empty($this->owner->customer_id)) {
            return false;
        }

        /** @var OptionUrl $optionUrl */
        $optionUrl = container()->get(OptionUrl::class);

        $surveyUrl = $optionUrl->getCustomerUrl(sprintf('surveys/%s/overview', $responder->survey->survey_uid));
        $resUrl    = $optionUrl->getCustomerUrl(sprintf('surveys/%s/responders/%s/update', $responder->survey->survey_uid, $responder->responder_uid));

        $message    = 'The responder having the IP address "{ip}" has been successfully updated in the "{surveyName}" survey!';
        $surveyLink = CHtml::link($responder->survey->name, $surveyUrl);
        $resLink    = CHtml::link($responder->ip_address, $resUrl);
        $message    = t('survey_responders', $message, ['{surveyName}' => $surveyLink, '{ip}' => $resLink]);

        $model = new CustomerActionLog();
        $model->customer_id           = $this->owner->customer_id;
        $model->category              = CustomerActionLog::CATEGORY_SURVEYS_RESPONDERS_UPDATED;
        $model->reference_id          = (int)$responder->responder_id;
        $model->reference_relation_id = (int)$responder->survey_id;
        $model->message = $message;
        return $model->save();
    }

    /**
     * @param SurveyResponder $responder
     *
     * @return bool
     */
    public function responderDeleted(SurveyResponder $responder): bool
    {
        if (empty($this->owner->customer_id)) {
            return false;
        }

        // remove responder logs
        $criteria = new CDbCriteria();
        $criteria->compare('customer_id', (int)$this->owner->customer_id);
        $criteria->addInCondition('category', [
            CustomerActionLog::CATEGORY_SURVEYS_RESPONDERS_CREATED,
            CustomerActionLog::CATEGORY_SURVEYS_RESPONDERS_UPDATED,
        ]);
        $criteria->compare('reference_id', (int)$responder->responder_id);
        $criteria->compare('reference_relation_id', (int)$responder->survey_id);
        CustomerActionLog::model()->deleteAll($criteria);

        /** @var OptionUrl $optionUrl */
        $optionUrl = container()->get(OptionUrl::class);

        // add new logs
        $surveyUrl = $optionUrl->getCustomerUrl(sprintf('surveys/%s/overview', $responder->survey->survey_uid));

        $message    = 'The responder having the IP address "{ip}" has been successfully removed from the "{surveyName}" survey!';
        $surveyLink = CHtml::link($responder->survey->name, $surveyUrl);
        $message    = t('survey_responders', $message, ['{ip}' => $responder->ip_address, '{surveyName}' => $surveyLink]);

        $model = new CustomerActionLog();
        $model->customer_id           = $this->owner->customer_id;
        $model->category              = CustomerActionLog::CATEGORY_SURVEYS_RESPONDERS_DELETED;
        $model->reference_relation_id = (int)$responder->survey_id;
        $model->message               = $message;
        return $model->save();
    }

    /**
     * @param SurveySegment $segment
     *
     * @return bool
     */
    public function surveySegmentCreated(SurveySegment $segment): bool
    {
        if (empty($this->owner->customer_id)) {
            return false;
        }

        /** @var OptionUrl $optionUrl */
        $optionUrl = container()->get(OptionUrl::class);

        $segUrl = $optionUrl->getCustomerUrl(sprintf('surveys/%s/segments/%s/update', $segment->survey->survey_uid, $segment->segment_uid));
        $url    = $optionUrl->getCustomerUrl(sprintf('surveys/%s/overview', $segment->survey->survey_uid));

        $message    = 'A new segment called "{segmentName}" has been added to the survey "{surveyName}" successfully!';
        $segmLink   = CHtml::link($segment->name, $segUrl);
        $surveyLink = CHtml::link($segment->survey->name, $url);
        $message    = t('survey_segments', $message, ['{segmentName}' => $segmLink, '{surveyName}' => $surveyLink]);

        $model = new CustomerActionLog();
        $model->customer_id           = $this->owner->customer_id;
        $model->category              = CustomerActionLog::CATEGORY_SURVEYS_SEGMENT_CREATED;
        $model->reference_id          = (int)$segment->segment_id;
        $model->reference_relation_id = (int)$segment->survey_id;
        $model->message               = $message;
        return $model->save();
    }

    /**
     * @param SurveySegment $segment
     *
     * @return bool
     */
    public function surveySegmentUpdated(SurveySegment $segment): bool
    {
        /** @var OptionUrl $optionUrl */
        $optionUrl = container()->get(OptionUrl::class);

        $segUrl = $optionUrl->getCustomerUrl(sprintf('surveys/%s/segments/%s/update', $segment->survey->survey_uid, $segment->segment_uid));
        $url    = $optionUrl->getCustomerUrl(sprintf('surveys/%s/overview', $segment->survey->survey_uid));

        $message  = 'The segment called "{segmentName}" belonging to the survey "{surveyName}" has been successfully updated!';
        $segmLink = CHtml::link($segment->name, $segUrl);
        $listLink = CHtml::link($segment->survey->name, $url);
        $message  = t('survey_segments', $message, ['{segmentName}' => $segmLink, '{surveyName}' => $listLink]);

        $model = new CustomerActionLog();
        $model->customer_id           = $this->owner->customer_id;
        $model->category              = CustomerActionLog::CATEGORY_SURVEYS_SEGMENT_UPDATED;
        $model->reference_id          = (int)$segment->segment_id;
        $model->reference_relation_id = (int)$segment->survey_id;
        $model->message               = $message;
        return $model->save();
    }

    /**
     * @param SurveySegment $segment
     *
     * @return bool
     */
    public function surveySegmentDeleted(SurveySegment $segment): bool
    {
        if (empty($this->owner->customer_id)) {
            return false;
        }

        // remove segment logs
        $criteria = new CDbCriteria();
        $criteria->compare('customer_id', (int)$this->owner->customer_id);
        $criteria->addInCondition('category', [
            CustomerActionLog::CATEGORY_SURVEYS_SEGMENT_CREATED,
            CustomerActionLog::CATEGORY_SURVEYS_SEGMENT_UPDATED,
        ]);
        $criteria->compare('reference_id', (int)$segment->segment_id);
        $criteria->compare('reference_relation_id', (int)$segment->survey_id);
        CustomerActionLog::model()->deleteAll($criteria);

        // remove segment campaigns logs
        $criteria = new CDbCriteria();
        $criteria->compare('customer_id', (int)$this->owner->customer_id);
        $criteria->compare('reference_relation_id', (int)$segment->segment_id);
        CustomerActionLog::model()->deleteAll($criteria);

        $message = 'The segment {segmentName} belonging to the survey "{surveyName}" has been successfully deleted!';
        $message = t('survey_segments', $message, ['{segmentName}' => $segment->name, '{surveyName}' => $segment->survey->name]);

        $model = new CustomerActionLog();
        $model->customer_id = $this->owner->customer_id;
        $model->category    = CustomerActionLog::CATEGORY_SURVEYS_SEGMENT_DELETED;
        $model->message     = $message;
        return $model->save();
    }

    /**
     * @param Survey $survey
     * @param SurveyCsvExport $export
     *
     * @return bool
     */
    public function surveyExportStart(Survey $survey, SurveyCsvExport $export): bool
    {
        if (empty($this->owner->customer_id)) {
            return false;
        }

        /** @var OptionUrl $optionUrl */
        $optionUrl = container()->get(OptionUrl::class);

        $url      = $optionUrl->getCustomerUrl(sprintf('surveys/%s/overview', $survey->survey_uid));
        $message  = 'The export process for survey "{surveyName}" has successfully started, counting {rowsCount} records!';
        $surveyLink = CHtml::link($survey->name, $url);
        $message  = t('survey_export', $message, ['{surveyName}' => $surveyLink, '{rowsCount}' => $export->count]);

        $model = new CustomerActionLog();
        $model->customer_id = $this->owner->customer_id;
        $model->category = CustomerActionLog::CATEGORY_SURVEYS_EXPORT_START;
        $model->reference_id = (int)$survey->survey_id;
        $model->message = $message;
        return $model->save();
    }

    /**
     * @param Survey $survey
     * @param SurveyCsvExport $export
     *
     * @return bool
     */
    public function surveyExportEnd(Survey $survey, SurveyCsvExport $export): bool
    {
        if (empty($this->owner->customer_id)) {
            return false;
        }

        /** @var OptionUrl $optionUrl */
        $optionUrl = container()->get(OptionUrl::class);

        $url      = $optionUrl->getCustomerUrl(sprintf('surveys/%s/overview', $survey->survey_uid));
        $message  = 'The export process for survey "{surveyName}" has successfully ended, processing {rowsCount} records!';
        $surveyLink = CHtml::link($survey->name, $url);
        $message  = t('survey_export', $message, ['{surveyName}' => $surveyLink, '{rowsCount}' => $export->count]);

        $model = new CustomerActionLog();
        $model->customer_id = $this->owner->customer_id;
        $model->category = CustomerActionLog::CATEGORY_SURVEYS_EXPORT_END;
        $model->reference_id = (int)$survey->survey_id;
        $model->message = $message;
        return $model->save();
    }
}
