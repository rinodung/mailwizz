<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * ReverseProxyCheckerWidget
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.1.10
 */

class ReverseProxyCheckerWidget extends CWidget
{
    /**
     * @var bool
     */
    public $enabled = true;

    /**
     * @return void
     */
    public function init()
    {
        parent::init();

        if (!$this->enabled) {
            return;
        }
        clientScript()->registerScriptFile(AssetsUrl::js('reverse-proxy-checker-widget.js'));
    }

    /**
     * @return void
     * @throws CException
     */
    public function run()
    {
        if (!$this->enabled) {
            return;
        }
        $this->render('reverse-proxy-checker', []);
    }
}
