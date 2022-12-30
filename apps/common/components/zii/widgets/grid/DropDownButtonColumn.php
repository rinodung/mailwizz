<?php declare(strict_types=1);
/**
 * CButtonColumn class file.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright 2008-2013 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

Yii::import('zii.widgets.grid.CButtonColumn');

/**
 * CButtonColumn represents a grid view column that renders one or several buttons.
 *
 * By default, it will display three buttons, "view", "update" and "delete", which triggers the corresponding
 * actions on the model of the row.
 *
 * By configuring {@link buttons} and {@link template} properties, the column can display other buttons
 * and customize the display order of the buttons.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package zii.widgets.grid
 * @since 1.1
 */
class DropDownButtonColumn extends CButtonColumn
{
    /**
     * @var int
     */
    protected $visibleButtonsCount = 0;

    /**
     * Initializes the column.
     * This method registers necessary client script for the button column.
     *
     * @return void
     */
    public function init()
    {
        $this->changeTemplateIntoDropdown();
        parent::init();
    }

    /**
     * @param int $row
     * @return string
     */
    public function getDataCellContent($row)
    {
        $this->visibleButtonsCount = 0;
        $content = parent::getDataCellContent($row);

        if (empty($this->visibleButtonsCount)) {
            return '';
        }

        $width      = (($this->visibleButtonsCount * 32) + 10);
        $marginLeft = $width - 10;

        $content = strtr($content, [
            '{dd-width}'        => $width,
            '{dd-margin-left}'  => $marginLeft,
        ]);

        return $content;
    }

    /**
     * Renders a link button.
     * @param string $id the ID of the button
     * @param array $button the button configuration which may contain 'label', 'url', 'imageUrl' and 'options' elements.
     * See {@link buttons} for more details.
     * @param integer $row the row number (zero-based)
     * @param mixed $data the data object associated with the row
     *
     * @return void
     */
    protected function renderButton($id, $button, $row, $data)
    {
        if (isset($button['visible']) && !$this->evaluateExpression($button['visible'], ['row'=>$row, 'data'=>$data])) {
            return;
        }
        $this->visibleButtonsCount++;

        $label=$button['label'] ?? $id;
        $url=isset($button['url']) ? $this->evaluateExpression($button['url'], ['data'=>$data, 'row'=>$row]) : '#';
        $options=$button['options'] ?? [];
        if (!isset($options['title'])) {
            $options['title']=$label;
        }
        if (isset($button['imageUrl']) && is_string($button['imageUrl'])) {
            echo CHtml::tag('li', [], CHtml::link(CHtml::image($button['imageUrl'], $label), $url, $options));
        } else {
            echo CHtml::tag('li', [], CHtml::link($label, $url, $options));
        }
    }

    /**
     * @return void
     */
    protected function changeTemplateIntoDropdown()
    {
        $this->template = '<div class="btn-group dropup dropdown-button-column">
		  <button type="button" class="btn btn-primary btn-flat dropdown-toggle no-spin" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
		    ' . IconHelper::make('fa-cogs') . '
		    <span class="sr-only">' . t('app', 'Toggle Dropdown') . '</span>
		  </button>
		  <ul class="dropdown-menu" style="min-width: {dd-width}px; margin-left: -{dd-margin-left}px">
		    ' . $this->template . '
		  </ul>
		</div>';
    }
}
