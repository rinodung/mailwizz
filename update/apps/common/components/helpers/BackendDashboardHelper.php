<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * BackendDashboardHelper
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.7.6
 */

class BackendDashboardHelper
{
    /**
     * @return array
     */
    public static function getGlanceStats(): array
    {
        /** @var OptionUrl $optionUrl */
        $optionUrl = container()->get(OptionUrl::class);

        $backendUrl = $optionUrl->getBackendUrl();

        // so we can call it multiple times under different language
        static $counters;
        if ($counters === null) {
            $counters = [
                'customers'     => Customer::model()->count(),
                'campaigns'     => Campaign::model()->count(),
                'lists'         => Lists::model()->count(),
                'subscribers'   => ListSubscriber::model()->count(),
                'segments'      => ListSegment::model()->count(),
                'servers'       => DeliveryServer::model()->count(),
            ];
        }

        return [
            [
                'id'        => 'customers',
                'count'     => formatter()->formatNumber($counters['customers']),
                'heading'   => t('dashboard', 'Customers'),
                'icon'      => IconHelper::make('ion-person-add'),
                'url'       => $backendUrl . 'customers/index',
            ],
            [
                'id'        => 'campaigns',
                'count'     => formatter()->formatNumber($counters['campaigns']),
                'heading'   => t('dashboard', 'Campaigns'),
                'icon'      => IconHelper::make('ion-ios-email-outline'),
                'url'       => $backendUrl . 'campaigns/index',
            ],
            [
                'id'        => 'lists',
                'count'     => formatter()->formatNumber($counters['lists']),
                'heading'   => t('dashboard', 'Lists'),
                'icon'      => IconHelper::make('ion ion-clipboard'),
                'url'       => $backendUrl . 'lists/index',
            ],
            [
                'id'        => 'subscribers',
                'count'     => formatter()->formatNumber($counters['subscribers']),
                'heading'   => t('dashboard', 'Subscribers'),
                'icon'      => IconHelper::make('ion-ios-people'),
                'url'       => 'javascript:;',
            ],
            [
                'id'        => 'segments',
                'count'     => formatter()->formatNumber($counters['segments']),
                'heading'   => t('dashboard', 'Segments'),
                'icon'      => IconHelper::make('ion-gear-b'),
                'url'       => 'javascript:;',
            ],
            [
                'id'        => 'servers',
                'count'     => formatter()->formatNumber($counters['servers']),
                'heading'   => t('dashboard', 'Delivery servers'),
                'icon'      => IconHelper::make('ion-paper-airplane'),
                'url'       => $backendUrl . 'delivery-servers/index',
            ],
        ];
    }

    /**
     * @return array
     * @throws Exception
     */
    public static function getTimelineItems(): array
    {
        /** @var OptionUrl $optionUrl */
        $optionUrl = container()->get(OptionUrl::class);

        $backendUrl = $optionUrl->getBackendUrl();

        $criteria = new CDbCriteria();
        $criteria->select    = 'DISTINCT(DATE(t.date_added)) as date_added';
        $criteria->condition = 'DATE(t.date_added) >= DATE_SUB(NOW(), INTERVAL 7 DAY)';
        $criteria->group     = 'DATE(t.date_added)';
        $criteria->order     = 't.date_added DESC';
        $criteria->limit     = 3;
        $models = CustomerActionLog::model()->findAll($criteria);

        $items = [];
        foreach ($models as $model) {
            $_item = [
                'date'  => $model->dateTimeFormatter->formatLocalizedDate($model->date_added),
                'items' => [],
            ];
            $criteria = new CDbCriteria();
            $criteria->select    = 't.log_id, t.customer_id, t.message, t.date_added';
            $criteria->condition = 'DATE(t.date_added) = :date';
            $criteria->params    = [':date' => $model->date_added];
            $criteria->limit     = 5;
            $criteria->order     = 't.date_added DESC';
            $criteria->with      = [
                'customer' => [
                    'select'   => 'customer.customer_id, customer.first_name, customer.last_name',
                    'together' => true,
                    'joinType' => 'INNER JOIN',
                ],
            ];
            $records = CustomerActionLog::model()->findAll($criteria);

            // since 1.9.26
            if (!empty($records)) {
                $_item['date'] = $records[0]->dateTimeFormatter->formatLocalizedDate($records[0]->date_added, 'yyyy-MM-dd HH:mm:ss');
            }

            foreach ($records as $record) {
                $customer = $record->customer;
                $time     = $record->dateTimeFormatter->formatLocalizedTime($record->date_added);
                $_item['items'][] = [
                    'time'         => $time,
                    'customerName' => $customer->getFullName(),
                    'customerUrl'  => $backendUrl . 'customers/update/id/' . $customer->customer_id,
                    'message'      => strip_tags($record->message),
                ];
            }
            $items[] = $_item;
        }

        return $items;
    }
}
