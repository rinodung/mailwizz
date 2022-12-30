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
 * @since 1.3.6.3
 */

/** @var Controller $controller */
$controller = controller();

/** @var EmailBlacklistFilters $filter */
$filter = $controller->getData('filter');

/** @var CActiveForm $form */
        $form = $controller->beginWidget('CActiveForm', [
    'id'          => 'filters-form',
    'method'      => 'get',
    'action'      => createUrl($controller->getRoute()),
    'htmlOptions' => [
        'style'        => 'display:' . ($filter->hasSetFilters ? 'block' : 'none'),
        'data-confirm' => t('email_blacklist', 'Are you sure you want to run this action?'),
    ],
]); ?>

<div class="box box-primary borderless">
    <div class="box-header">
        <div class="pull-left">
            <h3 class="box-title"><span class="glyphicon glyphicon-filter"><!-- --></span> <?php echo t('email_blacklist', 'Filters'); ?></h3>
        </div>
        <div class="clearfix"><!-- --></div>
    </div>
    <div class="box-body">
        <div class="row">
            <div class="col-lg-2">
                <div class="form-group">
                    <?php echo $form->labelEx($filter, 'email'); ?>
                    <?php echo $form->textField($filter, 'email', $filter->fieldDecorator->getHtmlOptions('email', ['name' => 'email'])); ?>
                    <?php echo $form->error($filter, 'email'); ?>
                </div>
            </div>
            <div class="col-lg-2">
                <div class="form-group">
                    <?php echo $form->labelEx($filter, 'reason'); ?>
                    <?php echo $form->textField($filter, 'reason', $filter->fieldDecorator->getHtmlOptions('reason', ['name' => 'reason'])); ?>
                    <?php echo $form->error($filter, 'reason'); ?>
                </div>
            </div>
            <div class="col-lg-2">
                <div class="form-group">
                    <?php echo $form->labelEx($filter, 'date_start'); ?>
                    <?php
                    $controller->widget('zii.widgets.jui.CJuiDatePicker', [
                        'model'     => $filter,
                        'attribute' => 'date_start',
                        'language'  => $filter->getDatePickerLanguage(),
                        'cssFile'   => null,
                        'options'   => [
                            'showAnim'      => 'fold',
                            'dateFormat'    => $filter->getDatePickerFormat(),
                        ],
                        'htmlOptions'=>$filter->fieldDecorator->getHtmlOptions('date_start', ['name' => 'date_start']),
                    ]);
                    ?>
                    <?php echo $form->error($filter, 'date_start'); ?>
                </div>
            </div>
            <div class="col-lg-2">
                <div class="form-group">
                    <?php echo $form->labelEx($filter, 'date_end'); ?>
                    <?php
                    $controller->widget('zii.widgets.jui.CJuiDatePicker', [
                        'model'     => $filter,
                        'attribute' => 'date_end',
                        'language'  => $filter->getDatePickerLanguage(),
                        'cssFile'   => null,
                        'options'   => [
                            'showAnim'      => 'fold',
                            'dateFormat'    => $filter->getDatePickerFormat(),
                        ],
                        'htmlOptions'=>$filter->fieldDecorator->getHtmlOptions('date_end', ['name' => 'date_end']),
                    ]);
                    ?>
                    <?php echo $form->error($filter, 'date_end'); ?>
                </div>
            </div>
            <div class="col-lg-2">
                <div class="form-group">
                    <?php echo $form->labelEx($filter, 'action'); ?>
                    <?php echo $form->dropDownList($filter, 'action', $filter->getActionsList(), $filter->fieldDecorator->getHtmlOptions('action', ['name' => 'action'])); ?>
                    <?php echo $form->error($filter, 'action'); ?>
                </div>
            </div>
        </div>
    </div>
    <div class="box-footer">
        <div class="pull-right">
            <?php echo CHtml::submitButton(t('email_blacklist', 'Submit'), ['name' => '', 'class' => 'btn btn-primary btn-flat']); ?>
        </div>
        <div class="clearfix"><!-- --></div>
    </div>
</div>
<?php $controller->endWidget(); ?>
