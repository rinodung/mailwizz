<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * OptionCronProcessResponders
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.7.8
 */

class OptionCronProcessResponders extends OptionBase
{
    /**
     * @var string
     */
    public $sync_custom_fields_values = self::TEXT_NO;

    /**
     * @var string
     */
    protected $_categoryName = 'system.cron.process_responders';

    /**
     * @return array
     * @throws CException
     */
    public function rules()
    {
        $rules = [
            ['sync_custom_fields_values', 'in', 'range' => array_keys($this->getYesNoOptions())],
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
            'sync_custom_fields_values' => $this->t('Custom fields sync'),
        ];

        return CMap::mergeArray($labels, parent::attributeLabels());
    }

    /**
     * @return array
     * @throws CException
     */
    public function attributePlaceholders()
    {
        $placeholders = [];

        return CMap::mergeArray($placeholders, parent::attributePlaceholders());
    }

    /**
     * @return array
     * @throws CException
     */
    public function attributeHelpTexts()
    {
        $texts = [
            'sync_custom_fields_values' => $this->t('Enable this if you need to populate all the custom fields with their default values if they are freshly created in a survey and they have no value'),
        ];

        return CMap::mergeArray($texts, parent::attributeHelpTexts());
    }

    /**
     * @return bool
     */
    public function getSyncCustomFieldsValues(): bool
    {
        return $this->sync_custom_fields_values === self::TEXT_YES;
    }
}
