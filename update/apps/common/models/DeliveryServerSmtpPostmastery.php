<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * DeliveryServerSmtpPostmastery
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.5.3
 */

class DeliveryServerSmtpPostmastery extends DeliveryServerSmtp
{
    /**
     * @var string
     */
    protected $serverType = 'smtp-postmastery';

    /**
     * @var string
     */
    protected $_providerUrl = 'https://www.postmastery.com/';

    /**
     * @inheritDoc
     */
    public function afterConstruct()
    {
        parent::afterConstruct();

        $this->port = 587;
        $this->additional_headers = [
            ['name' => 'x-job', 'value' => '[CAMPAIGN_UID]'],
        ];
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return DeliveryServerSmtpPostmastery the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var DeliveryServerSmtpPostmastery $model */
        $model = parent::model($className);

        return $model;
    }

    /**
     * @inheritDoc
     */
    public function getParamsArray(array $params = []): array
    {
        $params['transport'] = self::TRANSPORT_SMTP;
        return parent::getParamsArray($params);
    }

    /**
     * @inheritDoc
     */
    public function getDswhUrl(): string
    {
        /** @var OptionUrl $optionUrl */
        $optionUrl = container()->get(OptionUrl::class);

        $url = $optionUrl->getFrontendUrl('dswh/postmastery');
        if (is_cli()) {
            return $url;
        }
        if (request()->getIsSecureConnection() && parse_url($url, PHP_URL_SCHEME) == 'http') {
            $url = substr_replace($url, 'https', 0, 4);
        }
        return $url;
    }
}
