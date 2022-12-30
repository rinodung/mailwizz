<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * FrontendHttpRequest
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.6.2
 */

class FrontendHttpRequest extends BaseHttpRequest
{
    /**
     * @return bool
     * @throws CException
     */
    protected function checkCurrentRoute()
    {
        if (stripos($this->getPathInfo(), 'webhook') !== false) {
            return false;
        }
        return parent::checkCurrentRoute();
    }
}
