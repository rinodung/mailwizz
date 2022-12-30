<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * DeliveryServerSmtpAmazon
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

class DeliveryServerSmtpAmazon extends DeliveryServerSmtp
{
    /**
     * @var string
     */
    protected $serverType = 'smtp-amazon';

    /**
     * @var string
     */
    protected $_providerUrl = 'https://aws.amazon.com/ses/';

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            ['username, password, port, timeout', 'required'],
        ];

        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return DeliveryServerSmtpAmazon the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var DeliveryServerSmtpAmazon $model */
        $model = parent::model($className);

        return $model;
    }

    /**
     * @param array $params
     *
     * @return array
     * @throws CException
     */
    public function getParamsArray(array $params = []): array
    {
        $params['transport'] = self::TRANSPORT_SMTP;
        return parent::getParamsArray($params);
    }

    /**
     * @return array
     */
    public function attributeHelpTexts()
    {
        $texts = [
            'hostname'    => t('servers', 'Your Amazon SES hostname, usually this is standard and looks like the following: email-smtp.us-east-1.amazonaws.com.'),
            'username'    => t('servers', 'Your Amazon SES SMTP Username'),
            'password'    => t('servers', 'Your Amazon SES SMTP Password'),
            'port'        => t('servers', 'Amazon SES supports the following ports: 25, 465 or 587.'),
            'protocol'    => t('servers', 'There is no need to select a protocol for Amazon SES, but if you need a secure connection, TLS is supported.'),
            'from_email'  => t('servers', 'Your Amazon SES email address approved for sending emails.'),
        ];

        return CMap::mergeArray(parent::attributeHelpTexts(), $texts);
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'username'  => t('servers', 'Access Key ID'),
            'password'  => t('servers', 'Secret Access Key'),
        ];

        return CMap::mergeArray(parent::attributeLabels(), $labels);
    }

    /**
     * @return array
     */
    public function attributePlaceholders()
    {
        $placeholders = [
            'hostname'  => t('servers', 'i.e: email-smtp.us-east-1.amazonaws.com'),
            'username'  => t('servers', 'i.e: AKIAIYYYYYYYYYYUBBFQ'),
            'password'  => t('servers', 'i.e: pnSXPeHkmapf6gghCyfIDz8YJce9iu9fzyqLB123'),
        ];

        return CMap::mergeArray(parent::attributePlaceholders(), $placeholders);
    }

    /**
     * @return bool
     */
    public function getCanEmbedImages(): bool
    {
        return true;
    }

    /**
     * @param array $fields
     *
     * @return array
     * @throws CException
     */
    public function getFormFieldsDefinition(array $fields = []): array
    {
        return parent::getFormFieldsDefinition(CMap::mergeArray([
            'signing_enabled' => null,
            'force_sender'    => null,
        ], $fields));
    }
}
