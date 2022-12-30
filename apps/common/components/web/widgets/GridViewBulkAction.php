<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * GridViewBulkAction
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.5.4
 */

class GridViewBulkAction extends CWidget
{
    /**
     * @var ActiveRecord
     */
    public $model;

    /**
     * @var string
     */
    public $formAction = '';

    /**
     * @return void
     */
    public function init()
    {
        parent::init();
        clientScript()->registerScriptFile(apps()->getBaseUrl('assets/js/grid-view-bulk-action.js'));
    }

    /**
     * @return void
     * @throws CException
     */
    public function run()
    {
        $this->render('grid-view-bulk-action', [
            'model'       => $this->model,
            'bulkActions' => $this->model->getBulkActionsList(),
            'formAction'  => $this->formAction,
        ]);
    }
}
