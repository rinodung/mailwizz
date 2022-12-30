<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * EmailVerificationExtBulkEmailCheckerCommon
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.0.0
 */
class EmailVerificationExtBulkEmailCheckerCommon extends EmailVerificationExtBaseCommon
{
    /**
     * @var string
     */
    public $api_url = 'https://api-v4.bulkemailchecker.com';

    /**
     * @var string
     */
    public $api_key = '';

    /**
     * @return array
     * @throws CException
     */
    public function rules()
    {
        $rules = [
            ['api_key', 'safe'],
            ['api_url', 'url'],
        ];

        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array
     * @throws CException
     */
    public function attributeLabels()
    {
        $labels = [
            'api_key'         => $this->t('Api key'),
            'api_url'         => $this->t('Api url'),
        ];
        return CMap::mergeArray($labels, parent::attributeLabels());
    }

    /**
     * @return array
     * @throws CException
     */
    public function attributePlaceholders()
    {
        $placeholders = [
            'api_key' => '',
            'api_url' => $this->api_url,
        ];
        return CMap::mergeArray($placeholders, parent::attributePlaceholders());
    }

    /**
     * @inheritDoc
     */
    public function attributeHelpTexts()
    {
        $texts = [
            'api_key'         => $this->t('The api key for the service'),
            'api_url'         => $this->t('The api url for the service'),
        ];
        return CMap::mergeArray($texts, parent::attributeHelpTexts());
    }

    /**
     * @return bool
     */
    public function getIsEnabled(): bool
    {
        return $this->enabled === self::TEXT_YES;
    }

    /**
     * @return string
     */
    public function getApiUrl(): string
    {
        return rtrim((string)$this->api_url, '/');
    }

    /**
     * @return string
     */
    public function getApiKey(): string
    {
        return (string)$this->api_key;
    }

    /**
     * @return array
     */
    public function getCustomerGroups(): array
    {
        return array_filter((array)$this->customer_groups);
    }

    /**
     * @return array
     */
    public function getCheckZones(): array
    {
        return array_filter((array)$this->check_zones);
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return 'Bulk email checker';
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return 'Bulk email checker description.';
    }

    /**
     * @inheritDoc
     */
    public function getOptionsPrefix(): string
    {
        return 'blkemailchecker';
    }

    /**
     * @inheritDoc
     */
    public function addFilter(): void
    {
        hooks()->addFilter('email_blacklist_is_email_blacklisted', [$this, '_emailBlacklistIsEmailBlacklisted']);
    }

    /**
     * @param mixed $isBlacklisted
     * @param string $email
     * @param ListSubscriber|null $subscriber
     * @param Customer|null $customer
     * @param array $params
     *
     * @return mixed
     */
    public function _emailBlacklistIsEmailBlacklisted($isBlacklisted, string $email, ?ListSubscriber $subscriber = null, ?Customer $customer = null, array $params = [])
    {
        // if already blacklisted we stop
        if ($isBlacklisted !== false) {
            return $isBlacklisted;
        }

        // without customer we stop
        if (empty($customer)) {
            return $isBlacklisted;
        }

        /** @var EmailVerificationExtBulkEmailCheckerCustomer $customerSettings */
        $customerSettings = container()->get(EmailVerificationExtBulkEmailCheckerCustomer::class);
        $customerSettings->setCustomer($customer);

        /** @var EmailVerificationExtBulkEmailCheckerCommon $commonSettings */
        $commonSettings = container()->get(EmailVerificationExtBulkEmailCheckerCommon::class);

        $checkZone = !empty($params['checkZone']) ? $params['checkZone'] : '';
        $enabled   = $customerSettings->getIsEnabled();
        $apiKey    = $customerSettings->getApiKey();
        $apiUrl    = $customerSettings->getApiUrl();

        // not enabled, no api key/url
        if (empty($enabled) || empty($apiKey) || empty($apiUrl)) {
            return $isBlacklisted;
        }

        /** @var CMap $emails */
        $emails = app_param('extensions.email-checkers.emails', new CMap());
        if ($emails->contains($email)) {
            return $emails->itemAt($email);
        }
        $emails->add($email, false);

        // check if the customer is allowed
        $allowedGroups = $commonSettings->getCustomerGroups();
        if (!empty($allowedGroups) && !in_array($customer->group_id, $allowedGroups)) {
            return $emails->itemAt($email);
        }

        // check if the zone is allowed
        $checkZones = $customerSettings->getCheckZones();
        if (!empty($checkZones) && !in_array($checkZone, $checkZones)) {
            return $emails->itemAt($email);
        }

        $url = sprintf('%s/?key=%s&email=%s', $apiUrl, $apiKey, $email);
        try {
            $response = (string)(new GuzzleHttp\Client())->get($url)->getBody();
        } catch (Exception $e) {
            $response = '';
        }

        if (empty($response)) {
            return $emails->itemAt($email);
        }

        $response = json_decode($response);
        if (empty($response) || !is_object($response) || empty($response->status) || empty($response->details) || empty($response->event)) {
            return $emails->itemAt($email);
        }

        if ($response->status === 'passed') {
            return $emails->itemAt($email);
        }

        if ($response->status === 'failed') {
            $emails->add($email, (string)$response->details);
            return $emails->itemAt($email);
        }

        // disable the extension
        if ($response->status === 'error' && in_array($response->event, ['no_credits_remaining', 'invalid_api_key'])) {
            $customerSettings->saveAttributes([
                'enabled' => EmailVerificationExtBulkEmailCheckerCustomer::TEXT_NO,
            ]);

            $message = new CustomerMessage();
            $message->customer_id = (int)$customer->customer_id;
            $message->title       = 'Email verification';
            $message->message     = 'The "{name}" extension has been disabled, here is the service response: {response}';
            $message->message_translation_params = [
                '{name}'        => $this->getExtension()->name,
                '{response}'    => json_encode($response),
            ];
            $message->save();

            return $emails->itemAt($email);
        }

        if ($response->status === 'error' && !in_array($response->event, ['invalid_email'])) {
            return $emails->itemAt($email);
        }

        return $emails->itemAt($email);
    }
}
