<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * PricePlanPromoCode
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.4.4
 */

/**
 * This is the model class for table "{{price_plan_promo_code}}".
 *
 * The followings are the available columns in table '{{price_plan_promo_code}}':
 * @property integer $promo_code_id
 * @property string $code
 * @property string $type
 * @property string $discount
 * @property string $total_amount
 * @property integer $total_usage
 * @property integer $customer_usage
 * @property string $date_start
 * @property string $date_end
 * @property string $status
 * @property string|CDbExpression $date_added
 * @property string|CDbExpression $last_updated
 *
 * The followings are the available model relations:
 * @property PricePlanOrder[] $pricePlanOrders
 */
class PricePlanPromoCode extends ActiveRecord
{
    /**
     * Discount type
     */
    const TYPE_PERCENTAGE = 'percentage';
    const TYPE_FIXED_AMOUNT = 'fixed amount';

    /**
     * @var string
     */
    public $pickerDateStartComparisonSign;

    /**
     * @var string
     */
    public $pickerDateEndComparisonSign;

    /**
     * @return string
     */
    public function tableName()
    {
        return '{{price_plan_promo_code}}';
    }

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            ['code, type, discount, total_amount, total_usage, customer_usage, date_start, date_end, status', 'required'],

            ['code', 'length', 'min' => 1, 'max' => 15],
            ['code', 'unique'],
            ['type', 'in', 'range' => array_keys($this->getTypesList())],
            ['discount, total_amount', 'numerical'],
            ['discount, total_amount', 'type', 'type' => 'float'],
            ['total_usage, customer_usage', 'length', 'min' => 1],
            ['total_usage, customer_usage', 'numerical', 'integerOnly' => true, 'min' => 0, 'max' => 9999],
            ['date_start, date_end', 'date', 'format' => 'yyyy-MM-dd'],

