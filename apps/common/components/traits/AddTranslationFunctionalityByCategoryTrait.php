<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * AddTranslationFunctionalityByCategoryTrait
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.0.0
 */

trait AddTranslationFunctionalityByCategoryTrait
{
    /**
     * @return string
     */
    public function getTranslationCategory(): string
    {
        return '';
    }

    /**
     * @param string $message
     * @param array $params
     *
     * @return string
     */
    public function t(string $message, $params = []): string
    {
        if (!$this->getTranslationCategory()) {
            return t('app', $message, $params);
        }
        return t($this->getTranslationCategory(), $message, $params);
    }
}
