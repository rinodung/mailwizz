<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * AttributesShortErrorsBehavior
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

/**
 * @property ActiveRecord $owner
 */
class AttributesShortErrorsBehavior extends CBehavior
{
    /**
     * @return array
     */
    public function getAll(): array
    {
        $_errors = [];
        foreach ($this->owner->getErrors() as $attribute => $errors) {
            if (empty($errors)) {
                continue;
            }
            $_errors[$attribute] = is_array($errors) ? reset($errors) : $errors;
        }
        return $_errors;
    }

    /**
     * @param string $separator
     *
     * @return string
     */
    public function getAllAsString(string $separator = '<br />'): string
    {
        $errors = array_values($this->getAll());
        $errors = array_map('html_encode', $errors);

        return implode($separator, $errors);
    }
}
