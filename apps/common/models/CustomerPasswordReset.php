<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * CustomerPasswordReset
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

/**
 * This is the model class for table "customer_password_reset".
 *
 * The followings are the available columns in table 'customer_password_reset':
 * @property integer $request_id
 * @property integer $customer_id
 * @property string $reset_key
 * @property string $ip_address
 * @property string $status
 * @property string|CDbExpression $date_added
 * @property string|CDbExpression $last_updated
 *
 * The followings are the available model relations:
 * @property Customer $customer
 */
class CustomerPasswordReset extends ActiveRecord
{
    /**
     * Status flag
     */
    const STATUS_USED = 'used';

    /**
     * @var string
     */
    public $email;

    /**
     * @return string
     */
    public function tableName()
    {
        return '{{customer_password_reset}}';
    }

    /**
     * @return array
     */
    public function rules()
    {
        // NOTE: you should only define rules for those attributes that
        // will receive customer inputs.
        $rules = [
            ['email', 'required'],
            ['email', 'email', 'validateIDN' => true],
            ['email', 'exist', 'className' => Customer::class, 'criteria' => ['condition' => 'status = :st', 'params' => [':st' => Customer::STATUS_ACTIVE]]],
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
            'request_id'    => t('customers', 'Request'),
            'customer_id'   => t('customers', 'Customer'),
            'reset_key'     => t('customers', 'Reset key'),
            'ip_address'    => t('customers', 'Ip address'),
            'email'         => t('customers', 'Email'),
        ];

        return CMap::mergeArray($labels, parent::attributeLabels());
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return CustomerPasswordReset the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var CustomerPasswordReset $model */
        $model = parent::model($className);

        return $model;
    }

    /**
     * @param array $params
     *
     * @return bool
     * @throws CException
     */
    public function sendEmail(array $params = []): bool
    {
        /** @var DeliveryServer|null $server */
        $server = DeliveryServer::pickServer();

        if (empty($server)) {
            return $this->sendEmailFallback($params);
        }

        /** @var OptionCommon $common */
        $common = container()->get(OptionCommon::class);

        $params['from'] = [$server->getFromEmail() => $common->getSiteName()];

        $sent = false;
        for ($i = 0; $i < 3; ++$i) {
            if ($server->sendEmail($params)) {
                $sent = true;
                break;
            }

            /** @var DeliveryServer $server */
            $server = DeliveryServer::pickServer((int)$server->server_id);
        }

        if (!$sent) {
            $sent = $this->sendEmailFallback($params);
        }

        return !empty($sent);
    }

    /**
     * @param array $params
     *
     * @return bool
     * @throws CException
     */
    public function sendEmailFallback(array $params = []): bool
    {
        /** @var OptionCommon $common */
        $common = container()->get(OptionCommon::class);

        $email               = 'noreply@' . (string)request()->getServer('HTTP_HOST', (string)request()->getServer('SERVER_NAME', 'domain.com'));
        $params['from']      = [$email => $common->getSiteName()];
        $params['transport'] = 'sendmail';

        $sent = mailer()->send($params);

        return !empty($sent);
    }

    /**
     * @return bool
     */
    protected function beforeSave()
    {
        if ($this->getIsNewRecord()) {
            $this->reset_key  = StringHelper::randomSha1();
            $this->ip_address = (string)request()->getUserHostAddress();
            self::model()->updateAll(['status' => self::STATUS_USED], 'customer_id = :id', [':id' => (int)$this->customer_id]);
        }

        return parent::beforeSave();
    }
}
