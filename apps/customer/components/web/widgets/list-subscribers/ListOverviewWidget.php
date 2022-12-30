<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * ListOverviewWidget
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.1.6
 */

class ListOverviewWidget extends CWidget
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
        $customer = $list->customer;

        $confirmedSubscribersCount = $list->getConfirmedSubscribersCount(true);
        $subscribersCount          = $list->getSubscribersCount(true);
        $customFieldsCount         = $list->fieldsCount;
        $pagesCount                = ListPageType::model()->count();

        $segmentsCount   = 0;
        $canSegmentLists = $customer->getGroupOption('lists.can_segment_lists', 'yes') == 'yes';
        if ($canSegmentLists) {
            $segmentsCount = $list->activeSegmentsCount;
        }

        $createLink                    = CHtml::link(IconHelper::make('create') . t('app', 'Create new'), ['lists/create'], ['class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Create new')]);
        $updateLink                    = CHtml::link(IconHelper::make('update') . t('app', 'Update'), ['lists/update', 'list_uid' => $list->list_uid], ['class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Update')]);
        $confirmedSubscribersCountLink = CHtml::link(formatter()->formatNumber($confirmedSubscribersCount), createUrl('list_subscribers/index', ['list_uid' => $list->list_uid]), ['title' => t('list_subscribers', 'Confirmed Subscribers')]);
        $subscribersCountLink          = CHtml::link(formatter()->formatNumber($subscribersCount), createUrl('list_subscribers/index', ['list_uid' => $list->list_uid]), ['title' => t('app', 'View')]);
        $customFieldsCountLink         = CHtml::link(formatter()->formatNumber($customFieldsCount), createUrl('list_fields/index', ['list_uid' => $list->list_uid]), ['title' => t('app', 'View')]);
        $pagesCountLink                = CHtml::link(formatter()->formatNumber($pagesCount), createUrl('list_page/index', ['list_uid' => $list->list_uid, 'type' => 'subscribe-form']), ['title' => t('app', 'View')]);
        $formsLink                     = CHtml::link(t('list_forms', 'Forms'), createUrl('list_forms/index', ['list_uid' => $list->list_uid]), ['title' => t('app', 'View')]);
        $toolsLink                     = CHtml::link(t('lists', 'Tools'), createUrl('list_tools/index', ['list_uid' => $list->list_uid]), ['title' => t('app', 'View')]);
        $segmentsCountLink             = CHtml::link(formatter()->formatNumber($segmentsCount), createUrl('list_segments/index', ['list_uid' => $list->list_uid]), ['title' => t('app', 'View')]);

        if (apps()->isAppName('backend')) {
            $createLink = $updateLink = '';
            $confirmedSubscribersCountLink = HtmlHelper::backendCreateCustomerResourceLink((int)$list->customer_id, formatter()->formatNumber($confirmedSubscribersCount), sprintf('lists/%s/subscribers', $list->list_uid), ['title' => t('list_subscribers', 'Confirmed Subscribers')]);
            $subscribersCountLink          = HtmlHelper::backendCreateCustomerResourceLink((int)$list->customer_id, formatter()->formatNumber($subscribersCount), sprintf('lists/%s/subscribers', $list->list_uid), ['title' => t('list_subscribers', 'Subscribers')]);
            $customFieldsCountLink         = HtmlHelper::backendCreateCustomerResourceLink((int)$list->customer_id, formatter()->formatNumber($customFieldsCount), sprintf('lists/%s/fields', $list->list_uid), ['title' => t('list_fields', 'Custom fields')]);
            $pagesCountLink                = HtmlHelper::backendCreateCustomerResourceLink((int)$list->customer_id, formatter()->formatNumber($pagesCount), sprintf('lists/%s/page/subscribe-form', $list->list_uid), ['title' => t('list_pages', 'Pages')]);
            $formsLink                     = HtmlHelper::backendCreateCustomerResourceLink((int)$list->customer_id, t('list_forms', 'Forms'), sprintf('lists/%s/forms', $list->list_uid), ['title' => t('list_forms', 'Forms')]);
            $toolsLink                     = HtmlHelper::backendCreateCustomerResourceLink((int)$list->customer_id, t('lists', 'Tools'), sprintf('lists/%s/tools/index', $list->list_uid), ['title' => t('lists', 'List tools')]);
            $segmentsCountLink             = HtmlHelper::backendCreateCustomerResourceLink((int)$list->customer_id, formatter()->formatNumber($segmentsCount), sprintf('lists/%s/segments', $list->list_uid), ['title' => t('list_segments', 'Segments')]);
        }

        $this->render('list-overview', compact(
            'createLink',
            'updateLink',
            'confirmedSubscribersCountLink',
            'subscribersCountLink',
            'customFieldsCountLink',
            'pagesCountLink',
            'formsLink',
            'toolsLink',
            'segmentsCountLink',
            'canSegmentLists'
        ));
    }
}
