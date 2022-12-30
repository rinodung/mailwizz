<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * DeliveryServerSmtp
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

class DeliveryServerSmtp extends DeliveryServer
{
    /**
     * @var string
     */
    protected $serverType = 'smtp';

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            ['port, timeout', 'required'],
        ];

        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return DeliveryServer the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var DeliveryServer $model */
        $model = parent::model($className);

        return $model;
    }

    /**
     * @return array
     * @throws CException
     */
    public function sendEmail(array $params = []): array
    {
        /** @var array $params */
        $params = (array)hooks()->applyFilters('delivery_server_before_send_email', $this->getParamsArray($params), $this);

        if ($this->getMailer()->send($params)) {
            $sent = ['message_id' => $this->getMailer()->getEmailMessageId()];
            $this->logUsage();
        } else {
            $sent = [];
        }

        hooks()->doAction('delivery_server_after_send_email', $params, $this, $sent);

        return $sent;
    }

    /**
     * @inheritDoc
     */
    public function getCanEmbedImages(): bool
    {
        return true;
    }
}
