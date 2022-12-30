<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * CampaignQuickViewWidget
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.9.17
 */

class CampaignQuickViewWidget extends CWidget
{
    /**
     * @return void
     */
    public function init()
    {
        parent::init();
        clientScript()->registerScriptFile(apps()->getBaseUrl('assets/js/campaign-quick-view.js'));
    }

    /**
     * @return void
     * @throws CException
     */
    public function run()
    {
        $this->render('campaign-quick-view');
    }
}
