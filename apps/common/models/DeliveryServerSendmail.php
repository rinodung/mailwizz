<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * DeliveryServerSendmail
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.2
 */

class DeliveryServerSendmail extends DeliveryServer
{

    /**
     * @var string
     */
    public $sendmail_path = '/usr/sbin/sendmail';
    /**
     * @var string
     */
    protected $serverType = 'sendmail';

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            ['sendmail_path', 'required'],
        ];

        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'sendmail_path' => t('servers', 'Sendmail path'),
        ];

        return CMap::mergeArray(parent::attributeLabels(), $labels);
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

        if ($sent = $this->getMailer()->send($params)) {
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
    public function getParamsArray(array $params = []): array
    {
        $params['transport']    = self::TRANSPORT_SENDMAIL;
        $params['sendmailPath'] = $this->sendmail_path;
        return parent::getParamsArray($params);
    }

    /**
     * @return array
     */
    public function attributeHelpTexts()
    {
        $texts = [
            'sendmail_path'    => t('servers', 'The path to the sendmail executable, usually "{path}"', ['{path}' => '/usr/sbin/sendmail']),
        ];

        return CMap::mergeArray(parent::attributeHelpTexts(), $texts);
    }

    /**
     * @return array
     */
    public function attributePlaceholders()
    {
        $placeholders = [
            'sendmail_path'    => t('servers', 'i.e: /usr/sbin/sendmail'),
        ];

        return CMap::mergeArray(parent::attributePlaceholders(), $placeholders);
    }

    /**
     * @return string
     */
    public function requirementsFailedMessage(): string
    {
        if (!CommonHelper::functionExists('proc_open')) {
            return t('servers', 'The server type {type} requires following functions to be active on your host: {functions}!', [
                '{type}'      => $this->serverType,
                '{functions}' => 'proc_open',
            ]);
        }
        return parent::requirementsFailedMessage();
    }

    /**
     * @inheritDoc
     */
    public function getCanEmbedImages(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function getFormFieldsDefinition(array $fields = []): array
    {
        $form = new CActiveForm();
        return parent::getFormFieldsDefinition(CMap::mergeArray([
            'hostname'                => null,
            'username'                => null,
            'password'                => null,
            'port'                    => null,
            'protocol'                => null,
            'timeout'                 => null,
            'max_connection_messages' => null,
            'sendmail_path'           => [
                'visible'   => true,
                'fieldHtml' => $form->textField($this, 'sendmail_path', $this->fieldDecorator->getHtmlOptions('sendmail_path')),
            ],
        ], $fields));
    }

    /**
     * @return void
     */
    protected function afterConstruct()
    {
        if ($path = $this->modelMetaData->getModelMetaData()->itemAt('sendmail_path')) {
            $this->sendmail_path = $path;
        }

        parent::afterConstruct();
    }

    /**
     * @return void
     */
    protected function afterFind()
    {
        if ($path = $this->modelMetaData->getModelMetaData()->itemAt('sendmail_path')) {
            $this->sendmail_path = $path;
        }

        parent::afterFind();
    }

    /**
     * @return bool
     */
    protected function beforeValidate()
    {
        $this->hostname = 'sendmail.local.host';
        $this->port     = null;
        $this->timeout  = null;

        return parent::beforeValidate();
    }

    /**
     * @return bool
     */
    protected function beforeSave()
    {
        $this->modelMetaData->getModelMetaData()->add('sendmail_path', $this->sendmail_path);
        return parent::beforeSave();
    }
}
