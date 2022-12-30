<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * ClientScript
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.1.11
 */

class ClientScript extends CClientScript
{
    /**
     * @return void
     */
    public function init()
    {
        parent::init();

        $this->corePackages = require MW_ROOT_PATH . '/apps/common/components/web/js/packages.php';
    }
}
