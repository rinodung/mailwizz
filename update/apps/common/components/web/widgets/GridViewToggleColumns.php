<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * GridViewToggleColumns
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.8.8
 */

class GridViewToggleColumns extends CWidget
{
    /**
     * @var ActiveRecord
     */
    public $model;

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
        if (!in_array(apps()->getCurrentAppName(), ['customer', 'backend'])) {
            return;
        }
        hooks()->addFilter('grid_view_columns', [$this, '_handleGridViewColumns'], -1000);

        // since 2.0.0
        $dbColumns = (array)options()->get($this->getOptionKey(), []);
        if (empty($dbColumns)) {
            // backend_country_countries_index_default_hide_grid_view_columns
            $filterName = sprintf(
                '%s_%s_%s_%s_default_hide_grid_view_columns',
                apps()->getCurrentAppName(),
                strtolower($this->model->getModelName()),
                $this->getController()->getId(),
                $this->getController()->getAction()->getId()
            );
            $dbColumns = array_diff($this->columns, (array)hooks()->applyFilters($filterName, []));
        }
        //

        $this->render('grid-view-toggle-columns', [
            'model'      => $this->model,
            'modelName'  => $this->model->getModelName(),
            'controller' => $this->getController()->getId(),
            'action'     => $this->getController()->getAction()->getId(),
            'columns'    => $this->columns,
            'dbColumns'  => $dbColumns,
        ]);
    }

    /**
     * @param array $columns
     * @param Controller $controller
     *
     * @return array
     */
    public function _handleGridViewColumns(array $columns, $controller)
    {
        $optionKey = $this->getOptionKey();
        $dbColumns = (array)options()->get($optionKey, []);

        // since 2.0.0
        if (empty($dbColumns)) {
            // backend_country_countries_index_default_hide_grid_view_columns
            $filterName = sprintf(
                '%s_%s_%s_%s_default_hide_grid_view_columns',
                apps()->getCurrentAppName(),
                strtolower($this->model->getModelName()),
                $this->getController()->getId(),
                $this->getController()->getAction()->getId()
            );
            $dbColumns = array_diff($this->columns, (array)hooks()->applyFilters($filterName, []));
        }
        //

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
        $optionKey = sprintf('%s:%s:%s', $this->model->getModelName(), $this->getController()->getId(), $this->getController()->getAction()->getId());

        if (apps()->isAppName('backend')) {
            $userId    = (int)user()->getId();
            $optionKey = sprintf('system.views.grid_view_columns.users.%d.%s', $userId, $optionKey);
        } else {
            $customerId = (int)customer()->getId();
            if (is_subaccount()) {
                /** @var Customer $customer */
                $customer = subaccount()->customer();
                $customerId = (int)$customer->customer_id;
            }
            $optionKey  = sprintf('system.views.grid_view_columns.customers.%d.%s', $customerId, $optionKey);
        }

        return $optionKey;
    }
}
