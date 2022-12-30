<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * GridViewDropDownLinksSelector
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.6.6
 */

class GridViewDropDownLinksSelector extends CWidget
{
    /**
     * @var string
     */
    public $heading = '';

    /**
     * @var array
     */
    public $links = [];

    /**
     * @return void
     * @throws CException
     */
    public function run()
    {
        if (!in_array(apps()->getCurrentAppName(), ['customer', 'backend'])) {
            return;
        }

        $this->render('grid-view-drop-down-links-selector', [
            'heading'   => $this->heading,
            'links'     => $this->links,
        ]);
    }
}
