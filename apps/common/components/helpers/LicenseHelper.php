<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * LicenseHelper
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.5.0
 */

class LicenseHelper
{
    /**
     * @param OptionLicense|null $model
     *
     * @return Psr\Http\Message\ResponseInterface
     */
    public static function verifyLicense(?OptionLicense $model = null): Psr\Http\Message\ResponseInterface
    {
        if ($model === null) {
            /** @var OptionLicense $model */
            $model = container()->get(OptionLicense::class);
        }

        $criteria = new CDbCriteria();
        $criteria->compare('status', PricePlanOrder::STATUS_COMPLETE);
        $criteria->addCondition('total > 0');
        $ordersCount = PricePlanOrder::model()->count($criteria);

        return (new GuzzleHttp\Client())->post('https://www.mailwizz.com/api/license/verify', [
            'form_params' => [
                'key'           => $model->purchase_code,
                'orders_count'  => $ordersCount,
            ],
        ]);
    }
}
