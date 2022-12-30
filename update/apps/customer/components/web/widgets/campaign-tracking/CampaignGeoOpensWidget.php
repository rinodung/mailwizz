<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * CampaignGeoOpensWidget
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.4.5
 */

class CampaignGeoOpensWidget extends CWidget
{
    /**
     * @var Campaign|null
     */
    public $campaign;

    /**
     * @var mixed
     */
    public $headingLeft;

    /**
     * @var mixed
     */
    public $headingRight;

    /**
     * @return void
     * @throws CException
     */
    public function run()
    {
        $customer = null;

        if (!empty($this->campaign)) {

            /** @var Customer $customer */
            $customer = $this->campaign->customer;
        } elseif (apps()->isAppName('customer')) {

            /** @var Customer $customer */
            $customer = customer()->getModel();
        }

        if (empty($customer)) {
            return;
        }

        if ($customer->getGroupOption('campaigns.show_geo_opens', 'no') != 'yes') {
            return;
        }

        // 1.7.9
        if (!empty($this->campaign) && $this->campaign->option->open_tracking != CampaignOption::TEXT_YES) {
            return;
        }

        // 1.7.9 - static counters
        if (!empty($this->campaign) && $this->campaign->option->opens_count >= 0) {
            return;
        }

        if ($this->headingLeft === null || !is_object($this->headingLeft)) {
            $this->headingLeft = BoxHeaderContent::make(BoxHeaderContent::LEFT)->add('<h3 class="box-title">' . IconHelper::make('glyphicon-map-marker') . t('campaigns', 'Campaign Geo Opens') . '</h3>');
        }

        $cacheKey = __METHOD__;
        if (!empty($this->campaign)) {
            $cacheKey .= '::' . $this->campaign->campaign_uid;
        }
        if (apps()->isAppName('customer') && (int)customer()->getId() > 0) {
            $cacheKey .= '::' . $customer->customer_uid;
        }
        $cacheKey = sha1($cacheKey);

        if (($data = cache()->get($cacheKey)) === false) {
            $data = $this->getData();
            cache()->set($cacheKey, $data, 300);
        }

        if (empty($data)) {
            return;
        }

        $chartData = [];
        foreach ($data as $row) {
            $chartData[] = [
                'label'           => $row['country_name'],
                'data'            => $row['opens_count'],
                'count'           => $row['opens_count'],
                'count_formatted' => $row['opens_count_formatted'],
            ];
        }

        clientScript()->registerScriptFile(apps()->getBaseUrl('assets/js/flot/jquery.flot.min.js'));
        clientScript()->registerScriptFile(apps()->getBaseUrl('assets/js/flot/jquery.flot.pie.min.js'));
        clientScript()->registerScriptFile(apps()->getBaseUrl('assets/js/campaign-geo-opens.js'));

        $this->render('campaign-geo-opens', compact('chartData', 'data'));
    }

