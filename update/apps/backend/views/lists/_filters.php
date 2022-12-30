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

/** @var AllCustomersListsSubscribersFilters $filter */
$filter = $controller->getData('filter');

?>

<?php /** @var CActiveForm $form */
        $form = $controller->beginWidget('CActiveForm', [
    'id'          => 'filters-form',
    'method'      => 'get',
    'action'      => createUrl($controller->getRoute()),
    'htmlOptions' => [
        'style'        => 'display:' . ($filter->hasSetFilters ? 'block' : 'none'),
        'data-confirm' => t('list_subscribers', 'Are you sure you want to run this action?'),
    ],
]); ?>
<div class="box box-primary borderless">
    <div class="box-header">
        <div class="pull-left">
            <h3 class="box-title"><span class="glyphicon glyphicon-filter"><!-- --></span> <?php echo t('list_subscribers', 'Filters'); ?></h3>
        </div>
        <div class="clearfix"><!-- --></div>
    </div>
    <div class="box-body">
        <table class="table table-hover">
            <tr>
                <td>
                    <div class="form-group">
                        <?php echo $form->labelEx($filter, 'customers'); ?>
                        <?php echo $form->dropDownList($filter, 'customers', $filter->getCustomersList(), $filter->fieldDecorator->getHtmlOptions('customers', ['multiple' => true, 'name' => 'customers', 'class' => 'form-control select2', 'style' => 'width: 100%'])); ?>
                        <?php echo $form->error($filter, 'customers'); ?>
                    </div>
                </td>
                <td>
                    <div class="form-group">
                        <?php echo $form->labelEx($filter, 'lists'); ?>
                        <?php echo $form->dropDownList($filter, 'lists', $filter->getListsList(), $filter->fieldDecorator->getHtmlOptions('lists', ['multiple' => true, 'name' => 'lists', 'class' => 'form-control select2', 'style' => 'width: 100%'])); ?>
                        <?php echo $form->error($filter, 'lists'); ?>
                    </div>
                </td>
                <td>
                    <div class="form-group">
                        <?php echo $form->labelEx($filter, 'statuses'); ?>
                        <?php echo $form->dropDownList($filter, 'statuses', $filter->getStatusesList(), $filter->fieldDecorator->getHtmlOptions('statuses', ['multiple' => true, 'name' => 'statuses', 'class' => 'form-control select2', 'style' => 'width: 100%'])); ?>
                        <?php echo $form->error($filter, 'statuses'); ?>
                    </div>
                </td>
                <td>
                    <div class="form-group">
                        <?php echo $form->labelEx($filter, 'sources'); ?>
                        <?php echo $form->dropDownList($filter, 'sources', $filter->getSourcesList(), $filter->fieldDecorator->getHtmlOptions('sources', ['multiple' => true, 'name' => 'sources', 'class' => 'form-control select2', 'style' => 'width: 100%'])); ?>
                        <?php echo $form->error($filter, 'sources'); ?>
                    </div>
                </td>
                <td>
                    <div class="form-group">
                        <?php echo $form->labelEx($filter, 'unique'); ?>
                        <?php echo $form->dropDownList($filter, 'unique', CMap::mergeArray(['' => ''], $filter->getYesNoOptions()), $filter->fieldDecorator->getHtmlOptions('unique', ['name' => 'unique', 'class' => 'form-control select2', 'style' => 'width: 100%'])); ?>
                        <?php echo $form->error($filter, 'unique'); ?>
                    </div>
                </td>
                <td>
                    <div class="form-group">
                        <?php echo $form->labelEx($filter, 'action'); ?>
                        <?php echo $form->dropDownList($filter, 'action', $filter->getActionsList(), $filter->fieldDecorator->getHtmlOptions('action', ['name' => 'action', 'class' => 'form-control select2', 'style' => 'width: 100%'])); ?>
                        <?php echo $form->error($filter, 'action'); ?>
                    </div>
                </td>
            </tr>
            <tr>
                <td>
                    <div class="form-group">
                        <?php echo $form->labelEx($filter, 'email'); ?>
                        <?php echo $form->textField($filter, 'email', $filter->fieldDecorator->getHtmlOptions('email', ['name' => 'email'])); ?>
                        <?php echo $form->error($filter, 'email'); ?>
                    </div>
                </td>
                <td>
                    <div class="form-group">
                        <?php echo $form->labelEx($filter, 'uid'); ?>
                        <?php echo $form->textField($filter, 'uid', $filter->fieldDecorator->getHtmlOptions('uid', ['name' => 'uid'])); ?>
                        <?php echo $form->error($filter, 'uid'); ?>
                    </div>
                </td>
                <td>
                    <div class="form-group">
                        <?php echo $form->labelEx($filter, 'ip'); ?>
                        <?php echo $form->textField($filter, 'ip', $filter->fieldDecorator->getHtmlOptions('ip', ['name' => 'ip'])); ?>
                        <?php echo $form->error($filter, 'ip'); ?>
                    </div>
                </td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td>
                    <div class="form-group">
                        <?php echo $form->labelEx($filter, 'date_added_start'); ?>
                        <?php echo (string)$controller->widget('zii.widgets.jui.CJuiDatePicker', [
                            'model'     => $filter,
                            'attribute' => 'date_added_start',
                            'cssFile'   => null,
                            'language'  => $filter->getDatePickerLanguage(),
                            'options'   => [
                                'showAnim'   => 'fold',
                                'dateFormat' => $filter->getDatePickerFormat(),
                            ],
                            'htmlOptions' => $filter->fieldDecorator->getHtmlOptions('date_added_start', ['name' => 'date_added_start']),
                        ], true); ?>
                        <?php echo $form->error($filter, 'date_added_start'); ?>
                    </div>
                </td>
                <td>
                    <div class="form-group">
                        <?php echo $form->labelEx($filter, 'date_added_end'); ?>
                        <?php echo (string)$controller->widget('zii.widgets.jui.CJuiDatePicker', [
                            'model'     => $filter,
                            'attribute' => 'date_added_end',
                            'cssFile'   => null,
                            'language'  => $filter->getDatePickerLanguage(),
                            'options'   => [
                                'showAnim'   => 'fold',
                                'dateFormat' => $filter->getDatePickerFormat(),
                            ],
                            'htmlOptions' => $filter->fieldDecorator->getHtmlOptions('date_added_end', ['name' => 'date_added_end']),
                        ], true); ?><?php echo $form->error($filter, 'date_added_end'); ?>
                    </div>
                </td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td>
                    <div class="form-group">
                        <?php echo $form->labelEx($filter, 'campaigns_action'); ?>
                        <?php echo $form->dropDownList($filter, 'campaigns_action', CMap::mergeArray(['' => t('app', 'Choose')], $filter->getCampaignFilterActions()), $filter->fieldDecorator->getHtmlOptions('campaigns_action', ['name' => 'campaigns_action', 'class' => 'form-control select2', 'style' => 'width: 100%'])); ?>
                        <?php echo $form->error($filter, 'campaigns_action'); ?>
                    </div>
                </td>
                <td>
                    <div class="form-group">
                        <?php echo $form->labelEx($filter, 'campaigns'); ?>
                        <?php echo $form->dropDownList($filter, 'campaigns', $filter->getCampaignsList(), $filter->fieldDecorator->getHtmlOptions('campaigns', ['multiple' => true, 'name' => 'campaigns', 'class' => 'form-control select2', 'style' => 'width: 100%'])); ?>
                        <?php echo $form->error($filter, 'campaigns'); ?>
                    </div>
                </td>
                <td style="width:280px">
                    <div class="form-group">
                        <label><?php echo t('list_subscribers', 'In the last'); ?>:</label>
                        <div class="input-group">
		                    <?php echo $form->numberField($filter, 'campaigns_atuc', $filter->fieldDecorator->getHtmlOptions('campaign_atuc', ['name' => 'campaigns_atuc', 'type' => 'number', 'placeholder' => 30])); ?>
                            <span class="input-group-addon">
                            <?php echo $form->dropDownList($filter, 'campaigns_atu', $filter->getFilterTimeUnits(), $filter->fieldDecorator->getHtmlOptions('campaigns_atu', ['name' => 'campaigns_atu', 'class' => 'xform-control'])); ?>
                        </span>
                        </div>
                    </div>
                </td>
                <td></td>
                <td></td>
            </tr>
        </table>
    </div>
    <div class="box-footer">
        <div class="pull-right">
            <?php echo CHtml::submitButton(t('list_subscribers', 'Submit'), ['name' => '', 'class' => 'btn btn-primary btn-flat']); ?>
        </div>
        <div class="clearfix"><!-- --></div>
    </div>
</div>
<?php $controller->endWidget(); ?>
