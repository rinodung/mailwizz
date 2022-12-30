<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * FavoritePageWidget
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.1.11
 */

class FavoritePageWidget extends CWidget
{
    /**
     * @var string
     */
    public $route = '';

    /**
     * @var string
     */
    public $label = '';

    /**
     * @var array
     */
    public $route_params = [];

    /**
     * @return void
     */
    public function init()
    {
        parent::init();
        clientScript()->registerCssFile(apps()->getBaseUrl('assets/css/favorite-page.css'));
        clientScript()->registerScriptFile(apps()->getBaseUrl('assets/js/favorite-page.js'));
    }

    /**
     * @return void
     * @throws CException
     */
    public function run()
    {
        $controller = app()->getController();

        if (!$this->route) {
            $this->route = $controller->getRoute();
        }

        if (!$this->label) {
            $this->label = $controller->pageTitle;
        }

        if (!$this->route_params) {
            $this->route_params = $controller->getActionParams();
        }

        if (!$this->getIsVisible()) {
            return;
        }

        $serializedRouteParams = empty($this->route_params) ? '' : serialize($this->route_params);

        $hash = sha1($this->route . (string)$serializedRouteParams);

        $criteria = new CDbCriteria();

        if (apps()->isAppName('customer')) {
            $criteria->compare('customer_id', (int)customer()->getId());
        } elseif (apps()->isAppName('backend')) {
            $criteria->compare('user_id', (int)user()->getId());
        }
        $criteria->compare('route_hash', $hash);

        $page = FavoritePage::model()->find($criteria);

        $favoritePageColorClass = empty($page) ? 'favorite-page-gray' : 'favorite-page-green';
        $action                 = empty($page) ? t('app', 'add this page to') : t('app', 'remove this page from');
        $dataConfirmText        = t('favorite_pages', 'Are you sure you want to {action} favorites?', ['{action}' =>  $action]);
        $title                  = t('favorite_pages', 'Please click here to {action} favorites', ['{action}' =>  $action]);

        $this->render('favorite-page', [
            'route'                  => $this->route,
            'serializedRouteParams'  => $serializedRouteParams,
            'label'                  => $this->label,
            'favoritePageColorClass' => $favoritePageColorClass,
            'dataConfirmText'        => $dataConfirmText,
            'title'                  => $title,
        ]);
    }

    /**
     * @return bool
     */
    protected function getIsVisible(): bool
    {
        foreach ($this->getIncludedRoutes() as $includedRoute) {
            if (strpos($this->route, $includedRoute['route']) === false) {
                continue;
            }

            if (empty($includedRoute['param']) || in_array($includedRoute['param'], array_values($this->route_params))) {
                return true;
            }
        }

        foreach ($this->getExcludedRoutes() as $excludedRoute) {
            if (strpos($this->route, $excludedRoute['route']) === false) {
                continue;
            }

            if (empty($excludedRoute['param']) || in_array($excludedRoute['param'], array_values($this->route_params))) {
                return false;
            }
        }
        return true;
    }

    /**
     * @return array
     */
    protected function getExcludedRoutes(): array
    {
        return [
            ['route' => 'favorite_pages', 'param' => null],
            ['route' => 'update', 'param' => null],
            ['route' => 'import', 'param' => null],
            ['route' => 'export', 'param' => null],
            ['route' => 'campaigns/setup', 'param' => null],
            ['route' => 'campaigns/template', 'param' => null],
            ['route' => 'campaigns/confirm', 'param' => null],
            ['route' => 'price_plans/payment', 'param' => null],
            ['route' => 'users/2fa', 'param' => null],
            ['route' => 'customers/2fa', 'param' => null],
            ['route' => 'orders/view', 'param' => null],
            ['route' => 'misc/application_log', 'param' => '404'],
        ];
    }

    /**
     * @return array
     */
    protected function getIncludedRoutes(): array
    {
        return [
            ['route' => 'delivery_servers/update', 'param' => null],
        ];
    }
}
