<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * AddShortcutMethodsFromCurrentExtensionTrait
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.0.0
 */

trait AddShortcutMethodsFromCurrentExtensionTrait
{
    /**
     * Use the needed traits
     */
    use GetExtensionInstanceFromCurrentClassInstanceTrait;

    /**
     * @var ExtensionInit
     */
    private $_extension;

    /**
     * @return ExtensionInit
     */
    public function getExtension(): ExtensionInit
    {
        if ($this->_extension !== null) {
            return $this->_extension;
        }

        try {
            $this->_extension = $this->getExtensionInstanceFromCurrentClassInstance();
        } catch (Exception $e) {
        }

        return $this->_extension;
    }

    /**
     * @param string $message
     * @param array $params
     *
     * @return string
     */
    public function t(string $message, $params = []): string
    {
        return $this->getExtension()->t($message, $params);
    }
}
