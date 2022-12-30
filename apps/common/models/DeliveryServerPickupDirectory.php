<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * DeliveryServerPickupDirectory
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.2
 */

class DeliveryServerPickupDirectory extends DeliveryServer
{

    /**
     * @var string
     */
    public $pickup_directory_path = '';
    /**
     * @var string
     */
    protected $serverType = 'pickup-directory';

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            ['pickup_directory_path', 'required'],
            ['pickup_directory_path', '_validateDirectoryPath'],
        ];

        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'pickup_directory_path' => t('servers', 'Pickup directory path'),
        ];

        return CMap::mergeArray(parent::attributeLabels(), $labels);
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return DeliveryServerPickupDirectory the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var DeliveryServerPickupDirectory $model */
        $model = parent::model($className);

        return $model;
    }

    /**
     * @return array
     * @throws CException
     */
    public function sendEmail(array $params = []): array
    {
        $dirPath = $this->pickup_directory_path;

        static $canWrite;
        if ($canWrite === null) {
            $canWrite = (!empty($dirPath) && file_exists($dirPath) && is_dir($dirPath) && is_writable($dirPath));
        }

        if (!$canWrite) {
            return [];
        }

        /** @var array $params */
        $params = (array)hooks()->applyFilters('delivery_server_before_send_email', $this->getParamsArray($params), $this);

        $dirPath  = rtrim((string)$dirPath, '/\\');
        $message  = $this->getMailer()->getEmailMessage($params);
        $filePath = $dirPath . DIRECTORY_SEPARATOR . $this->getMailer()->getEmailMessageId() . '.eml';

        if ($sent = file_put_contents($filePath, $message)) {
            $sent = ['message_id' => $this->getMailer()->getEmailMessageId()];
            $this->getMailer()->addLog('OK');
            $this->logUsage();
        } else {
            $sent = [];
        }

        hooks()->doAction('delivery_server_after_send_email', $params, $this, $sent);

        return (array)$sent;
    }

    /**
     * @inheritDoc
     */
    public function getParamsArray(array $params = []): array
    {
        $params['transport']             = self::TRANSPORT_PICKUP_DIRECTORY;
        $params['pickup_directory_path'] = $this->pickup_directory_path;
        return parent::getParamsArray($params);
    }

    /**
     * @return array
     */
    public function attributeHelpTexts()
    {
        $texts = [
            'pickup_directory_path'    => t('servers', 'The path where the messages must be saved in order to be picked up by your MTA'),
        ];

        return CMap::mergeArray(parent::attributeHelpTexts(), $texts);
    }

    /**
     * @return array
     */
    public function attributePlaceholders()
    {
        $placeholders = [
            'pickup_directory_path' => t('servers', 'i.e: /home/username/pickup'),
        ];

        return CMap::mergeArray(parent::attributePlaceholders(), $placeholders);
    }

    /**
     * @param string $attribute
     * @param array $params
     */
    public function _validateDirectoryPath(string $attribute, array $params = []): void
    {
        if (empty($this->$attribute)) {
            $this->addError($attribute, t('servers', 'The attribute "{attribute}" cannot be blank!', [
                '{attribute}' => $this->getAttributeLabel($attribute),
            ]));
            return;
        }

        $directory = @realpath($this->$attribute);
        if (empty($directory) || !file_exists($directory) || !is_dir($directory) || !is_writable($directory)) {
            $this->addError($attribute, t('servers', 'The directory "{dir}" must exist and be writable by the web server process!', [
                '{dir}' => $this->$attribute,
            ]));
            return;
        }

        $this->$attribute = $directory;
    }

    /**
     * @inheritDoc
     */
    public function getCanEmbedImages(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function getFormFieldsDefinition(array $fields = []): array
    {
        $form = new CActiveForm();
        return parent::getFormFieldsDefinition(CMap::mergeArray([
            'hostname'                => null,
            'username'                => null,
            'password'                => null,
            'port'                    => null,
            'protocol'                => null,
            'timeout'                 => null,
            'max_connection_messages' => null,
            'pickup_directory_path'   => [
                'visible'   => true,
                'fieldHtml' => $form->textField($this, 'pickup_directory_path', $this->fieldDecorator->getHtmlOptions('pickup_directory_path')),
            ],
        ], $fields));
    }

    /**
     * @return void
     */
    protected function afterConstruct()
    {
        $this->pickup_directory_path = $this->modelMetaData->getModelMetaData()->itemAt('pickup_directory_path');
        parent::afterConstruct();
    }

    /**
     * @return void
     */
    protected function afterFind()
    {
        $this->pickup_directory_path = $this->modelMetaData->getModelMetaData()->itemAt('pickup_directory_path');
        parent::afterFind();
    }

    /**
     * @return bool
     */
    protected function beforeValidate()
    {
        $this->hostname = 'pickup-directory.local.host';
        $this->port     = null;
        $this->timeout  = null;

        return parent::beforeValidate();
    }

    /**
     * @return bool
     */
    protected function beforeSave()
    {
        $this->modelMetaData->getModelMetaData()->add('pickup_directory_path', $this->pickup_directory_path);
        return parent::beforeSave();
    }
}
