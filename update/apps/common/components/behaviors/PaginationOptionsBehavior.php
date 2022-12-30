<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * PaginationOptionsBehavior
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.4
 */

/**
 * @property ActiveRecord $owner
 */
class PaginationOptionsBehavior extends CBehavior
{
    /**
     * @var string
     */
    public $pageSizeVar = 'page_size';

    /**
     * @return int
     * @throws CException
     */
    public function getPageSize(): int
    {
        $pageSize = $this->getPageSizeFromOptions();
        if (is_cli()) {
            return $pageSize;
        }

        $lookIntoSession = !in_array(apps()->getCurrentAppName(), ['api']);
        if ($lookIntoSession && app()->hasComponent('session') && session()->contains($this->pageSizeVar)) {
            $pageSize = (int)session()->itemAt($this->pageSizeVar);
        }

        if (request()->getQuery($this->pageSizeVar)) {
            $pageSize = (int)request()->getQuery($this->pageSizeVar, $pageSize);
            if ($lookIntoSession && app()->hasComponent('session')) {
                session()->add($this->pageSizeVar, $pageSize);
            }
        }

        if (!in_array($pageSize, array_keys($this->getOptionsList()))) {
            $pageSize = 10;
        }

        return $pageSize;
    }

    /**
     * @return int
     */
    public function getPageSizeFromOptions(): int
    {
        $defaultPageSize = 10;

        /** @var OptionCommon $common */
        $common = container()->get(OptionCommon::class);
        if (apps()->isAppName('backend')) {
            $defaultPageSize = $common->getBackendPageSize();
        } elseif (apps()->isAppName('customer')) {
            $defaultPageSize = $common->getCustomerPageSize();
        }

        return $defaultPageSize;
    }

    /**
     * @return array
     */
    public function getOptionsList(): array
    {
        return [
            10    => 10,
            20    => 20,
            30    => 30,
            40    => 40,
            50    => 50,
            60    => 60,
            70    => 70,
            80    => 80,
            90    => 90,
            100   => 100,
            500   => 500,
            1000  => 1000,
        ];
    }

    /**
     * @param array $htmlOptions
     *
     * @return string
     * @throws CException
     */
    public function getGridFooterPagination(array $htmlOptions = []): string
    {
        return CHtml::dropDownList($this->pageSizeVar, $this->getPageSize(), $this->getOptionsList(), array_merge([
            'onchange' => "$.fn.yiiGridView.update('" . $this->owner->getModelName() . "-grid',{ data:{" . $this->pageSizeVar . ': $(this).val() }})',
        ], $htmlOptions));
    }
}
