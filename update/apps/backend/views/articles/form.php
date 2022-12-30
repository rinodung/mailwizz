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

/** @var Article $article */
$article = $controller->getData('article');

/** @var ArticleCategory $articleToCategory */
$articleToCategory = $controller->getData('articleToCategory');

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
        'controller' => $controller,
        'renderForm' => true,
    ]));

    // and render if allowed
    if ($collection->itemAt('renderForm')) {
        /** @var CActiveForm $form */
        $form = $controller->beginWidget('CActiveForm'); ?>
        <div class="box box-primary borderless">
            <div class="box-header">
                <div class="pull-left">
                    <?php BoxHeaderContent::make(BoxHeaderContent::LEFT)
                        ->add('<h3 class="box-title">' . IconHelper::make('glyphicon-book') . html_encode((string)$pageHeading) . '</h3>')
                        ->render(); ?>
                </div>
                <div class="pull-right">
                    <?php BoxHeaderContent::make(BoxHeaderContent::RIGHT)
                        ->addIf(HtmlHelper::accessLink(IconHelper::make('create') . t('app', 'Create new'), ['articles/create'], ['class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Create new')]), !$article->getIsNewRecord())
                        ->add(HtmlHelper::accessLink(IconHelper::make('cancel') . t('app', 'Cancel'), ['articles/index'], ['class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Cancel')]))
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
                    'controller' => $controller,
                    'form'       => $form,
                ])); ?>
                <div class="row">
                    <div class="col-lg-12">
                        <div class="form-group">
                            <?php echo $form->labelEx($article, 'title'); ?>
                            <?php echo $form->textField($article, 'title', $article->fieldDecorator->getHtmlOptions('title', ['data-article-id' => (int)$article->article_id, 'data-slug-url' => createUrl('articles/slug')])); ?>
                            <?php echo $form->error($article, 'title'); ?>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-12">
                        <div class="form-group">
                            <?php echo $form->labelEx($article, 'content'); ?>
                            <?php echo $form->textArea($article, 'content', $article->fieldDecorator->getHtmlOptions('content', ['rows' => 15])); ?>
                            <?php echo $form->error($article, 'content'); ?>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-8">
                        <div class="form-group">
                            <?php echo $form->labelEx($articleToCategory, 'category_id'); ?>
                            <div class="article-categories-scrollbox">
                                <ul class="list-group">
                                    <?php echo CHtml::checkBoxList($articleToCategory->getModelName(), $article->getSelectedCategoriesArray(), $article->getAvailableCategoriesArray(), $articleToCategory->fieldDecorator->getHtmlOptions('category_id', [
                                        'class'        => '',
                                        'template'     => '<li class="list-group-item">{beginLabel}{input} <span>{labelTitle}</span> {endLabel}</li>',
                                        'container'    => '',
                                        'separator'    => '',
                                        'labelOptions' => ['style' => 'margin-right: 10px;'],
                                    ])); ?>
                                </ul>
                            </div>
                            <?php echo $form->error($articleToCategory, 'category_id'); ?>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="form-group slug-wrapper"<?php if (empty($article->slug)) {
                                        echo ' style="display:none"';
                                    } ?>>
                            <?php echo $form->labelEx($article, 'slug'); ?>
                            <?php echo $form->textField($article, 'slug', $article->fieldDecorator->getHtmlOptions('slug')); ?>
                            <?php echo $form->error($article, 'slug'); ?>
                        </div>
                        <div class="form-group">
                            <?php echo $form->labelEx($article, 'status'); ?>
                            <?php echo $form->dropDownList($article, 'status', $article->getStatusesArray(), $article->fieldDecorator->getHtmlOptions('status')); ?>
                            <?php echo $form->error($article, 'status'); ?>
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
                    'controller' => $controller,
                    'form'       => $form,
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
        'controller'   => $controller,
        'renderedForm' => $collection->itemAt('renderForm'),
    ]));
}
/**
 * This hook gives a chance to append content after the view file default content.
 * Please note that from inside the action callback you can access all the controller view
 * variables via {@CAttributeCollection $collection->controller->getData()}
 * @since 1.3.3.1
 */
hooks()->doAction('after_view_file_content', new CAttributeCollection([
    'controller'      => $controller,
    'renderedContent' => $viewCollection->itemAt('renderContent'),
]));
