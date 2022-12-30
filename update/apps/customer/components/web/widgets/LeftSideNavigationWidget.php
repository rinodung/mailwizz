<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * LeftSideNavigationWidget
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

class LeftSideNavigationWidget extends CWidget
{
    /**
     * @return array
     */
    public function getMenuItems()
    {
        $controller = $this->getController();
        $route      = $controller->getRoute();

        /** @var Customer $customer */
        $customer = customer()->getModel();

        /** @var OptionCommon $common */
        $common = container()->get(OptionCommon::class);

        // to avoid database access error when upgrading from the web interface
        if (version_compare($common->version, '2.1.11', '>=')) {
            $favoritePagesMenuItems = FavoritePage::getTopPagesAsMenuItems();
        } else {
            $favoritePagesMenuItems = [];
        }
        $favoritePagesMenuHideShow = count($favoritePagesMenuItems) <= 1 ? ' hidden' : '';

        $menuItems = [
            'favorite-pages' => [
                'name'        => t('favorite_pages', 'Favorite pages'),
                'icon'        => 'glyphicon-bookmark',
                'active'      => ['favorite_pages'],
                'route'       => null,
                'items'       => $favoritePagesMenuItems,
                'linkOptions' => ['class' => 'favorite-pages-side-menu' . $favoritePagesMenuHideShow],
            ],
            'dashboard' => [
                'name'      => t('app', 'Dashboard'),
                'icon'      => 'glyphicon-dashboard',
                'active'    => 'dashboard',
                'route'     => ['dashboard/index'],
            ],
            'price_plans' => [
                'name'      => t('app', 'Price plans'),
                'icon'      => 'glyphicon-credit-card',
                'active'    => 'price_plans',
                'route'     => null,
                'items'     => [
                    ['url' => ['price_plans/index'], 'label' => t('app', 'Price plans'), 'active' => strpos($route, 'price_plans/index') === 0 || strpos($route, 'price_plans/payment') === 0],
                    ['url' => ['price_plans/orders'], 'label' => t('app', 'Orders history'), 'active' => strpos($route, 'price_plans/order') === 0],
                ],
            ],
            'lists' => [
                'name'      => t('app', 'Lists'),
                'icon'      => 'glyphicon-list-alt',
                'active'    => ['list', 'email_blacklist', 'suppression_lists', 'ip_blacklist'],
                'route'     => null,
                'items'     => [
                    ['url' => ['lists/index'], 'label' => t('app', 'Lists'), 'active' => strpos($route, 'lists') === 0 && strpos($route, 'lists_tools') === false],
                    ['url' => ['lists_tools/index'], 'label' => t('app', 'Tools'), 'active' => strpos($route, 'lists_tools') === 0],
                    ['url' => ['email_blacklist/index'], 'label' => t('app', 'Email blacklist'), 'active' => strpos($route, 'email_blacklist') === 0],
                    ['url' => ['suppression_lists/index'], 'label' => t('app', 'Suppression lists'), 'active' => strpos($route, 'suppression_lists') === 0],
                    ['url' => ['ip_blacklist/index'], 'label' => t('app', 'IP blacklist'), 'active' => strpos($route, 'ip_blacklist') === 0],
                ],
            ],
            'campaigns' => [
                'name'      => t('app', 'Campaigns'),
                'icon'      => 'fa-envelope',
                'active'    => 'campaign',
                'route'     => null,
                'items'     => [
                    ['url' => ['campaigns/index'], 'label' => t('app', 'All campaigns'), 'active' => $route == 'campaigns/index'],
                    ['url' => ['campaigns/regular'], 'label' => t('app', 'Regular campaigns'), 'active' => $route == 'campaigns/regular'],
                    ['url' => ['campaigns/autoresponder'], 'label' => t('app', 'Autoresponders'), 'active' => $route == 'campaigns/autoresponder'],
                    ['url' => ['campaign_groups/index'], 'label' => t('app', 'Groups'), 'active' => strpos($route, 'campaign_groups') === 0],
                    ['url' => ['campaign_send_groups/index'], 'label' => t('app', 'Send groups'), 'active' => strpos($route, 'campaign_send_groups') === 0],
                    ['url' => ['campaigns_geo_opens/index'], 'label' => t('app', 'Geo Opens'), 'active' => strpos($route, 'campaigns_geo_opens') === 0],
                    ['url' => ['campaigns_stats/index'], 'label' => t('app', 'Stats'), 'active' => strpos($route, 'campaigns_stats') === 0],
                    ['url' => ['campaign_tags/index'], 'label' => t('app', 'Custom tags'), 'active' => strpos($route, 'campaign_tags') === 0],
                    ['url' => ['campaigns_abuse_complaints/index'], 'label' => t('app', 'Abuse complaints'), 'active' => strpos($route, 'campaigns_abuse_complaints') === 0],
                    ['url' => ['campaigns_abuse_reports/index'], 'label' => t('app', 'Abuse reports'), 'active' => strpos($route, 'campaigns_abuse_reports') === 0],
                ],
            ],
            'templates' => [
                'name'      => t('app', 'Email templates'),
                'icon'      => 'glyphicon-text-width',
                'active'    => 'templates',
                'route'     => null,
                'items'     => [
                    ['url' => ['templates_categories/index'], 'label' => t('app', 'Categories'), 'active' => strpos($route, 'templates_categories') === 0],
                    ['url' => ['templates/index'], 'label' => t('app', 'Templates'), 'active' => in_array($route, ['templates/index', 'templates/create', 'templates/update'])],
                    ['url' => ['templates/gallery'], 'label' => t('app', 'Gallery'), 'active' => strpos($route, 'templates/gallery') === 0],
                ],
            ],
            'servers'       => [
                'name'      => t('app', 'Servers'),
                'icon'      => 'glyphicon-transfer',
                'active'    => ['delivery_servers', 'bounce_servers', 'feedback_loop_servers', 'email_box_monitors', 'delivery_server_warmup_plans'],
                'route'     => null,
                'items'     => [
                    ['url' => ['delivery_servers/index'], 'label' => t('app', 'Delivery servers'), 'active' => strpos($route, 'delivery_servers') === 0],
                    ['url' => ['bounce_servers/index'], 'label' => t('app', 'Bounce servers'), 'active' => strpos($route, 'bounce_servers') === 0],
                    ['url' => ['feedback_loop_servers/index'], 'label' => t('app', 'Feedback loop servers'), 'active' => strpos($route, 'feedback_loop_servers') === 0],
                    ['url' => ['email_box_monitors/index'], 'label' => t('app', 'Email box monitors'), 'active' => strpos($route, 'email_box_monitors') === 0],
                    ['url' => ['delivery_server_warmup_plans/index'], 'label' => t('app', 'Warmup plans'), 'active' => strpos($route, 'delivery_server_warmup_plans') === 0],
                ],
            ],
            'domains' => [
                'name'      => t('app', 'Domains'),
                'icon'      => 'glyphicon-globe',
                'active'    => ['sending_domains', 'tracking_domains'],
                'route'     => null,
                'items'     => [
                    ['url' => ['sending_domains/index'], 'label' => t('app', 'Sending domains'), 'active' => strpos($route, 'sending_domains') === 0],
                    ['url' => ['tracking_domains/index'], 'label' => t('app', 'Tracking domains'), 'active' => strpos($route, 'tracking_domains') === 0],
                ],
            ],
            'api-keys' => [
                'name'      => t('app', 'Api keys'),
                'icon'      => 'glyphicon-star',
                'active'    => 'api_keys',
                'route'     => ['api_keys/index'],
            ],
            'subaccounts' => [
                'name'      => t('app', 'Subaccounts'),
                'icon'      => 'fa-users',
                'active'    => 'subaccounts',
                'route'     => ['subaccounts/index'],
            ],
            'surveys' => [
                'name'      => t('app', 'Surveys'),
                'icon'      => 'glyphicon-list',
                'active'    => 'surveys',
                'route'     => ['surveys/index'],
            ],
            'articles' => [
                'name'      => t('app', 'Articles'),
                'icon'      => 'glyphicon-book',
                'active'    => 'article',
                'route'     => apps()->getAppUrl('frontend', 'articles', true),
                'items'     => [],
            ],
            'settings' => [
                'name'      => t('app', 'Settings'),
                'icon'      => 'glyphicon-cog',
                'active'    => 'settings',
                'route'     => null,
                'items'     => [],
            ],
        ];

        // lists
        if (is_subaccount() && !subaccount()->canManageLists()) {
            $menuItems['lists']['visible'] = false;
        }
        if (is_subaccount() && !subaccount()->canManageBlacklists()) {
            $menuItems['lists'][2]['visible'] = false;
            $menuItems['lists'][3]['visible'] = false;
        }

        // campaigns
        if (is_subaccount() && !subaccount()->canManageCampaigns()) {
            $menuItems['campaigns']['visible'] = false;
        }

        // templates
        if (is_subaccount() && !subaccount()->canManageEmailTemplates()) {
            $menuItems['templates']['visible'] = false;
        }

        // servers
        $maxDeliveryServers  = $customer->getGroupOption('servers.max_delivery_servers', 0);
        $maxBounceServers    = $customer->getGroupOption('servers.max_bounce_servers', 0);
        $maxFblServers       = $customer->getGroupOption('servers.max_fbl_servers', 0);
        $maxEmailBoxMonitors = $customer->getGroupOption('servers.max_email_box_monitors', 0);

        if (!$maxDeliveryServers && !$maxBounceServers && !$maxFblServers && !$maxEmailBoxMonitors) {
            $menuItems['servers']['visible'] = false;
        } else {
            foreach ([$maxDeliveryServers, $maxBounceServers, $maxFblServers, $maxEmailBoxMonitors] as $index => $value) {
                if (!$value && isset($menuItems['servers']['items'][$index])) { // @phpstan-ignore-line
                    $menuItems['servers']['items'][$index]['visible'] = false;
                }
            }
            if (!$maxDeliveryServers && isset($menuItems['servers']['items'][$index + 1])) { // @phpstan-ignore-line
                $menuItems['servers']['items'][$index + 1]['visible'] = false;
            }
        }
        if (is_subaccount() && !subaccount()->canManageServers()) {
            $menuItems['servers']['visible'] = false;
        }

        // domains
        if (SendingDomain::model()->getRequirementsErrors() || $customer->getGroupOption('sending_domains.can_manage_sending_domains', 'no') != 'yes') {
            $menuItems['domains']['items'][0]['visible'] = false;
        }

        if ($customer->getGroupOption('tracking_domains.can_manage_tracking_domains', 'no') != 'yes') {
            $menuItems['domains']['items'][1]['visible'] = false;
        }

        // @phpstan-ignore-next-line
        if (count($menuItems['domains']['items']) == 0) {
            $menuItems['domains']['visible'] = false;
        }

        if (is_subaccount() && !subaccount()->canManageDomains()) {
            $menuItems['domains']['visible'] = false;
        }

        // blacklist
        if ($customer->getGroupOption('lists.can_use_own_blacklist', 'no') != 'yes') {
            $menuItems['lists']['items'][2]['visible'] = false;
            $menuItems['lists']['items'][3]['visible'] = false;
        }

        // articles
        if ($customer->getGroupOption('common.show_articles_menu', 'no') != 'yes') {
            $menuItems['articles']['visible'] = false;
        }

        // surveys
        if ($customer->getGroupOption('surveys.max_surveys', -1) == 0) {
            $menuItems['surveys']['visible'] = false;
        }
        if (is_subaccount() && !subaccount()->canManageSurveys()) {
            $menuItems['surveys']['visible'] = false;
        }

        // monetization
        /** @var OptionMonetizationMonetization $optionMonetizationMonetization */
        $optionMonetizationMonetization = container()->get(OptionMonetizationMonetization::class);
        if (!$optionMonetizationMonetization->getIsEnabled()) {
            $menuItems['price_plans']['visible'] = false;
        }
        if (is_subaccount()) {
            $menuItems['price_plans']['visible'] = false;
        }

        /** @var OptionCommon $common */
        $common = container()->get(OptionCommon::class);

        // api keys
        if (!$common->getIsApiOnline()) {
            $menuItems['api-keys']['visible'] = false;
        } elseif ($customer->getGroupOption('api.enabled', 'yes') != 'yes') {
            $menuItems['api-keys']['visible'] = false;
        }
        if (is_subaccount() && !subaccount()->canManageApiKeys()) {
            $menuItems['api-keys']['visible'] = false;
        }

        // campaigns
        if ($customer->getGroupOption('campaigns.show_geo_opens', 'no') != 'yes') {
            $menuItems['campaigns']['items'][5]['visible'] = false;
        }

        if (is_subaccount() || $customer->getGroupOption('subaccounts.enabled', 'no') != 'yes') {
            $menuItems['subaccounts']['visible'] = false;
        }

        // filter out the items which should not be visible
        $menuItems = collect($menuItems)->map(function (array $item): array {
            // make sure we have defaults
            if (!isset($item['visible'])) {
                $item['visible'] = true;
            }
            if (!isset($item['items'])) {
                $item['items'] = [];
            }
            $item['has_children'] = !empty($item['items']);
            return $item;
        })->filter(function (array $item): bool {
            // keep items that are visible
            return $item['visible'] === true;
        })->map(function (array $item): array {
            // make sure we have defaults and keep only visible sub-items
            $item['items'] = collect($item['items'])->map(function (array $item): array {
                if (!isset($item['visible'])) {
                    $item['visible'] = true;
                }
                return $item;
            })->filter(function (array $item): bool {
                return $item['visible'] === true;
            })->all();
            return $item;
        })->filter(function (array $item): bool {
            // filter out the items without submenu items but which initially had them
            return !(empty($item['items']) && $item['has_children']);
        })->map(function (array $item): array {
            // remove the flags we have set
            unset($item['has_children']);
            return $item;
        })->all();

        /** @var array $menuItems */
        $menuItems = (array)hooks()->applyFilters('customer_left_navigation_menu_items', $menuItems);

        if (empty($menuItems['settings']['items'])) {
            unset($menuItems['settings']);
        }

        return $menuItems;
    }

