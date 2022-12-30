<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * SubscriberModalProfileInfoWidget
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.9.8
 */

class SubscriberModalProfileInfoWidget extends CWidget
{
    /**
     * @return void
     */
    public function init()
    {
        parent::init();
        clientScript()->registerScriptFile(AssetsUrl::js('subscriber-modal-profile-info.js'));
    }

    /**
     * @return void
     * @throws CException
     */
    public function run()
    {
        $this->render('subscriber-modal-profile-info');
    }
}
