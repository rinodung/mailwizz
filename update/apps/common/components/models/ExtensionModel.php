<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * ExtensionModel
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.0.0
 */

abstract class ExtensionModel extends OptionAttributes
{
    /**
     * Use the needed traits
     */
    use AddShortcutMethodsFromCurrentExtensionTrait;

    /**
     * @param string $key
     * @param mixed $defaultValue
     *
     * @return mixed
     */
    public function getOption(string $key, $defaultValue = null)
    {
        $prefix = '';
        if ($this->getCategoryName()) {
            $prefix = rtrim($this->getCategoryName(), '.') . '.';
        }

        return $this->getExtension()->getOption($prefix . $key, $defaultValue);
    }

    /**
     * @param string $key
     * @param mixed $value
     *
     * @return void
     */
    public function setOption(string $key, $value)
    {
        $prefix = '';
        if ($this->getCategoryName()) {
            $prefix = rtrim($this->getCategoryName(), '.') . '.';
        }

        $this->getExtension()->setOption($prefix . $key, $value);
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function removeOption(string $key): bool
    {
        $prefix = '';
        if ($this->getCategoryName()) {
            $prefix = rtrim($this->getCategoryName(), '.') . '.';
        }

        return $this->getExtension()->removeOption($prefix . $key);
    }
}
