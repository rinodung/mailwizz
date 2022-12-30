<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * SendingDomain
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.4.7
 */

/**
 * This is the model class for table "{{sending_domain}}".
 *
 * The followings are the available columns in table '{{sending_domain}}':
 * @property integer $domain_id
 * @property integer|string $customer_id
 * @property string $name
 * @property string $dkim_private_key
 * @property string $dkim_public_key
 * @property string $locked
 * @property string $verified
 * @property string $signing_enabled
 * @property string|CDbExpression $date_added
 * @property string|CDbExpression $last_updated
 *
 * The followings are the available model relations:
 * @property Customer $customer
 *
 */
class SendingDomain extends ActiveRecord
{
    /**
     * @return string
     */
    public function tableName()
    {
        return '{{sending_domain}}';
    }

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            ['name', 'required'],
            ['customer_id', 'numerical', 'integerOnly' => true],
            ['customer_id', 'exist', 'className' => Customer::class],
            ['name', 'length', 'max' => 64],
            ['name', 'match', 'pattern' => '/\w+\.\w{2,10}(\.(\w{2,10}))?/i'],
            ['name', 'unique'],
            ['dkim_private_key', 'match', 'pattern' => '/-----BEGIN\sRSA\sPRIVATE\sKEY-----(.*)-----END\sRSA\sPRIVATE\sKEY-----/sx'],
            ['dkim_public_key', 'match', 'pattern' => '/-----BEGIN\sPUBLIC\sKEY-----(.*)-----END\sPUBLIC\sKEY-----/sx'],
            ['dkim_private_key, dkim_public_key', 'length', 'max' => 10000],
            ['locked, verified, signing_enabled', 'length', 'max' => 3],
            ['locked, verified, signing_enabled', 'in', 'range' => array_keys($this->getYesNoOptions())],

