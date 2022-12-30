<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * CampaignEmailTemplateUpload
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.2
 */

/**
 * This class is a trick to make use of the {@link EmailTemplateUploadBehavior}
 * so that we can parse a uploaded zip file directly from the campaign without writing all the logic again
 *
 * @property EmailTemplateUploadBehavior $uploader
 */
class CampaignEmailTemplateUpload extends CustomerEmailTemplate
{
    /**
     * @var CUploadedFile
     */
    public $archive;

    /**
     * @var Campaign
     */
    public $campaign;

    /**
     * @var string
     */
    public $auto_plain_text = self::TEXT_YES;

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            ['auto_plain_text', 'in', 'range' => array_keys($this->getAutoPlainTextArray())],
        ];

        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return CustomerEmailTemplate the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var CustomerEmailTemplate $model */
        $model = parent::model($className);

        return $model;
    }

    /**
     * @param bool $runValidation
     * @param mixed $attributes
     *
     * @return bool
     */
    public function save($runValidation=true, $attributes=null)
    {
        return true;
    }

    /**
     * @return array
     */
    public function attributeHelpTexts()
    {
        $texts = [
            'auto_plain_text'   => 'Whether the plain text version of the html template should be auto generated.',
        ];

        return CMap::mergeArray($texts, parent::attributeHelpTexts());
    }

    /**
     * @return string
     */
    public function generateUid(): string
    {
        return 'cmp' . $this->campaign->campaign_uid;
    }

    /**
     * @return array
     */
    public function getAutoPlainTextArray(): array
    {
        return [
            self::TEXT_YES  => t('app', 'Yes'),
            self::TEXT_NO   => t('app', 'No'),
        ];
    }

    /**
     * @return bool
     */
    protected function beforeSave()
    {
        return true;
    }
}
