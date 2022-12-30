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
 * @since 1.0
 */

/** @var Controller $controller */
$controller = controller();

/** @var string $pageHeading */
$pageHeading = (string)$controller->getData('pageHeading');

/** @var PricePlanOrder $order */
$order = $controller->getData('order');

/** @var PricePlanOrderNote $note */
$note = $controller->getData('note');

/**
 * This hook gives a chance to prepend content or to replace the default view content with a custom content.
 * Please note that from inside the action callback you can access all the controller view
 * variables via {@CAttributeCollection $collection->controller->getData()}
 * In case the content is replaced, make sure to set {@CAttributeCollection $collection->add('renderContent', false)}
 * in order to stop rendering the default content.
 * @since 1.3.3.1
 */
hooks()->doAction('before_view_file_content', $viewCollection = new CAttributeCollection([
    'controller'    => $controller,
    'renderContent' => true,
]));

// and render if allowed
if ($viewCollection->itemAt('renderContent')) {
    /**
     * This hook gives a chance to prepend content before the active form or to replace the default active form entirely.
     * Please note that from inside the action callback you can access all the controller view variables
     * via {@CAttributeCollection $collection->controller->getData()}
     * In case the form is replaced, make sure to set {@CAttributeCollection $collection->add('renderForm', false)}
     * in order to stop rendering the default content.
     * @since 1.3.3.1
     */
    hooks()->doAction('before_active_form', $collection = new CAttributeCollection([
        'controller'    => $controller,
        'renderForm'    => true,
    ]));

    // and render if allowed
    if ($collection->itemAt('renderForm')) {
        /** @var CActiveForm $form */
        $form = $controller->beginWidget('CActiveForm'); ?>
        <div class="box box-primary borderless">
            <div class="box-header">
                <div class="pull-left">
                    <?php BoxHeaderContent::make(BoxHeaderContent::LEFT)
                        ->add('<h3 class="box-title">' . IconHelper::make('glyphicon-credit-card') . html_encode((string)$pageHeading) . '</h3>')
                        ->render(); ?>
                </div>
                <div class="pull-right">
                    <?php BoxHeaderContent::make(BoxHeaderContent::RIGHT)
                        ->addIf(HtmlHelper::accessLink(IconHelper::make('create') . t('app', 'Create new'), ['orders/create'], ['class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Create new')]), !$order->getIsNewRecord())
                        ->add(HtmlHelper::accessLink(IconHelper::make('cancel') . t('app', 'Cancel'), ['orders/index'], ['class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Cancel')]))
                        ->add(CHtml::link(IconHelper::make('info'), '#page-info', ['class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Info'), 'data-toggle' => 'modal']))
                        ->render(); ?>
                </div>
                <div class="clearfix"><!-- --></div>
            </div>
            <div class="box-body">
                <?php
                /**
                 * This hook gives a chance to prepend content before the active form fields.
                 * Please note that from inside the action callback you can access all the controller view variables
                 * via {@CAttributeCollection $collection->controller->getData()}
                 * @since 1.3.3.1
                 */
                hooks()->doAction('before_active_form_fields', new CAttributeCollection([
                    'controller'    => $controller,
                    'form'          => $form,
                ])); ?>
                <div class="row">
                    <div class="col-lg-4">
                        <div class="form-group">
                            <?php echo $form->labelEx($order, 'customer_id'); ?>
                            <?php echo $form->hiddenField($order, 'customer_id', $order->fieldDecorator->getHtmlOptions('customer_id')); ?>
                            <?php
                            $controller->widget('zii.widgets.jui.CJuiAutoComplete', [
                                'name'          => 'customer',
                                'value'         => !empty($order->customer_id) ? $order->customer->getFullName() : '',
                                'source'        => createUrl('customers/autocomplete'),
                                'cssFile'       => false,
                                'options'       => [
                                    'minLength' => '2',
                                    'select'    => 'js:function(event, ui) {
                                $("#' . CHtml::activeId($order, 'customer_id') . '").val(ui.item.customer_id);
                            }',
                                    'search'    => 'js:function(event, ui) {
                                $("#' . CHtml::activeId($order, 'customer_id') . '").val("");
                            }',
                                    'change'    => 'js:function(event, ui) {
                                if (!ui.item) {
                                    $("#' . CHtml::activeId($order, 'customer_id') . '").val("");
                                }
                            }',
                                ],
                                'htmlOptions'   => $order->fieldDecorator->getHtmlOptions('customer_id'),
                            ]); ?>
                            <?php echo $form->error($order, 'customer_id'); ?>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="form-group">
                            <?php echo $form->labelEx($order, 'plan_id'); ?>
                            <?php echo $form->hiddenField($order, 'plan_id', $order->fieldDecorator->getHtmlOptions('plan_id')); ?>
                            <?php
                            $controller->widget('zii.widgets.jui.CJuiAutoComplete', [
                                'name'          => 'plan',
                                'value'         => !empty($order->plan_id) ? $order->plan->name : '',
                                'source'        => createUrl('price_plans/autocomplete'),
                                'cssFile'       => false,
                                'options'       => [
                                    'minLength' => '2',
                                    'select'    => 'js:function(event, ui) {
                                $("#' . CHtml::activeId($order, 'plan_id') . '").val(ui.item.plan_id);
                            }',
                                    'search'    => 'js:function(event, ui) {
                                $("#' . CHtml::activeId($order, 'plan_id') . '").val("");
                            }',
                                    'change'    => 'js:function(event, ui) {
                                if (!ui.item) {
                                    $("#' . CHtml::activeId($order, 'plan_id') . '").val("");
                                }
                            }',
                                ],
                                'htmlOptions'   => $order->fieldDecorator->getHtmlOptions('plan_id'),
                            ]); ?>
                            <?php echo $form->error($order, 'plan_id'); ?>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="form-group">
                            <?php echo $form->labelEx($order, 'promo_code_id'); ?>
                            <?php echo $form->hiddenField($order, 'promo_code_id', $order->fieldDecorator->getHtmlOptions('promo_code_id')); ?>
                            <?php
                            $controller->widget('zii.widgets.jui.CJuiAutoComplete', [
                                'name'          => 'promoCode',
                                'value'         => !empty($order->promo_code_id) ? $order->promoCode->code : '',
                                'source'        => createUrl('promo_codes/autocomplete'),
                                'cssFile'       => false,
                                'options'       => [
                                    'minLength' => '2',
                                    'select'    => 'js:function(event, ui) {
                                $("#' . CHtml::activeId($order, 'promo_code_id') . '").val(ui.item.promo_code_id);
                            }',
                                    'search'    => 'js:function(event, ui) {
                                $("#' . CHtml::activeId($order, 'promo_code_id') . '").val("");
                            }',
                                    'change'    => 'js:function(event, ui) {
                                if (!ui.item) {
                                    $("#' . CHtml::activeId($order, 'promo_code_id') . '").val("");
                                }
                            }',
                                ],
                                'htmlOptions'   => $order->fieldDecorator->getHtmlOptions('promo_code_id'),
                            ]); ?>
                            <?php echo $form->error($order, 'promo_code_id'); ?>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-4">
                        <div class="form-group">
                            <?php echo $form->labelEx($order, 'subtotal'); ?>
                            <?php echo $form->textField($order, 'subtotal', $order->fieldDecorator->getHtmlOptions('subtotal')); ?>
                            <?php echo $form->error($order, 'subtotal'); ?>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="form-group">
                            <?php echo $form->labelEx($order, 'discount'); ?>
                            <?php echo $form->textField($order, 'discount', $order->fieldDecorator->getHtmlOptions('discount')); ?>
                            <?php echo $form->error($order, 'discount'); ?>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="form-group">
                            <?php echo $form->labelEx($order, 'total'); ?>
                            <?php echo $form->textField($order, 'total', $order->fieldDecorator->getHtmlOptions('total')); ?>
                            <?php echo $form->error($order, 'total'); ?>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-4">
                        <div class="form-group">
                            <?php echo $form->labelEx($order, 'tax_id'); ?>
                            <?php echo $form->dropDownList($order, 'tax_id', CMap::mergeArray([''=>'---'], Tax::getAsDropdownOptions()), $order->fieldDecorator->getHtmlOptions('tax_id')); ?>
                            <?php echo $form->error($order, 'tax_id'); ?>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="form-group">
                            <?php echo $form->labelEx($order, 'tax_percent'); ?>
                            <?php echo $form->textField($order, 'tax_percent', $order->fieldDecorator->getHtmlOptions('tax_percent')); ?>
                            <?php echo $form->error($order, 'tax_percent'); ?>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="form-group">
                            <?php echo $form->labelEx($order, 'tax_value'); ?>
                            <?php echo $form->textField($order, 'tax_value', $order->fieldDecorator->getHtmlOptions('tax_value')); ?>
                            <?php echo $form->error($order, 'tax_value'); ?>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-4">
                        <div class="form-group">
                            <?php echo $form->labelEx($order, 'status'); ?>
                            <?php echo $form->dropDownList($order, 'status', $order->getStatusesList(), $order->fieldDecorator->getHtmlOptions('status')); ?>
                            <?php echo $form->error($order, 'status'); ?>
                        </div>
                    </div>
                </div>
                <hr />
                <div class="row">
                    <div class="col-lg-12">
                        <div class="form-group">
                            <?php echo $form->label($note, 'note'); ?>
                            <?php echo $form->textArea($note, 'note', $note->fieldDecorator->getHtmlOptions('note')); ?>
                            <?php echo $form->error($note, 'note'); ?>
                        </div>
                    </div>
                </div>
                <?php
                /**
                 * This hook gives a chance to append content after the active form fields.
                 * Please note that from inside the action callback you can access all the controller view variables
                 * via {@CAttributeCollection $collection->controller->getData()}
                 * @since 1.3.3.1
                 */
                hooks()->doAction('after_active_form_fields', new CAttributeCollection([
                    'controller'    => $controller,
                    'form'          => $form,
                ])); ?>
                <div class="row">
                    <div class="col-lg-12">
                        <div class="table-responsive">
                            <?php
                            $controller->widget('zii.widgets.grid.CGridView', hooks()->applyFilters('grid_view_properties', [
                                'ajaxUrl'           => createUrl($controller->getRoute(), ['id' => (int)$order->order_id]),
                                'id'                => $note->getModelName() . '-grid',
                                'dataProvider'      => $note->search(),
                                'filter'            => null,
                                'filterPosition'    => 'body',
                                'filterCssClass'    => 'grid-filter-cell',
                                'itemsCssClass'     => 'table table-hover',
                                'selectableRows'    => 0,
                                'enableSorting'     => false,
                                'cssFile'           => false,
                                'pagerCssClass'     => 'pagination pull-right',
                                'pager'             => [
                                    'class'         => 'CLinkPager',
                                    'cssFile'       => false,
                                    'header'        => false,
                                    'htmlOptions'   => ['class' => 'pagination'],
                                ],
                                'columns' => hooks()->applyFilters('grid_view_columns', [
                                    [
                                        'name'  => 'author',
                                        'value' => '$data->getAuthor()',
                                    ],
                                    [
                                        'name'  => 'note',
                                        'value' => '$data->note',
                                    ],
                                    [
                                        'name'  => 'date_added',
                                        'value' => '$data->dateAdded',
                                    ],
                                    [
                                        'class'     => 'DropDownButtonColumn',
                                        'header'    => t('app', 'Options'),
                                        'footer'    => $note->paginationOptions->getGridFooterPagination(),
                                        'buttons'   => [
                                            'delete' => [
                                                'label'     => IconHelper::make('delete'),
                                                'url'       => 'createUrl("orders/delete_note", array("id" => $data->note_id))',
                                                'imageUrl'  => null,
                                                'options'   => ['title' => t('app', 'Delete'), 'class' => 'btn btn-danger btn-flat delete'],
                                            ],
                                        ],
                                        'headerHtmlOptions' => ['style' => 'text-align:right'],
                                        'footerHtmlOptions' => ['align' => 'right'],
                                        'htmlOptions'       => ['align' => 'right', 'class' => 'options'],
                                        'template'          => '{delete}',
                                    ],
                                ], $controller),
                            ], $controller)); ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="box-footer">
                <div class="pull-right">
                    <?php if (!$order->getIsNewRecord()) { ?>
                    <a href="<?php echo createUrl('orders/view', ['id' => $order->order_id]); ?>" class="btn btn-primary btn-flat"><?php echo IconHelper::make('view') . t('orders', 'View order'); ?></a>
                    <?php } ?>
                    <button type="submit" class="btn btn-primary btn-flat"><?php echo IconHelper::make('save') . t('app', 'Save changes'); ?></button>
                </div>
                <div class="clearfix"><!-- --></div>
            </div>
        </div>
        <!-- modals -->
        <div class="modal modal-info fade" id="page-info" tabindex="-1" role="dialog">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                        <h4 class="modal-title"><?php echo IconHelper::make('info') . t('app', 'Info'); ?></h4>
                    </div>
                    <div class="modal-body">
                        <?php echo t('orders', 'Please note that any order added/changed from this area is not verified nor it goes through a payment gateway.'); ?><br />
                        <?php echo t('orders', 'Updating orders from this area is useful for offline orders mostly or for payment corrections.'); ?><br />
                        <?php echo t('orders', 'If the order is incomplete, pending or due and changed to complete, the customer will be affected and the price plan will be assigned properly.'); ?><br />
                    </div>
                </div>
            </div>
        </div>
        <?php
        $controller->endWidget();
    }
    /**
     * This hook gives a chance to append content after the active form.
     * Please note that from inside the action callback you can access all the controller view variables
     * via {@CAttributeCollection $collection->controller->getData()}
     * @since 1.3.3.1
     */
    hooks()->doAction('after_active_form', new CAttributeCollection([
        'controller'      => $controller,
        'renderedForm'    => $collection->itemAt('renderForm'),
    ]));
}
/**
 * This hook gives a chance to append content after the view file default content.
 * Please note that from inside the action callback you can access all the controller view
 * variables via {@CAttributeCollection $collection->controller->getData()}
 * @since 1.3.3.1
 */
hooks()->doAction('after_view_file_content', new CAttributeCollection([
    'controller'        => $controller,
    'renderedContent'   => $viewCollection->itemAt('renderContent'),
]));
