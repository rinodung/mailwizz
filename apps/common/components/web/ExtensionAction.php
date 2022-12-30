<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * ExtensionAction
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.0.0
 */

class ExtensionAction extends CAction
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
}
