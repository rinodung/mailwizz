<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * OptionEmailTemplate
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

class OptionEmailTemplate extends OptionBase
{
    /**
     * @var string
     */
    public $common;

    /**
     * @var string
     */
    protected $_categoryName = 'system.email_templates';

    /**
     * @return array
     * @throws CException
     */
    public function rules()
    {
        $rules = [
            ['common', 'required', 'on' => 'common'],
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
            'common' => $this->t('Common template'),
        ];

        return CMap::mergeArray($labels, parent::attributeLabels());
    }

    /**
     * @return array
     * @throws CException
     */
    public function attributePlaceholders()
    {
        $placeholders = [
            'common' => null,
        ];

        return CMap::mergeArray($placeholders, parent::attributePlaceholders());
    }

    /**
     * @return array
     * @throws CException
     */
    public function attributeHelpTexts()
    {
        $texts = [
            'common' => $this->t('The "common" template is used when sending notifications, password reset emails, etc.'),
        ];

        return CMap::mergeArray($texts, parent::attributeHelpTexts());
    }

    /**
     * @param string $id
     *
     * @return array
     */
    public static function getTypeById(string $id): array
    {
        $types = self::getTypesList();
        foreach ($types as $type) {
            if ($type['id'] === $id) {
                return $type;
            }
        }
        return $types[0];
    }

    /**
     * @return array
     */
    public static function getTypesList(): array
    {
        return [
            ['id' => 'common', 'name' => 'Common layout'],
        ];
    }

    /**
     * @return bool
     */
    protected function beforeValidate()
    {
        if ($this->getScenario() == 'common' && strpos($this->common, '[CONTENT]') === false) {
            $this->addError('common', $this->t('The "[CONTENT]" tag is required but it has not been found in the content.'));
        }
        return parent::beforeValidate();
    }
}
