<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * GridViewToggleSubscriberColumns
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.8.8
 */

class GridViewToggleSubscriberColumns extends CWidget
{
    /**
     * @var ListSubscriber
     */
    public $model;

    /**
     * @var Lists
     */
    public $list;

    /**
     * @var array
     */
    public $columns = [];

    /**
     * @var array
     */
    public $saveRoute = ['account/save_grid_view_columns'];

    /**
     * @return void
     */
    public function init()
    {
        parent::init();
        clientScript()->registerScriptFile(apps()->getBaseUrl('assets/js/grid-view-toggle-columns.js'));
    }

    /**
     * @return void
     * @throws CException
     */
    public function run()
    {
        if (!in_array(apps()->getCurrentAppName(), ['customer'])) {
            return;
        }
        hooks()->addFilter('grid_view_columns', [$this, '_handleGridViewColumns'], -1000);

        $dbColumns = [];
        foreach ($this->columns as $column) {
            $dbColumns[] = $column['field_id'];
        }

        /** @var Lists $list */
        $list = $this->list;

        $this->render('grid-view-toggle-subscriber-columns', [
            'model'      => $this->model,
            'modelName'  => $this->model->getModelName() . '_list_' . $list->list_id,
            'controller' => $this->getController()->getId(),
            'action'     => $this->getController()->getAction()->getId(),
            'columns'    => $this->columns,
            'dbColumns'  => (array)options()->get($this->getOptionKey(), $dbColumns),
        ]);
    }

    /**
     * @param array $columns
     * @param Controller $controller
     *
     * @return array
     */
    public function _handleGridViewColumns(array $columns, Controller $controller)
    {
        $optionKey = $this->getOptionKey();
        $dbColumns = (array)options()->get($optionKey, []);

        // nothing to do, show all columns
        if (empty($dbColumns)) {
            return $columns;
        }

        $saveColumns = false;

        foreach ($dbColumns as $index => $column) {
            if (!in_array($column, $this->columns)) {
                unset($dbColumns[$index]);
                $saveColumns = true;
            }
        }

        if ($saveColumns) {
            options()->set($optionKey, $dbColumns);
        }

        foreach ($columns as $index => $column) {
            if (isset($column['class']) || !isset($column['name'])) {
                continue;
            }
            if (!in_array($column['name'], $dbColumns)) {
                unset($columns[$index]);
            }
        }

        return $columns;
    }

    /**
     * @return string
     */
    public function getOptionKey()
    {
        $modelName  = $this->model->getModelName() . '_list_' . $this->list->list_id;
        $customerId = (int)customer()->getId();
        $optionKey  = sprintf('%s:%s:%s', $modelName, $this->getController()->getId(), $this->getController()->getAction()->getId());
        return sprintf('system.views.grid_view_columns.customers.%d.%s', $customerId, $optionKey);
    }
}
