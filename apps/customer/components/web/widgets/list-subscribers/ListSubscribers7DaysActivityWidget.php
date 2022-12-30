<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * ListSubscribers7DaysActivityWidget
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.5.2
 */

class ListSubscribers7DaysActivityWidget extends CWidget
{
    /**
     * @var Lists
     */
    public $list;

    /**
     * @return void
     * @throws CException
     */
    public function run()
    {
        $list = $this->list;

        if ($list->customer->getGroupOption('lists.show_7days_subscribers_activity_graph', 'yes') != 'yes') {
            return;
        }

        $cacheKey = sha1(__METHOD__ . $list->list_id . date('H') . 'v1');
        if (($chartData = cache()->get($cacheKey)) === false) {
            $chartData = [
                'confirmed' => [
                    'label' => '&nbsp;' . t('list_subscribers', 'Confirmed'),
                    'data'  => [],
                ],
                'unconfirmed' => [
                    'label' => '&nbsp;' . t('list_subscribers', 'Unconfirmed'),
                    'data'  => [],
                ],
                'unsubscribed' => [
                    'label' => '&nbsp;' . t('list_subscribers', 'Unsubscribed'),
                    'data'  => [],
                ],
                'blacklisted' => [
                    'label' => '&nbsp;' . t('list_subscribers', 'Blacklisted'),
                    'data'  => [],
                ],
                'bounces' => [
                    'label' => '&nbsp;' . t('list_subscribers', 'Bounces'),
                    'data'  => [],
                ],
            ];

            for ($i = 0; $i < 7; $i++) {
                $timestamp = (int)strtotime(sprintf('-%d days', $i));

                // confirmed
                $count = ListSubscriber::model()->count([
                    'condition' => 'list_id = :lid AND status = :st AND DATE(date_added) = :date',
                    'params'    => [
                        ':lid'  => $list->list_id,
                        ':st'   => ListSubscriber::STATUS_CONFIRMED,
                        ':date' => date('Y-m-d', $timestamp),
                    ],
                ]);
                $chartData['confirmed']['data'][] = [$timestamp * 1000, (int)$count];

                // unconfirmed
                $count = ListSubscriber::model()->count([
                    'condition' => 'list_id = :lid AND status = :st AND DATE(date_added) = :date',
                    'params'    => [
                        ':lid'  => $list->list_id,
                        ':st'   => ListSubscriber::STATUS_UNCONFIRMED,
                        ':date' => date('Y-m-d', $timestamp),
                    ],
                ]);
                $chartData['unconfirmed']['data'][] = [$timestamp * 1000, (int)$count];

                // unsubscribes
                $count = ListSubscriber::model()->count([
                    'condition' => 'list_id = :lid AND status = :st AND DATE(date_added) = :date',
                    'params'    => [
                        ':lid'  => $list->list_id,
                        ':st'   => ListSubscriber::STATUS_UNSUBSCRIBED,
                        ':date' => date('Y-m-d', $timestamp),
                    ],
                ]);
                $chartData['unsubscribed']['data'][] = [$timestamp * 1000, (int)$count];

                // blacklisted
                $count = ListSubscriber::model()->count([
                    'condition' => 'list_id = :lid AND status = :st AND DATE(date_added) = :date',
                    'params'    => [
                        ':lid'  => $list->list_id,
                        ':st'   => ListSubscriber::STATUS_BLACKLISTED,
                        ':date' => date('Y-m-d', $timestamp),
                    ],
                ]);
                $chartData['blacklisted']['data'][] = [$timestamp * 1000, (int)$count];

                // bounces
                $criteria = new CDbCriteria();
                $criteria->with = [];
                $criteria->compare('DATE(t.date_added)', date('Y-m-d', $timestamp));
                $criteria->with['campaign'] = [
                    'select'    => false,
                    'together'  => true,
                    'joinType'  => 'INNER JOIN',
                    'with'      => [
                        'list'  => [
                            'select'    => false,
                            'together'  => true,
                            'joinType'  => 'INNER JOIN',
                            'condition' => 'list.list_id = :lid',
                            'params'    => [':lid' => $list->list_id],
                        ],
                    ],
                ];
                $count = CampaignBounceLog::model()->count($criteria);
                $chartData['bounces']['data'][] = [$timestamp * 1000, (int)$count];
            }

            $chartData = array_values($chartData);
            cache()->set($cacheKey, $chartData, 3600);
        }

        clientScript()->registerScriptFile(apps()->getBaseUrl('assets/js/flot/jquery.flot.min.js'));
        clientScript()->registerScriptFile(apps()->getBaseUrl('assets/js/flot/jquery.flot.resize.min.js'));
        clientScript()->registerScriptFile(apps()->getBaseUrl('assets/js/flot/jquery.flot.crosshair.min.js'));
        clientScript()->registerScriptFile(apps()->getBaseUrl('assets/js/flot/jquery.flot.time.min.js'));
        clientScript()->registerScriptFile(apps()->getBaseUrl('assets/js/strftime/strftime-min.js'));
        clientScript()->registerScriptFile(apps()->getBaseUrl('assets/js/list-subscribers-7days-activity.js'));

        $this->render('7days-activity', compact('chartData'));
    }
}