            ['pickerDateStartComparisonSign, pickerDateEndComparisonSign', 'in', 'range' => array_keys($this->getComparisonSignsList())],
            ['code, type, discount, total_amount, total_usage, customer_usage, date_start, date_end, status', 'safe', 'on'=>'search'],
        ];
        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array
     */
    public function relations()
    {
        $relations = [
            'pricePlanOrders' => [self::HAS_MANY, PricePlanOrder::class, 'promo_code_id'],
        ];
        return CMap::mergeArray($relations, parent::relations());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'code_id'        => t('promo_codes', 'Code'),
            'code'           => t('promo_codes', 'Code'),
            'type'           => t('promo_codes', 'Type'),
            'discount'       => t('promo_codes', 'Discount'),
            'total_amount'   => t('promo_codes', 'Total amount'),
            'total_usage'    => t('promo_codes', 'Total usage'),
            'customer_usage' => t('promo_codes', 'Customer usage'),
            'date_start'     => t('promo_codes', 'Date start'),
            'date_end'       => t('promo_codes', 'Date end'),
        ];
        return CMap::mergeArray($labels, parent::attributeLabels());
    }

    /**
     * @return array
     */
    public function attributeHelpTexts()
    {
        $texts = [
            'code_id'        => t('promo_codes', 'Code'),
            'code'           => t('promo_codes', 'The promotional code'),
            'type'           => t('promo_codes', 'The type of the promotional code'),
            'discount'       => t('promo_codes', 'The discount received after applying this promotional code'),
            'total_amount'   => t('promo_codes', 'The amount of the price plan in order for this promotional code to apply'),
            'total_usage'    => t('promo_codes', 'The maximum number of usages for this promotional code. Set it to 0 for unlimited'),
            'customer_usage' => t('promo_codes', 'How many times a customer can use this promotional code. Set it to 0 for unlimited'),
            'date_start'     => t('promo_codes', 'The start date for this promotional code'),
            'date_end'       => t('promo_codes', 'The end date for this promotional code'),
        ];

        return CMap::mergeArray($texts, parent::attributeHelpTexts());
    }

    /**
     * @return array attribute placeholders
     */
    public function attributePlaceholders()
    {
        $placeholders = [
            'code_id'        => '',
            'code'           => t('promo_codes', 'i.e: FREE100'),
            'type'           => '',
            'discount'       => t('promo_codes', 'i.e: 10'),
            'total_amount'   => t('promo_codes', 'i.e: 30'),
            'total_usage'    => t('promo_codes', 'i.e: 10'),
            'customer_usage' => t('promo_codes', 'i.e: 1'),
            'date_start'     => t('promo_codes', t('promo_codes', 'i.e: {date}', ['{date}' => date('Y-m-d')])),
            'date_end'       => t('promo_codes', t('promo_codes', 'i.e: {date}', ['{date}' => date('Y-m-d', (int)strtotime('+30 days'))])),
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

        $comparisonSigns   = $this->getComparisonSignsList();
        $originalDateStart = $this->date_start;
        $originalDateEnd   = $this->date_end;
        if (!empty($this->pickerDateStartComparisonSign) && in_array($this->pickerDateStartComparisonSign, array_keys($comparisonSigns))) {
            $this->date_start = $comparisonSigns[$this->pickerDateStartComparisonSign] . $this->date_start;
        }
        if (!empty($this->pickerDateEndComparisonSign) && in_array($this->pickerDateEndComparisonSign, array_keys($comparisonSigns))) {
            $this->date_end = $comparisonSigns[$this->pickerDateEndComparisonSign] . $this->date_end;
        }

        $criteria->compare('code', $this->code, true);
        $criteria->compare('type', $this->type);
        $criteria->compare('discount', $this->discount);
        $criteria->compare('total_amount', $this->total_amount);
        $criteria->compare('total_usage', $this->total_usage);
        $criteria->compare('customer_usage', $this->customer_usage);
        $criteria->compare('date_start', $this->date_start);
        $criteria->compare('date_end', $this->date_end);
        $criteria->compare('status', $this->status);

        $this->date_start = $originalDateStart;
        $this->date_end   = $originalDateEnd;

        return new CActiveDataProvider(get_class($this), [
            'criteria'      => $criteria,
            'pagination'    => [
                'pageSize'  => $this->paginationOptions->getPageSize(),
                'pageVar'   => 'page',
            ],
            'sort'=>[
                'defaultOrder' => [
                    'promo_code_id'  => CSort::SORT_DESC,
                ],
            ],
        ]);
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return PricePlanPromoCode the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var PricePlanPromoCode $model */
        $model = parent::model($className);

        return $model;
    }

    /**
     * @return array
     */
    public function getTypesList(): array
    {
        return [
            self::TYPE_FIXED_AMOUNT => ucfirst(t('promo_codes', self::TYPE_FIXED_AMOUNT)),
            self::TYPE_PERCENTAGE   => ucfirst(t('promo_codes', self::TYPE_PERCENTAGE)),
        ];
    }

    /**
     * @param string $type
     *
     * @return string
     */
    public function getTypeName(string $type = ''): string
    {
        if (!$type) {
            $type = $this->type;
        }
        return $this->getTypesList()[$type] ?? '';
    }

    /**
     * @return Currency|null
     */
    public function getCurrency(): ?Currency
    {
        return Currency::model()->findDefault();
    }

    /**
     * @return string
     */
    public function getFormattedDiscount(): string
    {
        if ($this->type == self::TYPE_FIXED_AMOUNT) {

            /** @var Currency|null $currency */
            $currency = $this->getCurrency();

            /** @var string $code */
            $code = !empty($currency) && !empty($currency->code) ? $currency->code : '';

            return numberFormatter()->formatCurrency($this->discount, $code);
        }

        return numberFormatter()->formatDecimal($this->discount) . '%';
    }

    /**
     * @return string
     */
    public function getFormattedTotalAmount(): string
    {
        /** @var Currency|null $currency */
        $currency = $this->getCurrency();

        /** @var string $code */
        $code = !empty($currency) && !empty($currency->code) ? $currency->code : '';

        return numberFormatter()->formatCurrency($this->total_amount, $code);
    }

    /**
     * @return string
     * @throws Exception
     */
    public function getDateStart(): string
    {
        return $this->dateTimeFormatter->formatLocalizedDate($this->date_start);
    }

    /**
     * @return string
     * @throws Exception
     */
    public function getDateEnd(): string
    {
        return $this->dateTimeFormatter->formatLocalizedDate($this->date_end);
    }

    /**
     * @return string
     */
    public function getDatePickerFormat(): string
    {
        return 'yy-mm-dd';
    }

    /**
     * @return string
     */
    public function getDatePickerLanguage(): string
    {
        $language = app()->getLanguage();
        if (strpos($language, '_') === false) {
            return $language;
        }
        $language = explode('_', $language);

        // commented since 1.3.5.9
        // return $language[0] . '-' . strtoupper((string)$language[1]);
        return $language[0];
    }
}
