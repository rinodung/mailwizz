<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * DeliveryServerDomainPolicy
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.4.5
 */

/**
 * This is the model class for table "{{delivery_server_domain_policy}}".
 *
 * The followings are the available columns in table '{{delivery_server_domain_policy}}':
 * @property integer|null $domain_id
 * @property integer|null $server_id
 * @property string $domain
 * @property string $policy
 * @property string|CDbExpression $date_added
 * @property string|CDbExpression $last_updated
 *
 * The followings are the available model relations:
 * @property DeliveryServer $server
 */
class DeliveryServerDomainPolicy extends ActiveRecord
{
    const POLICY_ALLOW = 'allow';
    const POLICY_DENY = 'deny';

    /**
     * @return string
     */
    public function tableName()
    {
        return '{{delivery_server_domain_policy}}';
    }

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            ['server_id, domain, policy', 'required'],
            ['domain', 'length', 'max' => 64],
            ['policy', 'in', 'range' => array_keys($this->getPoliciesList())],
            ['server_id', 'numerical', 'integerOnly' => true],
            ['server_id', 'exist', 'className' => DeliveryServer::class],

        ];
        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array
     */
    public function relations()
    {
        $relations = [
            'server' => [self::BELONGS_TO, DeliveryServer::class, 'server_id'],
        ];
        return CMap::mergeArray($relations, parent::relations());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'domain_id'  => t('servers', 'Domain'),
            'server_id'  => t('servers', 'Server'),
            'domain'     => t('servers', 'Domain'),
            'policy'     => t('servers', 'Policy'),
        ];
        return CMap::mergeArray($labels, parent::attributeLabels());
    }

    /**
     * @return array
     */
    public function attributeHelpTexts()
    {
        $texts = [];
        return CMap::mergeArray($texts, parent::attributeHelpTexts());
    }

    /**
     * @return array
     */
    public function attributePlaceholders()
    {
        $placeholders = [
            'domain'     => t('servers', 'i.e: yahoo.com'),
        ];

        return CMap::mergeArray($placeholders, parent::attributePlaceholders());
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return DeliveryServerDomainPolicy the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var DeliveryServerDomainPolicy $model */
        $model = parent::model($className);

        return $model;
    }

    /**
     * @return bool
     */
    public function getIsAllow(): bool
    {
        return $this->policy === self::POLICY_ALLOW;
    }

    /**
     * @return bool
     */
    public function getIsDeny(): bool
    {
        return $this->policy == self::POLICY_DENY;
    }

    /**
     * @return array
     */
    public function getPoliciesList(): array
    {
        return [
            self::POLICY_ALLOW => ucfirst(t('servers', self::POLICY_ALLOW)),
            self::POLICY_DENY  => ucfirst(t('servers', self::POLICY_DENY)),
        ];
    }

    /**
     * @param int $server_id
     * @param string $emailAddress
     * @return bool
     */
    public static function canSendToDomainOf(int $server_id, string $emailAddress): bool
    {
        static $serverPolicies = [];
        static $allowedDomains = [];

        if (!isset($serverPolicies[$server_id])) {
            $serverPolicies[$server_id] = self::model()->findAll([
                'select'    => 'domain, policy',
                'condition' => 'server_id = :sid',
                'order'     => 'policy ASC, domain_id ASC',
                'params'    => [':sid' => (int)$server_id],
            ]);
            if (!empty($serverPolicies[$server_id])) {
                $allowPolicies = [];
                $denyPolicies  = [];
                foreach ($serverPolicies[$server_id] as $model) {
                    if ($model->getIsAllow()) {
                        $allowPolicies[] = $model;
                    } else {
                        $denyPolicies[] = $model;
                    }
                }
                $serverPolicies[$server_id] = [
                    'allow' => $allowPolicies,
                    'deny'  => $denyPolicies,
                ];
                unset($allowPolicies, $denyPolicies);
            }
        }

        // if no policy, then allow all
        if (empty($serverPolicies[$server_id])) {
            return true;
        }

        if (!isset($allowedDomains[$server_id])) {
            $allowedDomains[$server_id] = [];
        }

        $domain = (string)$emailAddress;
        if (FilterVarHelper::email($emailAddress)) {
            $domain = explode('@', $emailAddress);
            $domain = (string)end($domain);
        }

        if (isset($allowedDomains[$server_id][$domain])) {
            return $allowedDomains[$server_id][$domain];
        }

        foreach ($serverPolicies[$server_id]['allow'] as $model) {
            if ($model->domain == '*' || stripos($domain, $model->domain) === 0) {
                return $allowedDomains[$server_id][$domain] = true;
            }
        }

        foreach ($serverPolicies[$server_id]['deny'] as $model) {
            if ($model->domain == '*' || stripos($domain, $model->domain) === 0) {
                return $allowedDomains[$server_id][$domain] = false;
            }
        }

        return $allowedDomains[$server_id][$domain] = true;
    }
}
