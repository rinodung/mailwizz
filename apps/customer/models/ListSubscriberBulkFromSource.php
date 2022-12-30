<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * ListSubscriberBulkFromSource
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.4.2
 */

class ListSubscriberBulkFromSource extends ListSubscriber
{
    /**
     * @var CUploadedFile|null
     */
    public $bulk_from_file;

    /**
     * @var string
     */
    public $bulk_from_text;

    /**
     * @inheritDoc
     */
    public function rules()
    {
        /** @var OptionImporter $optionImporter */
        $optionImporter = container()->get(OptionImporter::class);

        $mimes = null;
        if ($optionImporter->getCanCheckMimeType()) {

            /** @var FileExtensionMimes $extensionMimes */
            $extensionMimes = app()->getComponent('extensionMimes');

            /** @var array $mimes */
            $mimes = $extensionMimes->get('csv')->toArray();
        }

        return [
            ['status', 'in', 'range' => array_keys($this->getBulkActionsList())],
            ['bulk_from_file, bulk_from_text', 'safe'],
            ['bulk_from_file', 'file', 'types' => ['csv'], 'mimeTypes' => $mimes, 'maxSize' => 5242880, 'allowEmpty' => true],
        ];
    }

    /**
     * @inheritDoc
     */
    public function attributeLabels()
    {
        $labels = [
            'bulk_from_file'   => t('list_subscribers', 'From file'),
            'bulk_from_text'   => t('list_subscribers', 'From text'),
            'status'           => t('list_subscribers', 'Action'),
        ];

        return CMap::mergeArray(parent::attributeLabels(), $labels);
    }

    /**
     * @inheritDoc
     */
    public function attributeHelpTexts()
    {
        $texts = [
            'bulk_from_file'   => t('list_subscribers', 'Bulk action from CSV file, one email address per row and/or separated by a comma.'),
            'bulk_from_text'   => t('list_subscribers', 'Bulk action from text area, one email address per line and/or separated by a comma.'),
        ];

        return CMap::mergeArray($texts, parent::attributeHelpTexts());
    }

    /**
     * @inheritDoc
     */
    public function attributePlaceholders()
    {
        $placeholders = [
            'bulk_from_file'   => '',
            'bulk_from_text'   => '',
        ];

        return CMap::mergeArray($placeholders, parent::attributePlaceholders());
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return ListSubscriberBulkFromSource the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var ListSubscriberBulkFromSource $model */
        $model = parent::model($className);

        return $model;
    }
}
