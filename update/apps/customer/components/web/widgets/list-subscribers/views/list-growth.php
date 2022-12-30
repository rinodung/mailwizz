<?php declare(strict_types=1);
defined('MW_PATH') or exit('No direct script access allowed');

/**
 * This file is part of the MailWizz EMA application.
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.0.34
 */

/** @var Lists $list */
/** @var array $dateRanges */

?>

<div class="box box-primary borderless list-growth-wrapper">
    <div id="list-growth-wrapper-content">
        <div class="box-header">
            <div class="pull-left">
                <h3 class="box-title">
				    <?php echo IconHelper::make('fa-clock-o') . t('lists', 'List growth'); ?>
                </h3>
            </div>
            <div class="pull-right">
			    <?php BoxHeaderContent::make(BoxHeaderContent::RIGHT)
			        ->add(CHtml::link(IconHelper::make('export') . t('app', 'Export'), ['lists/list_growth_export', 'list_uid' => $list->list_uid], ['target' => '_blank', 'class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Export')]))
			        ->render(); ?>
            </div>
            <div class="clearfix"><!-- --></div>
        </div>
        <div class="box-body">
            <div class="row">
                <div class="col-lg-12">
                    <div class="pull-right">
					    <?php echo CHtml::dropDownList('list_growth_ranges', '', $dateRanges, [
                            'data-url' => createUrl('lists/list_growth', ['list_uid' => $list->list_uid]),
                        ]); ?>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-12">
                    <div style="width: 100%; height: 400px">
                        <canvas id="list-growth-chart" style="position: relative; height:40vh; width:80vw"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div id="list-growth-wrapper-loader" style="display: none">
        <div class="ph-item">
            <div class="ph-col-12">
                <div class="ph-row">
                    <div class="ph-col-2 big"></div>
                    <div class="ph-col-10 empty big"></div>
                </div>
            </div>
            <div class="ph-col-12">
                <div class="ph-picture"></div>
            </div>
        </div>
    </div>
</div>
