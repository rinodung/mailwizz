<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * DeliveryServerElasticemailWebApi
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.5
 *
 */

class DeliveryServerElasticemailWebApi extends DeliveryServer
{
    /**
     * @var string
     */
    protected $serverType = 'elasticemail-web-api';

    /**
     * @var string
     */
    protected $_providerUrl = 'https://elasticemail.com/';

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
            'password'   => t('servers', 'Api key'),
        ];
        return CMap::mergeArray(parent::attributeLabels(), $labels);
    }

    /**
     * @return array
     */
    public function attributeHelpTexts()
    {
        $texts = [
            'username' => t('servers', 'Your elastic email account username/email.'),
            'password' => t('servers', 'One of your elastic email api keys.'),
        ];

        return CMap::mergeArray(parent::attributeHelpTexts(), $texts);
    }

    /**
     * @return array
     */
    public function attributePlaceholders()
    {
        $placeholders = [
            'username'  => t('servers', 'Username'),
            'password'  => t('servers', 'Api key'),
        ];

        return CMap::mergeArray(parent::attributePlaceholders(), $placeholders);
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return DeliveryServerElasticemailWebApi the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var DeliveryServerElasticemailWebApi $model */
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

        [$fromEmail, $fromName] = $this->getMailer()->findEmailAndName($params['from']);
        [$toEmail, $toName]     = $this->getMailer()->findEmailAndName($params['to']);

        if (!empty($params['fromName'])) {
            $fromName = $params['fromName'];
        }

        $replyToEmail = $replyToName = null;
        if (!empty($params['replyTo'])) {
            [$replyToEmail, $replyToName] = $this->getMailer()->findEmailAndName($params['replyTo']);
        }

        $sent = [];

        try {
            $postData = [
                'Recipients' => [
                    [
                        'Email' => sprintf('%s <%s>', $toName, $toEmail),
                    ],
                ],
                'Content' => [
                    'EnvelopeFrom'  => sprintf(
                        '%s <%s>',
                        !empty($fromName) ? $fromName : $this->from_name,
                        !empty($fromEmail) ? $fromEmail : $this->from_email
                    ),
                    'From'  => sprintf(
                        '%s <%s>',
                        !empty($fromName) ? $fromName : $this->from_name,
                        !empty($fromEmail) ? $fromEmail : $this->from_email
                    ),
                    'ReplyTo'  => sprintf(
                        '%s <%s>',
                        !empty($replyToName) ? $replyToName : $this->from_name,
                        !empty($replyToEmail) ? $replyToEmail : $this->from_email
                    ),
                    'Subject' => $params['subject'],
                    'Body' => [
                        [
                            'ContentType'   => 'PlainText',
                            'Content'       => !empty($params['plainText']) ? $params['plainText'] : CampaignHelper::htmlToText($params['body']),
                            'Charset'       => 'utf-8',
                        ],
                        [
                            'ContentType'   => 'HTML',
                            'Content'       => !empty($params['body']) ? $params['body'] : '',
                            'Charset'       => 'utf-8',
                        ],
                    ],
                    'Options' => [
                        'Encoding' => 'QuotedPrintable',
                    ],
                ],
            ];

            if (!empty($params['headers'])) {
                $postData['Content']['Headers'] = [];
                $headers = $this->parseHeadersIntoKeyValue($params['headers']);
                foreach ($headers as $name => $value) {
                    $postData['Content']['Headers'][$name] = $value;
                }
            }

            $onlyPlainText = !empty($params['onlyPlainText']) && $params['onlyPlainText'] === true;
            if (!$onlyPlainText && !empty($params['attachments']) && is_array($params['attachments'])) {
                $postData['Content']['Attachments'] = [];
                $attachments = array_unique($params['attachments']);
                foreach ($attachments as $attachment) {
                    if (is_file($attachment)) {
                        $postData['Content']['Attachments'][] = [
                            'BinaryContent' => base64_encode((string)file_get_contents($attachment)),
                            'Name'          => basename($attachment),
                            'ContentType'   => 'application/octet-stream',
                        ];
                    }
                }
            }

            if ($onlyPlainText) {
                unset($postData['Content']['Body'][1]);
            }

            $response = (new GuzzleHttp\Client())->post('https://api.elasticemail.com/v4/emails', [
                'headers'   => [
                    'Content-Type'          => 'application/json',
                    'X-ElasticEmail-ApiKey' => $this->password,
                ],
                'timeout'   => (int)$this->timeout,
                'json'      => $postData,
            ]);

            /** @var stdClass|null $rsp */
            $rsp = json_decode((string)$response->getBody());
            if (empty($rsp) || empty($rsp->MessageID)) {
                throw new Exception((string)$response->getBody());
            }

            $this->getMailer()->addLog('OK');
            $sent = ['message_id' => trim((string)$rsp->MessageID)];
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
        $params['transport'] = self::TRANSPORT_ELASTICEMAIL_WEB_API;
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
        $this->hostname = 'web-api.elasticemail.com';
    }
}
