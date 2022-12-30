<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * MailListSubNavWidget
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

class MailListSubNavWidget extends CWidget
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
        if ($this->list->isNewRecord) {
            return;
        }

        $this->render('mail-list-sub-nav');
    }

    /**
     * @return array
     */
    public function getNavItems(): array
    {
        $items = [
            [
                'label'     => t('lists', 'All lists'),
                'url'       => createUrl('lists/index'),
            ],
            [
                'label' => t('lists', 'List overview'),
                'url'   => createUrl('lists/overview', ['list_uid' => $this->list->list_uid]),
            ],
            [
                'label' => t('list_subscribers', 'List subscribers'),
                'url'   => createUrl('list_subscribers/index', ['list_uid' => $this->list->list_uid]),
            ],
            [
                'label' => t('list_fields', 'List custom fields'),
                'url'   => createUrl('list_fields/index', ['list_uid' => $this->list->list_uid]),
            ],
            [
                'label' => t('list_pages', 'List pages'),
                'url'   => createUrl('list_page/index', ['list_uid' => $this->list->list_uid, 'type' => 'subscribe-form']),
            ],
            [
                'label' => t('list_forms', 'List embed forms'),
                'url'   => createUrl('list_forms/index', ['list_uid' => $this->list->list_uid]),
            ],
            [
                'label' => t('list_segments', 'List segments'),
                'url'   => createUrl('list_segments/index', ['list_uid' => $this->list->list_uid]),
            ],
            [
                'label' => t('lists', 'List open graph'),
                'url'   => createUrl('list_open_graph/index', ['list_uid' => $this->list->list_uid]),
            ],
            [
                'label' => t('lists', 'Update list'),
                'url'   => createUrl('lists/update', ['list_uid' => $this->list->list_uid]),
            ],
        ];

        /** @var Customer $customer */
        $customer = customer()->getModel();

        if (!($customer->getGroupOption('lists.can_segment_lists', 'yes') == 'yes')) {
            unset($items[6]);
        }

        return $items;
    }
}
