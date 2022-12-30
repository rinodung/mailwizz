<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * PricePlanOrder
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.4.4
 */

/**
 * This is the model class for table "{{price_plan_order}}".
 *
 * The followings are the available columns in table '{{price_plan_order}}':
 * @property integer|null $order_id
 * @property string|null $order_uid
 * @property integer $customer_id
 * @property integer $plan_id
 * @property integer|null $promo_code_id
 * @property integer $tax_id
 * @property integer $currency_id
 * @property float $subtotal
 * @property string $tax_percent
 * @property float $tax_value
 * @property float $discount
 * @property float $total
 * @property string $status
 * @property string|null $date_added
 * @property string|null $last_updated
 *
 * The followings are the available model relations:
 * @property Tax $tax
 * @property PricePlan $plan
 * @property Customer $customer
 * @property PricePlanPromoCode $promoCode
 * @property Currency $currency
 * @property PricePlanOrderNote[] $notes
 * @property PricePlanOrderTransaction[] $transactions
 */
class PricePlanOrder extends ActiveRecord
{
    /**
     * Statuses list
     */
    const STATUS_INCOMPLETE = 'incomplete';
    const STATUS_COMPLETE   = 'complete';
    const STATUS_PENDING    = 'pending';
    const STATUS_FAILED     = 'failed';
    const STATUS_REFUNDED   = 'refunded';
    const STATUS_DUE        = 'due';

    /**
     * @var string
     */
    protected $_initStatus;

    /**
     * @return string
     */
    public function tableName()
    {
        return '{{price_plan_order}}';
    }

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            ['customer_id, plan_id, currency_id', 'required'],
            ['customer_id, plan_id, promo_code_id, currency_id, tax_id', 'numerical', 'integerOnly' => true],
            ['subtotal, discount, total, tax_value, tax_percent', 'numerical'],
            ['subtotal, discount, total, tax_value, tax_percent', 'type', 'type' => 'float'],
            ['status', 'in', 'range' => array_keys($this->getStatusesList())],

