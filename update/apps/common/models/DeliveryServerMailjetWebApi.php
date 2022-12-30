<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * DeliveryServerMailjetWebApi
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.6.3
 *
 */

class DeliveryServerMailjetWebApi extends DeliveryServer
{
    /**
     * @var string
     */
    protected $serverType = 'mailjet-web-api';

    /**
     * @var string
     */
    protected $_initStatus;

    /**
     * @var string
     */
    protected $_preCheckError = '';

    /**
     * @var string
     */
    protected $_providerUrl = 'https://www.mailjet.com/';

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            ['username, password', 'required'],
            ['password', 'length', 'max' => 255],
        ];
        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'username'   => t('servers', 'Api key'),
            'password'   => t('servers', 'Api secret'),
        ];
        return CMap::mergeArray(parent::attributeLabels(), $labels);
    }

    /**
     * @return array
     */
    public function attributeHelpTexts()
    {
        $texts = [
            'username'    => t('servers', 'Your mailjet api key'),
            'password'    => t('servers', 'Your mailjet api secret'),
            'force_from'  => t('servers', 'When to force the FROM address. Please note that if you set this option to Never and you send from a unverified domain, all your emails will fail delivery. It is best to leave this option as is unless you really know what you are doing.'),
        ];

        return CMap::mergeArray(parent::attributeHelpTexts(), $texts);
    }

    /**
     * @return array
     */
    public function attributePlaceholders()
    {
        $placeholders = [
            'username'   => '124d28f660d808e0ea7bc19fc5cda116',
            'password'   => '0f1105ac9bc5ecd3f88ec8a172d25d22',
        ];

        return CMap::mergeArray(parent::attributePlaceholders(), $placeholders);
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

        if (!ArrayHelper::hasKeys($params, ['from', 'to', 'subject', 'body'])) {
            return [];
        }

        [$toEmail, $toName]     = $this->getMailer()->findEmailAndName($params['to']);
        [$fromEmail, $fromName] = $this->getMailer()->findEmailAndName($params['from']);

        if (!empty($params['fromName'])) {
            $fromName = $params['fromName'];
        }

        $replyToEmail = null;
        if (!empty($params['replyTo'])) {
            [$replyToEmail] = $this->getMailer()->findEmailAndName($params['replyTo']);
        }

        $headers = [];
        if (!empty($params['headers'])) {
            $headers = $this->parseHeadersIntoKeyValue($params['headers']);
        }
        $headers['Reply-To']   = $replyToEmail;

        $metaData = [];
        if (!empty($params['campaignUid'])) {
            $metaData['campaign_uid'] = $params['campaignUid'];
        }
        if (!empty($params['subscriberUid'])) {
            $metaData['subscriber_uid'] = $params['subscriberUid'];
        }

        $sent = [];

        try {
            if (!$this->preCheckWebHook()) {
                throw new Exception($this->_preCheckError);
            }

            $sendParams = [
                'FromEmail' => $fromEmail,
                'FromName'  => sprintf('=?%s?B?%s?=', strtolower(app()->charset), base64_encode((string)$fromName)),
                'Subject'   => sprintf('=?%s?B?%s?=', strtolower(app()->charset), base64_encode((string)$params['subject'])),
                'Text-Part' => !empty($params['plainText']) ? $params['plainText'] : CampaignHelper::htmlToText($params['body']),
                'Html-Part' => $params['body'],
                'Recipients'=> [
                    [
                        'Email' => $toEmail,
                        'Name'  => sprintf('=?%s?B?%s?=', strtolower(app()->charset), base64_encode((string)$toName)),
                    ],
                ],
                'Headers'       => $headers,
                'Vars'          => $metaData,
            ];

            $onlyPlainText = !empty($params['onlyPlainText']) && $params['onlyPlainText'] === true;
            if (!$onlyPlainText && !empty($params['attachments']) && is_array($params['attachments'])) {
                $sendParams['Attachments'] = [];
                $_attachments = array_unique($params['attachments']);
                foreach ($_attachments as $attachment) {
                    if (is_file($attachment)) {
                        $fileName = basename($attachment);
                        $sendParams['Attachments'][] = [
                            'Content-type' => 'application/octet-stream',
                            'Filename'     => $fileName,
                            'content'      => base64_encode((string)file_get_contents($attachment)),
                        ];
                    }
                }
            }

            if ($onlyPlainText) {
                unset($sendParams['Html-Part']);
            }

            $response = $this->getClient()->post(['send', ''], ['body' => $sendParams]);
            $data     = $response->getData();

            if ($response->success() && !empty($data) && isset($data['Sent'], $data['Sent'][0])) {
                $this->getMailer()->addLog('OK');
                $sent = ['message_id' => $data['Sent'][0]['MessageID']];
            } else {
                if (empty($data)) {
                    $data = (array)$response;
                }
                throw new Exception(print_r($data, true));
            }
        } catch (Exception $e) {
            $this->getMailer()->addLog($e->getMessage());
        }

        if ($sent) {
            $this->logUsage();
        }

        hooks()->doAction('delivery_server_after_send_email', $params, $this, $sent);

        return (array)$sent;
    }

    /**
     * @return Mailjet\Client
     */
    public function getClient(): Mailjet\Client
    {
        static $clients = [];
        $id = (int)$this->server_id;
        if (!empty($clients[$id])) {
            return $clients[$id];
        }

        return $clients[$id] = new Mailjet\Client($this->username, $this->password);
    }

    /**
     * @inheritDoc
     */
    public function getParamsArray(array $params = []): array
    {
        $params['transport'] = self::TRANSPORT_MAILJET_WEB_API;
        return parent::getParamsArray($params);
    }

    /**
     * @inheritDoc
     */
    public function getFormFieldsDefinition(array $fields = []): array
    {
        return parent::getFormFieldsDefinition(CMap::mergeArray([
            'hostname'                => null,
            'port'                    => null,
            'protocol'                => null,
            'timeout'                 => null,
            'signing_enabled'         => null,
            'max_connection_messages' => null,
            'bounce_server_id'        => null,
            'force_sender'            => null,
        ], $fields));
    }

    /**
     * @return void
     */
    protected function afterConstruct()
    {
        parent::afterConstruct();
        $this->_initStatus = $this->status;
        $this->hostname    = 'web-api.mailjet.com';
    }

    /**
     * @return void
     */
    protected function afterFind()
    {
        $this->_initStatus = $this->status;
        parent::afterFind();
    }

    /**
     * @return bool
     */
    protected function preCheckWebHook(): bool
    {
        if (is_cli() || $this->getIsNewRecord() || $this->_initStatus !== self::STATUS_INACTIVE) {
            return true;
        }

        try {
            foreach (['bounce', 'spam', 'unsub'] as $eventType) {
                $response = $this->getClient()->post(['eventcallbackurl', ''], [
                    'body' => [
                        'EventType' => $eventType,
                        'Url'       => $this->getDswhUrl(),
                        'Version'   => '2',
                    ],
                ]);
                $data = $response->getData();
                if (!$response->success() && !empty($data) && stripos($data['ErrorMessage'], 'already exists') === false) {
                    throw new Exception(t('servers', 'Please do not validate the delivery server until you fix this error') . ': ' . $data['ErrorMessage']);
                }
            }

            // try to activate the sender.
            $response = $this->getClient()->post(['sender', ''], [
                'body' => ['Email' => $this->from_email],
            ]);
            $data = $response->getData();

            // flag
            $validate = $response->success();

            // email has been added, must validate it.
            if (!$response->success() && !empty($data) && $data['StatusCode'] != 200) {
                if (stripos($data['ErrorMessage'], '"validate" action') !== false) {
                    $validate = true;
                } elseif (stripos($data['ErrorMessage'], 'already exists') !== false) {
                    $validate = true;
                } elseif (stripos($data['ErrorMessage'], 'already active') !== false) {
                    $validate = false;
                } else {
                    throw new Exception($data['ErrorMessage']);
                }
            }

            if ($validate) {
                $response = $this->getClient()->post(['sender', 'validate'], [
                    'id' => $this->from_email,
                ]);

                $data  = $response->getData();
                $error = !$response->success();
                $note  = true;

                if ($error && !empty($data) && stripos($data['ErrorMessage'], 'already active')) {
                    $error = false;
                    $note  = false;
                }

                if ($error) {
                    throw new Exception($data['ErrorMessage']);
                }

                if ($note) {
                    throw new Exception(t('servers', 'We just sent a mailjet.com verification email at "{email}". Please check it then try again.', [
                        '{email}' => $this->from_email,
                    ]));
                }
            }
        } catch (Exception $e) {
            $this->_preCheckError = $e->getMessage();
        }

        if ($this->_preCheckError) {
            return false;
        }

        return (bool)$this->save(false);
    }
}
