<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * OptionEmailBlacklist
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.2
 */

class OptionEmailBlacklist extends OptionBase
{
    /**
     * @var string
     */
    public $local_check = self::TEXT_YES;

    /**
     * @var string
     */
    public $allow_new_records = self::TEXT_YES;

    /**
     * @var string
     */
    public $reconfirm_blacklisted_subscribers_on_blacklist_delete = self::TEXT_YES;

    /**
     * @var string
     */
    public $regular_expressions;

    /**
     * @var string
     */
    public $allow_md5 = self::TEXT_NO;

    /**
     * @var string
     */
    public $regex_test_email = '';

    /**
     * @var string
     */
    protected $_categoryName = 'system.email_blacklist';

    /**
     * @return array
     * @throws CException
     */
    public function rules()
    {
        $rules = [
            ['local_check, allow_new_records, allow_md5', 'required'],
            ['local_check, allow_new_records, allow_md5', 'in', 'range' => array_keys($this->getYesNoOptions())],
            ['regular_expressions', 'safe'],

            ['reconfirm_blacklisted_subscribers_on_blacklist_delete', 'required'],
            ['reconfirm_blacklisted_subscribers_on_blacklist_delete', 'in', 'range' => array_keys($this->getYesNoOptions())],
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
            'local_check'         => $this->t('Local checks'),
            'allow_new_records'   => $this->t('Allow adding new records'),
            'regular_expressions' => $this->t('Regular expressions'),
            'allow_md5'           => $this->t('Allow md5 emails'),
            'regex_test_email'    => $this->t('Regex email address test'),

            'reconfirm_blacklisted_subscribers_on_blacklist_delete' => $this->t('Reconfirm subscribers on delete'),
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
            'local_check'         => '',
            'regular_expressions' => "/abuse@(.*)/i\n/spam@(.*)/i\n/(.*)@abc\.com/i",
            'regex_test_email'    => $this->t('Enter here one or more email addresses, separated by a comma, to test above regular expressions'),
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
            'local_check'         => $this->t('Whether to check the email addresses against local database.'),
            'allow_new_records'   => $this->t('Whether to allow adding new records to the email blacklist'),
            'regular_expressions' => $this->t('List of regular expressions for blacklisting an email. Please use one expression per line and make sure it is correct.'),
            'allow_md5'           => $this->t('Whether to allow md5 email addresses in the blacklists'),
            'reconfirm_blacklisted_subscribers_on_blacklist_delete' => $this->t('Deleting email addresses from the global blacklist will reconfirm those subscribers if their current status is blacklisted.'),
            'regex_test_email'    => $this->t('Enter here one or more email addresses, separated by a comma, to test above regular expressions'),
        ];

        return CMap::mergeArray($texts, parent::attributeHelpTexts());
    }

    /**
     * @return bool
     */
    public function getLocalCheck(): bool
    {
        return $this->local_check === self::TEXT_YES;
    }

    /**
     * @return bool
     */
    public function getAllowNewRecords(): bool
    {
        return $this->allow_new_records === self::TEXT_YES;
    }

    /**
     * @return bool
     */
    public function getReconfirmBlacklistedSubscribersOnBlacklistDelete(): bool
    {
        return $this->reconfirm_blacklisted_subscribers_on_blacklist_delete === self::TEXT_YES;
    }

    /**
     * @return string
     */
    public function getRegularExpressions(): string
    {
        return (string)$this->regular_expressions;
    }

    /**
     * @return array
     */
    public function getRegularExpressionsList(): array
    {
        static $regularExpressions;
        if ($regularExpressions === null) {
            $regularExpressions = explode("\n", $this->getRegularExpressions());
            /** @var array $regularExpressions */
            $regularExpressions = (array)hooks()->applyFilters('email_blacklist_regular_expressions', (array)$regularExpressions);
            $regularExpressions = array_unique(array_map('trim', (array)$regularExpressions));
            foreach ($regularExpressions as $index => $expr) {
                if (empty($expr)) {
                    unset($regularExpressions[$index]);
                }
            }
        }

        return is_array($regularExpressions) ? $regularExpressions : [];
    }

    /**
     * @return bool
     */
    public function getAllowMd5(): bool
    {
        return $this->allow_md5 === self::TEXT_YES;
    }

    /**
     * @return string
     */
    public function getRegexTestEmail(): string
    {
        return (string)$this->regex_test_email;
    }
}