            // The following rule is used by search().
            ['order_uid, customer_id, plan_id, promo_code_id, currency_id, tax_id, subtotal, tax_value, tax_percent, discount, total, status', 'safe', 'on'=>'search'],
            ['subtotal, tax_value, discount, total', 'safe', 'on'=>'customer-search'],
        ];
        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array
     */
    public function relations()
    {
        $relations = [
            'tax'            => [self::BELONGS_TO, Tax::class, 'tax_id'],
            'plan'           => [self::BELONGS_TO, PricePlan::class, 'plan_id'],
            'customer'       => [self::BELONGS_TO, Customer::class, 'customer_id'],
            'promoCode'      => [self::BELONGS_TO, PricePlanPromoCode::class, 'promo_code_id'],
            'currency'       => [self::BELONGS_TO, Currency::class, 'currency_id'],
            'notes'          => [self::HAS_MANY, PricePlanOrderNote::class, 'order_id'],
            'transactions'   => [self::HAS_MANY, PricePlanOrderTransaction::class, 'order_id'],
        ];
        return CMap::mergeArray($relations, parent::relations());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'order_id'       => t('orders', 'Order'),
            'order_uid'      => t('orders', 'Order no.'),
            'customer_id'    => t('orders', 'Customer'),
            'plan_id'        => t('orders', 'Plan'),
            'promo_code_id'  => t('orders', 'Promo code'),
            'tax_id'         => t('orders', 'Tax'),
            'currency_id'    => t('orders', 'Currency'),
            'subtotal'       => t('orders', 'Subtotal'),
            'tax_percent'    => t('orders', 'Tax percent'),
            'tax_value'      => t('orders', 'Tax value'),
            'discount'       => t('orders', 'Discount'),
            'total'          => t('orders', 'Total'),
        ];
        return CMap::mergeArray($labels, parent::attributeLabels());
    }

    /**
     * @return array
     */
    public function attributeHelpTexts()
    {
        $texts = [
            'customer_id'    => t('orders', 'The customer this order applies to, autocomplete enabled'),
            'plan_id'        => t('orders', 'The price plan included in this order, autocomplete enabled'),
            'promo_code_id'  => t('orders', 'The promo code applied to this order, autocomplete enabled'),
        ];

        return CMap::mergeArray($texts, parent::attributeHelpTexts());
    }

    /**
     * @return array
     */
    public function attributePlaceholders()
    {
        $placeholders = [
            'customer_id'    => t('orders', 'Customer, autocomplete enabled'),
            'plan_id'        => t('orders', 'Plan, autocomplete enabled'),
            'promo_code_id'  => t('orders', 'Promo code, autocomplete enabled'),
            'currency_id'    => '',
            'subtotal'       => '0.0000',
            'discount'       => '0.0000',
            'total'          => '0.0000',
        ];

        return CMap::mergeArray($placeholders, parent::attributePlaceholders());
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

        if ($this->customer_id) {
            if (is_string($this->customer_id)) {
                $criteria->with['customer'] = [
                    'together' => true,
                    'joinType' => 'INNER JOIN',
                    'condition'=> '(CONCAT(customer.first_name, " ", customer.last_name) LIKE :c01 OR customer.email LIKE :c01)',
                    'params'   => [':c01' => '%' . $this->customer_id . '%'],
                ];
            } else {
                $criteria->compare('t.customer_id', (int)$this->customer_id);
            }
        }

        if ($this->plan_id) {
            if (is_string($this->plan_id)) {
                $criteria->with['plan'] = [
                    'together' => true,
                    'joinType' => 'INNER JOIN',
                    'condition'=> 'plan.name LIKE :p01',
                    'params'   => [':p01' => '%' . $this->plan_id . '%'],
                ];
            } else {
                $criteria->compare('t.plan_id', (int)$this->plan_id);
            }
        }

        if ($this->promo_code_id) {
            if (is_string($this->promo_code_id)) {
                $criteria->with['promoCode'] = [
                    'together' => true,
                    'joinType' => 'INNER JOIN',
                    'condition'=> 'promoCode.code LIKE :pc01',
                    'params'   => [':pc01' => '%' . $this->promo_code_id . '%'],
                ];
            } else {
                $criteria->compare('t.promo_code_id', (int)$this->promo_code_id);
            }
        }

        if ($this->currency_id) {
            if (is_string($this->currency_id)) {
                $criteria->with['currency'] = [
                    'together' => true,
                    'joinType' => 'INNER JOIN',
                    'condition'=> 'currency.code LIKE :cr01',
                    'params'   => [':cr01' => '%' . $this->currency_id . '%'],
                ];
            } else {
                $criteria->compare('t.currency_id', (int)$this->currency_id);
            }
        }

        if ($this->tax_id) {
            if (is_string($this->tax_id)) {
                $criteria->with['tax'] = [
                    'together' => true,
                    'joinType' => 'INNER JOIN',
                    'condition'=> 'currency.code LIKE :t01',
                    'params'   => [':t01' => '%' . $this->tax_id . '%'],
                ];
            } else {
                $criteria->compare('t.tax_id', (int)$this->tax_id);
            }
        }

        $criteria->compare('t.order_uid', $this->order_uid, true);
        $criteria->compare('t.subtotal', $this->subtotal, true);
        $criteria->compare('t.tax_value', $this->tax_value, true);
        $criteria->compare('t.tax_percent', $this->tax_percent, true);
        $criteria->compare('t.discount', $this->discount, true);
        $criteria->compare('t.total', $this->total, true);
        $criteria->compare('t.status', $this->status);

        $criteria->order = 't.order_id DESC';

        return new CActiveDataProvider(get_class($this), [
            'criteria'   => $criteria,
            'pagination' => [
                'pageSize' => $this->paginationOptions->getPageSize(),
                'pageVar'  => 'page',
            ],
            'sort'=>[
                'defaultOrder' => [
                    't.order_id'  => CSort::SORT_DESC,
                ],
            ],
        ]);
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return PricePlanOrder the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var PricePlanOrder $model */
        $model = parent::model($className);

        return $model;
    }

    /**
     * @inheritDoc
     */
    public function getStatusesList(): array
    {
        return [
            self::STATUS_INCOMPLETE => t('app', 'Incomplete'),
            self::STATUS_COMPLETE   => t('app', 'Complete'),
            self::STATUS_PENDING    => t('app', 'Pending'),
            self::STATUS_DUE        => t('app', 'Due'),
            self::STATUS_FAILED     => t('app', 'Failed'),
            self::STATUS_REFUNDED   => t('app', 'Refunded'),
        ];
    }

    /**
     * @return PricePlanOrder
     */
    public function calculate(): self
    {
        if (empty($this->plan_id)) {
            return $this;
        }

        // since 1.7.6
        hooks()->applyFilters('price_plan_order_before_calculate_totals', $this);

        $this->subtotal = (float)$this->plan->price;
        $this->total    = (float)$this->plan->price;

        if (!empty($this->promo_code_id) && !empty($this->promoCode)) {
            $this->discount = 0.0;

            if ($this->promoCode->type == PricePlanPromoCode::TYPE_FIXED_AMOUNT) {
                $this->discount = (float)$this->discount + (float)$this->promoCode->discount;
            } else {
                $this->discount = (float)$this->discount + (float)(((float)$this->promoCode->discount / 100) * (float)$this->total);
            }

            $this->total = (float)$this->total - (float)$this->discount;
            if ($this->total < 0) {
                $this->total = 0.0;
            }
        }

        $this->applyTaxes();

        // since 1.7.6
        hooks()->applyFilters('price_plan_order_after_calculate_totals', $this);

        return $this;
    }

    /**
     * @return string
     */
    public function getNumber(): string
    {
        /** @var OptionMonetizationInvoices $invoiceOptions */
        $invoiceOptions = container()->get(OptionMonetizationInvoices::class);

        return trim($invoiceOptions->prefix) . '-' . ($this->order_id < 10 ? '0' . $this->order_id : $this->order_id);
    }

    /**
     * @return string
     */
    public function getFormattedSubtotal(): string
    {
        return numberFormatter()->formatCurrency($this->subtotal, $this->currency->code);
    }

    /**
     * @return string
     */
    public function getFormattedTaxPercent(): string
    {
        return formatter()->formatNumber($this->tax_percent) . '%';
    }

    /**
     * @return string
     */
    public function getFormattedTaxValue(): string
    {
        return numberFormatter()->formatCurrency($this->tax_value, $this->currency->code);
    }

    /**
     * @return string
     */
    public function getFormattedDiscount(): string
    {
        return numberFormatter()->formatCurrency($this->discount, $this->currency->code);
    }

    /**
     * @return string
     */
    public function getFormattedTotal(): string
    {
        return numberFormatter()->formatCurrency($this->total, $this->currency->code);
    }

    /**
     * @return string
     */
    public function getFormattedTotalDue(): string
    {
        return $this->getIsComplete() ?
            numberFormatter()->formatCurrency(0.00, $this->currency->code) :
            numberFormatter()->formatCurrency($this->total, $this->currency->code);
    }

    /**
     * @param string $order_uid
     *
     * @return PricePlanOrder|null
     */
    public function findByUid(string $order_uid): ?self
    {
        return self::model()->findByAttributes([
            'order_uid' => $order_uid,
        ]);
    }

    /**
     * @return string
     */
    public function generateUid(): string
    {
        $unique = StringHelper::uniqid();
        $exists = $this->findByUid($unique);

        if (!empty($exists)) {
            return $this->generateUid();
        }

        return $unique;
    }

    /**
     * @return string
     */
    public function getUid(): string
    {
        return (string)$this->order_uid;
    }

    /**
     * @return PricePlanOrder
     */
    public function applyTaxes(): self
    {
        if (empty($this->customer_id)) {
            return $this;
        }

        if ($this->tax_id !== null && $this->tax_percent > 0 && $this->tax_value > 0) {
            return $this;
        }

        if (empty($this->tax_id) || empty($this->tax)) {
            $tax = $zoneTax = $countryTax = null;
            $globalTax = Tax::model()->findByAttributes(['is_global' => Tax::TEXT_YES]);
            if (!empty($this->customer) && !empty($this->customer->company)) {
                $company  = $this->customer->company;
                $zoneTax  = Tax::model()->findByAttributes(['zone_id' => (int)$company->zone_id]);
                if (empty($zoneTax)) {
                    $countryTax = Tax::model()->findByAttributes(['country_id' => (int)$company->country_id]);
                }
            }

            if (!empty($zoneTax)) {
                $tax = $zoneTax;
            } elseif (!empty($countryTax)) {
                $tax = $countryTax;
            } elseif (!empty($globalTax)) {
                $tax = $globalTax;
            } else {
                return $this;
            }

            if ($tax->percent < 0.1) {
                return $this;
            }

            $this->tax_id = (int)$tax->tax_id;
            $this->addRelatedRecord('tax', $tax, false);
        }


        $this->tax_percent = $this->tax->percent;
        $this->tax_value   = ((float)$this->tax->percent / 100) * (float)$this->total;
        $this->total       = (float)$this->total + (float)$this->tax_value;

        return $this;
    }

    /**
     * @param string $headingTag
     * @param string $separator
     * @return string
     */
    public function getHtmlPaymentFrom(string $headingTag = 'strong', string $separator = '<br />'): string
    {
        if (empty($this->customer_id)) {
            return '';
        }

        $customer    = $this->customer;
        $paymentFrom = [];

        if ($headingTag !== '' && $headingTag != "\n") {
            $paymentFrom[] = CHtml::tag($headingTag, [], $customer->getFullName());
        } else {
            $paymentFrom[] = $customer->getFullName();
        }

        if (!empty($customer->company)) {
            $paymentFrom[] = $customer->company->name;
            $paymentFrom[] = $customer->company->address_1;
            $paymentFrom[] = $customer->company->address_2;

            $location = [];
            $location[] = !empty($customer->company->country_id) ? $customer->company->country->name : '';
            $location[] = !empty($customer->company->zone_id) ? $customer->company->zone_name : '';
            $location[] = $customer->company->city;
            $location[] = $customer->company->zip_code;

            foreach ($location as $index => $info) {
                if (empty($info)) {
                    unset($location[$index]);
                }
            }

            $paymentFrom[] = implode(', ', $location);
            $paymentFrom[] = $customer->company->phone;

            if (!empty($customer->company->vat_number)) {
                $paymentFrom[] = t('orders', 'VAT Number: {vat_number}', ['{vat_number}' => $customer->company->vat_number]);
            }

            foreach ($paymentFrom as $index => $info) {
                if (empty($info)) {
                    unset($paymentFrom[$index]);
                }
            }
        }

        $paymentFrom[] = $customer->email;

        $html = implode($separator, $paymentFrom);

        // 1.5.0
        $html = (string)hooks()->applyFilters('price_plan_order_get_html_payment_from', $html, $customer);

        return $html;
    }

    /**
     * @param string $headingTag
     * @param string $separator
     * @return string
     */
    public function getHtmlPaymentTo(string $headingTag = 'strong', string $separator = '<br />'): string
    {
        if (empty($this->customer_id)) {
            return '';
        }

        $customer  = $this->customer;
        $paymentTo = [];

        /** @var OptionCommon $common */
        $common = container()->get(OptionCommon::class);
        if ($headingTag !== '' && $headingTag != "\n") {
            $paymentTo[] = CHtml::tag($headingTag, [], $common->getSiteName());
        } else {
            $paymentTo[] = $common->getSiteName();
        }

        if ($separator !== null && $separator != "\n") {
            $paymentTo[] = nl2br($common->getCompanyInfo());
        } else {
            $paymentTo[] = $common->getCompanyInfo();
        }

        $html = implode($separator, $paymentTo);

        // 1.5.0
        $html = (string)hooks()->applyFilters('price_plan_order_get_html_payment_to', $html, $customer);

        return $html;
    }

    /**
     * @return bool
     */
    public function getIsComplete(): bool
    {
        return $this->getStatusIs(self::STATUS_COMPLETE);
    }

    /**
     * @return bool
     */
    public function getIsDue(): bool
    {
        return $this->getStatusIs(self::STATUS_DUE);
    }

    /**
     * @param CEvent $event
     *
     * @return void
     * @throws CException
     */
    public static function sendNewOrderNotificationsEvent(CEvent $event)
    {
        /** @var PricePlanOrder $order */
        $order = $event->sender;

        // since 1.9.5 - allow due orders as well.
        $canNotify = $order->getIsComplete() || $order->getIsDue();
        if (!$canNotify) {
            return;
        }

        /** @var OptionCommon $common */
        $common = container()->get(OptionCommon::class);

        /** @var OptionUrl $url */
        $url = container()->get(OptionUrl::class);

        $users = User::model()->findAll([
            'select'    => 'first_name, last_name, email',
            'condition' => '`status` = "active"',
        ]);

        foreach ($users as $user) {
            $params = CommonEmailTemplate::getAsParamsArrayBySlug(
                'new-order-placed-user',
                [
                    'subject' => t('orders', 'A new order has been placed!'),
                ],
                [
                    '[USER_NAME]'           => $user->getFullName(),
                    '[CUSTOMER_NAME]'       => $order->customer->getFullName(),
                    '[PLAN_NAME]'           => $order->plan->name,
                    '[ORDER_SUBTOTAL]'      => $order->getFormattedSubtotal(),
                    '[ORDER_TAX]'           => $order->getFormattedTaxValue(),
                    '[ORDER_DISCOUNT]'      => $order->getFormattedDiscount(),
                    '[ORDER_TOTAL]'         => $order->getFormattedTotal(),
                    '[ORDER_STATUS]'        => $order->getStatusName(),
                    '[ORDER_OVERVIEW_URL]'  => $url->getBackendUrl(sprintf('orders/view/id/%d', $order->order_id)),
                ]
            );

            $email = new TransactionalEmail();
            $email->to_name     = $user->getFullName();
            $email->to_email    = $user->email;
            $email->from_name   = $common->getSiteName();
            $email->subject     = $params['subject'];
            $email->body        = $params['body'];
            $email->save();
        }

        $customer = $order->customer;
        $params   = CommonEmailTemplate::getAsParamsArrayBySlug(
            'new-order-placed-customer',
            [
                'subject' => t('orders', 'Your order details!'),
            ],
            [
                '[CUSTOMER_NAME]'       => $order->customer->getFullName(),
                '[PLAN_NAME]'           => $order->plan->name,
                '[ORDER_SUBTOTAL]'      => $order->getFormattedSubtotal(),
                '[ORDER_TAX]'           => $order->getFormattedTaxValue(),
                '[ORDER_DISCOUNT]'      => $order->getFormattedDiscount(),
                '[ORDER_TOTAL]'         => $order->getFormattedTotal(),
                '[ORDER_STATUS]'        => $order->getStatusName(),
                '[ORDER_OVERVIEW_URL]'  => $url->getCustomerUrl(sprintf('price-plans/orders/%s', $order->order_uid)),
            ]
        );

        $email = new TransactionalEmail();
        if ($email->hasAttribute('fallback_system_servers')) {
            $email->fallback_system_servers = TransactionalEmail::TEXT_YES;
        }
        $email->customer_id             = (int)$customer->customer_id;
        $email->to_name                 = $customer->getFullName();
        $email->to_email                = $customer->email;
        $email->from_name               = $common->getSiteName();
        $email->subject                 = $params['subject'];
        $email->body                    = $params['body'];
        $email->save();
    }

    /**
     * @return bool
     */
    protected function beforeSave()
    {
        if (!parent::beforeSave()) {
            return false;
        }

        if (empty($this->order_uid)) {
            $this->order_uid = $this->generateUid();
        }

        return true;
    }

    /**
     * @return void
     */
    protected function afterConstruct()
    {
        $this->_initStatus = $this->status;
        if (empty($this->currency_id)) {

            /** @var Currency|null $currency */
            $currency = Currency::model()->findDefault();

            if (!empty($currency)) {
                $this->addRelatedRecord('currency', $currency, false);
                $this->currency_id = (int)$currency->currency_id;
            }
        }
        parent::afterConstruct();
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
     * @return void
     */
    protected function afterSave()
    {
        if (
            in_array($this->_initStatus, [self::STATUS_INCOMPLETE, self::STATUS_PENDING, self::STATUS_DUE]) &&
            $this->getStatusIs(self::STATUS_COMPLETE)
        ) {
            $this->customer->group_id = $this->plan->group_id;
            $this->customer->save(false);
            $this->customer->createQuotaMark();
        }
        parent::afterSave();
    }
}
