<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * PaymentHandlerAbstract
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.4.4
 */

abstract class PaymentHandlerAbstract extends CApplicationComponent
{
    /**
     * @var ExtensionInit
     */
    public $extension;

    /**
     * @var Controller
     */
    public $controller;

    /**
     * Render the payment view
     *
     * @return void
     */
    abstract public function renderPaymentView();

    /**
     * Process the order
     *
     * @return void
     */
    abstract public function processOrder();
}
