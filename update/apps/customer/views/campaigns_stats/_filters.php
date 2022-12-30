<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * This file is part of the MailWizz EMA application.
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.9.2
 */

/** @var Controller $controller */
$controller = controller();

/** @var CampaignStatsFilter $filter */
$filter = $controller->getData('filter');

/** @var int $customerId */
$customerId = (int)$controller->getData('customerId');

/** @var CActiveForm $form */
$form = $controller->beginWidget('CActiveForm', [
    'id'          => 'filters-form',
    'method'      => 'get',
    'action'      => createUrl($controller->getRoute()),
    'htmlOptions' => [
        'style'        => 'display:' . ($filter->getHasFilters() ? 'block' : 'none'),
    ],
]); ?>
<div class="box box-primary borderless">
    <div class="box-header">
        <div class="pull-left">
            <h3 class="box-title"><span class="glyphicon glyphicon-filter"><!-- --></span> <?php echo t('filters', 'Filters'); ?></h3>
        </div>
        <div class="clearfix"><!-- --></div>
    </div>
    <div class="box-body">

        <div class="row">
            <div class="col-lg-3">
                <div class="form-group">
                    <?php echo $form->labelEx($filter, 'lists'); ?>
                    <?php echo $form->dropDownList($filter, 'lists', CMap::mergeArray(['' => ''], Lists::getListsForCampaignFilterDropdown($customerId)), $filter->fieldDecorator->getHtmlOptions('lists', ['multiple' => true])); ?>
                    <?php echo $form->error($filter, 'lists'); ?>
                </div>
            </div>
            <div class="col-lg-3">
                <div class="form-group">
                    <?php echo $form->labelEx($filter, 'campaigns'); ?>
                    <?php echo $form->dropDownList($filter, 'campaigns', CMap::mergeArray(['' => ''], CampaignStatsFilter::getCampaignsForCampaignFilterDropdown($customerId)), $filter->fieldDecorator->getHtmlOptions('campaigns', ['multiple' => true])); ?>
                    <?php echo $form->error($filter, 'campaigns'); ?>
                </div>
            </div>
            <div class="col-lg-3">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="form-group" style="margin-bottom: 5px">
                            <?php
                            echo $form->labelEx($filter, 'date_start');
                            echo $controller->widget('zii.widgets.jui.CJuiDatePicker', [
                                'model'     => $filter,
                                'attribute' => 'date_start',
                                'cssFile'   => null,
                                'language'  => $filter->getDatePickerLanguage(),
                                'options'   => [
                                    'showAnim'   => 'fold',
                                    'dateFormat' => $filter->getDatePickerFormat(),
                                ],
                                'htmlOptions' => ['class' => ''],
                            ], true);
                            echo $form->error($filter, 'date_start');
                            ?>
                        </div>
                    </div>
                    <div class="col-lg-12">
                        <div class="form-group">
                            <?php
                            echo $form->labelEx($filter, 'date_end');
                            echo $controller->widget('zii.widgets.jui.CJuiDatePicker', [
                                'model'     => $filter,
                                'attribute' => 'date_end',
                                'cssFile'   => null,
                                'language'  => $filter->getDatePickerLanguage(),
                                'options'   => [
                                    'showAnim'   => 'fold',
                                    'dateFormat' => $filter->getDatePickerFormat(),
                                ],
                                'htmlOptions' => ['class' => ''],
                            ], true);
                            echo $form->error($filter, 'date_end');
                            ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3">
                <div class="form-group">
                    <?php echo $form->labelEx($filter, 'action'); ?>
                    <?php echo $form->dropDownList($filter, 'action', CMap::mergeArray(['' => ''], $filter->getFilterActionsList()), $filter->fieldDecorator->getHtmlOptions('action')); ?>
                    <?php echo $form->error($filter, 'action'); ?>
                </div>
            </div>
        </div>

    </div>
    <div class="box-footer">
        <div class="pull-right">
            <?php echo CHtml::submitButton(t('filters', 'Submit'), ['name' => '', 'class' => 'btn btn-primary btn-flat']); ?>
        </div>
        <div class="clearfix"><!-- --></div>
    </div>
</div>
<?php $controller->endWidget(); ?>
