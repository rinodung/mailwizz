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

/** @var string $pageHeading */
$pageHeading = (string)$controller->getData('pageHeading');

/** @var StartPage $model */
$model = $controller->getData('model');

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
if ($viewCollection->itemAt('renderContent')) { ?>
    <?php
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
                        ->add('<h3 class="box-title">' . IconHelper::make('glyphicon-list-alt') . html_encode((string)$pageHeading) . '</h3>')
                        ->render(); ?>
                </div>
                <div class="pull-right">
                    <?php BoxHeaderContent::make(BoxHeaderContent::RIGHT)
                        ->addIf(HtmlHelper::accessLink(IconHelper::make('create') . t('app', 'Create new'), ['start_pages/create'], ['class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Create new')]), !$model->getIsNewRecord())
                        ->add(HtmlHelper::accessLink(IconHelper::make('cancel') . t('app', 'Cancel'), ['start_pages/index'], ['class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Cancel')]))
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
                    <div class="col-lg-6">
                        <div class="form-group">
                            <?php echo $form->labelEx($model, 'application'); ?>
                            <?php echo $form->dropDownList($model, 'application', $model->getApplications(), $model->fieldDecorator->getHtmlOptions('application')); ?>
                            <?php echo $form->error($model, 'application'); ?>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="form-group">
                            <?php echo $form->labelEx($model, 'route'); ?>
                            <?php echo $form->textField($model, 'route', $model->fieldDecorator->getHtmlOptions('route')); ?>
                            <?php echo $form->error($model, 'route'); ?>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-12">
                        <div class="form-group">
                            <?php echo $form->labelEx($model, 'heading'); ?>
                            <?php echo $form->textField($model, 'heading', $model->fieldDecorator->getHtmlOptions('heading')); ?>
                            <?php echo $form->error($model, 'heading'); ?>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-12">
                        <div class="form-group">
                            <?php echo $form->labelEx($model, 'content'); ?>
                            <?php echo $form->textArea($model, 'content', $model->fieldDecorator->getHtmlOptions('content')); ?>
                            <?php echo $form->error($model, 'content'); ?>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-12 select-icon-wrapper">
                        <div class="form-group">
                            <?php echo $form->labelEx($model, 'icon'); ?>
                            <div class="input-group">
                                <span class="input-group-addon" style="<?php echo empty($model->icon) ? 'display:none' : ''; ?>">
                                    <a href="javascript:;" class="icon-wrap" style="<?php echo !empty($model->icon_color) ? sprintf('color:#%s', $model->icon_color) : ''; ?>"><?php echo IconHelper::make((string)$model->icon); ?></a>
                                    <div class="clearfix"><!-- --></div>
                                    <a href="javascript:;" class="btn btn-xs btn-primary btn-flat btn-select-color" title="<?php echo t('start_pages', 'Select icon color'); ?>"><?php echo IconHelper::make('fa-paint-brush'); ?></a>&nbsp;<a href="javascript:;" class="btn btn-xs btn-primary btn-flat btn-reset-color" title="<?php echo t('start_pages', 'Reset icon color'); ?>"><?php echo IconHelper::make('fa-history'); ?></a>&nbsp;<a href="javascript:;" class="btn btn-xs btn-danger btn-flat btn-remove-icon" title="<?php echo t('start_pages', 'Remove icon'); ?>"><?php echo IconHelper::make('delete'); ?></a>
                                </span>
                                <?php echo $form->textField($model, 'search_icon', $model->fieldDecorator->getHtmlOptions('search_icon')); ?>
                                <?php echo $form->hiddenField($model, 'icon', $model->fieldDecorator->getHtmlOptions('icon')); ?>
                                <?php echo $form->hiddenField($model, 'icon_color', $model->fieldDecorator->getHtmlOptions('icon_color')); ?>
                            </div>
                            <?php echo $form->error($model, 'icon'); ?>
                        </div>
                        <div class="icons-list">
                            <?php foreach ($model->getIcons() as $icon) { ?>
                                <span class="icon-item">
                                    <a href="javascript:;" data-icon="<?php echo html_encode((string)$icon); ?>" title="<?php echo html_encode((string)$icon); ?>"><?php echo IconHelper::make($icon); ?></a>
                                </span>
                            <?php } ?>
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
                <div class="clearfix"><!-- --></div>
            </div>
            <div class="box-footer">
                <div class="pull-right">
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
                        <?php echo t('start_pages', 'You can use following tags in heading and content:'); ?>
                        <div style="width: 100%; max-height: 500px; overflow-y: scroll">
                            <table class="table table-striped table-condensed table-bordered">
                                <thead>
                                <tr>
                                    <th><?php echo t('start_pages', 'Tag'); ?></th>
                                    <th><?php echo t('start_pages', 'Description'); ?></th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($model->getAvailableTags() as $tag => $description) { ?>
                                    <tr>
                                        <td><?php echo html_encode((string)$tag); ?></td>
                                        <td><?php echo html_purify((string)$description); ?></td>
                                    </tr>
                                <?php } ?>
                                </tbody>
                            </table>
                        </div>
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