            // The following rule is used by search().
            ['customer_id, name, locked, verified, signing_enabled', 'safe', 'on'=>'search'],
        ];
        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array
     */
    public function relations()
    {
        $relations = [
            'customer' => [self::BELONGS_TO, Customer::class, 'customer_id'],
        ];
        return CMap::mergeArray($relations, parent::relations());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'domain_id'          => t('sending_domains', 'Domain'),
            'customer_id'        => t('sending_domains', 'Customer'),
            'name'               => t('sending_domains', 'Domain name'),
            'dkim_private_key'   => t('sending_domains', 'Dkim private key'),
            'dkim_public_key'    => t('sending_domains', 'Dkim public key'),
            'locked'             => t('sending_domains', 'Locked'),
            'verified'           => t('sending_domains', 'Verified'),
            'signing_enabled'    => t('sending_domains', 'DKIM Signing'),
        ];
        return CMap::mergeArray($labels, parent::attributeLabels());
    }

    /**
     * @return array
     */
    public function attributeHelpTexts()
    {
        $labels = [
            'customer_id'        => t('sending_domains', 'If this domain is verified in behalf of a customer, choose the customer.'),
            'name'               => t('sending_domains', 'Domain name, i.e: example.com'),
            'verified'           => t('sending_domains', 'Set this to yes only if you already have DNS records set for this domain.'),
            'locked'             => t('sending_domains', 'Whether this domain is locked and the customer cannot modify or delete it.'),
            'signing_enabled'    => t('sending_domains', 'Whether we should use DKIM to sign outgoing campaigns for this domain.'),
            'dkim_private_key'   => t('sending_domains', 'DKIM private key, leave this empty to be auto-generated. Please do not edit this record unless you really know what you are doing.'),
            'dkim_public_key'    => t('sending_domains', 'DKIM public key, leave this empty to be auto-generated. Please do not edit this record unless you really know what you are doing.'),
        ];
        return CMap::mergeArray($labels, parent::attributeLabels());
    }

    /**
     * Retrieves a list of models based on the current search/filter conditions.
     *
     * Typical usecase:
     * - Initialize the model fields with values from filter form.
     * - Execute this method to get CActiveDataProvider instance which will filter
     * models according to data in model fields.
     * - Pass data provider to CGridView, CListView or any similar widget.
     *
     * @return CActiveDataProvider the data provider that can return the models
     * based on the search/filter conditions.
     * @throws CException
     */
    public function search()
    {
        $criteria = new CDbCriteria();
        $criteria->with = [];

        if (!empty($this->customer_id)) {
            $customerId = (string)$this->customer_id;
            if (is_numeric($customerId)) {
                $criteria->compare('t.customer_id', $customerId);
            } else {
                $criteria->with = [
                    'customer' => [
                        'joinType'  => 'INNER JOIN',
                        'condition' => 'CONCAT(customer.first_name, " ", customer.last_name) LIKE :name',
                        'params'    => [
                            ':name'    => '%' . $customerId . '%',
                        ],
                    ],
                ];
            }
        }

        $criteria->compare('t.name', $this->name, true);
        $criteria->compare('t.locked', $this->locked);
        $criteria->compare('t.verified', $this->verified);
        $criteria->compare('t.signing_enabled', $this->signing_enabled);

        $criteria->order = 't.domain_id DESC';

        return new CActiveDataProvider(get_class($this), [
            'criteria'   => $criteria,
            'pagination' => [
                'pageSize' => $this->paginationOptions->getPageSize(),
                'pageVar'  => 'page',
            ],
            'sort'=>[
                'defaultOrder' => [
                    't.domain_id'  => CSort::SORT_DESC,
                ],
            ],
        ]);
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return SendingDomain the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var SendingDomain $model */
        $model = parent::model($className);

        return $model;
    }

    /**
     * @return bool
     */
    public function getIsVerified(): bool
    {
        return (string)$this->verified === self::TEXT_YES;
    }

    /**
     * @return bool
     */
    public function getIsLocked(): bool
    {
        return (string)$this->locked === self::TEXT_YES;
    }

    /**
     * @param string $verified
     *
     * @return bool
     */
    public function saveVerified(string $verified = ''): bool
    {
        if (empty($this->domain_id)) {
            return false;
        }

        if ($verified && $verified === (string)$this->verified) {
            return true;
        }

        if ($verified) {
            $this->verified = $verified;
        }

        $attributes = ['verified' => $this->verified];
        $this->last_updated = $attributes['last_updated'] = MW_DATETIME_NOW;

        return (bool)db()->createCommand()->update($this->tableName(), $attributes, 'domain_id = :id', [':id' => (int)$this->domain_id]);
    }

    /**
     * @return bool
     */
    public function getSigningEnabled(): bool
    {
        return (string)$this->signing_enabled === self::TEXT_YES;
    }

    /**
     * @return SendingDomain
     */
    public function setDefaultDkimKeys(): self
    {
        if (empty($this->dkim_private_key)) {
            $this->dkim_private_key = DnsTxtHelper::getDefaultDkimPrivateKey();
        }

        if (empty($this->dkim_public_key)) {
            $this->dkim_public_key = DnsTxtHelper::getDefaultDkimPublicKey();
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function generateDkimKeys(): bool
    {
        if (!empty($this->dkim_public_key) && !empty($this->dkim_private_key)) {
            return true;
        }

        $result = DnsTxtHelper::generateDkimKeys();
        if (!empty($result['errors'])) {
            $this->addError('name', $result['errors'][0]);
            return false;
        }

        if (!empty($result['private_key']) && !empty($result['public_key'])) {
            $this->dkim_private_key = $result['private_key'];
            $this->dkim_public_key  = $result['public_key'];
            unset($result);
            return true;
        }

        return false;
    }

    /**
     * @return string
     */
    public function getDnsTxtDkimSelectorToAdd(): string
    {
        // since 1.3.6.6
        if (!($key = DnsTxtHelper::getDefaultDkimPublicKey())) {
            $key = $this->dkim_public_key;
        }

        $record = sprintf('%s         TXT     "v=DKIM1; k=rsa; p=%s;"', DnsTxtHelper::getDkimFullSelector(), DnsTxtHelper::cleanDkimKey($key));

        // since 1.3.5.9
        $record = (string)hooks()->applyFilters('sending_domain_get_dns_txt_dkim_record', $record, $this);

        return $record;
    }

    /**
     * @return string
     */
    public function getDnsTxtSpfRecordToAdd(): string
    {
        $smtpHosts = [];

        // since 1.3.6.6
        if (!($spf = DnsTxtHelper::getDefaultSpfValue())) {
            $criteria  = new CDbCriteria();
            $criteria->select    = '`t`.`hostname`';
            $criteria->addCondition('`t`.`status` = :st AND (`t`.`customer_id` = :cid OR `t`.`customer_id` IS NULL)');
            $criteria->addInCondition('t.type', [
                DeliveryServer::TRANSPORT_SMTP, DeliveryServer::TRANSPORT_SMTP_POSTMASTERY,
                DeliveryServer::TRANSPORT_SMTP_POSTAL, DeliveryServer::TRANSPORT_SMTP_PMTA,
                DeliveryServer::TRANSPORT_SMTP_AMAZON,
            ]);
            $criteria->params[':st']  = DeliveryServer::STATUS_ACTIVE;
            $criteria->params[':cid'] = (int)$this->customer_id;
            $servers = DeliveryServer::model()->findAll($criteria);
            foreach ($servers as $server) {
                $smtpHosts[] = sprintf('a:%s', $server->hostname);
            }
            if (isset($_SERVER['HTTP_HOST'])) {
                $smtpHosts[] = sprintf('a:%s', $_SERVER['HTTP_HOST']);
            }
            if (isset($_SERVER['SERVER_ADDR'])) {
                $blocks = explode('.', $_SERVER['SERVER_ADDR']);
                if (count($blocks) == 4) {
                    $smtpHosts[] = sprintf('ip4:%s', $_SERVER['SERVER_ADDR']);
                } else {
                    $smtpHosts[] = sprintf('ip6:%s', $_SERVER['SERVER_ADDR']);
                }
            }

            $spf = implode(' ', array_filter(array_unique($smtpHosts)));
            $spf = sprintf('v=spf1 mx %s ~all', $spf);
        }

        $record = sprintf('%s.      IN TXT     "%s"', $this->name, $spf);

        // since 1.3.5.9
        $record = (string)hooks()->applyFilters('sending_domain_get_dns_txt_spf_record', $record, $this, $smtpHosts);

        return $record;
    }

    /**
     * @return string
     */
    public function getDnsTxtDmarcRecordToAdd(): string
    {
        if (!($dmarc = DnsTxtHelper::getDefaultDmarcValue())) {
            $dmarc = 'v=DMARC1; p=none';
        }

        $record = sprintf('_DMARC.%s.      IN TXT     "%s"', $this->name, $dmarc);

        return (string)hooks()->applyFilters('sending_domain_get_dns_txt_dmarc_record', $record, $this);
    }

    /**
     * @param string $email
     * @param int $customerId
     * @param bool|null $signingEnabled
     *
     * @return SendingDomain|null
     */
    public function findVerifiedByEmailForCustomer(string $email, int $customerId, ?bool $signingEnabled = null): ?self
    {
        if (!FilterVarHelper::email($email)) {
            return null;
        }

        static $domains = [];

        $parts  = explode('@', $email);
        $domain = $parts[1];

        if (isset($domains[$domain]) || array_key_exists($domain, $domains)) {
            return $domains[$domain];
        }

        $criteria = new CDbCriteria();
        $criteria->compare('t.name', $domain);
        $criteria->compare('t.verified', self::TEXT_YES);
        if (is_bool($signingEnabled)) {
            $criteria->compare('t.signing_enabled', $signingEnabled ? self::TEXT_YES : self::TEXT_NO);
        }
        $criteria->compare('t.customer_id', $customerId);

        return $domains[$domain] = self::model()->find($criteria);
    }

    /**
     * @param string $email
     * @param bool|null $signingEnabled
     *
     * @return SendingDomain|null
     */
    public function findVerifiedByEmailForSystem(string $email, ?bool $signingEnabled = null): ?self
    {
        if (!FilterVarHelper::email($email)) {
            return null;
        }

        static $domains = [];

        $parts  = explode('@', $email);
        $domain = $parts[1];

        if (isset($domains[$domain]) || array_key_exists($domain, $domains)) {
            return $domains[$domain];
        }

        $criteria = new CDbCriteria();
        $criteria->compare('t.name', $domain);
        $criteria->compare('t.verified', self::TEXT_YES);
        if (is_bool($signingEnabled)) {
            $criteria->compare('t.signing_enabled', $signingEnabled ? self::TEXT_YES : self::TEXT_NO);
        }
        $criteria->addCondition('t.customer_id IS NULL');

        return $domains[$domain] = self::model()->find($criteria);
    }

    /**
     * @param string $email
     * @param int|null $customerId
     * @param bool|null $signingEnabled
     *
     * @return SendingDomain|null
     */
    public function findVerifiedByEmail(string $email, ?int $customerId = null, ?bool $signingEnabled = null): ?self
    {
        $domain = null;

        if ((int)$customerId > 0) {
            $domain = $this->findVerifiedByEmailForCustomer($email, (int)$customerId, $signingEnabled);
        }

        if (!$domain) {
            $domain = $this->findVerifiedByEmailForSystem($email, $signingEnabled);
        }

        return $domain;
    }

    /**
     * Proxy method
     *
     * @return array
     */
    public function getRequirementsErrors(): array
    {
        return DnsTxtHelper::getDkimRequirementsErrors();
    }

    /**
     * @return string
     */
    public function getCleanPublicKey(): string
    {
        return DnsTxtHelper::cleanDkimKey((string)$this->dkim_public_key);
    }

    /**
     * Proxy method
     *
     * @return string
     */
    public static function getDkimSelector(): string
    {
        return DnsTxtHelper::getDkimSelector();
    }

    /**
     * Proxy method
     *
     * @return string
     */
    public static function getDkimFullSelector(): string
    {
        return DnsTxtHelper::getDkimFullSelector();
    }

    /**
     * @since 2.0.30
     *
     * @return bool
     * @throws Net_DNS2_Exception
     */
    public function hasValidDNSTxtRecord(): bool
    {
        $resolver = new Net_DNS2_Resolver([
            'nameservers' => DnsHelper::getDnsResolverNameservers(),
        ]);

        $domainName = self::getDkimFullSelector() . '.' . $this->name;

        $result = $resolver->query($domainName, 'TXT');

        $found = false;
        $pattern    = '/[^a-z0-9=\+\/]/six';
        $publicKey  = $this->getCleanPublicKey();
        $publicKey  = (string)preg_replace($pattern, '', $publicKey);

        /** @var Net_DNS2_RR_TXT $record */
        foreach ($result->answer as $record) {
            $text = (string)implode('', (array)$record->text);
            if (strpos((string)preg_replace($pattern, '', $text), $publicKey) !== false) {
                $found = true;
                break;
            }
        }

        return $found;
    }

    /**
     * @return bool
     */
    protected function beforeSave()
    {
        if (!$this->getIsNewRecord()) {
            $keys  = ['name', 'dkim_private_key', 'dkim_public_key'];
            $model = self::model()->findByPk($this->domain_id);
            foreach ($keys as $key) {
                if ($model->$key != $this->$key) {
                    $this->verified = self::TEXT_NO;
                    break;
                }
            }
        }
        return parent::beforeSave();
    }

    /**
     * @return void
     */
    protected function afterValidate()
    {
        if (!$this->hasErrors()) {
            $this->setDefaultDkimKeys()->generateDkimKeys();
        }
        parent::afterValidate();
    }

    /**
     * @return void
     */
    protected function afterConstruct()
    {
        $this->setDefaultDkimKeys();
        parent::afterConstruct();
    }

    /**
     * @return void
     */
    protected function afterFind()
    {
        $this->setDefaultDkimKeys();
        parent::afterFind();
    }
}
