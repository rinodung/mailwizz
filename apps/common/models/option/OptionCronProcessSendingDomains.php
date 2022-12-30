<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * OptionCronProcessSendingDomains
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.0.30
 */

class OptionCronProcessSendingDomains extends OptionBase
{
    /**
     * @var string
     */
    public $hourly_checks = self::TEXT_NO;

    /**
     * @var string
     */
    protected $_categoryName = 'system.cron.sending_domains';

    /**
     * @return array
     * @throws CException
     */
    public function rules()
    {
        $rules = [
            ['hourly_checks', 'required'],
            ['hourly_checks', 'in', 'range' => array_keys($this->getYesNoOptions())],
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
            'hourly_checks' => $this->t('Hourly checks'),
        ];

        return CMap::mergeArray($labels, parent::attributeLabels());
    }

    /**
     * @return array
     * @throws CException
     */
    public function attributeHelpTexts()
    {
        $texts = [
            'hourly_checks' => $this->t('Whether to enable hourly checks against sending domains and their DNS records. If the checks fail for certain sending domains, notifications will be emitted and the sending domains will require to be manually verified once again. Use with extra care.'),
        ];

        return CMap::mergeArray($texts, parent::attributeHelpTexts());
    }

    /**
     * @return bool
     */
    public function getHourlyChecksEnabled(): bool
    {
        return $this->hourly_checks === self::TEXT_YES;
    }
}
