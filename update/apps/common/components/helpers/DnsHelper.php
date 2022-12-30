<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * DnsHelper
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.0.30
 */

class DnsHelper
{
    /**
     * @return array
     */
    public static function getDnsResolverNameservers(): array
    {
        return (array)hooks()->applyFilters('dns_resolver_nameservers', (array)app_param('dns.resolver.nameservers', []));
    }
}
