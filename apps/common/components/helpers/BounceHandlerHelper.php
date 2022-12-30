<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * BounceHandlerHelper
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.9.7
 */

class BounceHandlerHelper
{
    /**
     * @return array
     */
    public static function getRules(): array
    {
        $cacheTtl = 3600 * 24;
        $cacheKey = sha1(__METHOD__);
        if (($rules = cache()->get($cacheKey)) !== false) {
            return $rules;
        }

        /** @var OptionLicense $license */
        $license = container()->get(OptionLicense::class);

        $licenseKey = $license->getPurchaseCode();
        if (empty($licenseKey)) {
            cache()->set($cacheKey, [], $cacheTtl);
            return [];
        }

        try {
            $response = (new GuzzleHttp\Client())->get('https://www.mailwizz.com/api/bounces/rules', [
                'timeout' => 10,
                'headers' => [
                    'X-LICENSEKEY' => $licenseKey,
                ],
            ]);
        } catch (Exception $e) {
            cache()->set($cacheKey, [], $cacheTtl);
            return [];
        }

        if ((int)$response->getStatusCode() !== 200) {
            cache()->set($cacheKey, [], $cacheTtl);
            return [];
        }

        $_rules = (array)json_decode((string)$response->getBody(), true);
        if (empty($_rules) || empty($_rules['rules'])) {
            cache()->set($cacheKey, [], $cacheTtl);
            return [];
        }

        cache()->set($cacheKey, $_rules['rules'], $cacheTtl);
        return (array)$_rules['rules'];
    }
}
