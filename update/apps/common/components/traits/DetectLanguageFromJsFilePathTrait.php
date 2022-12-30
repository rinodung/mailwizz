<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * DetectLanguageFromJsFilePathTrait
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.0.0
 */

trait DetectLanguageFromJsFilePathTrait
{
    /**
     * @param array $languagesPaths
     *
     * @return string
     */
    public function detectLanguage(array $languagesPaths = []): string
    {
        $detectedLang = '';

        $language = (string)str_replace('_', '-', app()->getLanguage());
        if (strpos($language, '-') !== false) {
            $language = explode('-', $language);
            $locale   = array_pop($language);
            $language = implode('-', $language) . '-' . strtoupper((string)$locale);
        }

        foreach ($languagesPaths as $languagesPath) {
            if (is_file($languagesPath . '.' . $language . '.js')) {
                $detectedLang = $language;
                break;
            }
        }

        if ($detectedLang === '' && strpos($language, '-') !== false) {
            $language = explode('-', $language);
            $language = $language[0];

            foreach ($languagesPaths as $languagesPath) {
                if (is_file($languagesPath . '.' . $language . '.js')) {
                    $detectedLang = $language;
                    break;
                }
            }
        }

        return $detectedLang;
    }
}
