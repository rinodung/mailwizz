<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * DeliveryServerPepipostWebApi
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.5.1
 *
 */

class DeliveryServerPepipostWebApi extends DeliveryServer
{
    /**
     * @var string
     */
    protected $serverType = 'pepipost-web-api';

    /**
     * @var string
     */
    protected $_providerUrl = 'https://www.pepipost.com/';

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            ['password', 'required'],
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
            'password' => t('servers', 'Api key'),
        ];
        return CMap::mergeArray(parent::attributeLabels(), $labels);
    }

    /**
     * @return array
     */
    public function attributeHelpTexts()
    {
        $texts = [
            'password' => t('servers', 'One of your pepipost api keys.'),
        ];

        return CMap::mergeArray(parent::attributeHelpTexts(), $texts);
    }

    /**
     * @return array
     */
    public function attributePlaceholders()
    {
        $placeholders = [
            'password' => t('servers', 'Api key'),
        ];

        return CMap::mergeArray(parent::attributePlaceholders(), $placeholders);
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return DeliveryServerPepipostWebApi the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var DeliveryServerPepipostWebApi $model */
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

        $replyToEmail = $replyToName = null;
        if (!empty($params['replyTo'])) {
            [$replyToEmail, $replyToName] = $this->getMailer()->findEmailAndName($params['replyTo']);
        }

        $messageID = StringHelper::randomSha1();
        $metaData  = [
            'message_id' => $messageID,
        ];
        if (!empty($params['campaignUid'])) {
            $metaData['campaign_uid'] = $params['campaignUid'];
        }
        if (!empty($params['subscriberUid'])) {
            $metaData['subscriber_uid'] = $params['subscriberUid'];
        }

        $sent = [];

        try {
            $sendParams = [
                'api_key'       => $this->password,
                'email_details' => [
                    'fromname' => $fromName,
                    'from'     => $fromEmail,
                    'subject'  => $params['subject'],
                    'replytoid'=> !empty($replyToEmail) ? $replyToEmail : $fromEmail,
                    'content'  => utf8_encode($params['body']),
                ],
                'X-APIHEADER' => [json_encode($metaData)],
                'settings'    => [
                    'footer'     => 0,
                    'unsubscribe'=> 0,
                ],
                'recipients' => [$toEmail],
            ];

            $onlyPlainText = !empty($params['onlyPlainText']) && $params['onlyPlainText'] === true;
            if (!$onlyPlainText && !empty($params['attachments']) && is_array($params['attachments'])) {
                $attachments = array_unique($params['attachments']);
                $sendParams['files'] = [];
                foreach ($attachments as $attachment) {
                    if (is_file($attachment)) {
                        $sendParams['files'][basename($attachment)] = base64_encode((string)file_get_contents($attachment));
                    }
                }
            }

            if ($onlyPlainText) {
                $sendParams['email_details']['content'] = !empty($params['plainText']) ? $params['plainText'] : CampaignHelper::htmlToText($params['body']);
            }

            $resp = $this->getClient()->post('web.send.json', [
                'json' => $sendParams,
            ]);
            if ((int)$resp->getStatusCode() !== 200) {
                throw new Exception($resp->getReasonPhrase());
            }
            /** @var stdClass $response */
            $response = json_decode((string)$resp->getBody());

            if (!empty($response->errorcode)) {
                throw new Exception((string)json_encode($response));
            }

            $this->getMailer()->addLog('OK');
            $sent = ['message_id' => $messageID];
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
     * @inheritDoc
     */
    public function getParamsArray(array $params = []): array
    {
        $params['transport'] = self::TRANSPORT_PEPIPOST_WEB_API;
        return parent::getParamsArray($params);
    }

    /**
     * @return GuzzleHttp\Client
     */
    public function getClient(): GuzzleHttp\Client
    {
        static $client;
        if ($client !== null) {
            return $client;
        }

        return $client = new GuzzleHttp\Client([
            'timeout'  => (int)$this->timeout,
            'base_uri' => 'https://api.pepipost.com/api/',
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getFormFieldsDefinition(array $fields = []): array
    {
        return parent::getFormFieldsDefinition(CMap::mergeArray([
            'username'                => null,
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
        $this->hostname = 'web-api.pepipost.com';
    }
}
