<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * OptionExporter
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

class OptionExporter extends OptionBase
{
    /**
     * @var string
     */
    public $enabled = self::TEXT_YES;

    /**
     * @var int
     */
    public $process_at_once = 500;

    /**
     * @var int
     */
    public $pause = 1; // pause between the batches

    /**
     * @var string
     */
    protected $_categoryName = 'system.exporter';

    /**
     * @return array
     * @throws CException
     */
    public function rules()
    {
        $rules = [
            ['enabled, process_at_once, pause', 'required'],
            ['enabled', 'in', 'range' => array_keys($this->getYesNoOptions())],
            ['process_at_once, pause', 'numerical', 'integerOnly' => true],
            ['process_at_once', 'numerical', 'min' => 5, 'max' => 10000],
            ['pause', 'numerical', 'min' => 0, 'max' => 60],
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
            'enabled'           => $this->t('Enabled'),
            'process_at_once'   => $this->t('Process at once'),
            'pause'             => $this->t('Pause'),
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
            'enabled'           => null,
            'process_at_once'   => null,
            'pause'             => null,
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
            'enabled'           => $this->t('Whether customers are allowed to export subscribers.'),
            'process_at_once'   => $this->t('How many subscribers to process at once for each batch.'),
            'pause'             => $this->t('How many seconds the script should "sleep" after each batch of subscribers.'),
        ];

        return CMap::mergeArray($texts, parent::attributeHelpTexts());
    }

    /**
     * @return bool
     */
    public function getIsEnabled(): bool
    {
        return $this->enabled === self::TEXT_YES;
    }

    /**
     * @return int
     */
    public function getProcessAtOnce(): int
    {
        return (int)$this->process_at_once;
    }

    /**
     * @return int
     */
    public function getPause(): int
    {
        return (int)$this->pause;
    }
}
