<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * EmailVerificationExtKickboxCommon
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.0.0
 */
class EmailVerificationExtKickboxCommon extends EmailVerificationExtBaseCommon
{
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
        return 'Kickbox';
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return 'Check email address validity using Kickbox.io service.';
    }

    /**
     * @inheritDoc
     */
    public function getOptionsPrefix(): string
    {
        return 'kickbox';
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

        /** @var EmailVerificationExtKickboxCustomer $customerSettings */
        $customerSettings = container()->get(EmailVerificationExtKickboxCustomer::class);
        $customerSettings->setCustomer($customer);

        /** @var EmailVerificationExtKickboxCommon $commonSettings */
        $commonSettings = container()->get(EmailVerificationExtKickboxCommon::class);

        $checkZone = !empty($params['checkZone']) ? $params['checkZone'] : '';
        $enabled   = $customerSettings->getIsEnabled();
        $apiKey    = $customerSettings->getApiKey();

        // not enabled, no api key/url
        if (empty($enabled) || empty($apiKey)) {
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

        $client  = new Kickbox\Client($apiKey);
        $kickbox = $client->kickbox();

        try {
            $response = $kickbox->verify($email);
        } catch (Exception $e) {
            return $emails->itemAt($email);
        }

        if (empty($response) || empty($response->body) || empty($response->headers)) {
            return $emails->itemAt($email);
        }

        // disable if needed.
        $headers = (array)$response->headers;
        $balance = (int)($headers['X-Kickbox-Balance'][0] ?? 0);
        if ($balance <= 0) {
            $customerSettings->saveAttributes([
                'enabled' => EmailVerificationExtKickboxCustomer::TEXT_NO,
            ]);

            $message = new CustomerMessage();
            $message->customer_id = (int)$customer->customer_id;
            $message->title       = 'Email verification';
            $message->message     = 'The "{name}" extension has been disabled, here is the service response: {response}';
            $message->message_translation_params = [
                '{name}'        => $this->getExtension()->name,
                '{response}'    => json_encode(['response' => 'Insufficient credits']),
            ];
            $message->save();

            return $emails->itemAt($email);
        }

        if (empty($response->body['success']) || empty($response->body['result'])) {
            return $emails->itemAt($email);
        }

        if (in_array($response->body['result'], ['deliverable', 'risky'])) {
            return $emails->itemAt($email);
        }

        $emails->add($email, $response->body['reason']);
        return $emails->itemAt($email);
    }
}
