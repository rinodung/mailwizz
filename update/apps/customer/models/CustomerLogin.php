<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * CustomerLogin
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

class CustomerLogin extends Customer
{
    /**
     * @var bool
     */
    public $remember_me = true;

    /**
     * @var string
     */
    public $twofa_code = '';

    /**
     * @var Customer|null
     */
    protected $_model;

    /**
     * @inheritDoc
     */
    public function rules()
    {
        $filter = apps()->getCurrentAppName() . '_model_' . strtolower(get_class($this)) . '_' . strtolower(__FUNCTION__);

        $rules = [
            ['email, password', 'required'],
            ['twofa_code', 'required', 'on' => 'twofa-login'],

            ['email', 'length', 'min' => 7, 'max' => 100],
            ['email', 'email', 'validateIDN' => true],
            ['password', 'length', 'min' => 6, 'max' => 100],
            ['password', '_preAuthenticate'],

            ['remember_me', 'safe'],
            ['twofa_code', 'length', 'min' => 3, 'max' => 100],
        ];

        /** @var CList $rules */
        $rules = hooks()->applyFilters($filter, new CList($rules));

        $this->onRules(new CModelEvent($this, [
            'rules' => $rules,
        ]));

        return $rules->toArray();
    }

    /**
     * @inheritDoc
     */
    public function attributeLabels()
    {
        $labels = [
            'remember_me' => t('customers', 'Remember me'),
            'twofa_code'  => t('customers', '2FA code'),
        ];

        return CMap::mergeArray($labels, parent::attributeLabels());
    }

    /**
     * @inheritDoc
     */
    public function attributePlaceholders()
    {
        $placeholders = [
            'twofa_code'  => '',
        ];

        return CMap::mergeArray($placeholders, parent::attributePlaceholders());
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return Customer the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var Customer $model */
        $model = parent::model($className);

        return $model;
    }

    /**
     * @param string $attribute
     * @param array $params
     *
     * @throws CException
     */
    public function _preAuthenticate(string $attribute, array $params = []): void
    {
        if ($this->hasErrors()) {
            return;
        }

        $identity = new CustomerIdentity($this->email, $this->password);
        if (!$identity->authenticate()) {
            $this->addError($attribute, $identity->errorMessage);
            return;
        }


        if (!($model = $this->getModel())) {
            $this->addError($attribute, t('customers', 'Invalid login credentials.'));
            return;
        }
    }

    /**
     * @return bool
     * @throws CException
     */
    public function authenticate(): bool
    {
        if ($this->hasErrors()) {
            return false;
        }

        $identity = new CustomerIdentity($this->email, $this->password);
        if (!$identity->authenticate()) {
            $this->addError('password', $identity->errorMessage);
            return false;
        }

        if (!customer()->login($identity, $this->remember_me ? 3600 * 24 * 30 : 0)) {
            $this->addError('password', t('customers', 'Unable to login with the given identity!'));
            return false;
        }

        return true;
    }

    /**
     * @return Customer|null
     */
    public function getModel(): ?Customer
    {
        if ($this->_model === null) {
            $this->_model = Customer::model()->findByAttributes([
                'email' => $this->email,
            ]);
        }
        return $this->_model;
    }
}
