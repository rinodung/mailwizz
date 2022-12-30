<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * DeliveryServerSmtpInboxroad
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.9.15
 */

class DeliveryServerSmtpInboxroad extends DeliveryServerSmtp
{

    /**
     * @var string
     */
    public $inboxroad_return_path = '';
    /**
     * @var string
     */
    protected $serverType = 'smtp-inboxroad';

    /**
     * @var string
     */
    protected $_providerUrl = 'https://www.inboxroad.com/';

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            ['inboxroad_return_path', 'required'],
            ['inboxroad_return_path', 'email', 'validateIDN' => true],
        ];

        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return DeliveryServerSmtpInboxroad the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var DeliveryServerSmtpInboxroad $model */
        $model = parent::model($className);

        return $model;
    }

    /**
     * @inheritDoc
     */
    public function getParamsArray(array $params = []): array
    {
        $params = parent::getParamsArray($params);
        $params['transport'] = self::TRANSPORT_SMTP;

        if (!empty($this->inboxroad_return_path)) {
            $params['returnPath'] = (string)$this->inboxroad_return_path;
        }

        return $params;
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'inboxroad_return_path'  => t('servers', 'Return Path'),
        ];

        return CMap::mergeArray(parent::attributeLabels(), $labels);
    }

    /**
     * @inheritDoc
     */
    public function getDswhUrl(): string
    {
        /** @var OptionUrl $optionUrl */
        $optionUrl = container()->get(OptionUrl::class);

        $url = $optionUrl->getFrontendUrl('dswh/inboxroad');
        if (is_cli()) {
            return $url;
        }
        if (request()->getIsSecureConnection() && parse_url($url, PHP_URL_SCHEME) == 'http') {
            $url = substr_replace($url, 'https', 0, 4);
        }
        return $url;
    }

    /**
     * @inheritDoc
     */
    public function getFormFieldsDefinition(array $fields = []): array
    {
        $form = new CActiveForm();
        return parent::getFormFieldsDefinition(CMap::mergeArray([
            'bounce_server_id'      => null,
            'inboxroad_return_path' => [
                'visible'   => true,
                'fieldHtml' => $form->textField($this, 'inboxroad_return_path', $this->fieldDecorator->getHtmlOptions('inboxroad_return_path')),
            ],
        ], $fields));
    }

    /**
     * @return void
     */
    protected function afterConstruct()
    {
        parent::afterConstruct();
        $this->inboxroad_return_path = $this->modelMetaData->getModelMetaData()->itemAt('inboxroad_return_path');
    }

    /**
     * @return void
     */
    protected function afterFind()
    {
        $this->inboxroad_return_path = $this->modelMetaData->getModelMetaData()->itemAt('inboxroad_return_path');
        parent::afterFind();
    }

    /**
     * @return bool
     */
    protected function beforeSave()
    {
        $this->modelMetaData->getModelMetaData()->add('inboxroad_return_path', $this->inboxroad_return_path);
        return parent::beforeSave();
    }
}
