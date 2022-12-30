<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * OptionLicense
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.4.9
 */

class OptionLicense extends OptionBase
{
    /**
     * Marketplaces
     */
    const MARKETPLACE_MAILWIZZ = 'mailwizz';
    const MARKETPLACE_ENVATO   = 'envato';

    /**
     * @var string
     */
    public $market_place = '';

    /**
     * @var string
     */
    public $purchase_code = '';

    /**
     * @var string
     */
    public $error_message = '';

    /**
     * @var string
     */
    protected $_categoryName = 'system.license';

    /**
     * @var string
     */
    private $_displayPurchaseCode = '';

    /**
     * @var string
     */
    private $_initPurchaseCode = '';

    /**
     * @return array
     * @throws CException
     */
    public function rules()
    {
        $rules = [
            ['market_place, displayPurchaseCode', 'required'],
            ['market_place, displayPurchaseCode', 'length', 'max' => 255],
            ['market_place', 'in', 'range' => array_keys($this->getMarketplacesList())],
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
            'market_place'        => $this->t('Market place'),
            'purchase_code'       => $this->t('Purchase code'),
            'displayPurchaseCode' => $this->t('Purchase code'),
        ];

        return CMap::mergeArray($labels, parent::attributeLabels());
    }

    /**
     * @return array
     * @throws CException
     */
    public function attributeHelpTexts()
    {
        $texts = [];
        return CMap::mergeArray($texts, parent::attributeHelpTexts());
    }

    /**
     * @return array
     */
    public function getMarketplacesList(): array
    {
        return [
            self::MARKETPLACE_ENVATO    => $this->t('Envato Market Places'),
            self::MARKETPLACE_MAILWIZZ  => $this->t('Mailwizz Website'),
        ];
    }

    /**
     * @return string
     */
    public function getMarketPlace(): string
    {
        return (string)$this->market_place;
    }

    /**
     * @param string $code
     */
    public function setDisplayPurchaseCode(string $code): void
    {
        $this->_displayPurchaseCode = $code;
    }

    /**
     * @return string
     */
    public function getDisplayPurchaseCode(): string
    {
        if (!empty($this->_displayPurchaseCode)) {
            return (string)$this->_displayPurchaseCode;
        }

        return StringHelper::maskStringEnding($this->purchase_code);
    }

    /**
     * @return string
     */
    public function getPurchaseCode(): string
    {
        return (string)$this->purchase_code;
    }

    /**
     * @return string
     */
    public function getErrorMessage(): string
    {
        return (string)$this->error_message;
    }

    /**
     * @return string
     */
    public function getMissingPurchaseCodeMessage(): string
    {
        return (string)file_get_contents((string)Yii::getPathOfAlias('common.data.license.missing-purchase-code') . '.php');
    }

    /**
     * @return string
     */
    public function getCurrentPurchaseCode(): string
    {
        $displayPurchaseCode = $this->_displayPurchaseCode;
        $initPurchaseCode    = $this->_initPurchaseCode;
        $isAlreadyMasked     = str_repeat('x', 10) === substr(strtolower($displayPurchaseCode), -10);

        if ($isAlreadyMasked) {
            return $initPurchaseCode;
        }

        if ($displayPurchaseCode === StringHelper::maskStringEnding($initPurchaseCode)) {
            return $initPurchaseCode;
        }

        return $displayPurchaseCode;
    }

    /**
     * @return void
     */
    protected function afterConstruct()
    {
        parent::afterConstruct();
        $this->_initPurchaseCode = $this->purchase_code;
    }

    /**
     * @return bool
     */
    protected function beforeValidate()
    {
        $this->purchase_code = $this->getCurrentPurchaseCode();

        return parent::beforeValidate();
    }
}
