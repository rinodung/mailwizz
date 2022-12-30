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
    public function getMenuItems(): array
    {
        $controller = $this->getController();
        $route      = $controller->getRoute();

        /** @var User $user */
        $user = user()->getModel();

        /** @var OptionCommon $common */
        $common = container()->get(OptionCommon::class);

        /** @var string $supportUrl */
        $supportUrl = $common->getSupportUrl();
        if (empty($supportUrl) && defined('MW_SUPPORT_KB_URL')) {
            $supportUrl = MW_SUPPORT_KB_URL;
        }

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
            'support' => [
                'name'        => t('app', 'Support'),
                'icon'        => 'glyphicon-question-sign',
                'active'      => '',
                'route'       => $supportUrl,
                'linkOptions' => ['target' => '_blank'],
            ],
            'dashboard' => [
                'name'      => t('app', 'Dashboard'),
                'icon'      => 'glyphicon-dashboard',
                'active'    => 'dashboard',
                'route'     => ['dashboard/index'],
            ],
            'users' => [
                'name'      => t('app', 'Users'),
                'icon'      => 'glyphicon-user',
                'active'    => ['users', 'user_groups'],
                'route'     => null,
                'items'     => [
                    ['url' => ['users/index'], 'label' => t('app', 'Users'), 'active' => strpos($route, 'users') === 0],
                    ['url' => ['user_groups/index'], 'label' => t('app', 'Groups'), 'active' => strpos($route, 'user_groups') === 0],
                ],
            ],
            'customers' => [
                'name'      => t('app', 'Customers'),
                'icon'      => 'fa-users',
                'active'    => ['customer', 'campaigns', 'lists', 'list_subscribers', 'survey'],
                'route'     => null,
                'items'     => [
                    ['url' => ['customers/index'], 'label' => t('app', 'Customers'), 'active' => strpos($route, 'customers') === 0 && strpos($route, 'customers_mass_emails') === false && strpos($route, 'customers_notes') === false],
                    ['url' => ['customer_groups/index'], 'label' => t('app', 'Groups'), 'active' => strpos($route, 'customer_groups') === 0],
                    ['url' => ['lists/index'], 'label' => t('app', 'Lists'), 'active' => strpos($route, 'lists') === 0],
                    ['url' => ['campaigns/index'], 'label' => t('app', 'All campaigns'), 'active' => $route == 'campaigns/index'],
                    ['url' => ['campaigns/regular'], 'label' => t('app', 'Regular campaigns'), 'active' => $route == 'campaigns/regular'],
                    ['url' => ['campaigns/autoresponder'], 'label' => t('app', 'Autoresponders'), 'active' => $route == 'campaigns/autoresponder'],
                    ['url' => ['surveys/index'], 'label' => t('app', 'Surveys'), 'active' => strpos($route, 'surveys') === 0],
                    ['url' => ['customers_mass_emails/index'], 'label' => t('app', 'Mass emails'), 'active' => strpos($route, 'customers_mass_emails') === 0],
                    ['url' => ['customer_messages/index'], 'label' => t('app', 'Messages'), 'active' => strpos($route, 'customer_messages') === 0],
                    ['url' => ['customer_login_logs/index'], 'label' => t('app', 'Login logs'), 'active' => strpos($route, 'customer_login_logs') === 0],
                    ['url' => ['customers_notes/index'], 'label' => t('app', 'Notes'), 'active' => strpos($route, 'customers_notes') === 0],
                ],
            ],
            'monetization' => [
                'name'      => t('app', 'Monetization'),
                'icon'      => 'glyphicon-credit-card',
                'active'    => ['payment_gateway', 'price_plans', 'orders', 'promo_codes', 'currencies', 'taxes'],
                'route'     => null,
                'items'     => [
                    ['url' => ['payment_gateways/index'], 'label' => t('app', 'Payment gateways'), 'active' => strpos($route, 'payment_gateway') === 0],
                    ['url' => ['price_plans/index'], 'label' => t('app', 'Price plans'), 'active' => strpos($route, 'price_plans') === 0],
                    ['url' => ['orders/index'], 'label' => t('app', 'Orders'), 'active' => strpos($route, 'orders') === 0],
                    ['url' => ['promo_codes/index'], 'label' => t('app', 'Promo codes'), 'active' => strpos($route, 'promo_codes') === 0],
                    ['url' => ['currencies/index'], 'label' => t('app', 'Currencies'), 'active' => strpos($route, 'currencies') === 0],
                    ['url' => ['taxes/index'], 'label' => t('app', 'Taxes'), 'active' => strpos($route, 'taxes') === 0],
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
            'email-templates' => [
                'name'      => t('app', 'Email templates'),
                'icon'      => 'glyphicon-text-width',
                'active'    => ['email_templates_categories', 'email_templates_gallery'],
                'route'     => null,
                'items'     => [
                    ['url' => ['email_templates_categories/index'], 'label' => t('app', 'Categories'), 'active' => strpos($route, 'email_templates_categories') === 0],
                    ['url' => ['email_templates_gallery/index'], 'label' => t('app', 'Gallery'), 'active' => strpos($route, 'email_templates_gallery') === 0],
                ],
            ],
            'blacklist' => [
                'name'      => t('app', 'Email blacklist'),
                'icon'      => 'glyphicon-ban-circle',
                'active'    => ['email_blacklist', 'block_email_request'],
                'route'     => null,
                'items'     => [
                    ['url' => ['email_blacklist/index'], 'label' => t('app', 'Email blacklist'), 'active' => $route == 'email_blacklist' || strpos($route, 'email_blacklist/') === 0],
                    ['url' => ['domain_blacklist/index'], 'label' => t('app', 'Domain blacklist'), 'active' => $route == 'domain_blacklist' || strpos($route, 'domain_blacklist/') === 0],
                    ['url' => ['email_blacklist_monitors/index'], 'label' => t('app', 'Blacklist monitors'), 'active' => strpos($route, 'email_blacklist_monitors') === 0],
                    ['url' => ['block_email_request/index'], 'label' => t('app', 'Block email requests'), 'active' => strpos($route, 'block_email_request') === 0],
                ],
            ],
            'extend' => [
                'name'      => t('app', 'Extend'),
                'icon'      => 'glyphicon-plus-sign',
                'active'    => ['extensions', 'theme', 'languages', 'ext'],
                'route'     => null,
                'items'     => [
                    ['url' => ['extensions/index'], 'label' => t('app', 'Extensions'), 'active' => strpos($route, 'ext') === 0],
                    ['url' => ['theme/index'], 'label' => t('app', 'Themes'), 'active' => strpos($route, 'theme') === 0],
                    ['url' => ['languages/index'], 'label' => t('app', 'Languages'), 'active' => strpos($route, 'languages') === 0],
                ],
            ],
            'content' => [
                'name'      => t('app', 'Content'),
                'icon'      => 'glyphicon-folder-open',
                'active'    => ['article', 'pages', 'menus', 'list_page_type'],
                'route'     => null,
                'items'     => [
                    ['url' => ['list_page_type/index'], 'label' => t('app', 'List page types'), 'active' => strpos($route, 'list_page_type/index') === 0],
                    ['url' => ['menus/index'], 'label' => t('app', 'Menus'), 'active' => in_array($route, ['menus/index', 'menus/create', 'menus/update'])],
                    ['url' => ['pages/index'], 'label' => t('app', 'Pages'), 'active' => strpos($route, 'pages/index') === 0],
                    ['url' => ['articles/index'], 'label' => t('app', 'Articles'), 'active' => strpos($route, 'articles/index') === 0],
                    ['url' => ['article_categories/index'], 'label' => t('app', 'Articles categories'), 'active' => strpos($route, 'article_categories') === 0],
                ],
            ],
            'locations' => [
                'name'      => t('app', 'Locations'),
                'icon'      => 'glyphicon-globe',
                'active'    => ['ip_location_services', 'maxmind', 'countries', 'zones'],
                'route'     => null,
                'items'     => [
                    ['url' => ['ip_location_services/index'], 'label' => t('app', 'Ip location services'), 'active' => strpos($route, 'ip_location_services') === 0],
                    ['url' => ['maxmind/index'], 'label' => t('app', 'Maxmind Database'), 'active' => strpos($route, 'maxmind') === 0],
                    ['url' => ['countries/index'], 'label' => t('app', 'Countries'), 'active' => strpos($route, 'countries') === 0],
                    ['url' => ['zones/index'], 'label' => t('app', 'Zones'), 'active' => strpos($route, 'zones') === 0],
                ],
            ],
            'settings' => [
                'name'      => t('app', 'Settings'),
                'icon'      => 'glyphicon-cog',
                'active'    => ['settings', 'start_pages', 'common_email_templates'],
                'route'     => null,
                'items'     => [
                    ['url' => ['settings/index'], 'label' => t('app', 'Common'), 'active' => strpos($route, 'settings/index') === 0],
                    ['url' => ['settings/system_urls'], 'label' => t('app', 'System urls'), 'active' => strpos($route, 'settings/system_urls') === 0],
                    ['url' => ['settings/reverse_proxy'], 'label' => t('app', 'Reverse proxy'), 'active' => strpos($route, 'settings/reverse_proxy') === 0],
                    ['url' => ['settings/import_export'], 'label' => t('app', 'Import/Export'), 'active' => strpos($route, 'settings/import_export') === 0],
                    ['url' => ['settings/email_templates'], 'label' => t('app', 'Email templates'), 'active' => strpos($route, 'settings/email_templates') === 0 || strpos($route, 'common_email_templates') === 0],
                    ['url' => ['settings/cron'], 'label' => t('app', 'Cron'), 'active' => strpos($route, 'settings/cron') === 0],
                    ['url' => ['settings/email_blacklist'], 'label' => t('app', 'Email blacklist'), 'active' => strpos($route, 'settings/email_blacklist') === 0],
                    ['url' => ['settings/campaign_attachments'], 'label' => t('app', 'Campaigns'), 'active' => strpos($route, 'settings/campaign_') === 0],
                    ['url' => ['settings/transactional_email_attachments'], 'label' => t('app', 'Transactional emails'), 'active' => strpos($route, 'settings/transactional_email_') === 0],
                    ['url' => ['settings/customer_common'], 'label' => t('app', 'Customers'), 'active' => strpos($route, 'settings/customer_') === 0],
                    ['url' => ['settings/2fa'], 'label' => t('app', '2FA'), 'active' => strpos($route, 'settings/2fa') === 0],
                    ['url' => ['settings/api_ip_access'], 'label' => t('app', 'Api'), 'active' => strpos($route, 'settings/api_ip_access') === 0],
                    ['url' => ['start_pages/index'], 'label' => t('app', 'Start pages'), 'active' => strpos($route, 'start_pages') === 0],
                    ['url' => ['settings/monetization'], 'label' => t('app', 'Monetization'), 'active' => strpos($route, 'settings/monetization') === 0],
                    ['url' => ['settings/customization'], 'label' => t('app', 'Customization'), 'active' => strpos($route, 'settings/customization') === 0],
                    ['url' => ['settings/cdn'], 'label' => t('app', 'CDN'), 'active' => strpos($route, 'settings/cdn') === 0],
                    ['url' => ['settings/spf_dkim'], 'label' => t('app', 'SPF/DKIM'), 'active' => strpos($route, 'settings/spf_dkim') === 0],
                    ['url' => ['settings/license'], 'label' => t('app', 'License'), 'active' => strpos($route, 'settings/license') === 0],
                    ['url' => ['settings/social_links'], 'label' => t('app', 'Social links'), 'active' => strpos($route, 'settings/social_links') === 0],
                ],
            ],
            'misc' => [
                'name'      => t('app', 'Miscellaneous'),
                'icon'      => 'glyphicon-bookmark',
                'active'    => ['misc', 'transactional_emails', 'company_types', 'campaign_abuse_reports'],
                'route'     => null,
                'items'     => [
                    ['url' => ['misc/campaigns_delivery_logs'], 'label' => t('app', 'Campaigns delivery logs'), 'active' => strpos($route, 'misc/campaigns_delivery_logs') === 0],
                    ['url' => ['misc/campaigns_bounce_logs'], 'label' => t('app', 'Campaigns bounce logs'), 'active' => strpos($route, 'misc/campaigns_bounce_logs') === 0],
                    ['url' => ['misc/campaigns_stats'], 'label' => t('app', 'Campaigns stats'), 'active' => strpos($route, 'misc/campaigns_stats') === 0],
                    ['url' => ['campaign_abuse_reports/index'], 'label' => t('app', 'Campaign abuse reports'), 'active' => strpos($route, 'campaign_abuse_reports/index') === 0],
                    ['url' => ['transactional_emails/index'], 'label' => t('app', 'Transactional emails'), 'active' => strpos($route, 'transactional_emails') === 0],
                    ['url' => ['misc/delivery_servers_usage_logs'], 'label' => t('app', 'Delivery servers usage logs'), 'active' => strpos($route, 'misc/delivery_servers_usage_logs') === 0],
                    ['url' => ['company_types/index'], 'label' => t('app', 'Company types'), 'active' => strpos($route, 'company_types') === 0],
                    ['url' => ['misc/application_log'], 'label' => t('app', 'Application log'), 'active' => strpos($route, 'misc/application_log') === 0],
                    ['url' => ['misc/emergency_actions'], 'label' => t('app', 'Emergency actions'), 'active' => strpos($route, 'misc/emergency_actions') === 0],
                    ['url' => ['misc/guest_fail_attempts'], 'label' => t('app', 'Guest fail attempts'), 'active' => strpos($route, 'misc/guest_fail_attempts') === 0],
                    ['url' => ['misc/cron_jobs_list'], 'label' => t('app', 'Cron jobs list'), 'active' => strpos($route, 'misc/cron_jobs_list') === 0],
                    ['url' => ['misc/cron_jobs_history'], 'label' => t('app', 'Cron jobs history'), 'active' => strpos($route, 'misc/cron_jobs_history') === 0],
                    ['url' => ['misc/queue_monitor'], 'label' => t('app', 'Queue monitor'), 'active' => strpos($route, 'misc/queue_monitor') === 0],
                    ['url' => ['misc/phpinfo'], 'label' => t('app', 'PHP info'), 'active' => strpos($route, 'misc/phpinfo') === 0],
                    ['url' => ['misc/changelog'], 'label' => t('app', 'Changelog'), 'active' => strpos($route, 'misc/changelog') === 0],
                ],
            ],
            'store' => [
                'name'        => t('app', 'Store'),
                'icon'        => 'glyphicon-shopping-cart',
                'active'      => 'store',
                'route'       => 'https://store.onetwist.com/index.php?product[]=mailwizz',
                'linkOptions' => ['target' => '_blank'],
            ],
        ];

        if ($supportUrl == '') {
            unset($menuItems['support']);
        }

        if (!app_param('store.enabled', true)) {
            unset($menuItems['store']);
        }

        /** @var array<array> $menuItems */
        $menuItems = (array)hooks()->applyFilters('backend_left_navigation_menu_items', $menuItems);

        /**
         * @since since 1.3.5
         *
         * @var int $key
         * @var array $data
         */
        foreach ($menuItems as $key => $data) {
            if (!empty($data['route']) && !$user->hasRouteAccess($data['route'])) {
                unset($menuItems[$key]);
                continue;
            }
            if (isset($data['items']) && is_array($data['items'])) {
                foreach ($data['items'] as $index => $item) {
                    if (isset($item['url']) && !$user->hasRouteAccess($item['url'])) {
                        unset($menuItems[$key]['items'][$index], $data['items'][$index]);
                    }
                }
            }
            if (empty($data['route']) && empty($data['items'])) {
                unset($menuItems[$key]);
            }
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
