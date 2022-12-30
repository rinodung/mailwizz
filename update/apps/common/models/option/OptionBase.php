<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * OptionBase
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.3.1
 */

class OptionBase extends OptionAttributes
{
    /**
     * Load the model traits
     */
    use FileSizeTrait;

    /**
     * @var string
     */
    protected $_categoryName = '';

    /**
     * @return string
     */
    public function getCategoryName(): string
    {
        return $this->_categoryName;
    }

    /**
     * @return string
     */
    public function getTranslationCategory(): string
    {
        return 'settings';
    }
}
