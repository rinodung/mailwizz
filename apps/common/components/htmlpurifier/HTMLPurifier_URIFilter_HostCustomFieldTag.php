<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * HTMLPurifier_URIFilter_HostCustomFieldTag
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.6.1
 */

class HTMLPurifier_URIFilter_HostCustomFieldTag extends HTMLPurifier_URIFilter
{
    /**
     * @var string
     */
    public $name = 'HostCustomFieldTag';

    /**
     * @param HTMLPurifier_URI $uri
     * @param HTMLPurifier_Config $config
     * @param HTMLPurifier_Context $context
     *
     * @return bool
     */
    public function filter(&$uri, $config, $context)
    {
        return true;
    }
}