    /**
     * @return void
     * @throws CException
     */
    public function buildMenu()
    {
        $controller = $this->getController();
        $route      = $controller->getRoute();

        Yii::import('zii.widgets.CMenu');

        $menu = new CMenu();
        $menu->htmlOptions          = ['class' => 'sidebar-menu'];
        $menu->submenuHtmlOptions   = ['class' => 'treeview-menu'];
        $menuItems                  = $this->getMenuItems();

        foreach ($menuItems as $data) {
            $_route  = !empty($data['route']) ? $data['route'] : 'javascript:;';
            $active  = false;

            if (!empty($data['active']) && is_string($data['active']) && strpos($route, $data['active']) === 0) {
                $active = true;
            } elseif (!empty($data['active']) && is_array($data['active'])) {
                foreach ($data['active'] as $in) {
                    if (strpos($route, $in) === 0) {
                        $active = true;
                        break;
                    }
                }
            }

            $item = [
                'url'         => $_route,
                'label'       => IconHelper::make($data['icon']) . ' <span>' . $data['name'] . '</span>' . (!empty($data['items']) ? '<span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span>' : ''),
                'active'      => $active,
                'linkOptions' => !empty($data['linkOptions']) && is_array($data['linkOptions']) ? $data['linkOptions'] : [],
            ];

            if (!empty($data['items'])) {
                foreach ($data['items'] as $index => $i) {
                    if (isset($i['label'])) {
                        $data['items'][$index]['label'] = '<i class="fa fa-circle text-primary"></i>' . $i['label'];
                    }
                }
                $item['items']       = $data['items'];
                $item['itemOptions'] = ['class' => 'treeview'];
            }

            $menu->items[] = $item;
        }

        $menu->run();
    }

    /**
     * @return void
     * @throws CException
     */
    public function run()
    {
        $this->buildMenu();
    }
}
