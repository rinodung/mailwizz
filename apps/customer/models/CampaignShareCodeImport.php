<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * CampaignShareCodeImport
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.7.6
 */

class CampaignShareCodeImport extends FormModel
{
    /**
     * @var string
     */
    public $code = '';

    /**
     * @var int
     */
    public $list_id = 0;

    /**
     * @var int
     */
    public $customer_id = 0;

    /**
     * @var CampaignShareCode
     */
    private $_campaign_share_code;

    /**
     * @var Lists
     */
    private $_list;

    /**
     * @inheritDoc
     */
    public function rules()
    {
        return [
            ['list_id, code', 'required'],
            ['list_id', 'numerical', 'integerOnly' => true],
            ['code', 'length', 'is' => 40],

            ['list_id', '_validateList'],
            ['code', '_validateCode'],

            ['customer_id', 'unsafe'],
        ];
    }

    /**
     * @inheritDoc
     */
    public function attributeLabels()
    {
        return [
            'code'    => t('campaigns', 'Code'),
            'list_id' => t('campaigns', 'List'),
        ];
    }

    /**
     * @inheritDoc
     */
    public function attributeHelpTexts()
    {
        return [];
    }

    /**
     * @param string $attribute
     * @param array $params
     */
    public function _validateList(string $attribute, array $params = []): void
    {
        if (empty($this->$attribute)) {
            return;
        }

        if ($this->getList()) {
            return;
        }

        $this->addError($attribute, t('campaigns', 'The list you choose is not a valid list.'));
    }

    /**
     * @param string $attribute
     * @param array $params
     */
    public function _validateCode(string $attribute, array $params = []): void
    {
        if (empty($this->$attribute)) {
            return;
        }

        if ($this->hasErrors($attribute)) {
            return;
        }

        if ($this->getCampaignShareCode()) {
            return;
        }

        $this->addError($attribute, t('campaigns', 'The sharing code you provided is not a valid campaign sharing code.'));
    }

    /**
     * @return array
     */
    public function getListsAsDropDownOptionsByCustomerId(): array
    {
        $this->customer_id = (int)$this->customer_id;

        static $options = [];
        if (isset($options[$this->customer_id])) {
            return $options[$this->customer_id];
        }

        $criteria = new CDbCriteria();
        $criteria->select = 'list_id, name';
        $criteria->compare('customer_id', $this->customer_id);
        $criteria->addNotInCondition('status', [Lists::STATUS_PENDING_DELETE, Lists::STATUS_ARCHIVED]);
        $criteria->order = 'name ASC';

        return $options[$this->customer_id] = ListsCollection::findAll($criteria)->mapWithKeys(function (Lists $list) {
            return [$list->list_id => $list->name];
        })->toArray();
    }

    /**
     * @return CampaignShareCode|null
     */
    public function getCampaignShareCode(): ?CampaignShareCode
    {
        if ($this->_campaign_share_code !== null) {
            return $this->_campaign_share_code;
        }

        if (empty($this->code) || empty($this->customer_id)) {
            return null;
        }

        $criteria = new CDbCriteria();
        $criteria->compare('code_uid', $this->code);
        $criteria->addNotInCondition('used', [CampaignShareCode::TEXT_YES]);

        return $this->_campaign_share_code = CampaignShareCode::model()->find($criteria);
    }

    /**
     * @return Lists|null
     */
    public function getList(): ?Lists
    {
        if ($this->_list !== null) {
            return $this->_list;
        }

        if (empty($this->list_id) || empty($this->customer_id)) {
            return null;
        }

        $criteria = new CDbCriteria();
        $criteria->compare('list_id', (int)$this->list_id);
        $criteria->compare('customer_id', (int)$this->customer_id);
        $criteria->addNotInCondition('status', [Lists::STATUS_PENDING_DELETE, Lists::STATUS_ARCHIVED]);

        return $this->_list = Lists::model()->find($criteria);
    }
}