    /**
     * @return array
     * @throws CException
     */
    protected function getData()
    {
        $query = 'SELECT DISTINCT(`cto`.`location_id`) FROM `{{campaign_track_open}}` cto INNER JOIN `{{ip_location}}` l on `l`.`location_id` = `cto`.`location_id` ';
        if (empty($this->campaign) && (int)customer()->getId() > 0) {
            $query .= ' INNER JOIN `{{campaign}}` `c` ON `c`.`campaign_id` = `cto`.`campaign_id` ';
        }
        $query .= ' WHERE `cto`.`location_id` IS NOT NULL ';
        if (empty($this->campaign) && (int)customer()->getId() > 0) {
            $query .= ' AND `c`.`customer_id` = ' . (int)customer()->getId();
        } elseif (!empty($this->campaign)) {
            $query .= ' AND `cto`.`campaign_id` = ' . (int)$this->campaign->campaign_id;
        }
        $query .= ' GROUP BY `cto`.`location_id` ';

        $rows = db()->createCommand($query)->queryAll(true);
        if (empty($rows)) {
            return [];
        }

        $ids = [];
        foreach ($rows as $row) {
            $ids[] = (int)$row['location_id'];
        }

        $query = 'SELECT `location_id`, `country_code`, `country_name` FROM `{{ip_location}}` WHERE `location_id` IN (' . implode(',', $ids) . ')';
        $rows  = db()->createCommand($query)->queryAll(true);

        if (empty($rows)) {
            return [];
        }

        $countries = [];
        foreach ($rows as $row) {
            if (!isset($countries[$row['country_name']])) {
                $countries[$row['country_name']] = [];
            }
            $countries[$row['country_name']][] = $row;
        }

        $sorts = [];
        $data  = [];
        foreach ($countries as $countryName => $locations) {
            $countryCode = '';
            $ids = [];
            foreach ($locations as $location) {
                $ids[] = (int)$location['location_id'];
                if (!$countryCode) {
                    $countryCode = $location['country_code'];
                }
            }

            $query = 'SELECT COUNT(*) as `cnt` FROM `{{campaign_track_open}}` cto ';
            if (empty($this->campaign) && (int)customer()->getId() > 0) {
                $query .= ' INNER JOIN `{{campaign}}` `c` ON `c`.`campaign_id` = `cto`.`campaign_id` ';
            }
            $query .= ' WHERE `location_id` IN (' . implode(',', $ids) . ') ';
            if (empty($this->campaign) && (int)customer()->getId() > 0) {
                $query .= ' AND `c`.`customer_id` = ' . (int)customer()->getId();
            } elseif (!empty($this->campaign)) {
                $query .= ' AND `cto`.`campaign_id` = ' . (int)$this->campaign->campaign_id;
            }

            $row = db()->createCommand($query)->queryRow(true);

            $controller = $this->getController();

            $actionLinks = '';

            if (apps()->isAppName('customer') || apps()->isAppName('frontend')) {
                if (!empty($this->campaign)) {
                    $canExport = $this->campaign->customer->getGroupOption('campaigns.can_export_stats', 'yes') == 'yes';

                    if ($canExport) {
                        $campaignsReport       = 'campaign_reports';
                        $campaignsReportExport = 'campaign_reports_export';
                        if (apps()->isAppName('frontend')) {
                            $campaignsReport        = 'campaigns_reports';
                            $campaignsReportExport  = 'campaigns_reports_export';
                        }

                        $actionLinks  = '[%s] [' . t('campaigns', 'Export') . ': %s / %s]';
                        $campaignUid  = $this->campaign->campaign_uid;
                        $detailsUrl   = CHtml::link(t('campaigns', 'Details'), createUrl($campaignsReport . '/open', ['campaign_uid' => $campaignUid, 'country_code' => $countryCode]));
                        $exportAll    = CHtml::link(t('campaigns', 'All'), createUrl($campaignsReportExport . '/open', ['campaign_uid' => $campaignUid, 'country_code' => $countryCode]));
                        $exportUnique = CHtml::link(t('campaigns', 'Unique'), createUrl($campaignsReportExport . '/open_unique', ['campaign_uid' => $campaignUid, 'country_code' => $countryCode]));
                        $actionLinks  = sprintf($actionLinks, $detailsUrl, $exportAll, $exportUnique);
                    } else {
                        $actionLinks = '[%s]';
                        $campaignUid = $this->campaign->campaign_uid;
                        $detailsUrl  = CHtml::link(t('campaigns', 'Details'), createUrl('campaign_reports/open', ['campaign_uid' => $campaignUid, 'country_code' => $countryCode]));
                        $actionLinks = sprintf($actionLinks, $detailsUrl);
                    }
                } elseif (customer()->getId()) {

                    /** @var Customer $customer */
                    $customer  = customer()->getModel();
                    $canExport = $customer->getGroupOption('campaigns.can_export_stats', 'yes') == 'yes';

                    if ($canExport) {
                        $actionLinks  = '[%s] [' . t('campaigns', 'Export') . ': %s / %s]';
                        $detailsUrl   = CHtml::link(t('campaigns', 'Details'), createUrl('campaigns_geo_opens/all', ['country_code' => $countryCode]));
                        $exportAll    = CHtml::link(t('campaigns', 'All'), createUrl('campaigns_geo_opens/export_all', ['country_code' => $countryCode]));
                        $exportUnique = CHtml::link(t('campaigns', 'Unique'), createUrl('campaigns_geo_opens/export_unique', ['country_code' => $countryCode]));
                        $actionLinks  = sprintf($actionLinks, $detailsUrl, $exportAll, $exportUnique);
                    } else {
                        $actionLinks = '[%s]';
                        $detailsUrl  = CHtml::link(t('campaigns', 'Details'), createUrl('campaigns_geo_opens/opens', ['country_code' => $countryCode]));
                        $actionLinks = sprintf($actionLinks, $detailsUrl);
                    }
                }
            }

            $data[] = [
                'location_ids'          => $ids,
                'country_name'          => $countryName,
                'country_code'          => $countryCode,
                'opens_count'           => $row['cnt'],
                'opens_count_formatted' => numberFormatter()->formatDecimal($row['cnt']),
                'action_links'          => $actionLinks,
                'flag_url'              => apps()->getAppUrl('frontend', 'assets/img/country-flags/' . strtolower((string)$countryCode) . '.png', true, true),
            ];
            $sorts[] = $row['cnt'];
        }

        array_multisort($sorts, SORT_NUMERIC | SORT_DESC, $data);

        return $data;
    }
}
