<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * AccessHelper
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.5
 */

class AccessHelper
{
    /**
     * @param string|array $route
     *
     * @return bool
     */
    public static function hasRouteAccess($route): bool
    {
        if (apps()->isAppName('backend') && app()->hasComponent('user') && user()->getId() && user()->getModel()) {
            return (bool)user()->getModel()->hasRouteAccess($route);
        }
        return true;
    }
}
