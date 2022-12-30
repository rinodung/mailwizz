<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * CampaignOpenUserAgentsWidget
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.6.4
 */

class CampaignOpenUserAgentsWidget extends CWidget
{
    /**
     * @var Campaign|null
     */
    public $campaign;

    /**
     * @return void
     * @throws CException
     */
    public function run()
    {
        if (empty($this->campaign) || !version_compare(PHP_VERSION, '5.4', '>=')) {
            return;
        }

        // 1.7.9
        if ($this->campaign->option->open_tracking != CampaignOption::TEXT_YES) {
            return;
        }

        // 1.7.9 - static counters
        if ($this->campaign->option->opens_count >= 0) {
            return;
        }

        $cacheKey = __METHOD__;
        if (!empty($this->campaign)) {
            $cacheKey .= '::' . $this->campaign->campaign_uid;
        }
        $cacheKey = sha1($cacheKey);

        if (($data = cache()->get($cacheKey)) === false) {
            $data = $this->getData();
            cache()->set($cacheKey, $data, 300);
        }

        if (empty($data)) {
            return;
        }

        $chartData = [
            'os'     => [],
            'device' => [],
            'browser'=> [],
        ];

        $allEmpty = true;
        foreach ($chartData as $key => $_) {
            if (empty($data[$key])) {
                continue;
            }
            $allEmpty = false;

            foreach ($data[$key] as $row) {
                $chartData[$key][] = [
                    'label'           => $row['name'],
                    'data'            => $row['count'],
                    'count'           => $row['count'],
                    'count_formatted' => numberFormatter()->formatDecimal($row['count']),
                ];
            }
        }

        if ($allEmpty) {
            return;
        }

        clientScript()->registerScriptFile(apps()->getBaseUrl('assets/js/flot/jquery.flot.min.js'));
        clientScript()->registerScriptFile(apps()->getBaseUrl('assets/js/flot/jquery.flot.pie.min.js'));
        clientScript()->registerScriptFile(apps()->getBaseUrl('assets/js/campaign-open-user-agents.js'));

        $this->render('campaign-open-user-agents', compact('chartData', 'data'));
    }

    /**
     * @return array
     */
    protected function getData()
    {
        $limit  = 5000;
        $offset = 0;

        $data   = [
            'os'       => [],
            'device'   => [],
            'browser'  => [],
        ];

        while (($models = $this->getModels($limit, $offset))) {
            $offset = $offset + $limit;

            foreach ($models as $model) {
                if (strlen($model['user_agent']) < 10) {
                    continue;
                }
                $result = new WhichBrowser\Parser($model['user_agent'], ['detectBots' => false]);

                if (empty($result->os->name) || empty($result->device->type) || empty($result->browser->name)) {
                    continue;
                }

                // OS
                if (!isset($data['os'][$result->os->name])) {
                    $data['os'][$result->os->name] = [
                        'name'  => ucwords($result->os->name),
                        'count' => 0,
                    ];
                }
                $data['os'][$result->os->name]['count'] += $model['counter'];

                // Device
                if (!isset($data['device'][$result->device->type])) {
                    $data['device'][$result->device->type] = [
                        'name'  => ucwords($result->device->type),
                        'count' => 0,
                    ];
                }
                $data['device'][$result->device->type]['count'] += $model['counter'];

                // Browser
                $name = $result->browser->name;
                //
                if (!empty($result->browser->version->value)) {
                    $version = explode('.', (string)$result->browser->version->value);
                    $version = array_slice($version, 0, 2);
                    $version = implode('.', $version);
                    $name .= sprintf('(v.%s)', $version);
                }
                if (!isset($data['browser'][$name])) {
                    $data['browser'][$name] = [
                        'name'  => ucwords($name),
                        'count' => 0,
                    ];
                }
                $data['browser'][$name]['count'] += $model['counter'];
            }
        }

        foreach ($data as $key => $contents) {
            $counts = [];
            foreach ($contents as $content) {
                $counts[] = $content['count'];
            }
            $items = $data[$key];
            array_multisort($counts, SORT_NUMERIC | SORT_DESC, $items);
            $data[$key] = array_slice($items, 0, 50);
        }

        return $data;
    }

    /**
     * @param int $limit
     * @param int $offset
     *
     * @return array
     */
    protected function getModels(int $limit, int $offset): array
    {
        if (empty($this->campaign)) {
            return [];
        }

        try {
            $rows = db()->createCommand()
                ->select('user_agent, count(user_agent) as counter')
                ->from(CampaignTrackOpen::model()->tableName())
                ->where('campaign_id = :campaign_id', [':campaign_id' => (int)$this->campaign->campaign_id])
                ->group('user_agent')
                ->limit($limit)
                ->offset($offset)
                ->queryAll();
        } catch (Exception $e) {
            $rows = [];
        }

        return $rows;
    }
}
